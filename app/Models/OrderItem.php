<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'product_snapshot'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'product_snapshot' => 'array'
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' €';
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2) . ' €';
    }

    public function getProductNameAttribute()
    {
        // Utilise le snapshot si le produit n'existe plus
        if ($this->product) {
            return $this->product->name;
        }
        
        return $this->product_snapshot['name'] ?? 'Produit supprimé';
    }

    public function getProductImageAttribute()
    {
        // Utilise le snapshot si le produit n'existe plus
        if ($this->product) {
            return $this->product->main_image;
        }
        
        return $this->product_snapshot['image'] ?? 'default-product.jpg';
    }

    public function getProductSlugAttribute()
    {
        if ($this->product) {
            return $this->product->slug;
        }
        
        return $this->product_snapshot['slug'] ?? null;
    }

    // Mutators
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = $value;
        $this->calculateTotal();
    }

    public function setQuantityAttribute($value)
    {
        $this->attributes['quantity'] = $value;
        $this->calculateTotal();
    }

    // Methods
    public function calculateTotal()
    {
        if (isset($this->attributes['price']) && isset($this->attributes['quantity'])) {
            $this->attributes['total'] = $this->attributes['price'] * $this->attributes['quantity'];
        }
    }

    public function saveProductSnapshot()
    {
        if ($this->product) {
            $this->product_snapshot = [
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'description' => $this->product->short_description,
                'image' => $this->product->main_image,
                'sku' => $this->product->sku,
                'category' => $this->product->category->name ?? null,
                'attributes' => $this->product->attributes,
                'saved_at' => now()->toDateTimeString()
            ];
            $this->save();
        }
    }

    public function canBeReturned()
    {
        // Exemple : retour possible dans les 30 jours
        return $this->created_at->diffInDays(now()) <= 30;
    }

    public function isProductStillAvailable()
    {
        return $this->product && $this->product->status === 'active';
    }

    public function getSubtotal()
    {
        return $this->price * $this->quantity;
    }

    // Boot method pour auto-calculer le total et sauvegarder le snapshot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            $orderItem->calculateTotal();
            
            // Sauvegarde le snapshot du produit au moment de la création
            if ($orderItem->product) {
                $orderItem->saveProductSnapshot();
            }
        });

        static::updating(function ($orderItem) {
            if ($orderItem->isDirty(['price', 'quantity'])) {
                $orderItem->calculateTotal();
            }
        });
    }

    // Scopes
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeWithProduct($query)
    {
        return $query->with('product');
    }

    // Static methods
    public static function createFromCartItem($cartItem, $orderId)
    {
        return static::create([
            'order_id' => $orderId,
            'product_id' => $cartItem->product_id,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->product->final_price,
            'total' => $cartItem->product->final_price * $cartItem->quantity
        ]);
    }

    public static function getTotalForOrder($orderId)
    {
        return static::where('order_id', $orderId)->sum('total');
    }
}