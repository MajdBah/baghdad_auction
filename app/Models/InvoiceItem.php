<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'car_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'tax_amount',
        'total',
        'item_type'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the invoice that owns the item
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the car associated with the invoice item
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Calculate the subtotal (before tax)
     */
    public function getSubtotal()
    {
        return ($this->quantity * $this->unit_price) - $this->discount;
    }

    /**
     * Calculate tax amount based on subtotal and tax rate
     */
    public function calculateTaxAmount()
    {
        return $this->getSubtotal() * ($this->tax_rate / 100);
    }

    /**
     * Calculate the total amount including tax
     */
    public function calculateTotal()
    {
        return $this->getSubtotal() + $this->tax_amount;
    }
}
