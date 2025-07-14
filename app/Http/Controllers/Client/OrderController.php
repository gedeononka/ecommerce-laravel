<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
                      ->with('items.product')
                      ->latest()
                      ->paginate(10);

        return view('client.orders.index', compact('orders'));
    }

    /**
     * Display checkout page.
     */
    public function checkout()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product && $product->stock >= $quantity) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity
                ];
                $total += $product->price * $quantity;
            }
        }

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'Certains produits ne sont plus disponibles.');
        }

        return view('client.orders.checkout', compact('cartItems', 'total'));
    }

    /**
     * Process the order.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:100',
            'payment_method' => 'required|in:card,paypal,bank_transfer',
            'phone' => 'required|string|max:20'
        ]);

        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        DB::beginTransaction();

        try {
            // Créer la commande
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => 'CMD-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_country' => $request->shipping_country,
                'phone' => $request->phone,
                'total' => 0,
                'notes' => $request->notes
            ]);

            $total = 0;

            // Créer les items de commande
            foreach ($cart as $productId => $quantity) {
                $product = Product::find($productId);
                
                if (!$product || $product->stock < $quantity) {
                    throw new \Exception("Produit {$product->name} indisponible en quantité demandée.");
                }

                $subtotal = $product->price * $quantity;
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $subtotal
                ]);

                // Réduire le stock
                $product->decrement('stock', $quantity);
            }

            // Mettre à jour le total de la commande
            $order->update(['total' => $total]);

            DB::commit();

            // Vider le panier
            Session::forget('cart');

            return redirect()->route('orders.success', $order->id)
                           ->with('success', 'Commande passée avec succès!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Erreur lors du traitement de la commande: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display order success page.
     */
    public function success($orderId)
    {
        $order = Order::where('id', $orderId)
                     ->where('user_id', Auth::id())
                     ->with('items.product')
                     ->firstOrFail();

        return view('client.orders.success', compact('order'));
    }

    /**
     * Show a specific order.
     */
    public function show($id)
    {
        $order = Order::where('id', $id)
                     ->where('user_id', Auth::id())
                     ->with('items.product')
                     ->firstOrFail();

        return view('client.orders.show', compact('order'));
    }

    /**
     * Cancel an order.
     */
    public function cancel($id)
    {
        $order = Order::where('id', $id)
                     ->where('user_id', Auth::id())
                     ->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Cette commande ne peut pas être annulée.');
        }

        DB::beginTransaction();

        try {
            // Restaurer le stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Mettre à jour le statut
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled'
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Commande annulée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors de l\'annulation de la commande.');
        }
    }

    /**
     * Download order invoice.
     */
    public function invoice($id)
    {
        $order = Order::where('id', $id)
                     ->where('user_id', Auth::id())
                     ->with('items.product')
                     ->firstOrFail();

        if ($order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'La facture n\'est disponible que pour les commandes payées.');
        }

        return view('client.orders.invoice', compact('order'));
    }

    /**
     * Track order status.
     */
    public function track($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
                     ->where('user_id', Auth::id())
                     ->firstOrFail();

        $statusSteps = [
            'pending' => ['label' => 'En attente', 'completed' => true],
            'processing' => ['label' => 'En traitement', 'completed' => false],
            'shipped' => ['label' => 'Expédiée', 'completed' => false],
            'delivered' => ['label' => 'Livrée', 'completed' => false]
        ];

        $currentStep = $order->status;
        $stepCompleted = false;

        foreach ($statusSteps as $step => $data) {
            if ($step === $currentStep) {
                $stepCompleted = true;
                $statusSteps[$step]['completed'] = true;
            } elseif ($stepCompleted) {
                break;
            } else {
                $statusSteps[$step]['completed'] = true;
            }
        }

        return view('client.orders.track', compact('order', 'statusSteps'));
    }
}