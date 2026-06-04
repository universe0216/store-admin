<?php

namespace App\Controllers;

use App\Enums\Department;

class Inventory extends BaseController
{
    public function index(): string
    {
        return view('inventory/index');
    }

    public function stockMovements(): string
    {
        return view('inventory/stock_movements');
    }

    public function salesStatistics(): string
    {
        $month = trim((string) ($this->request->getGet('month') ?? date('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);
        $department  = strtolower(trim((string) ($this->request->getGet('department') ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        return view('inventory/sales_statistics', [
            'month'       => $month,
            'warehouseId' => $warehouseId,
            'department'  => $department,
            'warehouses'  => (new \App\Models\WarehouseModel())->listActive(),
            'departments' => Department::cases(),
            'report'      => (new \App\Models\SaleStatisticsModel())->getMonthlyReport($month, [
                'warehouse_id' => $warehouseId,
                'department'   => $department,
            ]),
        ]);
    }
}
