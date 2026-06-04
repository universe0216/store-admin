<?php

namespace App\Commands;

use App\Database\Migrations\AddTransferFeeToPurchases;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigratePurchaseSchema extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:purchases';
    protected $description = 'Apply purchase schema updates (transfer_fee, shipping, item allocations).';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-29-000002_AddTransferFeeToPurchases.php';
        require_once APPPATH . 'Database/Migrations/2026-06-05-000004_AddPurchaseShippingAndItemAllocations.php';

        (new AddTransferFeeToPurchases())->up();
        (new \App\Database\Migrations\AddPurchaseShippingAndItemAllocations())->up();
        CLI::write('Purchase schema migration applied.', 'green');
    }
}
