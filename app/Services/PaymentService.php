<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    protected $orderService;
    protected $emailService;

    public function __construct(OrderService $orderService, EmailService $emailService)
    {
        $this->orderService = $orderService;
        $this->emailService = $emailService;
    }

    /**
     * Traiter un paiement (Zone Admin)
     */
    public function processPayment(Order $order, array $paymentData)
    {
        try {
            // Vérifier si la commande peut être payée
            if ($order->status !== 'pending') {
                throw new \Exception('Cette commande ne peut pas être payée');
            }

            // Simuler le traitement du paiement
            $paymentResult = $this->simulatePaymentGateway($paymentData);

            if (!$paymentResult['success']) {
                throw new \Exception('Échec du paiement: ' . $paymentResult['message']);
            }

            // Créer l'enregistrement du paiement
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_method' => $paymentData['method'],
                'transaction_id' => $paymentResult['transaction_id'],
                'status' => 'completed',
                'gateway_response' => json_encode($paymentResult),
                'processed_at' => now()
            ]);

            // Mettre à jour le statut de la commande
            $this->orderService->updateOrderStatus($order, 'paid');

            Log::info('Paiement traité avec succès', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $order->total_amount
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'Paiement traité avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur traitement paiement', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Rembourser un paiement (Zone Admin)
     */
    public function refundPayment(Order $order, float $amount = null, string $reason = null)
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement trouvé pour cette commande');
            }

            if ($payment->status !== 'completed') {
                throw new \Exception('Le paiement ne peut pas être remboursé');
            }

            $refundAmount = $amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                throw new \Exception('Le montant du remboursement ne peut pas dépasser le montant du paiement');
            }

            // Simuler le remboursement
            $refundResult = $this->simulateRefund($payment, $refundAmount);

            if (!$refundResult['success']) {
                throw new \Exception('Échec du remboursement: ' . $refundResult['message']);
            }

            // Mettre à jour le paiement
            $payment->update([
                'status' => $refundAmount == $payment->amount ? 'refunded' : 'partially_refunded',
                'refund_amount' => ($payment->refund_amount ?? 0) + $refundAmount,
                'refund_reason' => $reason
            ]);

            // Mettre à jour le statut de la commande
            $newStatus = $refundAmount == $payment->amount ? 'refunded' : 'partially_refunded';
            $this->orderService->updateOrderStatus($order, $newStatus);

            Log::info('Remboursement traité', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'message' => 'Remboursement traité avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur remboursement', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les statistiques de paiement (Zone Admin)
     */
    public function getPaymentStatistics(array $filters = [])
    {
        try {
            $query = Payment::query();

            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->whereDate('processed_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('processed_at', '<=', $filters['date_to']);
            }

            $payments = $query->get();

            return [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'total_refunds' => $payments->sum('refund_amount'),
                'net_revenue' => $payments->sum('amount') - $payments->sum('refund_amount'),
                'payments_by_method' => $payments->groupBy('payment_method')->map->count(),
                'payments_by_status' => $payments->groupBy('status')->map->count(),
                'average_payment' => $payments->avg('amount')
            ];

        } catch (\Exception $e) {
            Log::error('Erreur statistiques paiement', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Simuler le traitement d'un paiement
     */
    protected function simulatePaymentGateway(array $paymentData)
    {
        // Simulation d'un appel à une passerelle de paiement
        $transactionId = 'TXN_' . Str::random(10);
        
        // Simuler quelques échecs aléatoires
        $success = rand(1, 100) > 5; // 95% de succès

        return [
            'success' => $success,
            'transaction_id' => $success ? $transactionId : null,
            'message' => $success ? 'Paiement approuvé' : 'Paiement refusé',
            'gateway' => $paymentData['method'],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Simuler un remboursement
     */
    protected function simulateRefund(Payment $payment, float $amount)
    {
        // Simulation d'un remboursement
        $refundId = 'REF_' . Str::random(10);
        
        // Simuler quelques échecs aléatoires
        $success = rand(1, 100) > 10; // 90% de succès

        return [
            'success' => $success,
            'refund_id' => $success ? $refundId : null,
            'message' => $success ? 'Remboursement approuvé' : 'Remboursement refusé',
            'amount' => $amount,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Vérifier le statut d'un paiement auprès de la passerelle
     */
    public function checkPaymentStatus(Payment $payment)
    {
        try {
            // Simulation de vérification du statut
            $statusCheck = [
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'last_updated' => now()->toISOString()
            ];

            Log::info('Vérification statut paiement', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id
            ]);

            return $statusCheck;

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut paiement', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}