<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;
    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Purchase Invoice')
                ->submit('store')
                ->keyBindings(['option+s', 'alt+s']),

            Action::make('addRepeaterItem')
                ->keyBindings(['option+arrow-down', 'alt+arrow-down'])
                ->color('secondary')
                ->action(function () {
                    // Safeguard to ensure the action is not triggered multiple times
                    static $isActionTriggered = false;

                    if (!$isActionTriggered) {
                        $isActionTriggered = true;
                        $this->addRepeaterItem();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
    private function addRepeaterItem()
    {
        if (!isset($this->data['purchaseInvoiceItems'])) {
            $this->data['purchaseInvoiceItems'] = [];
        }
        $this->data['purchaseInvoiceItems'][] = [
            'product_id' => null,
            'pack_size' => null,
            'pack_purchase_price' => 0,
            'unit_purchase_price' => 0,
            'pack_sale_price' => 0,
            'unit_sale_price' => 0,
            'sub_total' => 0,
        ];
        $this->fill($this->data);
    }
}
