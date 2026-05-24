<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactoWebMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly array $datos,
        public readonly ?string $ip,
        public readonly ?string $userAgent
    ) {
    }

    public function envelope(): Envelope
    {
        $asunto = $this->datos['asunto'] ?? null;

        return new Envelope(
            subject: $asunto !== null && $asunto !== ''
                ? "[SmartSuper Contacto] {$asunto}"
                : '[SmartSuper Contacto] Nuevo mensaje'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contacto-web'
        );
    }
}
