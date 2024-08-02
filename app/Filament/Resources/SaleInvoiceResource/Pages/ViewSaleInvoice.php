<?php

namespace App\Filament\Resources\SaleInvoiceResource\Pages;

use App\Filament\Resources\SaleInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSaleInvoice extends ViewRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('createSaleInvoice')
                ->label('Create Sale Invoice')
                ->url(SaleInvoiceResource::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->keyBindings(['option+n', 'alt+n']),
        ];
    }
}
