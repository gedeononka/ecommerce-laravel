<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->subject = 'Confirmation de votre commande #' . $order->order_number;
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
                    ->view('emails.order-confirmation')
                    ->with([
                        'order' => $this->order,
                        'customer' => $this->order->user,
                        'orderItems' => $this->order->orderItems->load('product'),
                        'companyName' => config('app.name'),
                        'supportEmail' => config('mail.support.address', config('mail.from.address')),
                        'trackingUrl' => route('client.orders.show', $this->order->id),
                    ]);
    }
}