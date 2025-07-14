<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display the cart.
     */
    public function index()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity
                ];
                $total += $product->price * $quantity;
            }
        }

        return view('client.cart.index', compact('cartItems', 'total'));
    }

    /**
     * Add product to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour ce produit.'
            ]);
        }

        $cart = Session::get('cart', []);
        $productId = $request->product_id;
        $quantity = $request->quantity;

        if (isset($cart[$productId])) {
            $newQuantity = $cart[$productId] + $quantity;
            if ($newQuantity > $product->stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour cette quantité.'
                ]);
            }
            $cart[$productId] = $newQuantity;
        } else {
            $cart[$productId] = $quantity;
        }

        Session::put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier avec succès.',
            'cart_count' => array_sum($cart)
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour cette quantité.'
            ]);
        }

        $cart = Session::get('cart', []);
        $cart[$request->product_id] = $request->quantity;
        Session::put('cart', $cart);

        $subtotal = $product->price * $request->quantity;
        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $prod = Product::find($productId);
            if ($prod) {
                $total += $prod->price * $quantity;
            }
        }

        return response()->json([
            'success' => true,
            'subtotal' => $subtotal,
            'total' => $total,
            'cart_count' => array_sum($cart)
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $cart = Session::get('cart', []);
        unset($cart[$request->product_id]);
        Session::put('cart', $cart);

        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $total += $product->price * $quantity;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Produit retiré du panier.',
            'total' => $total,
            'cart_count' => array_sum($cart)
        ]);
    }

    /**
     * Clear entire cart.
     */
    public function clear()
    {
        Session::forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès.'
        ]);
    }

    /**
     * Get cart count.
     */
    public function count()
    {
        $cart = Session::get('cart', []);
        return response()->json([
            'count' => array_sum($cart)
        ]);
    }

    /**
     * Get cart summary for mini cart.
     */
    public function summary()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $cartItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                    'image' => $product->image
                ];
                $total += $product->price * $quantity;
            }
        }

        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'count' => array_sum($cart)
        ]);
    }
}