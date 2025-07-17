<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'is_active',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'email_verified_at',
        'last_login_at',
    ];

    // Relations
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function completedOrders()
    {
        return $this->orders()->where('status', 'delivered');
    }

    public function pendingOrders()
    {
        return $this->orders()->whereIn('status', ['pending', 'processing']);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitialsAttribute()
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        
        // Génère un avatar par défaut avec les initiales
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function getFullAddressAttribute()
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $addressParts);
    }

    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) {
            return null;
        }
        
        // Format simple pour les numéros français
        return preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $this->phone);
    }

    public function getTotalSpentAttribute()
    {
        return $this->completedOrders()->sum('total');
    }

    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }

    // Mutators
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst(strtolower($value));
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst(strtolower($value));
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function setPhoneAttribute($value)
    {
        // Nettoie le numéro de téléphone (retire les espaces, points, tirets)
        $this->attributes['phone'] = preg_replace('/[^0-9+]/', '', $value);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    public function scopeWithOrders($query)
    {
        return $query->has('orders');
    }

    public function scopeRecentlyRegistered($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('first_name', 'LIKE', "%{$term}%")
                  ->orWhere('last_name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
        });
    }

    // Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isClient()
    {
        return $this->role === 'client';
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function isVerified()
    {
        return !is_null($this->email_verified_at);
    }

    public function hasCompleteProfile()
    {
        return !empty($this->first_name) && 
               !empty($this->last_name) && 
               !empty($this->email) && 
               !empty($this->phone) && 
               !empty($this->address) && 
               !empty($this->city) && 
               !empty($this->postal_code) && 
               !empty($this->country);
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
        return $this;
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
        return $this;
    }

    public function toggleStatus()
    {
        $this->is_active = !$this->is_active;
        $this->save();
        return $this;
    }

    public function makeAdmin()
    {
        $this->role = 'admin';
        $this->save();
        return $this;
    }

    public function makeClient()
    {
        $this->role = 'client';
        $this->save();
        return $this;
    }

    public function updateLastLogin()
    {
        $this->last_login_at = now();
        $this->save();
        return $this;
    }

    public function getCartTotal()
    {
        return $this->cart->sum(function ($item) {
            return $item->product->final_price * $item->quantity;
        });
    }

    public function getCartItemsCount()
    {
        return $this->cart->sum('quantity');
    }

    public function clearCart()
    {
        return $this->cart()->delete();
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function canAccessAdmin()
    {
        return $this->isAdmin() && $this->isActive();
    }

    public function getCustomerTier()
    {
        $totalSpent = $this->total_spent;
        
        if ($totalSpent >= 1000) {
            return 'platinum';
        } elseif ($totalSpent >= 500) {
            return 'gold';
        } elseif ($totalSpent >= 100) {
            return 'silver';
        }
        
        return 'bronze';
    }

    public function getCustomerTierColor()
    {
        $tier = $this->getCustomerTier();
        
        $colors = [
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2'
        ];
        
        return $colors[$tier] ?? '#CD7F32';
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Définit le rôle par défaut
            if (empty($user->role)) {
                $user->role = 'client';
            }
            
            // Active l'utilisateur par défaut
            if (is_null($user->is_active)) {
                $user->is_active = true;
            }
        });

        static::created(function ($user) {
            // Envoie un email de bienvenue (optionnel)
            // Mail::to($user->email)->send(new WelcomeEmail($user));
        });
    }

    // Static methods
    public static function getAdmins()
    {
        return static::admins()->active()->get();
    }

    public static function getClients()
    {
        return static::clients()->active()->get();
    }

    public static function getActiveUsersCount()
    {
        return static::active()->count();
    }

    public static function getNewUsersCount($days = 30)
    {
        return static::recentlyRegistered($days)->count();
    }

    public static function getTopCustomers($limit = 10)
    {
        return static::clients()
                    ->withCount('orders')
                    ->orderBy('orders_count', 'desc')
                    ->limit($limit)
                    ->get();
    }
}