<?php

namespace App\Mail;

use App\Models\RepairOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RepairOrderReceived extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public RepairOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($this->order->company->mail_from_address ?: config('mail.from.address'), $this->order->company->mail_from_name ?: $this->order->company->name),
            subject: 'Comprobante de recepción '.$this->order->code,
        );
    }

    public function content(): Content { return new Content(view: 'emails.repair-order-received'); }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('repair-orders.pdf', ['order' => $this->order])->output();
        $attachments = [Attachment::fromData(fn () => $pdf, 'orden-'.$this->order->code.'.pdf')->withMime('application/pdf')];
        foreach ($this->order->photos as $index => $photo) {
            if (Storage::disk('local')->exists($photo->path)) {
                $attachments[] = Attachment::fromStorageDisk('local', $photo->path)->as('foto-'.($index + 1).'-'.$this->order->code.'.webp')->withMime('image/webp');
            }
        }
        return $attachments;
    }
}
