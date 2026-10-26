<?php
namespace App\Mail;
use App\Models\DataRecoveryCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
class DataRecoveryReceived extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public DataRecoveryCase $case) {}
    public function envelope(): Envelope { return new Envelope(from: new \Illuminate\Mail\Mailables\Address($this->case->company->mail_from_address ?: config('mail.from.address'), $this->case->company->mail_from_name ?: 'Recuperadata'), subject: 'Comprobante de recepción Recuperadata '.$this->case->code); }
    public function content(): Content { return new Content(view: 'emails.data-recovery-received'); }
    public function attachments(): array { $pdf = Pdf::loadView('data-recovery.pdf', ['case' => $this->case])->output(); $files = [Attachment::fromData(fn () => $pdf, 'recepcion-'.$this->case->code.'.pdf')->withMime('application/pdf')]; foreach ($this->case->photos as $i => $photo) if (Storage::disk('local')->exists($photo->path)) $files[] = Attachment::fromStorageDisk('local',$photo->path)->as('foto-'.($i+1).'-'.$this->case->code.'.webp')->withMime('image/webp'); return $files; }
}
