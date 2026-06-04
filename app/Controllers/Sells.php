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
}
