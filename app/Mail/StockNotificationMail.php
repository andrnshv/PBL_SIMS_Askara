<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StockNotificationMail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $rows,        // tiap baris: name, sku, stock, unit, note
        public ?string $footer = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stock-notification');
    }
}