<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
            unset($data['image_url']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (filter_var($data['image'], FILTER_VALIDATE_URL)) {
            $data['image_url'] = $data['image'];
        }

        return $data;
    }
}
