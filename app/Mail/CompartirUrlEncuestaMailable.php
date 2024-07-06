<?php

namespace App\Mail;

use App\Models\Encuesta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompartirUrlEncuestaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Encuesta $encuesta,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Una breve encuesta para responder.',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.compartir_url_encuesta', // vista markdown
            with: [
                'titulo_encuesta' => $this->encuesta->titulo_encuesta,
                'descripcion' => $this->encuesta->descripcion,
                'url' => $this->encuesta->url,
                'mensaje_finalizacion' => !($this->encuesta->fecha_finalizacion) ? 'Sin fecha de finalización.' :  'Fecha de finalización: ' . $this->encuesta->fecha_finalizacion,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
