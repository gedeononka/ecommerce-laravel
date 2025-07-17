<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'product_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getSubtotalAttribute()
    {
        return $this->product->final_price * $this->quantity;
    }

    public function getTotalPriceAttribute()
    {
        return $this->getSubtotalAttribute();
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForGuest($query, $sessionId)
    {
        return $query->where('session_id', $sessionId)->whereNull('user_id');
    }

    // Methods
    public function increaseQuantity($amount = 1)
    {
        $this->quantity += $amount;
        $this->save();
        return $this;
    }

    public function decreaseQuantity($amount = 1)
    {
        $this->quantity = max(1, $this->quantity - $amount);
        $this->save();
        return $this;
    }

    public function updateQuantity($quantity)
    {
        $this->quantity = max(1, $quantity);
        $this->save();
        return $this;
    }

    public function isAvailable()
    {
        return $this->product->isInStock() && $this->product->stock >= $this->quantity;
    }

    // Static methods
    public static function getTotalItems($userId = null, $sessionId = null)
    {
        $query = static::query();
        
        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }
        
        return $query->sum('quantity');
    }

    public static function getTotalPrice($userId = null, $sessionId = null)
    {
        $query = static::with('product');
        
        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }
        
        return $query->get()->sum('subtotal');
    }

    public static function clearCart($userId = null, $sessionId = null)
    {
        $query = static::query();
        
        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }
        
        return $query->delete();
    }

    public static function transferGuestCartToUser($sessionId, $userId)
    {
        return static::where('session_id', $sessionId)
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId, 'session_id' => null]);
    }
}