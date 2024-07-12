<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaleReturn extends Model
{
    use HasFactory;
    protected $fillable = [
        'sale_invoice_id', 'user_id', 'customer_id', 'posted_number', 'date', 'discount_percentage', 'discount_amount', 'tax_percetage', 'tax_amount', 'total', 'gross_total'
    ];

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleInvoice()
    {
        return $this->belongsTo(SaleInvoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->posted_number)) {
                $model->posted_number = self::generateCode();
            }
        });
        static::deleting(function ($invoice) {
            foreach ($invoice->saleReturnItems as $item) {
                $item->delete();
            }
        });
    }

    public static function generateCode()
    {
        DB::beginTransaction();

        try {
            $lastestReturn = self::lockForUpdate()->orderBy('id', 'desc')->first();

            if (!$lastestReturn) {
                $newCode = 'SRINV-0001';
            } else {
                $lastCode = $lastestReturn->posted_number;
                $number = (int) substr($lastCode, 6) + 1;
                $newCode = 'SRINV-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }

            DB::commit();

            return $newCode;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
