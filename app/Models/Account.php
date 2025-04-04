<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'account_number',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'balance',
        'notes',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions where this account is the source
     */
    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    /**
     * Get the transactions where this account is the destination
     */
    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }

    /**
     * Get all cars associated with this account as a customer
     */
    public function customerCars()
    {
        return $this->hasMany(Car::class, 'customer_account_id');
    }

    /**
     * Get all cars associated with this account as a shipping company
     */
    public function shippingCars()
    {
        return $this->hasMany(Car::class, 'shipping_company_id');
    }

    /**
     * Get all invoices for this account
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope a query to only include customer accounts.
     */
    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    /**
     * Scope a query to only include shipping company accounts.
     */
    public function scopeShippingCompanies($query)
    {
        return $query->where('type', 'shipping_company');
    }

    /**
     * Scope a query to only include intermediary accounts.
     */
    public function scopeIntermediaries($query)
    {
        return $query->where('type', 'intermediary');
    }
}
