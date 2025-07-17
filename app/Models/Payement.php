<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'reference_number',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'fee',
        'processed_at',
        'failed_at',
        'refunded_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment method constants
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_STRIPE = 'stripe';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    // Gateway constants
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_PAYPAL = 'paypal';
    const GATEWAY_BANK = 'bank';

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Accesseurs
    public function getNetAmountAttribute()
    {
        return $this->amount - ($this->fee ?? 0);
    }

    public function getIsSuccessfulAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsFailedAttribute()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsRefundedAttribute()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Méthodes
    public function generateReferenceNumber()
    {
        $this->reference_number = 'PAY-' . date('Y') . '-' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
        $this->save();
    }

    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();
        $this->save();
    }

    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        if ($reason) {
            $this->notes = $reason;
        }
        $this->save();
    }

    public function markAsRefunded()
    {
        $this->status = self::STATUS_REFUNDED;
        $this->refunded_at = now();
        $this->save();
    }

    public function canBeRefunded()
    {
        return $this->status === self::STATUS_COMPLETED && $this->refunded_at === null;
    }
}