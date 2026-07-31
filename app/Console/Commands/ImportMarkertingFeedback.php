<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketingFeedback;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportMarkertingFeedback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-marketing-feedback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import historical marketing feedback data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting marketing feedback import...');

        $data = [
                [
                    'created_at' => '2025-11-18 12:37:31 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3754,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-18 4:59:50 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3768,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-18 5:48:02 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3773,
                    'heard_about_source' => 'Instagram Promotion',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 10:51:23 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3784,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 12:24:13 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3789,
                    'heard_about_source' => 'Instagram Promotion',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 12:25:05 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3791,
                    'heard_about_source' => 'Refered by the daughter',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 12:47:59 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3793,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 1:27:45 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3798,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 1:30:14 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3788,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 4:37:12 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3808,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-19 5:07:59 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3802,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 10:05:18 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3820,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 12:24:50 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3826,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 12:39:06 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3832,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 12:40:54 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3830,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 12:42:33 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3831,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 1:11:38 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3834,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 1:33:38 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3836,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 4:14:49 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3843,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 4:31:28 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3821,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-20 4:41:02 ',
                    'ordering_process_rating' => 'Fair',
                    'order_number' => 3845,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 10:08:50 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3871,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 10:42:52 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3874,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 11:44:00 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3878,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 11:52:57 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3879,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 12:09:59 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3883,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 12:13:24 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3880,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 12:17:56 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3884,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 1:20:54 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3882,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 3:10:22 ',
                    'ordering_process_rating' => 'Fair',
                    'order_number' => 3890,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-21 5:20:20 ',
                    'ordering_process_rating' => 'Poor',
                    'order_number' => 3795,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 9:20:58 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3909,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 10:03:28 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3910,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:20:52 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3916,
                    'heard_about_source' => 'Instagram Promotion',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:26:27 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3907,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:27:03 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3908,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:27:28 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3911,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:28:16 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3912,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:28:46 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3912,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 11:52:19 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3918,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-22 12:42:46 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3922,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 10:22:27 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3965,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 10:23:12 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3967,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 10:29:23 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3936,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 10:37:53 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3968,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 11:48:16 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3970,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 12:23:30 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3900,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-24 4:31:31 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3992,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 9:59:33 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4027,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 10:00:34 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3987,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 10:02:07 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3993,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 12:20:58 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4032,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 1:29:28 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4037,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 2:02:17 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4039,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 2:53:19 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4043,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 3:12:22 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4041,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 3:22:50 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4040,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 3:23:47 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4030,
                    'heard_about_source' => 'Instagram Promotion',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 3:32:46 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4045,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 4:14:15 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 3550,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 4:38:46 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4048,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 4:41:04 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4049,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 5:08:43 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 3669,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-25 5:27:02 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4052,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 8:15:50 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4059,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 11:01:39 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4067,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 12:28:39 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4071,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 12:29:00 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4072,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 12:29:20 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4069,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 12:54:39 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4078,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 2:27:29 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4091,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 3:10:10 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4097,
                    'heard_about_source' => 'Pa Ghetto ',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 3:10:27 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4096,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 3:10:43 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4093,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-26 4:51:36 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4107,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 11:52:55 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4132,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 12:23:15 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4135,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 12:44:31 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4138,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 12:51:32 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4140,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 4:36:52 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4152,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-27 4:56:29 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4154,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 10:14:06 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4175,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 11:27:27 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4185,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 11:28:16 -',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4182,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 11:28:32 -',
                    'ordering_process_rating' => 'Fair',
                    'order_number' => 4179,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 12:34:14 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4189,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 12:54:09 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4169,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 12:54:38 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4181,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 12:55:04 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4158,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 12:55:23 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4142,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 1:40:08 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4197,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 2:01:40 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4024,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 2:23:35 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4200,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 2:27:27 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4202,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 2:44:41 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4204,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-28 4:19:46 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4211,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-11-29 1:26:33 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4245,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 10:41:04 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4316,
                    'heard_about_source' => 'She was just passing by and saw the store',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 10:50:20 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4318,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 12:29:07 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4322,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 1:56:09 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4328,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:16:51 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4243,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:17:20 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4241,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:17:47 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4236,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:18:11 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4235,
                    'heard_about_source' => 'Instagram Promotion',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:18:41 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4233,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:19:19 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4230,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:19:56 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4238,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:20:37 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4228,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:21:04 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4227,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:21:46 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4248,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:22:12 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4251,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:22:47 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4254,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:23:26 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4234,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:23:49 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4257,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:24:24 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4258,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-01 5:24:55 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4260,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-02 9:54:17 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4359,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-02 12:19:23 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4370,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-02 12:30:35 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4374,
                    'heard_about_source' => 'Facebook Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-02 3:03:55 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4383,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-02 3:17:15 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4384,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-03 1:50:36 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4415,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-03 5:11:36 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4431,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-04 11:44:47 -',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4440,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-05 1:14:44 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4488,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-05 2:36:27 ',
                    'ordering_process_rating' => 'Excellent',
                    'order_number' => 4492,
                    'heard_about_source' => 'Refered by a Friend',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-05 2:37:20 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4484,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ],
                [
                    'created_at' => '2025-12-06 12:53:16 ',
                    'ordering_process_rating' => 'Good',
                    'order_number' => 4534,
                    'heard_about_source' => 'Google Adverts',
                    'user_id' => '',
                    'order_id' => ''
                ]
            ];

        $this->info('Total records to import: ' . count($data));

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar(count($data));
        $progressBar->start();

        foreach ($data as $index => $item) {
            try {
                // Normalize rating to lowercase for database enum
                $rating = strtolower(trim($item['ordering_process_rating']));

                // Normalize source (ensure snake_case)
                $source = $this->normalizeSource($item['heard_about_source']);

                // Clean order number
                $orderNumber = trim($item['order_number']);

                // Parse created_at date
                $createdAt = $this->parseDate($item['created_at']);

                // Randomly assign country (Zimbabwe or Zambia)
                $country = $this->getRandomCountry();

                // Get random user agent
                $userAgent = $this->getRandomUserAgent();

                // Find order by order number
                $order = Order::where('order_number', $orderNumber)->first();

                if (!$order) {
                    $this->warn("\nOrder not found for order number: {$orderNumber}");
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Check if feedback already exists for this order
                $existingFeedback = MarketingFeedback::where('order_number', $orderNumber)->first();

                if ($existingFeedback) {
                    $this->warn("\nFeedback already exists for order number: {$orderNumber}");
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Create marketing feedback record
                $feedback = MarketingFeedback::create([
                    'user_id' => $order->consumer_id,
                    'order_number' => $orderNumber,
                    'order_id' => $order->id,
                    'ordering_process_rating' => $rating,
                    'heard_about_source' => $source['source'],
                    'heard_about_other' => $source['other'],
                    'user_name' => $order->consumer?->name,
                    'user_email' => $order->consumer?->email,
                    'user_phone' => $order->consumer?->phone,
                    'additional_comments' => null,
                    'ip_address' => null,
                    'user_agent' => $userAgent,
                    'country_code' => $country['code'],
                    'country_name' => $country['name'],
                ]);

                // Update timestamps to match import data
                DB::table('marketing_feedback')
                    ->where('id', $feedback->id)
                    ->update([
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                $successCount++;

            } catch (\Exception $e) {
                $this->error("\nError importing record {$index}: " . $e->getMessage());
                Log::error('Marketing feedback import error', [
                    'index' => $index,
                    'order_number' => $item['order_number'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('Import completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
                ['Total', count($data)],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Normalize the source value
     */
    private function normalizeSource($source)
    {
        $source = trim($source);
        $sourceLower = strtolower($source);

        // Map variations to standard source values
        $sourceMap = [
            'google adverts' => 'google_adverts',
            'google advert' => 'google_adverts',
            'google' => 'google_adverts',
            'facebook adverts' => 'facebook_adverts',
            'facebook advert' => 'facebook_adverts',
            'facebook' => 'facebook_adverts',
            'instagram promotion' => 'instagram_promotion',
            'instagram' => 'instagram_promotion',
            'comic awards' => 'comic_awards',
            'dare remachinda' => 'dare_remachinda',
            'zimcelebs' => 'zimcelebs',
            'tiktok advert' => 'tiktok_advert',
            'tiktok' => 'tiktok_advert',
            'refered by a friend' => 'refered_by_friend',
            'referred by a friend' => 'refered_by_friend',
            'friend' => 'refered_by_friend',
        ];

        // Check if it's a mapped source
        if (isset($sourceMap[$sourceLower])) {
            return [
                'source' => $sourceMap[$sourceLower],
                'other' => null,
            ];
        }

        // Check if it contains "refer" or "friend"
        if (str_contains($sourceLower, 'refer') || str_contains($sourceLower, 'friend')) {
            return [
                'source' => 'refered_by_friend',
                'other' => null,
            ];
        }

        // Everything else goes to "other"
        return [
            'source' => 'other',
            'other' => $source,
        ];
    }

    /**
     * Parse the date string
     */
    private function parseDate($dateString)
    {
        try {
            // Clean the date string
            $dateString = trim($dateString);
            $dateString = rtrim($dateString, ' -');

            // Try to parse the date
            $date = \Carbon\Carbon::parse($dateString);

            return $date;
        } catch (\Exception $e) {
            // If parsing fails, return current timestamp
            $this->warn("Could not parse date: {$dateString}, using current timestamp");
            return now();
        }
    }

    /**
     * Get random country (Zimbabwe or Zambia)
     */
    private function getRandomCountry()
    {
        $countries = [
            ['code' => 'ZW', 'name' => 'Zimbabwe'],
            ['code' => 'ZM', 'name' => 'Zambia'],
        ];

        return $countries[array_rand($countries)];
    }

    /**
     * Get random user agent string
     */
    private function getRandomUserAgent()
    {
        $userAgents = [
            // Chrome on Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',

            // Chrome on Mac
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',

            // Safari on Mac
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15',

            // Safari on iPhone
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',

            // Chrome on Android
            'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Mobile Safari/537.36',

            // Firefox on Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:119.0) Gecko/20100101 Firefox/119.0',

            // Edge on Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 Edg/119.0.0.0',

            // Samsung Internet on Android
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 12; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/22.0 Chrome/111.0.0.0 Mobile Safari/537.36',

            // Opera on Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/105.0.0.0',

            // iPad
            'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
