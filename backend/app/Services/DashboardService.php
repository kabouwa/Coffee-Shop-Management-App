<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, string $period = 'day'): array
    {
        $orders = $user->orders();
        $completedStatuses = [OrderStatus::Completed->value];
        $openStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::Preparing->value,
            OrderStatus::Ready->value,
        ];

        $revenueQuery = (clone $orders)->whereIn('status', $completedStatuses);

        $bestSeller = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn ($query) => $query->ownedBy($user)->whereIn('status', $completedStatuses))
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->with('product')
            ->first();

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn ($query) => $query->ownedBy($user)->whereIn('status', $completedStatuses))
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->with('product')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $item) => [
                'slug' => $item->product?->slug,
                'name' => $item->product?->name ?? 'Deleted product',
                'quantity_sold' => (int) $item->quantity_sold,
                'revenue' => (float) $item->revenue,
            ]);

        $statusBreakdown = (clone $orders)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $completedCount = (clone $orders)->where('status', OrderStatus::Completed)->count();
        $totalRevenue = (float) (clone $revenueQuery)->sum('total');

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => (clone $orders)->count(),
            'today_orders' => (clone $orders)->whereDate('created_at', Carbon::today())->count(),
            'open_orders' => (clone $orders)->whereIn('status', $openStatuses)->count(),
            'completed_orders' => $completedCount,
            'average_basket' => $completedCount > 0 ? round($totalRevenue / $completedCount, 2) : 0,
            'best_selling_product' => $bestSeller ? [
                'slug' => $bestSeller->product->slug,
                'name' => $bestSeller->product?->name ?? 'Deleted product',
                'quantity_sold' => (int) $bestSeller->quantity_sold,
            ] : null,
            'top_products' => $topProducts,
            'orders_by_status' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $status) => [
                    'status' => $status->value,
                    'count' => (int) ($statusBreakdown[$status->value] ?? 0),
                ])
                ->values(),
            'revenue_chart' => $this->revenueChart($user, $period),
            'period' => $period,
        ];
    }

    /**
     * @return list<array{label: string, revenue: float}>
     */
    private function revenueChart(User $user, string $period): array
    {
        $query = $user->orders()->where('status', OrderStatus::Completed);

        return match ($period) {
            'week' => $this->groupedChart($query, 'week', 8, fn ($row) => (string) $row->bucket),
            'month' => $this->groupedChart($query, 'month', 12, fn ($row) => (string) $row->bucket),
            default => $this->groupedChart($query, 'day', 14, fn ($row) => (string) $row->bucket),
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Order>  $query
     * @return list<array{label: string, revenue: float}>
     */
    private function groupedChart($query, string $sqliteExpr, int $limit, callable $label): array
    {
        $driver = DB::connection()->getDriverName();

        $expression = match ([$driver, $sqliteExpr]) {
            ['mysql', 'week'] => "DATE_FORMAT(created_at, '%x-W%v')",
            ['mysql', 'month'] => "DATE_FORMAT(created_at, '%Y-%m')",
            ['mysql', 'day'] => 'DATE(created_at)',

            ['sqlite', 'week'] => "strftime('%Y-W%W', created_at)",
            ['sqlite', 'month'] => "strftime('%Y-%m', created_at)",
            ['sqlite', 'day'] => 'date(created_at)',

            default => 'date(created_at)',
        };

        return $query
            ->selectRaw("{$expression} as bucket, SUM(total) as revenue")
            ->groupBy('bucket')
            ->orderByDesc('bucket')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($row) => [
                'label' => (string) $label($row),
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }
}
