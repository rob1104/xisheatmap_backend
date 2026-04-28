<?php

namespace App\Mail;

use App\Models\TarjetaDescuento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TarjetaDescuentoEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public $card;

    /**
     * Create a new message instance.
     */
    public function __construct(TarjetaDescuento $card)
    {
        $this->card = $card;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu Tarjeta de Beneficios MESIL está lista!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tarjeta_descuentp',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Tomamos la imagen que guardó Browsershot en public/cards/...
        return [
            Attachment::fromStorageDisk('public', $this->card->image_path)
                ->as('Tarjeta_Beneficios_MESIL.png')
                ->withMime('image/png'),
        ];
    }
}
