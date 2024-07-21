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
                ->label('Create Sale Invoice')
                ->submit('store')
                ->keyBindings(['option+s', 'alt+s']),

            Action::make('addRepeaterItem')
                ->keyBindings(['option+n', 'alt+n'])
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
        $this->data['purchaseInvoiceItems'][] = [];
    }
}
