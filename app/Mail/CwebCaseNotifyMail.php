<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CwebCaseNotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->text('emails.cweb_case_notify_text')
            ->with([
                'bodyText' => $this->bodyText,
            ]);
    }
}
