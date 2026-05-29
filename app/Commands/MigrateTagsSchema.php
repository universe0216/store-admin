<?php

namespace App\Commands;

use App\Database\Migrations\CreateTagsAndTaggingsTables;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateTagsSchema extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:tags';
    protected $description = 'Apply tags and taggings table schema.';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-30-000003_CreateTagsAndTaggingsTables.php';

        (new CreateTagsAndTaggingsTables())->up();
        CLI::write('Tags schema migration applied.', 'green');
    }
}
