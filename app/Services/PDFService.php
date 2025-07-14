<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    /**
     * Générer une facture PDF pour une commande
     */
    public function generateInvoicePDF(Order $order)
    {
        try {
            $data = [
                'order' => $order,
                'user' => $order->user,
                'items' => $order->orderItems()->with('product')->get(),
                'total' => $order->total_amount,
                'date' => $order->created_at->format('d/m/Y'),
                'invoice_number' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)
            ];

            $pdf = Pdf::loadView('admin.pdf.invoice', $data);
            
            $filename = 'invoice_' . $order->id . '_' . time() . '.pdf';
            $path = 'invoices/' . $filename;
            
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Générer un rapport de ventes PDF (Zone Admin)
     */
    public function generateSalesReport(array $filters = [])
    {
        try {
            $query = Order::with(['user', 'orderItems.product']);
            
            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            
            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
            
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            $orders = $query->get();
            
            $data = [
                'orders' => $orders,
                'total_revenue' => $orders->sum('total_amount'),
                'total_orders' => $orders->count(),
                'filters' => $filters,
                'generated_at' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('admin.pdf.sales-report', $data);
            
            $filename = 'sales_report_' . now()->format('Y_m_d_H_i') . '.pdf';
            $path = 'reports/' . $filename;
            
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la génération du rapport: ' . $e->getMessage());
        }
    }

    /**
     * Générer un rapport d'inventaire PDF (Zone Admin)
     */
    public function generateInventoryReport()
    {
        try {
            $products = \App\Models\Product::with('category')->get();
            
            $data = [
                'products' => $products,
                'total_products' => $products->count(),
                'low_stock_products' => $products->where('stock', '<=', 10),
                'generated_at' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('admin.pdf.inventory-report', $data);
            
            $filename = 'inventory_report_' . now()->format('Y_m_d_H_i') . '.pdf';
            $path = 'reports/' . $filename;
            
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la génération du rapport d\'inventaire: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un fichier PDF
     */
    public function deletePDF(string $path)
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si un fichier PDF existe
     */
    public function pdfExists(string $path)
    {
        return Storage::disk('public')->exists($path);
    }
}