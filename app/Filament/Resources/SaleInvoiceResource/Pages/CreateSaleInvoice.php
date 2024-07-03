<?php

namespace App\Filament\Resources\SaleInvoiceResource\Pages;

use App\Filament\Resources\SaleInvoiceResource;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\CreateRecord;

class CreateSaleInvoice extends CreateRecord
{
    protected static string $resource = SaleInvoiceResource::class;
}
