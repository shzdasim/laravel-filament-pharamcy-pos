<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'sale_return_id', 'product_id', 'unit_sale_quantity', 'unit_return_quantity', 'unit_sale_price', 'item_discount_percentage', 'sub_total'
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
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
            $product->quantity += $item->unit_return_quantity;
            $product->save();
        });

        static::updated(function ($item) {
            $original = $item->getOriginal();
            $quantityDifference = $item->unit_return_quantity - $original['unit_return_quantity'];

            $product = $item->product;
            $product->quantity += $quantityDifference;
            $product->save();
        });

        static::deleted(function ($item) {
            $product = $item->product;
            $product->quantity -= $item->unit_return_quantity;
            $product->save();
        });
    }
}
