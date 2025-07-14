<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index()
    {
        // Produits en vedette
        $featuredProducts = Product::where('is_featured', true)
                                  ->where('stock', '>', 0)
                                  ->with('category')
                                  ->limit(8)
                                  ->get();

        // Nouveaux produits
        $newProducts = Product::where('stock', '>', 0)
                             ->with('category')
                             ->latest()
                             ->limit(6)
                             ->get();

        // Produits les plus vendus
        $bestSellers = Product::select('products.*')
                             ->join('order_items', 'products.id', '=', 'order_items.product_id')
                             ->join('orders', 'order_items.order_id', '=', 'orders.id')
                             ->where('orders.payment_status', 'paid')
                             ->where('products.stock', '>', 0)
                             ->groupBy('products.id')
                             ->orderByRaw('SUM(order_items.quantity) DESC')
                             ->limit(6)
                             ->get();

        // Catégories principales
        $categories = Category::whereNotNull('parent_id')
                             ->withCount('products')
                             ->having('products_count', '>', 0)
                             ->limit(6)
                             ->get();

        return view('client.home', compact(
            'featuredProducts',
            'newProducts',
            'bestSellers',
            'categories'
        ));
    }

    /**
     * Search products.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $categoryId = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sortBy = $request->get('sort', 'name');

        $products = Product::where('stock', '>', 0)
                          ->with('category');

        if ($query) {
            $products->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        }

        if ($categoryId) {
            $products->where('category_id', $categoryId);
        }

        if ($minPrice) {
            $products->where('price', '>=', $minPrice);
        }

        if ($maxPrice) {
            $products->where('price', '<=', $maxPrice);
        }

        switch ($sortBy) {
            case 'price_asc':
                $products->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $products->orderBy('price', 'desc');
                break;
            case 'newest':
                $products->orderBy('created_at', 'desc');
                break;
            default:
                $products->orderBy('name', 'asc');
        }

        $products = $products->paginate(12);
        $categories = Category::all();

        return view('client.search', compact('products', 'categories', 'query'));
    }
}