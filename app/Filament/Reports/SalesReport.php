<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use App\Models\SaleInvoice;
use App\Models\PurchaseInvoiceItem;
use Carbon\Carbon;
use EightyNine\Reports\Components\Text;

class SalesReport extends Report
{
    public ?string $heading = "Sales Report";
    // Add the navigation icon here
    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make('Sales Report')
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
                        Body\TextColumn::make('sale_date')->label('Sale Date'),
                        Body\TextColumn::make('gross_sale')->label('Gross Sale'),
                        Body\TextColumn::make('item_discount')->label('Item Discount'),
                        Body\TextColumn::make('invoice_discount')->label('Invoice Discount'),
                        Body\TextColumn::make('total_sale')->label('Total Sale'),
                        Body\TextColumn::make('sale_return')->label('Sale Return'),
                        Body\TextColumn::make('cost_of_sale')->label('Cost Of Sale'),
                        Body\TextColumn::make('gross_profit_amount')->label('Gross Profit (Amount)'),
                        Body\TextColumn::make('gross_profit_percentage')->label('Gross Profit (%)'),
                    ])
                    ->data(function (?array $filters) {
                        $startDate = Carbon::parse($filters['start_date'] ?? null);
                        $endDate = Carbon::parse($filters['end_date'] ?? null);

                        // Load sales data within the date range
                        $salesData = SaleInvoice::whereBetween('date', [$startDate, $endDate])
                            ->with(['saleInvoiceItems.product', 'saleReturns'])
                            ->get()
                            ->groupBy(function ($date) {
                                return Carbon::parse($date->date)->format('Y-m-d');
                            })
                            ->map(function ($items, $date) {
                                $grossSale = $items->sum('gross_amount');
                                $itemDiscount = $items->sum('item_discount');
                                $invoiceDiscount = $items->sum('discount_amount');
                                $totalSale = $items->sum('total');
                                
                                // Calculate sale return
                                $saleReturn = $items->sum(function ($item) {
                                    return $item->saleReturns->sum('total');
                                });

                                // Calculate cost of sale
                                $costOfSale = $items->sum(function ($item) {
                                    return $item->saleInvoiceItems->sum(function ($saleItem) {
                                        $remainingQuantity = $saleItem->quantity;
                                        $totalCost = 0;

                                        // Fetch corresponding purchase items in the order they were bought
                                        $purchaseItems = PurchaseInvoiceItem::where('product_id', $saleItem->product_id)
                                            ->orderBy('purchase_invoice_id', 'asc')
                                            ->get();

                                        foreach ($purchaseItems as $purchaseItem) {
                                            if ($remainingQuantity <= 0) {
                                                break;
                                            }

                                            $purchaseQuantity = $purchaseItem->quantity;
                                            $quantityToConsider = min($remainingQuantity, $purchaseQuantity);
                                            $remainingQuantity -= $quantityToConsider;

                                            $purchasePrice = $purchaseItem->purchase_price;
                                            $purchaseDiscount = $purchasePrice * $purchaseItem->item_discount_percentage / 100;
                                            $purchaseTax = $purchasePrice * $purchaseItem->purchaseInvoice->tax_percentage / 100;

                                            $totalCost += ($purchasePrice - $purchaseDiscount + $purchaseTax) * $quantityToConsider;
                                        }

                                        return $totalCost;
                                    });
                                });

                                $grossProfitAmount = $totalSale - $costOfSale;
                                $grossProfitPercentage = $totalSale > 0 ? ($grossProfitAmount / $totalSale) * 100 : 0;

                                return [
                                    'sale_date' => $date,
                                    'gross_sale' => $grossSale,
                                    'item_discount' => $itemDiscount,
                                    'invoice_discount' => $invoiceDiscount,
                                    'total_sale' => $totalSale,
                                    'sale_return' => $saleReturn,
                                    'cost_of_sale' => $costOfSale,
                                    'gross_profit_amount' => $grossProfitAmount,
                                    'gross_profit_percentage' => round($grossProfitPercentage, 2),
                                ];
                            });

                        $totalSummary = [
                            'sale_date' => 'Total',
                            'gross_sale' => $salesData->sum('gross_sale'),
                            'item_discount' => $salesData->sum('item_discount'),
                            'invoice_discount' => $salesData->sum('invoice_discount'),
                            'total_sale' => $salesData->sum('total_sale'),
                            'sale_return' => $salesData->sum('sale_return'),
                            'cost_of_sale' => $salesData->sum('cost_of_sale'),
                            'gross_profit_amount' => $salesData->sum('gross_profit_amount'),
                            'gross_profit_percentage' => $salesData->sum('total_sale') > 0 ? round(($salesData->sum('gross_profit_amount') / $salesData->sum('total_sale')) * 100, 2) : 0,
                        ];

                        return $salesData->values()->push($totalSummary);
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
            ]);
    }
}
