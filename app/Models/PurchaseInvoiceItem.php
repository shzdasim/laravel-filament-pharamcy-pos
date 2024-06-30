<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'pack_size',
        'pack_quantity',
        'unit_quantity',
        'pack_purchase_price',
        'unit_purchase_price',
        'pack_sale_price',
        'unit_sale_price',
        'pack_bonus',
        'unit_bonus',
        'item_discount_percentage',
        'margin',
        'sub_total',
        'avg_price',
        'quantity'
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            self::updateProduct($item, 'create');
        });

        static::updating(function ($item) {
            self::updateProduct($item, 'update');
        });

        static::deleting(function ($item) {
            self::updateProduct($item, 'delete');
        });
    }

    protected static function updateProduct($item, $action)
    {
        $product = $item->product;
        
        if ($product) {
            // Ensure the relationship is loaded
            $product->load('purchaseInvoiceItems');

            // Initialize totals
            $totalPackQuantity = $product->purchaseInvoiceItems->sum('pack_quantity') ?? 0;
            $totalUnitQuantity = $product->purchaseInvoiceItems->sum('unit_quantity') ?? 0;
            $totalPackBonus = $product->purchaseInvoiceItems->sum('pack_bonus') ?? 0;
            $totalUnitBonus = $product->purchaseInvoiceItems->sum('unit_bonus') ?? 0;

            // Adjust totals based on the action
            if ($action == 'create') {
                $totalPackQuantity += $item->pack_quantity;
                $totalUnitQuantity += $item->unit_quantity;
                $totalPackBonus += $item->pack_bonus;
                $totalUnitBonus += $item->unit_bonus;
            } elseif ($action == 'update') {
                $original = $item->getOriginal();
                $totalPackQuantity += ($item->pack_quantity - $original['pack_quantity']);
                $totalUnitQuantity += ($item->unit_quantity - $original['unit_quantity']);
                $totalPackBonus += ($item->pack_bonus - $original['pack_bonus']);
                $totalUnitBonus += ($item->unit_bonus - $original['unit_bonus']);
            } elseif ($action == 'delete') {
                $totalPackQuantity -= $item->pack_quantity;
                $totalUnitQuantity -= $item->unit_quantity;
                $totalPackBonus -= $item->pack_bonus;
                $totalUnitBonus -= $item->unit_bonus;
            }

            // Calculate total quantity
            $totalQuantity = ($totalPackQuantity * $product->pack_size) + $totalUnitBonus;

            // Calculate avg_price
            $avgPrice = ($totalQuantity > 0) ? ($totalPackQuantity * $item->pack_purchase_price) / $totalQuantity : 0;

            // Apply discount
            $discountedAvgPrice = $avgPrice * (1 - $item->item_discount_percentage / 100);

            // Calculate margin
            $margin = ($item->pack_sale_price > 0) ? (($item->pack_sale_price - $discountedAvgPrice) / $item->pack_sale_price) * 100 : 0;

            // Update product fields
            $product->quantity = $totalQuantity;
            $product->pack_purchase_price = $item->pack_purchase_price;
            $product->unit_purchase_price = ($product->pack_size > 0) ? $item->pack_purchase_price / $product->pack_size : 0;
            $product->pack_sale_price = $item->pack_sale_price;
            $product->unit_sale_price = ($product->pack_size > 0) ? $item->pack_sale_price / $product->pack_size : 0;
            $product->avg_price = $discountedAvgPrice;
            $product->margin = $margin;

            $product->save();
        }
    }
}
