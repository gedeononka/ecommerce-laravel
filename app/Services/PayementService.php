<?php

namespace App\Services;

use App\Models\Payement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PayementService
{
    /**
     * Traiter un paiement
     */
    public function processPayment(Order $order, array $paymentData)
    {
        DB::beginTransaction();
        
        try {
            // Créer l'enregistrement de paiement
            $payment = $this->createPayment($order, $paymentData);
            
            // Traiter selon la méthode de paiement
            switch ($paymentData['payment_method']) {
                case Payement::METHOD_STRIPE:
                    $result = $this->processStripePayment($payment, $paymentData);
                    break;
                case Payement::METHOD_PAYPAL:
                    $result = $this->processPaypalPayment($payment, $paymentData);
                    break;
                case Payement::METHOD_CASH_ON_DELIVERY:
                    $result = $this->processCashOnDelivery($payment);
                    break;
                default:
                    throw new Exception('Méthode de paiement non supportée');
            }
            
            if ($result['success']) {
                $payment->markAsCompleted();
                $order->update(['payment_status' => Order::PAYMENT_PAID]);
                DB::commit();
                
                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Paiement traité avec succès'
                ];
            } else {
                $payment->markAsFailed($result['error']);
                DB::rollback();
                
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Erreur lors du traitement du paiement: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Erreur lors du traitement du paiement'
            ];
        }
    }
    
    /**
     * Créer un enregistrement de paiement
     */
    private function createPayment(Order $order, array $paymentData): Payement
    {
        $payment = Payement::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'payment_method' => $paymentData['payment_method'],
            'payment_gateway' => $paymentData['payment_gateway'] ?? null,
            'amount' => $order->total_amount,
            'currency' => 'EUR',
            'status' => Payement::STATUS_PENDING,
        ]);
        
        $payment->generateReferenceNumber();
        
        return $payment;
    }
    
    /**
     * Traiter un paiement Stripe
     */
    private function processStripePayment(Payement $payment, array $paymentData): array
    {
        try {
            // Simulation du traitement Stripe
            // Dans un vrai projet, vous utiliseriez l'API Stripe
            
            $transactionId = 'stripe_' . uniqid();
            
            $payment->update([
                'transaction_id' => $transactionId,
                'payment_gateway' => Payement::GATEWAY_STRIPE,
                'gateway_response' => [
                    'transaction_id' => $transactionId,
                    'status' => 'succeeded',
                    'payment_method' => $paymentData['stripe_payment_method'] ?? 'card'
                ]
            ]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur Stripe: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Traiter un paiement PayPal
     */
    private function processPaypalPayment(Payement $payment, array $paymentData): array
    {
        try {
            // Simulation du traitement PayPal
            // Dans un vrai projet, vous utiliseriez l'API PayPal
            
            $transactionId = 'paypal_' . uniqid();
            
            $payment->update([
                'transaction_id' => $transactionId,
                'payment_gateway' => Payement::GATEWAY_PAYPAL,
                'gateway_response' => [
                    'transaction_id' => $transactionId,
                    'status' => 'completed',
                    'payer_email' => $paymentData['payer_email'] ?? null
                ]
            ]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur PayPal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Traiter un paiement à la livraison
     */
    private function processCashOnDelivery(Payement $payment): array
    {
        $payment->update([
            'payment_gateway' => Payement::GATEWAY_BANK,
            'gateway_response' => [
                'method' => 'cash_on_delivery',
                'status' => 'pending_delivery'
            ]
        ]);
        
        return ['success' => true];
    }
    
    /**
     * Rembourser un paiement
     */
    public function refundPayment(Payement $payment, float $amount = null): array
    {
        if (!$payment->canBeRefunded()) {
            return [
                'success' => false,
                'error' => 'Ce paiement ne peut pas être remboursé'
            ];
        }
        
        $refundAmount = $amount ?? $payment->amount;
        
        try {
            DB::beginTransaction();
            
            // Traiter le remboursement selon la passerelle
            switch ($payment->payment_gateway) {
                case Payement::GATEWAY_STRIPE:
                    $result = $this->processStripeRefund($payment, $refundAmount);
                    break;
                case Payement::GATEWAY_PAYPAL:
                    $result = $this->processPaypalRefund($payment, $refundAmount);
                    break;
                default:
                    $result = ['success' => true]; // Remboursement manuel
            }
            
            if ($result['success']) {
                $payment->markAsRefunded();
                $payment->order->update(['status' => Order::STATUS_REFUNDED]);
                
                DB::commit();
                
                return [
                    'success' => true,
                    'message' => 'Remboursement traité avec succès'
                ];
            } else {
                DB::rollback();
                return $result;
            }
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Erreur lors du remboursement: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Erreur lors du remboursement'
            ];
        }
    }
    
    /**
     * Traiter un remboursement Stripe
     */
    private function processStripeRefund(Payement $payment, float $amount): array
    {
        // Simulation du remboursement Stripe
        return ['success' => true];
    }
    
    /**
     * Traiter un remboursement PayPal
     */
    private function processPaypalRefund(Payement $payment, float $amount): array
    {
        // Simulation du remboursement PayPal
        return ['success' => true];
    }
    
    /**
     * Obtenir les statistiques de paiement
     */
    public function getPaymentStats(): array
    {
        return [
            'total_payments' => Payement::count(),
            'successful_payments' => Payement::successful()->count(),
            'failed_payments' => Payement::failed()->count(),
            'pending_payments' => Payement::pending()->count(),
            'refunded_payments' => Payement::refunded()->count(),
            'total_amount' => Payement::successful()->sum('amount'),
            'total_fees' => Payement::successful()->sum('fee'),
        ];
    }
    
    /**
     * Obtenir les paiements par méthode
     */
    public function getPaymentsByMethod(): array
    {
        return Payement::successful()
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }
}