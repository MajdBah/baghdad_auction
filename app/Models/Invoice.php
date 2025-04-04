<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'type',
        'account_id',
        'car_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax_rate',
        'tax_amount',
        'shipping_fee',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the account associated with the invoice
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the invoice
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the items for this invoice
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scope a query to only include invoices of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include invoices with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['issued', 'partially_paid']);
    }

    /**
     * Check if the invoice is fully paid
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the invoice is partially paid
     */
    public function isPartiallyPaid()
    {
        return $this->status === 'partially_paid';
    }

    /**
     * Check if the invoice is overdue
     */
    public function isOverdue()
    {
        return $this->due_date < now() && !$this->isPaid();
    }

    /**
     * Record a payment for this invoice
     */
    public function recordPayment($amount)
    {
        $this->paid_amount += $amount;
        $this->balance = $this->total_amount - $this->paid_amount;

        if ($this->balance <= 0) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }

        return $this->save();
    }
}
