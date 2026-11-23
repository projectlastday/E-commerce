<?php

namespace App\Filament\Widgets;

use App\Models\EventLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\RecommendationLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecSysStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::whereIn('status', ['PAID', 'COMPLETED'])->sum('total_amount');
        $telemetryEvents = EventLog::count();
        $recsServed = RecommendationLog::count();

        return [
            Stat::make('Katalog Produk Aktif', $totalProducts)
                ->description('Total SKU UMKM Terdaftar')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Total Pesanan Masuk', $totalOrders)
                ->description('Omset: Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Event Telemetri Terekam', $telemetryEvents)
                ->description('Dwell time, click, cart, purchase')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Kurasi Produk Disajikan', $recsServed)
                ->description('Total tayangan kurasi cerdas')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
        ];
    }
}
