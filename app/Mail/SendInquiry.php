<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendInquiry extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $attachmentPath;
    public $originalName;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $attachmentPath = null, $originalName = null)
    {
        $this->data = $data;
        $this->attachmentPath = $attachmentPath;
        $this->originalName = $originalName ?? 'documento.pdf';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Innosure - Documento adjunto',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.simple',  // Vista simple sin datos del formulario
            with: []
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (!$this->attachmentPath || !file_exists($this->attachmentPath)) {
            return [];
        }

        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($this->attachmentPath)
                ->as($this->originalName)
                ->withMime('application/pdf')
        ];
    }
}
