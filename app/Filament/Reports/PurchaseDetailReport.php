<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use App\Models\PurchaseInvoice;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use EightyNine\Reports\Components\Text;

class PurchaseDetailReport extends Report
{
    public ?string $heading = "Purchase Detail Report";

    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make('Purchase Detail Report')
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
                        Body\TextColumn::make('purchase_date')->label('Purchase Date'),
                        Body\TextColumn::make('invoice_number')->label('Invoice Number'),
                        Body\TextColumn::make('product_name')->label('Product Name'),
                        Body\TextColumn::make('supplier_name')->label('Supplier Name'),
                        Body\TextColumn::make('quantity_purchased')->label('Quantity Purchased'),
                        Body\TextColumn::make('item_discount')->label('Item Discount'),
                        Body\TextColumn::make('tax_amount')->label('Tax Amount'),
                        Body\TextColumn::make('total_purchase')->label('Total Purchase'),
                    ])
                    ->data(function (?array $filters) {
                        $startDate = Carbon::parse($filters['start_date'] ?? null);
                        $endDate = Carbon::parse($filters['end_date'] ?? null);
                        $productId = $filters['product_id'] ?? null;
                        $supplierId = $filters['supplier_id'] ?? null;

                        $purchaseQuery = PurchaseInvoice::with(['purchaseInvoiceItems.product', 'supplier'])
                            ->whereBetween('posted_date', [$startDate, $endDate]);

                        if ($productId) {
                            $purchaseQuery->whereHas('purchaseInvoiceItems', function ($query) use ($productId) {
                                $query->where('product_id', $productId);
                            });
                        }

                        if ($supplierId) {
                            $purchaseQuery->where('supplier_id', $supplierId);
                        }

                        $purchaseData = $purchaseQuery->get()
                            ->map(function ($invoice) {
                                return $invoice->purchaseInvoiceItems->map(function ($item) use ($invoice) {
                                    return [
                                        'purchase_date' => Carbon::parse($invoice->posted_date)->format('Y-m-d'),
                                        'invoice_number' => $invoice->invoice_number,
                                        'product_name' => $item->product->name,
                                        'supplier_name' => $invoice->supplier->name,
                                        'quantity_purchased' => $item->quantity,
                                        'item_discount' => $item->item_discount_percentage,
                                        'tax_amount' => $invoice->tax_amount,
                                        'total_purchase' => $item->sub_total,
                                    ];
                                });
                            })->flatten(1);

                        // Calculate totals
                        $totals = [
                            'purchase_date' => 'Total',
                            'invoice_number' => '',
                            'product_name' => '',
                            'supplier_name' => '',
                            'quantity_purchased' => $purchaseData->sum('quantity_purchased'),
                            'item_discount' => $purchaseData->sum('item_discount'),
                            'tax_amount' => $purchaseData->sum('tax_amount'),
                            'total_purchase' => $purchaseData->sum('total_purchase'),
                        ];

                        return $purchaseData->values()->push($totals);
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
                Select::make('product_id')
                    ->label('Select Product')
                    ->options(Product::all()->pluck('name', 'id'))
                    ->searchable(),
                Select::make('supplier_id')
                    ->label('Select Supplier')
                    ->options(Supplier::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }
}
