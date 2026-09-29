<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your STO2 login code: '.$this->code);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:sans-serif;max-width:480px">'
                .'<h2>Your login code</h2>'
                .'<p style="font-size:32px;font-weight:bold;letter-spacing:8px">'.$this->code.'</p>'
                .'<p>Enter this 6-digit code within 10 minutes. If you did not request it, ignore this email.</p>'
                .'</div>',
        );
    }
}
