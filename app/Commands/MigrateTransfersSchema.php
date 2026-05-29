<?php

namespace App\Commands;

use App\Database\Migrations\CreateTransfersTables;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateTransfersSchema extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:transfers';
    protected $description = 'Apply transfers table schema.';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-30-000002_CreateTransfersTables.php';

        (new CreateTransfersTables())->up();
        CLI::write('Transfers schema migration applied.', 'green');
    }
}
