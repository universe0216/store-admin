<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowSignedPurchaseItemQty extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('purchase_items', [
            'qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => false,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('purchase_items', [
            'qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);
    }
}
