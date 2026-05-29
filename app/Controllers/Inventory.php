<?php

namespace App\Controllers;

class Inventory extends BaseController
{
    public function index(): string
    {
        return view('inventory/index');
    }

    public function salesStatistics(): string
    {
        $month = trim((string) ($this->request->getGet('month') ?? date('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);
        $warehouseFilter = $warehouseId > 0 ? $warehouseId : null;

        return view('inventory/sales_statistics', [
            'month'       => $month,
            'warehouseId' => $warehouseId,
            'warehouses'  => (new \App\Models\WarehouseModel())->listActive(),
            'report'      => (new \App\Models\SaleStatisticsModel())->getMonthlyReport($month, $warehouseFilter),
        ]);
    }
}
