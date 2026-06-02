<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentMethodsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('payment_methods')) {
            $this->seedDefaults();

            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('payment_methods');

        $this->seedDefaults();
    }

    public function down(): void
    {
        if ($this->db->tableExists('payment_methods')) {
            $this->forge->dropTable('payment_methods', true);
        }
    }

    private function seedDefaults(): void
    {
        $defaults = [
            ['code' => 'cash', 'name' => 'Cash', 'description' => 'Cash payment'],
            ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'description' => 'Bank transfer payment'],
            ['code' => 'card', 'name' => 'Card', 'description' => 'Card payment'],
            ['code' => 'check', 'name' => 'Check', 'description' => 'Check payment'],
            ['code' => 'other', 'name' => 'Other', 'description' => 'Other payment method'],
        ];

        $table = $this->db->table('payment_methods');
        $now   = date('Y-m-d H:i:s');

        foreach ($defaults as $row) {
            if ($table->where('code', $row['code'])->countAllResults() > 0) {
                continue;
            }

            $table->insert([
                'code'        => $row['code'],
                'name'        => $row['name'],
                'description' => $row['description'],
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}
