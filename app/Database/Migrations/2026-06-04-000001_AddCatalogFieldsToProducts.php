<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCatalogFieldsToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'brand',
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'department',
            ],
            'season' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'gender',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('products', ['department', 'gender', 'season']);
    }
}
