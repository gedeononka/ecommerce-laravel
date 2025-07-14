<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $oldStatus;
    public $newStatus;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @param Order $order
     * @param string $oldStatus
     */
    public function __construct(Order $order, string $oldStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $order->status;
        $this->subject = 'Mise à jour de votre commande #' . $order->order_number;
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
                    ->view('emails.order-status-update')
                    ->with([
                        'order' => $this->order,
                        'customer' => $this->order->user,
                        'oldStatus' => $this->getStatusLabel($this->oldStatus),
                        'newStatus' => $this->getStatusLabel($this->newStatus),
                        'statusColor' => $this->getStatusColor($this->newStatus),
                        'statusMessage' => $this->getStatusMessage($this->newStatus),
                        'companyName' => config('app.name'),
                        'supportEmail' => config('mail.support.address', config('mail.from.address')),
                        'trackingUrl' => route('client.orders.show', $this->order->id),
                    ]);
    }

    /**
     * Get status label in French
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'En attente',
            'processing' => 'En cours de traitement',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get status color for email styling
     *
     * @param string $status
     * @return string
     */
    private function getStatusColor(string $status): string
    {
        $colors = [
            'pending' => '#fbbf24',
            'processing' => '#3b82f6',
            'shipped' => '#8b5cf6',
            'delivered' => '#06b6d4',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
        ];

        return $colors[$status] ?? '#6b7280';
    }

    /**
     * Get status message for customer
     *
     * @param string $status
     * @return string
     */
    private function getStatusMessage(string $status): string
    {
        $messages = [
            'pending' => 'Votre commande est en attente de traitement. Nous vous tiendrons informé des prochaines étapes.',
            'processing' => 'Votre commande est actuellement en cours de préparation dans nos entrepôts.',
            'shipped' => 'Votre commande a été expédiée ! Vous devriez la recevoir sous peu.',
            'delivered' => 'Votre commande a été livrée avec succès. Nous espérons que vous êtes satisfait de votre achat.',
            'completed' => 'Votre commande est terminée. Merci pour votre confiance !',
            'cancelled' => 'Votre commande a été annulée. Si vous avez des questions, n\'hésitez pas à nous contacter.',
        ];

        return $messages[$status] ?? 'Le statut de votre commande a été mis à jour.';
    }
}