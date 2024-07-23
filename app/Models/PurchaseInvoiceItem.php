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

            // Get old values
            $oldQuantity = $product->quantity ?? 0;
            $oldPurchasePrice = $product->avg_price ?? 0;

            // Get new values
            $newQuantity = $item->quantity;
            $newPurchasePrice = $item->unit_purchase_price;

            if ($action == 'create') {
                $totalQuantity = $oldQuantity + $newQuantity;
                $totalCost = ($oldQuantity * $oldPurchasePrice) + ($newQuantity * $newPurchasePrice);
            } elseif ($action == 'update') {
                $original = $item->getOriginal();
                $originalQuantity = $original['quantity'];
                $originalPurchasePrice = $original['unit_purchase_price'];

                $totalQuantity = $oldQuantity - $originalQuantity + $newQuantity;
                $totalCost = ($oldQuantity * $oldPurchasePrice) - ($originalQuantity * $originalPurchasePrice) + ($newQuantity * $newPurchasePrice);
            } elseif ($action == 'delete') {
                $totalQuantity = $oldQuantity - $newQuantity;
                $totalCost = ($oldQuantity * $oldPurchasePrice) - ($newQuantity * $newPurchasePrice);
            }

            // Calculate new average price
            $newAvgPrice = ($totalQuantity > 0) ? $totalCost / $totalQuantity : 0;

            // Calculate margin
            $salePrice = $item->unit_sale_price; // Assuming unit sale price, adjust if necessary
            $margin = ($salePrice > 0) ? (($salePrice - $newAvgPrice) / $salePrice) * 100 : 0;

            // Update product fields with calculated values
            $product->quantity = $totalQuantity;
            $product->avg_price = $newAvgPrice;
            $product->margin = $margin;

            // Update product fields with values directly from the form fields
            $product->pack_purchase_price = $item->pack_purchase_price;
            $product->unit_purchase_price = $item->unit_purchase_price;
            $product->pack_sale_price = $item->pack_sale_price;
            $product->unit_sale_price = $item->unit_sale_price;

            $product->save();
        }
    }
}
