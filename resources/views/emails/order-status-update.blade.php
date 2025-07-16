<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, $newStatus)
    {
        $this->order = $order;
        $this->newStatus = $newStatus;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Mise à jour de votre commande #' . $this->order->id)
                    ->view('emails.order-status-update')
                    ->with([
                        'order' => $this->order,
                        'newStatus' => $this->newStatus,
                    ]);
    }
}