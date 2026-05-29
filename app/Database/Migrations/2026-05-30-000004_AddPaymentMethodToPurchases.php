<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentMethodToPurchases extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('purchases')) {
            return;
        }

        if (! $this->db->fieldExists('payment_method', 'purchases')) {
            $this->forge->addColumn('purchases', [
                'payment_method' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'cash',
                    'after'      => 'paid_total',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('purchases') && $this->db->fieldExists('payment_method', 'purchases')) {
            $this->forge->dropColumn('purchases', 'payment_method');
        }
    }
}
