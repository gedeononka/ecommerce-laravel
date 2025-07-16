<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PDFService
{
    /**
     * Générer une facture PDF
     */
    public function generateInvoice(Order $order)
    {
        try {
            // Charger les données nécessaires
            $order->load(['user', 'orderItems.product']);

            // Générer le contenu HTML de la facture
            $html = $this->generateInvoiceHTML($order);

            // Nom du fichier
            $filename = 'invoice_' . $order->order_number . '.pdf';
            $filepath = 'invoices/' . $filename;

            // Simuler la génération PDF (vous pouvez utiliser une vraie librairie comme DomPDF)
            $this->generatePDFFromHTML($html, $filepath);

            Log::info('Facture PDF générée', [
                'order_id' => $order->id,
                'filepath' => $filepath
            ]);

            return storage_path('app/public/' . $filepath);

        } catch (\Exception $e) {
            Log::error('Erreur génération facture PDF', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Générer le HTML de la facture
     */
    protected function generateInvoiceHTML(Order $order)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Facture ' . $order->order_number . '</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; }
                .invoice-details { margin-bottom: 20px; }
                .customer-details { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .total { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>FACTURE</h1>
                <h2>E-Commerce Laravel</h2>
            </div>
            
            <div class="invoice-details">
                <p><strong>Numéro de facture:</strong> ' . $order->order_number . '</p>
                <p><strong>Date:</strong> ' . $order->created_at->format('d/m/Y') . '</p>
                <p><strong>Statut:</strong> ' . ucfirst($order->status) . '</p>
            </div>
            
            <div class="customer-details">
                <h3>Informations client</h3>
                <p><strong>Nom:</strong> ' . $order->user->name . '</p>
                <p><strong>Email:</strong> ' . $order->user->email . '</p>
                <p><strong>Téléphone:</strong> ' . $order->phone . '</p>
                <p><strong>Adresse de livraison:</strong> ' . $order->shipping_address . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix unitaire</th>
                        <th>Quantité</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($order->orderItems as $item) {
            $html .= '
                    <tr>
                        <td>' . $item->product->name . '</td>
                        <td>' . number_format($item->price, 2) . ' €</td>
                        <td>' . $item->quantity . '</td>
                        <td>' . number_format($item->total, 2) . ' €</td>
                    </tr>';
        }

        $html .= '
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="3">TOTAL</td>
                        <td>' . number_format($order->total, 2) . ' €</td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 30px;">
                <p><strong>Mode de paiement:</strong> ' . ucfirst($order->payment_method) . '</p>
                <p><strong>Statut du paiement:</strong> ' . ucfirst($order->payment_status) . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Simuler la génération PDF à partir du HTML
     */
    protected function generatePDFFromHTML($html, $filepath)
    {
        // Ici vous pouvez utiliser une vraie librairie PDF comme DomPDF, TCPDF, etc.
        // Pour la simulation, on sauvegarde juste le HTML
        
        Storage::disk('public')->put($filepath, $html);
        
        // Exemple avec DomPDF (si installé):
        /*
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        
        Storage::disk('public')->put($filepath, $pdf->output());
        */
    }

    /**
     * Générer un rapport de ventes PDF
     */
    public function generateSalesReport(array $orders, array $filters = [])
    {
        try {
            $html = $this->generateSalesReportHTML($orders, $filters);
            
            $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = 'reports/' . $filename;
            
            $this->generatePDFFromHTML($html, $filepath);
            
            return storage_path('app/public/' . $filepath);

        } catch (\Exception $e) {
            Log::error('Erreur génération rapport ventes', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Générer le HTML du rapport de ventes
     */
    protected function generateSalesReportHTML(array $orders, array $filters)
    {
        $totalRevenue = collect($orders)->sum('total');
        $totalOrders = count($orders);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Rapport de Ventes</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; }
                .summary { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>RAPPORT DE VENTES</h1>
                <p>Généré le ' . date('d/m/Y à H:i') . '</p>
            </div>
            
            <div class="summary">
                <h3>Résumé</h3>
                <p><strong>Nombre total de commandes:</strong> ' . $totalOrders . '</p>
                <p><strong>Chiffre d\'affaires total:</strong> ' . number_format($totalRevenue, 2) . ' €</p>
                <p><strong>Panier moyen:</strong> ' . number_format($totalOrders > 0 ? $totalRevenue / $totalOrders : 0, 2) . ' €</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orders as $order) {
            $html .= '
                    <tr>
                        <td>' . $order['order_number'] . '</td>
                        <td>' . $order['user']['name'] . '</td>
                        <td>' . date('d/m/Y', strtotime($order['created_at'])) . '</td>
                        <td>' . ucfirst($order['status']) . '</td>
                        <td>' . number_format($order['total'], 2) . ' €</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        return $html;
    }
}