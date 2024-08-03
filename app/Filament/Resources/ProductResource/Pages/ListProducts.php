<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Forms\BulkUpdate\BulkUpdateForm;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->keyBindings(['option+n', 'alt+n']),
        ];
    }
    // Define the bulk update action
    public static function bulkUpdate(Collection $records, array $values): void
    {
        foreach ($records as $record) {
            $record->update([
                'category_id' => $values['category_id'] ?? $record->category_id,
                'supplier_id' => $values['supplier_id'] ?? $record->supplier_id,
                'brand_id' => $values['brand_id'] ?? $record->brand_id,
            ]);
        }

    }



}
