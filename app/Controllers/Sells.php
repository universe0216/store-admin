<?php

namespace App\Controllers;

use App\Enums\Department;

class Sells extends BaseController
{
    public function index(): string
    {
        return view('sells/index');
    }

    public function create(): string
    {
        return view('sells/create');
    }

    public function visualStatistics(): string
    {
        $db = db_connect();
        $warehouses = [];

        if ($db->tableExists('warehouses')) {
            $warehouses = $db->table('warehouses')
                ->select('id, name')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('sells/visual_statistics', [
            'warehouses'  => $warehouses,
            'departments' => Department::cases(),
        ]);
    }

    public function priceAnalyzer(): string
    {
        $db = db_connect();
        $warehouses = [];

        if ($db->tableExists('warehouses')) {
            $warehouses = $db->table('warehouses')
                ->select('id, name')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('sells/price_analyzer', [
            'warehouses' => $warehouses,
        ]);
    }

    public function yearlyStatistics(): string
    {
        $year = (int) ($this->request->getGet('year') ?? date('Y'));
        if ($year < 2025 || $year > (int) date('Y')) {
            $year = (int) date('Y');
        }

        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);
        $department  = strtolower(trim((string) ($this->request->getGet('department') ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        return view('sells/yearly_statistics', [
            'year'        => $year,
            'warehouseId' => $warehouseId,
            'department'  => $department,
            'warehouses'  => (new \App\Models\WarehouseModel())->listActive(),
            'departments' => Department::cases(),
            'report'      => (new \App\Models\SaleStatisticsModel())->getYearlyReport($year, [
                'warehouse_id' => $warehouseId,
                'department'   => $department,
            ]),
        ]);
    }
}
