<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        if (filter_var($this->record->image, FILTER_VALIDATE_URL)) {
            $this->record->image_url = $this->record->image;
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (filter_var($data['image'], FILTER_VALIDATE_URL)) {
            $data['image_url'] = $data['image'];
        }

        return $data;
    }
}
