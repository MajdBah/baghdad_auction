<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'type',
        'from_account_id',
        'to_account_id',
        'car_id',
        'amount',
        'commission_amount',
        'with_commission',
        'reference_number',
        'transaction_date',
        'description',
        'status',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'with_commission' => 'boolean',
        'transaction_date' => 'date',
    ];

    /**
     * Get the source account of the transaction
     */
    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Get the destination account of the transaction
     */
    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Get the car associated with the transaction
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the user who created the transaction
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the primary account associated with this transaction
     * Returns the 'from' account for most transaction types, or the 'to' account if 'from' is null
     */
    public function account()
    {
        // For most transactions, the 'from' account is the primary one
        // But if it's null, use the 'to' account
        return $this->from_account_id
            ? $this->belongsTo(Account::class, 'from_account_id')
            : $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Scope a query to only include transactions of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include transactions with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate the total amount including commission
     */
    public function getTotalAmount()
    {
        return $this->with_commission ? $this->amount + $this->commission_amount : $this->amount;
    }
}
