<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PurchaseChart extends ApexChartWidget
{
    use InteractsWithPageFilters;
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'purchaseChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Purchase Chart';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $settings = app(GeneralSettings::class);

        $primaryColor = $settings->site_theme['primary'] ?? '#f59e0b'; // Default color if not set

        $start = $this->filters['startDate'] ?? null;
        $end = $this->filters['endDate'] ?? null;
        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfMonth();  // Default start date
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfMonth();      // Default end date

        $salesData = PurchaseInvoice::whereBetween('posted_date', [$startDate, $endDate])
            ->selectRaw('DATE(posted_date) as date, SUM(total_amount) as total_purchase')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total_purchase', 'date')
            ->toArray();

        $dates = array_keys($salesData);
        $totals = array_values($salesData);
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'BasicBarChart',
                    'data' => $totals,
                ],
            ],
            'xaxis' => [
                'categories' => $dates,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => [$primaryColor],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                ],
            ],
        ];
    }
}
