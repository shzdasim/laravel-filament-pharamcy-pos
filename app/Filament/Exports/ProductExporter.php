<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('product_code'),
            ExportColumn::make('name'),
            ExportColumn::make('image'),
            ExportColumn::make('formulation'),
            ExportColumn::make('description'),
            ExportColumn::make('pack_size'),
            ExportColumn::make('quantity'),
            ExportColumn::make('pack_purchase_price'),
            ExportColumn::make('pack_sale_price'),
            ExportColumn::make('unit_purchase_price'),
            ExportColumn::make('unit_sale_price'),
            ExportColumn::make('avg_price'),
            ExportColumn::make('narcotic'),
            ExportColumn::make('max_discount'),
            ExportColumn::make('margin'),
            ExportColumn::make('category_id'),
            ExportColumn::make('supplier_id'),
            ExportColumn::make('brand_id'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
