<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWarehouseIdToSales extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('sales', [
            'warehouse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'customer_name',
            ],
        ]);
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'SET NULL', 'CASCADE');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('sales', 'sales_warehouse_id_foreign');
        $this->forge->dropColumn('sales', 'warehouse_id');
    }
}
