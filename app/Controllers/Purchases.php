<?php

namespace App\Controllers;

use App\Enums\Department;
use App\Models\PurchaseStatisticsModel;

class Purchases extends BaseController
{
    public function index(): string
    {
        return view('purchases/index');
    }

    public function create(): string
    {
        return view('purchases/create');
    }

    public function products(): string
    {
        return view('purchases/products');
    }

    public function history(): string
    {
        return view('purchases/history');
    }

    public function yearlyStatistics(): string
    {
        $year = (int) ($this->request->getGet('year') ?? date('Y'));
        if ($year < 2025 || $year > (int) date('Y')) {
            $year = (int) date('Y');
        }

        $department = strtolower(trim((string) ($this->request->getGet('department') ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        return view('purchases/yearly_statistics', [
            'year'        => $year,
            'department'  => $department,
            'departments' => Department::cases(),
            'report'      => (new PurchaseStatisticsModel())->getYearlyReport($year, [
                'department' => $department,
            ]),
        ]);
    }
}
