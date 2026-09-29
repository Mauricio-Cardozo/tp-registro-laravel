<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Solo el nombre: la contraseña nunca entra al mailable, porque con
     * ShouldQueue el constructor se serializa y queda en la tabla jobs.
     */
    public function __construct(public string $name) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido/a a ' . config('app.name') . '!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }
}
