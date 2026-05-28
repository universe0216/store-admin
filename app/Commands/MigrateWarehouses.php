<?php

namespace App\Commands;

use App\Database\Migrations\CreateWarehousesTable;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateWarehouses extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:warehouses';
    protected $description = 'Apply warehouses table schema (is_deleted flag and timestamps).';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-29-000001_CreateWarehousesTable.php';

        (new CreateWarehousesTable())->up();
        CLI::write('Warehouses migration applied.', 'green');
    }
}
