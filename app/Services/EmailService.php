<?php

namespace App\Services;

use App\Models\Order;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Mail\InvoiceEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoyer email de confirmation de commande
     */
    public function sendOrderConfirmation(Order $order)
    {
        try {
            Mail::to($order->user->email)->send(new OrderConfirmation($order));
            
            Log::info('Email confirmation commande envoyé', [
                'order_id' => $order->id,
                'user_email' => $order->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer email de mise à jour du statut
     */
    public function sendOrderStatusUpdate(Order $order, $newStatus)
    {
        try {
            Mail::to($order->user->email)->send(new OrderStatusUpdate($order, $newStatus));
            
            Log::info('Email mise à jour statut envoyé', [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'user_email' => $order->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email statut', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer facture par email
     */
    public function sendInvoice(Order $order, $invoicePath)
    {
        try {
            Mail::to($order->user->email)->send(new InvoiceEmail($order, $invoicePath));
            
            Log::info('Facture envoyée par email', [
                'order_id' => $order->id,
                'user_email' => $order->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi facture', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer email de bienvenue
     */
    public function sendWelcomeEmail($user)
    {
        try {
            // Logique d'envoi d'email de bienvenue
            Log::info('Email de bienvenue envoyé', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email bienvenue', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer notification de stock faible (Admin)
     */
    public function sendLowStockAlert($product)
    {
        try {
            // Logique d'envoi d'alerte stock faible aux admins
            Log::info('Alerte stock faible envoyée', [
                'product_id' => $product->id,
                'stock' => $product->stock
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi alerte stock', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}