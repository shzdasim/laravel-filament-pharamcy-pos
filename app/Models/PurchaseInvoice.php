<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'posted_number',
        'posted_date',
        'remarks',
        'invoice_number',
        'invoice_amount',
        'tax_percentage',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'total_amount'
    ];

    protected $guarded = ['purchaseInvoiceItems'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->posted_number)) {
                $model->posted_number = self::generateCode();
            }
        });

        static::deleting(function ($invoice) {
            foreach ($invoice->purchaseInvoiceItems as $item) {
                $item->delete();
            }
        });
    }

    public static function generateCode()
    {
        DB::beginTransaction();

        try {
            $lastestPurchaseInvoice = self::lockForUpdate()->orderBy('id', 'desc')->first();

            if (!$lastestPurchaseInvoice) {
                $newCode = 'PRINV-0001';
            } else {
                $lastCode = $lastestPurchaseInvoice->posted_number;
                $number = (int) substr($lastCode, 6) + 1;
                $newCode = 'PRINV-' . str_pad($number, 4, '0', STR_PAD_LEFT);
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

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }
}
