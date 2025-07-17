<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relations
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts()
    {
        return $this->hasMany(Product::class)->where('status', 'active');
    }

    // Accessors
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/categories/' . $this->image);
        }
        return asset('images/default-category.jpg');
    }

    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    public function getActiveProductsCountAttribute()
    {
        return $this->activeProducts()->count();
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopeWithProductCount($query)
    {
        return $query->withCount('products');
    }

    public function scopeWithActiveProducts($query)
    {
        return $query->whereHas('products', function ($query) {
            $query->where('status', 'active');
        });
    }

    // Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function hasProducts()
    {
        return $this->products()->exists();
    }

    public function hasActiveProducts()
    {
        return $this->activeProducts()->exists();
    }

    public function toggleStatus()
    {
        $this->is_active = !$this->is_active;
        $this->save();
        return $this;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Boot method pour auto-générer le slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Static methods
    public static function getActiveCategories()
    {
        return static::active()->ordered()->get();
    }

    public static function getCategoriesWithProducts()
    {
        return static::active()
                    ->withActiveProducts()
                    ->withCount('activeProducts')
                    ->ordered()
                    ->get();
    }

    public static function getNextSortOrder()
    {
        return static::max('sort_order') + 1;
    }
}