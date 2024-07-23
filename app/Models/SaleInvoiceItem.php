<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_invoice_id',
        'product_id',
        'pack_size',
        'current_quantity',
        'quantity',
        'price',
        'item_discount_percentage',
        'sub_total'
    ];

    public function saleInvoice()
    {
        return $this->belongsTo(SaleInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            $product = $item->product;
            $product->quantity -= $item->quantity;
            $product->save();
        });

        static::updated(function ($item) {
            $original = $item->getOriginal();
            $product = $item->product;

            // If product_id changes, handle old and new products separately
            if ($original['product_id'] !== $item->product_id) {
                // Update old product
                $oldProduct = Product::find($original['product_id']);
                if ($oldProduct) {
                    $oldProduct->quantity += $original['quantity'];
                    $oldProduct->save();
                }

                // Update new product
                $product->quantity -= $item->quantity;
            } else {
                // Adjust quantity for the same product
                $product->quantity += ($original['quantity'] - $item->quantity);
            }

            $product->save();
        });

        static::deleting(function ($item) {
            $product = $item->product;
            $product->quantity += $item->quantity;
            $product->save();
        });
    }
}
