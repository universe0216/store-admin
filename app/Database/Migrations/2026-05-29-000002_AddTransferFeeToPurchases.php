<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransferFeeToPurchases extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('purchases')) {
            return;
        }

        if (! $this->db->fieldExists('transfer_fee', 'purchases')) {
            $this->forge->addColumn('purchases', [
                'transfer_fee' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'discount_total',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('purchases') && $this->db->fieldExists('transfer_fee', 'purchases')) {
            $this->forge->dropColumn('purchases', 'transfer_fee');
        }
    }
}
