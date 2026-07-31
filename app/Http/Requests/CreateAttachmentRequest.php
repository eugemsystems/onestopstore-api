<?php

namespace App\Http\Requests;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\UploadedFile;

class CreateAttachmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // ---------- Merge JSON body when Content-Type=application/json ----------
        $contentType = (string) $this->header('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            try {
                $raw  = $this->getContent();
                $json = json_decode($raw, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    if (empty($json)) {
                        \Log::warning('CreateAttachmentRequest: empty JSON body', [
                            'raw_len' => strlen($raw),
                            'json_error' => json_last_error_msg(),
                        ]);
                    } else {
                        $this->merge($json);

                    }
                } else {
                    \Log::warning('CreateAttachmentRequest: invalid JSON body', [
                        'raw_len' => strlen($raw),
                        'json_error' => json_last_error_msg(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('CreateAttachmentRequest: JSON merge failed', ['err' => $e->getMessage()]);
            }
        }

        // ---------- Accept common multipart keys and normalize ----------
        // Single -> attachment
        if (!$this->hasFile('attachment')) {
            if ($this->hasFile('file'))   { $this->files->set('attachment', $this->file('file')); }
            if ($this->hasFile('image'))  { $this->files->set('attachment', $this->file('image')); }
        }
        // Multiple -> attachments
        if (!$this->hasFile('attachments')) {
            if ($this->hasFile('files'))  { $this->files->set('attachments', $this->file('files')); }
            if ($this->hasFile('images')) { $this->files->set('attachments', $this->file('images')); }
        }
        // If "attachment" was an array, treat it as multiple
        if ($this->hasFile('attachment') && is_array($this->file('attachment'))) {
            $this->files->set('attachments', $this->file('attachment'));
            $this->files->remove('attachment');
        }

        // ---------- JSON base64 strings -> UploadedFile(s) ----------
        // Accept keys: image_base64 (string) or images_base64 (array)
        $base64Singles = [];
        if (is_string($this->input('image_base64'))) {
            $base64Singles[] = $this->input('image_base64');
        }
        // Also accept "attachment_base64" or "file_base64"
        foreach (['attachment_base64', 'file_base64'] as $k) {
            if (is_string($this->input($k))) $base64Singles[] = $this->input($k);
        }

        $base64Array = [];
        if (is_array($this->input('images_base64'))) {
            $base64Array = array_values(array_filter($this->input('images_base64')));
        }
        // Also accept "attachments_base64" or "files_base64"
        foreach (['attachments_base64', 'files_base64'] as $k) {
            if (is_array($this->input($k))) {
                $base64Array = array_merge($base64Array, array_values(array_filter($this->input($k))));
            }
        }

        // Some clients put data-URIs into "image" as a string
        if (!$this->hasFile('attachment') && !$this->hasFile('attachments') && is_string($this->input('image'))) {
            $maybeDataUri = $this->input('image');
            if (str_starts_with($maybeDataUri, 'data:image/')) {
                $base64Singles[] = $maybeDataUri;
            }
        }

        $convertedFiles = [];

        foreach ($base64Singles as $idx => $b64) {
            $uploaded = $this->uploadedFromBase64($b64, "image_single_{$idx}");
            if ($uploaded) $convertedFiles[] = $uploaded;
        }

        foreach ($base64Array as $idx => $b64) {
            $uploaded = $this->uploadedFromBase64($b64, "image_array_{$idx}");
            if ($uploaded) $convertedFiles[] = $uploaded;
        }

        // ---------- JSON URLs -> UploadedFile(s) ----------
        // Accept keys: image_url (string) or images_url (array)
        $urlSingles = [];
        if (is_string($this->input('image_url'))) {
            $urlSingles[] = $this->input('image_url');
        }
        foreach (['attachment_url', 'file_url'] as $k) {
            if (is_string($this->input($k))) $urlSingles[] = $this->input($k);
        }

        $urlArray = [];
        if (is_array($this->input('images_url'))) {
            $urlArray = array_values(array_filter($this->input('images_url')));
        }
        foreach (['attachments_url', 'files_url'] as $k) {
            if (is_array($this->input($k))) {
                $urlArray = array_merge($urlArray, array_values(array_filter($this->input($k))));
            }
        }

        foreach ($urlSingles as $idx => $u) {
            $uploaded = $this->uploadedFromUrl($u, "image_url_single_{$idx}");
            if ($uploaded) $convertedFiles[] = $uploaded;
        }
        foreach ($urlArray as $idx => $u) {
            $uploaded = $this->uploadedFromUrl($u, "image_url_array_{$idx}");
            if ($uploaded) $convertedFiles[] = $uploaded;
        }

        // ---------- Final unification: ALWAYS use attachments[] ----------
        $existing = [];
        if ($this->hasFile('attachments')) {
            $a = $this->file('attachments');
            $existing = is_array($a) ? $a : [$a];
        } elseif ($this->hasFile('attachment')) {
            $existing = [$this->file('attachment')];
        }

        $all = array_values(array_filter(array_merge($existing, $convertedFiles)));
        if (!empty($all)) {
            $this->files->set('attachments', $all);
            $this->files->remove('attachment');
        }
    }

    public function rules()
    {
        // Only one ruleset: we validate attachments[]
        if ($this->hasFile('attachments')) {
            return [
                'attachments'   => ['required', 'array'],
                // Allow common browser-rendered images and .file extension
                'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,avif,bmp,svg,ico,file', 'max:20480'], // 20MB each
            ];
        }

        // Nothing provided
        throw new ExceptionHandler('Attachment is Null Value, Required Image Binary', 400);
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }

    // ---------- Helpers ----------

    private function uploadedFromBase64(string $payload, string $namePrefix): ?UploadedFile
    {
        // Accept raw base64 OR data URI
        $mime = 'application/octet-stream';
        if (str_starts_with($payload, 'data:')) {
            if (preg_match('/^data:(.*?);base64,(.*)$/', $payload, $m)) {
                $mime = $m[1] ?: 'application/octet-stream';
                $payload = $m[2];
            }
        }

        $bin = base64_decode($payload, true);
        if ($bin === false) return null;

        // Guess extension by mime
        $ext = $this->guessExt($mime, 'bin');
        $filename = "{$namePrefix}." . $ext;

        $tmp = tempnam(sys_get_temp_dir(), 'up_');
        file_put_contents($tmp, $bin);

        return new UploadedFile($tmp, $filename, $mime, null, true);
    }

    private function uploadedFromUrl(string $url, string $namePrefix): ?UploadedFile
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;

        try {
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $bin = @file_get_contents($url, false, $ctx);
            if ($bin === false) return null;

            $mime = $this->detectMimeFromBuffer($bin) ?: 'application/octet-stream';
            $ext  = $this->guessExt($mime, pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'bin');
            $filename = "{$namePrefix}." . $ext;

            $tmp = tempnam(sys_get_temp_dir(), 'up_');
            file_put_contents($tmp, $bin);

            return new UploadedFile($tmp, $filename, $mime, null, true);
        } catch (\Throwable $e) {
            \Log::warning('uploadedFromUrl failed', ['url' => $url, 'err' => $e->getMessage()]);
            return null;
        }
    }

    private function detectMimeFromBuffer(string $bin): ?string
    {
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $m = finfo_buffer($f, $bin);
            finfo_close($f);
            return $m ?: null;
        }
        return null;
    }

    private function guessExt(string $mime, string $fallback = 'bin'): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif',
            'image/bmp'  => 'bmp',
            'image/svg+xml' => 'svg',
            'image/x-icon'  => 'ico',
        ];
        return $map[strtolower($mime)] ?? $fallback;
    }
}
