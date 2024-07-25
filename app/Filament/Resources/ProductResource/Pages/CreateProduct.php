<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function getFormActions(): array
    {
        return [
            Action::make('create')
            ->submit('store')
            ->color('success')
            ->label('Create Product')
            ->successRedirectUrl(fn (Model $record): string => route('products.edit', [
                'product' => $record,
            ]))

            ->keyBindings(['option+s', 'alt+s']),

            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
        }

        return $data;
    }
}

