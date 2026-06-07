<?php

namespace App\Commands;

use App\Database\Migrations\AddCanStoreCanSellToWarehouses;
use App\Database\Migrations\CreateWarehousesTable;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateWarehouses extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:warehouses';
    protected $description = 'Apply warehouses table schema (is_deleted flag, timestamps, can_store/can_sell).';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-29-000001_CreateWarehousesTable.php';
        require_once APPPATH . 'Database/Migrations/2026-06-08-000001_AddCanStoreCanSellToWarehouses.php';

        (new CreateWarehousesTable())->up();
        (new AddCanStoreCanSellToWarehouses())->up();
        CLI::write('Warehouses migration applied.', 'green');
    }
}
