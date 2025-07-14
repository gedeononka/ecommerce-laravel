<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected $emailService;
    protected $pdfService;

    public function __construct(EmailService $emailService, PDFService $pdfService)
    {
        $this->emailService = $emailService;
        $this->pdfService = $pdfService;
    }

    /**
     * Créer une nouvelle commande (Zone Admin)
     */
    public function createOrder(array $data)
    {
        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $data['user_id'],
                'status' => $data['status'] ?? 'pending',
                'total_amount' => 0,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Vérifier le stock
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ]);

                $totalAmount += $product->price * $item['quantity'];

                // Mettre à jour le stock
                $product->decrement('stock', $item['quantity']);
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            // Envoyer l'email de confirmation
            $this->emailService->sendOrderConfirmation($order);

            Log::info('Commande créée avec succès', ['order_id' => $order->id]);

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création commande', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Mettre à jour le statut d'une commande (Zone Admin)
     */
    public function updateOrderStatus(Order $order, string $newStatus)
    {
        try {
            $oldStatus = $order->status;
            
            $order->update(['status' => $newStatus]);

            // Envoyer l'email de mise à jour
            $this->emailService->sendOrderStatusUpdate($order, $oldStatus, $newStatus);

            // Si la commande est terminée, générer et envoyer la facture
            if ($newStatus === 'completed') {
                $pdfPath = $this->pdfService->generateInvoicePDF($order);
                $this->emailService->sendInvoiceEmail($order, $pdfPath);
            }

            Log::info('Statut commande mis à jour', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut commande', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Annuler une commande (Zone Admin)
     */
    public function cancelOrder(Order $order, string $reason = null)
    {
        try {
            if ($order->status === 'cancelled') {
                throw new \Exception('La commande est déjà annulée');
            }

            DB::beginTransaction();

            // Restaurer le stock
            foreach ($order->orderItems as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $order->update([
                'status' => 'cancelled',
                'notes' => $reason ? "Annulée: {$reason}" : 'Commande annulée'
            ]);

            DB::commit();

            // Envoyer l'email de notification
            $this->emailService->sendOrderStatusUpdate($order, $order->status, 'cancelled');

            Log::info('Commande annulée', [
                'order_id' => $order->id,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur annulation commande', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtenir les statistiques des commandes (Zone Admin)
     */
    public function getOrderStatistics(array $filters = [])
    {
        try {
            $query = Order::query();

            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $orders = $query->get();

            return [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total_amount'),
                'average_order_value' => $orders->avg('total_amount'),
                'orders_by_status' => $orders->groupBy('status')->map->count(),
                'recent_orders' => $orders->sortByDesc('created_at')->take(10),
                'top_customers' => $this->getTopCustomers($filters)
            ];

        } catch (\Exception $e) {
            Log::error('Erreur statistiques commandes', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtenir les meilleurs clients (Zone Admin)
     */
    protected function getTopCustomers(array $filters = [])
    {
        $query = Order::with('user');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->select('user_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total_amount) as total_spent'))
                    ->groupBy('user_id')
                    ->orderBy('total_spent', 'desc')
                    ->limit(10)
                    ->get();
    }

    /**
     * Supprimer une commande (Zone Admin)
     */
    public function deleteOrder(Order $order)
    {
        try {
            if ($order->status !== 'cancelled') {
                throw new \Exception('Seules les commandes annulées peuvent être supprimées');
            }

            DB::beginTransaction();

            // Supprimer les items de commande
            $order->orderItems()->delete();
            
            // Supprimer la commande
            $order->delete();

            DB::commit();

            Log::info('Commande supprimée', ['order_id' => $order->id]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression commande', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}