<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $pdfContent;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @param Order $order
     * @param string $pdfContent
     */
    public function __construct(Order $order, string $pdfContent)
    {
        $this->order = $order;
        $this->pdfContent = $pdfContent;
        $this->subject = 'Facture pour votre commande #' . $order->order_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject($this->subject)
                    ->view('emails.invoice')
                    ->with([
                        'order' => $this->order,
                        'customer' => $this->order->user,
                        'companyName' => config('app.name'),
                        'supportEmail' => config('mail.support.address', config('mail.from.address')),
                        'invoiceNumber' => $this->generateInvoiceNumber(),
                        'invoiceDate' => now()->format('d/m/Y'),
                    ])
                    ->attachData(
                        $this->pdfContent,
                        'facture-' . $this->order->order_number . '.pdf',
                        [
                            'mime' => 'application/pdf',
                        ]
                    );
    }

    /**
     * Generate invoice number
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT);
    }
}