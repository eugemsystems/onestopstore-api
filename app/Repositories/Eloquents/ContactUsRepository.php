<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\ContactUs;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactUs as MailContactUs;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;

class ContactUsRepository extends BaseRepository
{
    function model()
    {
        return ContactUs::class;
    }

    public function contactUs($request)
    {
        try {
            // Get the recipient email from env or config
            $recipientEmail = env('MAIL_FROM_ADDRESS', config('mail.from.address', 'info@example.com'));

            Mail::to($recipientEmail)->send(new MailContactUs($request));
            return response()->json([
                'message' => 'Thank you for contacting us, we will get back to you shortly.' ,
                'success' => true
            ], 200);

        } catch (Exception $e){
            throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
