<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\SaleInvoiceItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Brand;
use Carbon\Carbon;
use EightyNine\Reports\Components\Text;

class PurchaseOrderReport extends Report
{
    public ?string $heading = "Purchase Order Report";

    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make('Purchase Order Report')
                    ->title()
                    ->primary(),
            ]);
    }

    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Body\Table::make()
                    ->columns([
                        Body\TextColumn::make('product_name')->label('Product Name'),
                        Body\TextColumn::make('pack_size')->label('Pack Size'),
                        Body\TextColumn::make('available_pack')->label('Available Pack'),
                        Body\TextColumn::make('pack_purchase_price')->label('Pack Purchase Price'),
                        Body\TextColumn::make('pack_sold')->label('Pack Sold'),
                        Body\TextColumn::make('packs_needed')->label('Packs Needed'),
                        Body\TextColumn::make('needed_pack_price')->label('Needed Pack Price'),
                    ])
                    ->data(function (?array $filters) {
                        $startDate = Carbon::parse($filters['start_date'] ?? null);
                        $endDate = Carbon::parse($filters['end_date'] ?? null);
                        $brandId = $filters['brand_id'] ?? null;
                        $supplierId = $filters['supplier_id'] ?? null;
                        $numberOfDays = $filters['number_of_days'] ?? 0;

                        // Get the sold quantity of products between the selected dates
                        $soldProductsQuery = SaleInvoiceItem::whereHas('saleInvoice', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('date', [$startDate, $endDate]);
                        });

                        if ($brandId) {
                            $soldProductsQuery->whereHas('product', function ($query) use ($brandId) {
                                $query->where('brand_id', $brandId);
                            });
                        }

                        if ($supplierId) {
                            $soldProductsQuery->whereHas('product', function ($query) use ($supplierId) {
                                $query->where('supplier_id', $supplierId);
                            });
                        }

                        $soldProducts = $soldProductsQuery->get()->groupBy('product_id');

                        $purchaseOrders = $soldProducts->map(function ($items, $productId) use ($numberOfDays, $startDate, $endDate) {
                            $totalQuantitySold = $items->sum('quantity');
                            $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                            $averageDailySales = $totalQuantitySold / $totalDays;
                            $product = $items->first()->product;
                            $packsNeeded = ceil($averageDailySales * $numberOfDays / $product->pack_size);
                            $neededPackPrice = $packsNeeded * $product->pack_purchase_price;

                            return [
                                'product_name' => $product->name,
                                'pack_size' => $product->pack_size,
                                'available_pack' => $product->quantity,
                                'pack_purchase_price' => $product->pack_purchase_price,
                                'pack_sold' => ceil($totalQuantitySold / $product->pack_size),
                                'packs_needed' => $packsNeeded,
                                'needed_pack_price' => $neededPackPrice,
                            ];
                        });

                        // Calculate totals
                        $totals = [
                            'product_name' => 'Total',
                            'pack_size' => '',
                            'available_pack' => '',
                            'pack_purchase_price' => '',
                            'pack_sold' => $purchaseOrders->sum('pack_sold'),
                            'packs_needed' => $purchaseOrders->sum('packs_needed'),
                            'needed_pack_price' => $purchaseOrders->sum('needed_pack_price'),
                        ];

                        return $purchaseOrders->values()->push($totals);
                    }),
            ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
                Text::make('Generated on: ' . now()->format('Y-m-d H:i:s')),
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')->label('Start Date')->native(false)->default(now())->closeOnDateSelection(),
                DatePicker::make('end_date')->label('End Date')->native(false)->default(now())->closeOnDateSelection(),
                Select::make('brand_id')
                    ->label('Select Brand')
                    ->options(Brand::all()->pluck('name', 'id'))
                    ->searchable(),
                Select::make('supplier_id')
                    ->label('Select Supplier')
                    ->options(Supplier::all()->pluck('name', 'id'))
                    ->searchable(),
                TextInput::make('number_of_days')->label('Number of Days')->numeric()->default(0),
            ]);
    }
}
