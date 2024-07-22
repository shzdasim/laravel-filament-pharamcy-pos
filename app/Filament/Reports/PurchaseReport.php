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
use App\Models\Supplier;
use Carbon\Carbon;
use EightyNine\Reports\Components\Text;

class PurchaseReport extends Report
{
    public ?string $heading = "Purchase Report";

    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make('Purchase Report')
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
                        Body\TextColumn::make('posted_number')->label('Posted Number'),
                        Body\TextColumn::make('supplier_name')->label('Supplier Name'),
                        Body\TextColumn::make('invoice_discount')->label('Invoice Discount'),
                        Body\TextColumn::make('tax_amount')->label('Tax Amount'),
                        Body\TextColumn::make('total_purchase')->label('Total Purchase'),
                    ])
                    ->data(function (?array $filters) {
                        $startDate = Carbon::parse($filters['start_date'] ?? null);
                        $endDate = Carbon::parse($filters['end_date'] ?? null);
                        $supplierId = $filters['supplier_id'] ?? null;

                        $purchaseQuery = PurchaseInvoice::with(['purchaseInvoiceItems.product', 'supplier'])
                            ->whereBetween('posted_date', [$startDate, $endDate]);

                        if ($supplierId) {
                            $purchaseQuery->where('supplier_id', $supplierId);
                        }

                        $purchaseData = $purchaseQuery->get()
                            ->map(function ($invoice) {
                                $totalPurchase = $invoice->purchaseInvoiceItems->sum('sub_total');

                                return [
                                    'purchase_date' => Carbon::parse($invoice->posted_date)->format('Y-m-d'),
                                    'posted_number' => $invoice->posted_number,
                                    'supplier_name' => $invoice->supplier->name,
                                    'invoice_discount' => $invoice->discount_amount,
                                    'tax_amount' => $invoice->tax_amount,
                                    'total_purchase' => $totalPurchase,
                                ];
                            });

                        // Calculate totals
                        $totals = [
                            'purchase_date' => 'Total',
                            'posted_number' => '',
                            'supplier_name' => '',
                            'invoice_discount' => $purchaseData->sum('invoice_discount'),
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
                Select::make('supplier_id')
                    ->label('Select Supplier')
                    ->options(Supplier::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }
}
