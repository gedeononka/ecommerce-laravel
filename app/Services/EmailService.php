<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Mail\InvoiceEmail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoyer l'email de confirmation de commande
     */
    public function sendOrderConfirmation(Order $order)
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderConfirmation($order));
            
            Log::info('Email de confirmation envoyé', [
                'order_id' => $order->id,
                'user_email' => $order->user->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envoyer l'email de mise à jour du statut de commande
     */
    public function sendOrderStatusUpdate(Order $order, string $oldStatus, string $newStatus)
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderStatusUpdate($order, $oldStatus, $newStatus));
            
            Log::info('Email de mise à jour statut envoyé', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_email' => $order->user->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email mise à jour statut', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envoyer l'email avec facture (Zone Admin)
     */
    public function sendInvoiceEmail(Order $order, string $pdfPath)
    {
        try {
            Mail::to($order->user->email)
                ->send(new InvoiceEmail($order, $pdfPath));
            
            Log::info('Email avec facture envoyé', [
                'order_id' => $order->id,
                'user_email' => $order->user->email,
                'pdf_path' => $pdfPath
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email facture', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envoyer un email personnalisé aux clients (Zone Admin)
     */
    public function sendCustomEmail(array $recipients, string $subject, string $message, array $attachments = [])
    {
        try {
            foreach ($recipients as $email) {
                Mail::send('emails.custom', ['message' => $message], function ($mail) use ($email, $subject, $attachments) {
                    $mail->to($email)
                         ->subject($subject);
                    
                    foreach ($attachments as $attachment) {
                        $mail->attach($attachment);
                    }
                });
            }
            
            Log::info('Email personnalisé envoyé', [
                'recipients_count' => count($recipients),
                'subject' => $subject
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email personnalisé', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envoyer des notifications aux admins
     */
    public function sendAdminNotification(string $subject, string $message, array $data = [])
    {
        try {
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                Mail::send('emails.admin-notification', [
                    'message' => $message,
                    'data' => $data
                ], function ($mail) use ($admin, $subject) {
                    $mail->to($admin->email)
                         ->subject($subject);
                });
            }
            
            Log::info('Notification admin envoyée', [
                'subject' => $subject,
                'admins_count' => $admins->count()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi notification admin', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}