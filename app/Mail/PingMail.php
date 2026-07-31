<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct() { $this->onQueue('emails'); }

    public function build()
    {
        return $this->markdown('emails.ping')
            ->subject($this->subject ?? 'Raines: queued email ping');
    }
}
