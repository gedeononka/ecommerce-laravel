<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PDFService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $orderService;
    protected $pdfService;
    protected $emailService;

    public function __construct(OrderService $orderService, PDFService $pdfService, EmailService $emailService)
    {
        $this->middleware('admin');
        $this->orderService = $orderService;
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);
        
        // Search functionality
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $orders = $query->latest()->paginate(20);
        
        // Calculate statistics
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
        ];
        
        return view('admin.orders.index', compact('orders', 'stats'));
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'orderItems.product']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,completed,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);
        
        $oldStatus = $order->status;
        $order->status = $request->status;
        
        if ($request->filled('notes')) {
            $order->notes = $request->notes;
        }
        
        $order->save();
        
        // Send notification email to customer
        $this->emailService->sendOrderStatusUpdate($order, $oldStatus);
        
        return redirect()->back()->with('success', 'Statut de la commande mis à jour');
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded'
        ]);
        
        $order->payment_status = $request->payment_status;
        $order->save();
        
        return redirect()->back()->with('success', 'Statut du paiement mis à jour');
    }

    /**
     * Generate and download invoice
     */
    public function generateInvoice(Order $order)
    {
        $order->load(['user', 'orderItems.product']);
        
        $pdf = $this->pdfService->generateInvoice($order);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf;
        }, 'invoice-' . $order->order_number . '.pdf');
    }

    /**
     * Send invoice via email
     */
    public function sendInvoice(Order $order)
    {
        $order->load(['user', 'orderItems.product']);
        
        $this->emailService->sendInvoice($order);
        
        return redirect()->back()->with('success', 'Facture envoyée par email');
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        if (in_array($order->status, ['delivered', 'completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cette commande ne peut pas être annulée');
        }
        
        DB::transaction(function() use ($order, $request) {
            $order->status = 'cancelled';
            $order->cancellation_reason = $request->reason;
            $order->cancelled_at = now();
            $order->save();
            
            // Restore product stock
            foreach ($order->orderItems as $item) {
                $item->product->increment('stock', $item->quantity);
            }
        });
        
        // Send cancellation notification
        $this->emailService->sendOrderCancellation($order);
        
        return redirect()->back()->with('success', 'Commande annulée avec succès');
    }

    /**
     * Process refund
     */
    public function refund(Request $request, Order $order)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $order->total_amount,
            'reason' => 'required|string|max:500'
        ]);
        
        if ($order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Le remboursement n\'est possible que pour les commandes payées');
        }
        
        // Process refund logic here (integrate with payment gateway)
        $refundSuccess = $this->orderService->processRefund($order, $request->amount, $request->reason);
        
        if ($refundSuccess) {
            $order->payment_status = 'refunded';
            $order->refund_amount = $request->amount;
            $order->refund_reason = $request->reason;
            $order->refunded_at = now();
            $order->save();
            
            return redirect()->back()->with('success', 'Remboursement traité avec succès');
        }
        
        return redirect()->back()->with('error', 'Erreur lors du traitement du remboursement');
    }

    /**
     * Export orders
     */
    public function export(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);
        
        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $orders = $query->get();
        
        $csvData = $this->orderService->exportToCSV($orders);
        
        return response()->streamDownload(function() use ($csvData) {
            echo $csvData;
        }, 'orders-' . date('Y-m-d') . '.csv');
    }

    /**
     * Bulk actions for orders
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:update_status,export',
            'orders' => 'required|array|min:1',
            'orders.*' => 'exists:orders,id',
            'status' => 'required_if:action,update_status|in:pending,processing,shipped,delivered,completed,cancelled'
        ]);
        
        $orders = Order::whereIn('id', $request->orders);
        
        switch ($request->action) {
            case 'update_status':
                $orders->update(['status' => $request->status]);
                
                // Send notifications for each order
                foreach ($orders->get() as $order) {
                    $this->emailService->sendOrderStatusUpdate($order, $order->getOriginal('status'));
                }
                
                $message = 'Statut des commandes mis à jour';
                break;
                
            case 'export':
                $ordersList = $orders->with(['user', 'orderItems.product'])->get();
                $csvData = $this->orderService->exportToCSV($ordersList);
                
                return response()->streamDownload(function() use ($csvData) {
                    echo $csvData;
                }, 'selected-orders-' . date('Y-m-d') . '.csv');
        }
        
        return redirect()->back()->with('success', $message);
    }
}