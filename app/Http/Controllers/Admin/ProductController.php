<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with('category');
        
        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $products = $query->latest()->paginate(15);
        $categories = Category::all();
        
        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        // Handle gallery images
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $gallery[] = $image->store('products', 'public');
            }
            $data['gallery'] = json_encode($gallery);
        }
        
        Product::create($data);
        
        return redirect()->route('admin.products.index')
                        ->with('success', 'Produit créé avec succès');
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        $product->load('category');
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product
     */
    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        // Handle gallery images
        if ($request->hasFile('gallery')) {
            // Delete old gallery images
            if ($product->gallery) {
                $oldGallery = json_decode($product->gallery, true);
                foreach ($oldGallery as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $gallery[] = $image->store('products', 'public');
            }
            $data['gallery'] = json_encode($gallery);
        }
        
        $product->update($data);
        
        return redirect()->route('admin.products.index')
                        ->with('success', 'Produit mis à jour avec succès');
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        // Delete associated images
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        if ($product->gallery) {
            $gallery = json_decode($product->gallery, true);
            foreach ($gallery as $image) {
                Storage::disk('public')->delete($image);
            }
        }
        
        $product->delete();
        
        return redirect()->route('admin.products.index')
                        ->with('success', 'Produit supprimé avec succès');
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(Product $product)
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();
        
        return redirect()->back()->with('success', 'Statut du produit modifié');
    }

    /**
     * Bulk actions for products
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id'
        ]);
        
        $products = Product::whereIn('id', $request->products);
        
        switch ($request->action) {
            case 'delete':
                foreach ($products->get() as $product) {
                    $this->destroy($product);
                }
                $message = 'Produits supprimés avec succès';
                break;
                
            case 'activate':
                $products->update(['status' => 'active']);
                $message = 'Produits activés avec succès';
                break;
                
            case 'deactivate':
                $products->update(['status' => 'inactive']);
                $message = 'Produits désactivés avec succès';
                break;
        }
        
        return redirect()->back()->with('success', $message);
    }
}