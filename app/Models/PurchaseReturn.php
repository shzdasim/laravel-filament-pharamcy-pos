<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseReturn extends Model
{
    use HasFactory;
    protected $fillable = [
        'supplier_id', 'posted_number', 'date', 'purchase_invoice_id', 'gross_total', 'discount_percentage', 'tax_percentage', 'discount_amount', 'tax_amount', 'total',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->posted_number)) {
                $model->posted_number = self::generateCode();
            }
        });
        static::deleting(function ($saleInvoice) {
            $saleInvoice->purchaseReturnItems()->each(function ($item) {
                $item->delete();
            });
        });
    }

    public static function generateCode()
    {
        DB::beginTransaction();

        try {
            $lastPurchaseReturn = self::lockForUpdate()->orderBy('id', 'desc')->first();

            if (!$lastPurchaseReturn) {
                $newCode = 'PRRET-0001';
            } else {
                $lastCode = $lastPurchaseReturn->posted_number;
                $number = (int) substr($lastCode, 6) + 1;
                $newCode = 'PRRET-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }

            DB::commit();

            return $newCode;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
