<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display products by category.
     */
    public function category($categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        
        $products = Product::where('category_id', $category->id)
                          ->where('stock', '>', 0)
                          ->with('category')
                          ->paginate(12);

        $subcategories = Category::where('parent_id', $category->id)
                               ->withCount('products')
                               ->get();

        return view('client.products.category', compact('category', 'products', 'subcategories'));
    }

    /**
     * Display a specific product.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
                         ->with(['category', 'reviews.user'])
                         ->firstOrFail();

        // Produits similaires
        $relatedProducts = Product::where('category_id', $product->category_id)
                                 ->where('id', '!=', $product->id)
                                 ->where('stock', '>', 0)
                                 ->limit(4)
                                 ->get();

        // Avis clients
        $reviews = $product->reviews()
                          ->with('user')
                          ->latest()
                          ->paginate(5);

        // Vérifier si l'utilisateur a déjà laissé un avis
        $userReview = null;
        if (Auth::check()) {
            $userReview = Review::where('product_id', $product->id)
                              ->where('user_id', Auth::id())
                              ->first();
        }

        return view('client.products.show', compact(
            'product',
            'relatedProducts',
            'reviews',
            'userReview'
        ));
    }

    /**
     * Add product review.
     */
    public function addReview(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000'
        ]);

        $product = Product::findOrFail($productId);

        // Vérifier si l'utilisateur a déjà laissé un avis
        $existingReview = Review::where('product_id', $productId)
                              ->where('user_id', Auth::id())
                              ->first();

        if ($existingReview) {
            return redirect()->back()->with('error', 'Vous avez déjà laissé un avis pour ce produit.');
        }

        Review::create([
            'product_id' => $productId,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return redirect()->back()->with('success', 'Votre avis a été ajouté avec succès.');
    }

    /**
     * Get product quick view data.
     */
    public function quickView($id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'product' => $product,
            'html' => view('client.products.quick-view', compact('product'))->render()
        ]);
    }

    /**
     * Check product availability.
     */
    public function checkAvailability($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'available' => $product->stock > 0,
            'stock' => $product->stock
        ]);
    }
}