<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        [$period, $prev] = $this->getPeriods();

        // ===== Aggregasi bulanan (TOTAL) =====
        $curTotals  = $this->monthlyTotals($period['start'], $period['end']);
        $prevTotals = $this->monthlyTotals($prev['start'], $prev['end']);

        $grossNow = $curTotals['revenue'] - $curTotals['discounts'];
        $grossPrev = $prevTotals['revenue'] - $curTotals['discounts'];

        $netNow = ($curTotals['revenue'] - $curTotals['discounts'] - $curTotals['taxes']) - $curTotals['cogs'];
        $netPrev = ($prevTotals['revenue'] - $prevTotals['discounts'] - $prevTotals['taxes']) - $prevTotals['cogs'];

        $itemsNow = $curTotals['items'];
        $itemsPrev = $prevTotals['items'];

        // ===== Data harian untuk chart =====
        $daily = $this->dailySeries($period['start'], $period['end']);

        $grossSeries = $this->alignDays(
            $period['start'],
            $period['end'],
            // gross per hari = revenue - cogs - (discount + tax) per hari
            array_map(function ($d) {
                return ($d['revenue'] - $d['cogs']) - ($d['discounts'] + $d['taxes']);
            }, $daily)
        );

        $netSeries = $grossSeries; // definisi net di atas sama dengan gross - (disc+tax), jadi sama seri hariannya
        $itemsSeries = $this->alignDays(
            $period['start'],
            $period['end'],
            array_map(fn ($d) => $d['items'], $daily)
        );

        return [
            // ===== Keuntungan Kotor =====
            Stat::make('Keuntungan Kotor', $this->idr($grossNow))
                ->description($this->deltaText($grossNow, $grossPrev))
                ->descriptionIcon($this->deltaIcon($grossNow, $grossPrev))
                ->color($this->deltaColor($grossNow, $grossPrev))
                ->chart($grossSeries),

            // ===== Keuntungan Bersih =====
            Stat::make('Keuntungan Bersih', $this->idr($netNow))
                ->description($this->deltaText($netNow, $netPrev))
                ->descriptionIcon($this->deltaIcon($netNow, $netPrev))
                ->color($this->deltaColor($netNow, $netPrev))
                ->chart($netSeries),

            // ===== Total Penjualan Barang (Qty) =====
            Stat::make('Total Penjualan Barang', number_format($itemsNow))
                ->description($this->deltaText($itemsNow, $itemsPrev, suffix: ''))
                ->descriptionIcon($this->deltaIcon($itemsNow, $itemsPrev))
                ->color($this->deltaColor($itemsNow, $itemsPrev))
                ->chart($itemsSeries),
        ];
    }

    /**
     * Hitung total bulanan: revenue, cogs, items, discounts, taxes.
     * - revenue: SUM(qty * products.sell_price)
     * - cogs:    SUM(qty * products.cost_price)
     * - items:   SUM(qty)
     * - discounts & taxes: SUM dari tabel sales (tanpa join agar tidak duplikat)
     */
    protected function monthlyTotals(Carbon $start, Carbon $end): array
    {
        // Dari sale_items + products (revenue, cogs, items)
        $si = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->whereBetween('s.paid_at', [$start, $end])
            ->whereNull('s.deleted_at')
            ->whereNull('si.deleted_at')
            ->selectRaw('
                COALESCE(SUM(si.qty * p.sell_price), 0) as revenue,
                COALESCE(SUM(si.qty * p.cost_price), 0) as cogs,
                COALESCE(SUM(si.qty), 0) as items
            ')
            ->first();

        // Dari sales (discounts, taxes)
        $sal = DB::table('sales as s')
            ->whereBetween('s.paid_at', [$start, $end])
            ->whereNull('s.deleted_at')
            ->selectRaw('
                COALESCE(SUM(s.discount_total), 0) as discounts,
                COALESCE(SUM(s.tax_total), 0) as taxes
            ')
            ->first();

        return [
            'revenue'   => (float) $si->revenue,
            'cogs'      => (float) $si->cogs,
            'items'     => (int)   $si->items,
            'discounts' => (float) $sal->discounts,
            'taxes'     => (float) $sal->taxes,
        ];
    }

    /**
     * Data harian untuk sparkline (array keyed by date Y-m-d).
     * Kita gabungkan agregasi harian dari sale_items/products dan dari sales (disc & tax),
     * lalu satukan per tanggal.
     */
    protected function dailySeries(Carbon $start, Carbon $end): array
    {
        // Harian revenue / cogs / items
        $rows1 = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->whereBetween('s.paid_at', [$start, $end])
            ->whereNull('s.deleted_at')
            ->whereNull('si.deleted_at')
            ->selectRaw("
                DATE(s.paid_at) as d,
                COALESCE(SUM(si.qty * p.sell_price), 0) as revenue,
                COALESCE(SUM(si.qty * p.cost_price), 0) as cogs,
                COALESCE(SUM(si.qty), 0) as items
            ")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d')
            ->toArray();

        // Harian discounts / taxes (dari sales langsung supaya tidak terduplikasi)
        $rows2 = DB::table('sales as s')
            ->whereBetween('s.paid_at', [$start, $end])
            ->whereNull('s.deleted_at')
            ->selectRaw("
                DATE(s.paid_at) as d,
                COALESCE(SUM(s.discount_total), 0) as discounts,
                COALESCE(SUM(s.tax_total), 0) as taxes
            ")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d')
            ->toArray();

        // Merge per tanggal
        $cursor = $start->copy();
        $data = [];
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $a = $rows1[$key] ?? null;
            $b = $rows2[$key] ?? null;

            $data[$key] = [
                'revenue'   => $a ? (float) $a->revenue : 0.0,
                'cogs'      => $a ? (float) $a->cogs : 0.0,
                'items'     => $a ? (int)   $a->items : 0,
                'discounts' => $b ? (float) $b->discounts : 0.0,
                'taxes'     => $b ? (float) $b->taxes : 0.0,
            ];

            $cursor->addDay();
        }

        return $data;
    }

    /**
     * Ubah associative daily array menjadi indexed array sesuai urutan hari untuk chart().
     * $mapper menerima satu elemen harian dan mengembalikan angka (int|float).
     */
    protected function alignDays(Carbon $start, Carbon $end, array $mapped): array
    {
        // $mapped di sini sudah menjadi array nilai per tanggal (Y-m-d => number)
        $out = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $out[] = (int) round($mapped[$key] ?? 0);
            $cursor->addDay();
        }
        return $out;
    }

    protected function getPeriods(): array
    {
        $now = now();
        $period = [
            'start' => $now->copy()->startOfMonth(),
            'end'   => $now->copy()->endOfMonth(),
        ];

        $prevMonth = $now->copy()->subMonthNoOverflow();
        $prev = [
            'start' => $prevMonth->startOfMonth(),
            'end'   => $prevMonth->endOfMonth(),
        ];

        return [$period, $prev];
    }

    protected function deltaText(float|int $now, float|int $prev, string $suffix = '%'): string
    {
        if ($prev == 0) {
            return $now == 0 ? "0{$suffix} dari bulan lalu" : "100{$suffix} dari bulan lalu";
        }
        $pct = (($now - $prev) / $prev) * 100;
        return number_format($pct, 1) . "{$suffix} dari bulan lalu";
    }

    protected function deltaIcon(float|int $now, float|int $prev): string
    {
        if ($now == $prev) return 'heroicon-m-minus-small';
        return $now > $prev ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function deltaColor(float|int $now, float|int $prev): string
    {
        if ($now == $prev) return 'gray';
        return $now > $prev ? 'success' : 'danger';
    }

    protected function idr(float|int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
