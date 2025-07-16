<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Services\PDFService;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    protected $pdfService;
    protected $emailService;

    public function __construct(PDFService $pdfService, EmailService $emailService)
    {
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
    }

    /**
     * Créer une commande à partir du panier
     */
    public function createOrder(array $orderData, $cartItems)
    {
        try {
            DB::beginTransaction();

            // Calculer le total
            $total = $cartItems->sum(function($item) {
                return $item->quantity * $item->product->price;
            });

            // Créer la commande
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'total' => $total,
                'shipping_address' => $orderData['shipping_address'],
                'billing_address' => $orderData['billing_address'] ?? $orderData['shipping_address'],
                'phone' => $orderData['phone'],
                'notes' => $orderData['notes'] ?? null,
                'payment_method' => $orderData['payment_method'],
            ]);

            // Créer les items de commande
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                    'total' => $cartItem->quantity * $cartItem->product->price,
                ]);

                // Décrémenter le stock
                $cartItem->product->decrement('stock', $cartItem->quantity);
            }

            DB::commit();

            // Envoyer email de confirmation
            $this->emailService->sendOrderConfirmation($order);

            Log::info('Commande créée avec succès', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'total' => $total
            ]);

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création commande', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer une commande via API
     */
    public function createOrderFromApi(array $orderData)
    {
        try {
            DB::beginTransaction();

            $total = 0;
            $orderItems = [];

            // Valider et calculer le total
            foreach ($orderData['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if (!$product->is_active || $product->stock < $item['quantity']) {
                    throw new \Exception("Produit {$product->name} non disponible");
                }

                $itemTotal = $product->price * $item['quantity'];
                $total += $itemTotal;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal
                ];
            }

            // Créer la commande
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'total' => $total,
                'shipping_address' => $orderData['shipping_address'],
                'billing_address' => $orderData['billing_address'] ?? $orderData['shipping_address'],
                'phone' => $orderData['phone'] ?? Auth::user()->phone,
                'payment_method' => $orderData['payment_method'],
            ]);

            // Créer les items et décrémenter le stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateOrderStatus(Order $order, string $status)
    {
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        // Envoyer notification email
        $this->emailService->sendOrderStatusUpdate($order, $status);

        Log::info('Statut commande mis à jour', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $status
        ]);

        return $order;
    }

    /**
     * Générer une facture PDF
     */
    public function generateInvoicePDF(Order $order)
    {
        return $this->pdfService->generateInvoice($order);
    }

    /**
     * Annuler une commande
     */
    public function cancelOrder(Order $order, string $reason = null)
    {
        try {
            DB::beginTransaction();

            // Remettre en stock les produits
            foreach ($order->orderItems as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Mettre à jour le statut
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason
            ]);

            DB::commit();

            Log::info('Commande annulée', [
                'order_id' => $order->id,
                'reason' => $reason
            ]);

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtenir les statistiques des commandes
     */
    public function getOrderStatistics(array $filters = [])
    {
        $query = Order::query();

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->where('status', 'completed')->sum('total'),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'average_order_value' => $orders->avg('total'),
            'orders_by_status' => $orders->groupBy('status')->map->count(),
        ];
    }

    /**
     * Générer un numéro de commande unique
     */
    protected function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}