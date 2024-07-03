<?php
namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\SaleInvoice;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SalesChart extends ApexChartWidget
{
    use InteractsWithPageFilters;
    
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'salesChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Sales Chart';

    /**
     * Chart options (series, labels, types, size, animations...)
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

        $salesData = SaleInvoice::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE(date) as date, SUM(total) as total_sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total_sales', 'date')
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
                    'name' => 'Total Sales',
                    'data' => $totals,
                ],
            ],
            'xaxis' => [
                'categories' => $dates,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
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
        ];
    }
}
