<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use EightyNine\Reports\Components\Text;

class SaleDetailReport extends Report
{
    public ?string $heading = "Sale Detail Report";

    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make('Sale Detail Report')
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
                        Body\TextColumn::make('invoice_number')->label('Invoice Number'),
                        Body\TextColumn::make('sale_date')->label('Sale Date'),
                        Body\TextColumn::make('product_name')->label('Product Name'),
                        Body\TextColumn::make('customer_name')->label('Customer Name'),
                        Body\TextColumn::make('quantity_sold')->label('Quantity Sold'),
                        Body\TextColumn::make('item_discount')->label('Item Discount'),
                        Body\TextColumn::make('invoice_discount')->label('Invoice Discount'),
                        Body\TextColumn::make('tax_amount')->label('Tax Amount'),
                        Body\TextColumn::make('total_sale')->label('Total Sale'),
                    ])
                    ->data(function (?array $filters) {
                        $startDate = Carbon::parse($filters['start_date'] ?? null);
                        $endDate = Carbon::parse($filters['end_date'] ?? null);
                        $productId = $filters['product_id'] ?? null;
                        $customerId = $filters['customer_id'] ?? null;

                        $salesQuery = SaleInvoice::with(['saleInvoiceItems.product', 'customer'])
                            ->whereBetween('date', [$startDate, $endDate]);

                        if ($productId) {
                            $salesQuery->whereHas('saleInvoiceItems', function ($query) use ($productId) {
                                $query->where('product_id', $productId);
                            });
                        }

                        if ($customerId) {
                            $salesQuery->where('customer_id', $customerId);
                        }

                        $salesData = $salesQuery->get()
                            ->map(function ($invoice) {
                                return $invoice->saleInvoiceItems->map(function ($item) use ($invoice) {
                                    return [
                                        'invoice_number' => $invoice->posted_number,
                                        'sale_date' => Carbon::parse($invoice->date)->format('Y-m-d'),
                                        'product_name' => $item->product->name,
                                        'customer_name' => $invoice->customer->name,
                                        'quantity_sold' => $item->quantity,
                                        'item_discount' => $item->item_discount_percentage,
                                        'invoice_discount' => $invoice->discount_amount,
                                        'tax_amount' => $invoice->tax_amount,
                                        'total_sale' => $item->sub_total,
                                    ];
                                });
                            })->flatten(1);

                        // Calculate totals
                        $totals = [
                            'invoice_number' => 'Total',
                            'sale_date' => '',
                            'product_name' => '',
                            'customer_name' => '',
                            'quantity_sold' => $salesData->sum('quantity_sold'),
                            'item_discount' => $salesData->sum('item_discount'),
                            'invoice_discount' => $salesData->sum('invoice_discount'),
                            'tax_amount' => $salesData->sum('tax_amount'),
                            'total_sale' => $salesData->sum('total_sale'),
                        ];

                        return $salesData->values()->push($totals);
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
                Select::make('customer_id')
                    ->label('Select Customer')
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }
}
