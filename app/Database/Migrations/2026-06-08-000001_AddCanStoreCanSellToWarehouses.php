<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanStoreCanSellToWarehouses extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('warehouses')) {
            return;
        }

        if (! $this->db->fieldExists('can_store', 'warehouses')) {
            $this->forge->addColumn('warehouses', [
                'can_store' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'location',
                ],
            ]);
        }

        if (! $this->db->fieldExists('can_sell', 'warehouses')) {
            $this->forge->addColumn('warehouses', [
                'can_sell' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'can_store',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('warehouses')) {
            return;
        }

        if ($this->db->fieldExists('can_sell', 'warehouses')) {
            $this->forge->dropColumn('warehouses', 'can_sell');
        }

        if ($this->db->fieldExists('can_store', 'warehouses')) {
            $this->forge->dropColumn('warehouses', 'can_store');
        }
    }
}
