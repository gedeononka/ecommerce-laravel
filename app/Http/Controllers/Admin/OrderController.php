<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\EmailService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;
    protected $emailService;

    public function __construct(OrderService $orderService, EmailService $emailService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->orderService = $orderService;
        $this->emailService = $emailService;
    }

    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'orderItems.product']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Envoyer email de notification
        $this->emailService->sendOrderStatusUpdate($order, $request->status);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Statut de la commande mis à jour');
    }

    public function generateInvoice(Order $order)
    {
        $invoice = $this->orderService->generateInvoicePDF($order);
        
        return response()->download($invoice)
            ->deleteFileAfterSend(true);
    }

    public function destroy(Order $order)
    {
        if ($order->status === 'delivered') {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer une commande livrée');
        }

        $order->delete();
        
        return redirect()->route('admin.orders.index')
            ->with('success', 'Commande supprimée avec succès');
    }
}