<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vin',
        'make',
        'model',
        'year',
        'color',
        'purchase_price',
        'selling_price',
        'shipping_cost',
        'intermediary_profit',
        'auction_name',
        'auction_lot_number',
        'customer_account_id',
        'shipping_company_id',
        'status',
        'purchase_date',
        'shipping_date',
        'delivery_date',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'intermediary_profit' => 'decimal:2',
        'purchase_date' => 'date',
        'shipping_date' => 'date',
        'delivery_date' => 'date',
        'year' => 'integer',
    ];

    /**
     * Get the customer account associated with the car
     */
    public function customerAccount()
    {
        return $this->belongsTo(Account::class, 'customer_account_id');
    }

    /**
     * Get the shipping company associated with the car
     */
    public function shippingCompany()
    {
        return $this->belongsTo(Account::class, 'shipping_company_id');
    }

    /**
     * Get the transactions associated with the car
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the invoice items associated with the car
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the invoices associated with the car through invoice items
     */
    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            InvoiceItem::class,
            'car_id', // Foreign key on InvoiceItem table
            'id', // Foreign key on Invoice table
            'id', // Local key on Car table
            'invoice_id' // Local key on InvoiceItem table
        );
    }

    /**
     * Scope a query to only include cars with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate the total cost of the car
     */
    public function getTotalCost()
    {
        return $this->purchase_price + $this->shipping_cost;
    }

    /**
     * Calculate the profit on this car
     */
    public function getProfit()
    {
        if (!$this->selling_price) {
            return 0;
        }

        return $this->selling_price - $this->getTotalCost();
    }
}
