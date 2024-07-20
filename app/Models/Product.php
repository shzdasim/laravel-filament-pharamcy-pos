<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code', 'name', 'image', 'formulation', 'description', 'pack_size', 'quantity', 'pack_purchase_price',
        'pack_sale_price', 'unit_purchase_price', 'unit_sale_price', 'avg_price', 'narcotic', 'margin', 'max_discount',
        'category_id', 'supplier_id', 'brand_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->product_code = self::generateCode();
        });

        static::saving(function ($model) {
            if ($model->image && $model->image_url) {
                throw new \Exception('Please provide either an image file or an image URL, not both.');
            }
            if ($model->image_url) {
                $model->image = $model->image_url;
            }
        });
    }

    public static function generateCode()
    {
        // Start a transaction
        DB::beginTransaction();

        try {
            // Lock the table to prevent concurrent writes
            $latestProduct = self::lockForUpdate()->orderBy('id', 'desc')->first();

            if (!$latestProduct) {
                $newCode = 'PRD-0001';
            } else {
                $lastCode = $latestProduct->product_code;
                $number = (int) substr($lastCode, 4) + 1;
                $newCode = 'PRD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }

            // Commit the transaction
            DB::commit();

            return $newCode;
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();
            throw $e;
        }
    }
}
