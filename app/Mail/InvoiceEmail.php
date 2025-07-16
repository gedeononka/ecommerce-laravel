<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $invoicePath;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, $invoicePath)
    {
        $this->order = $order;
        $this->invoicePath = $invoicePath;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Facture pour votre commande #' . $this->order->order_number)
                    ->view('emails.invoice')
                    ->attach($this->invoicePath, [
                        'as' => 'facture_' . $this->order->order_number . '.pdf',
                        'mime' => 'application/pdf',
                    ])
                    ->with([
                        'order' => $this->order,
                    ]);
    }
}