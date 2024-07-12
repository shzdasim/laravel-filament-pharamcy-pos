<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PurchaseReturnItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_return_id', 'product_id', 'return_quantity', 'unit_purchase_price',
        'item_discount_percentage', 'sub_total',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
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
            $product->quantity -= $item->return_quantity;
            Log::alert($item->return_quantity);
            $product->save();
        });

        static::updated(function ($item) {
            $original = $item->getOriginal();
            $product = $item->product;
            $quantityDifference = $original['return_quantity'] - $item->return_quantity;
            $product->quantity += $quantityDifference;
            $product->save();
        });

        static::deleted(function ($item) {
            $product = $item->product;
            $product->quantity += $item->return_quantity;
            Log::alert($item->return_quantity);
            $product->save();
        });
    }
}
