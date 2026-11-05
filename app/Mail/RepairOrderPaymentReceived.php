<?php

namespace App\Mail;

use App\Models\RepairOrderPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RepairOrderPaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RepairOrderPayment $payment) {}

    public function envelope(): Envelope
    {
        $company = $this->payment->company;

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($company->mail_from_address ?: config('mail.from.address'), $company->mail_from_name ?: $company->name),
            subject: 'Comprobante de abono '.$this->payment->repairOrder->code,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.repair-order-payment-received');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('repair-orders.payment-pdf', ['order' => $this->payment->repairOrder, 'payment' => $this->payment])
            ->setPaper('a4', 'portrait')
            ->output();

        return [Attachment::fromData(fn () => $pdf, 'comprobante-abono-'.$this->payment->repairOrder->code.'-'.$this->payment->id.'.pdf')->withMime('application/pdf')];
    }
}
