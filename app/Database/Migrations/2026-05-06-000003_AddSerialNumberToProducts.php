<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSerialNumberToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'serial_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'brand',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('products', 'serial_number');
    }
}
