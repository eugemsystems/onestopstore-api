<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
use App\Models\Attachment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultImagesSeeder extends Seeder
{
    protected $baseURL;
    protected $theme;

    public function __construct()
    {
        $this->baseURL = config('app.url');
        $this->theme = config('app.theme');
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultImagePaths = [
            'admin/images/themes/'.$this->theme.'/favicon.png',
            'admin/images/themes/'.$this->theme.'/logo-white.png',
            'admin/images/themes/'.$this->theme.'/logo-dark.png',
            'admin/images/themes/'.$this->theme.'/tiny-logo.png',
            'admin/images/themes/'.$this->theme.'/maintainance.jpg',
        ];

        foreach ($defaultImagePaths as $defaultImagePath) {
            Attachment::create([
                'uuid' => Str::uuid()->toString(),
                'image_url' => $this->baseURL.'/'.$defaultImagePath,
                'file_name' => $this->baseURL.'/'.$defaultImagePath,
            ]);

        }

        DB::table('seeders')->updateOrInsert([
            'name' => 'DefaultImagesSeeder',
            'is_completed' => true
        ]);
    }
}
