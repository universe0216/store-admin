<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransfersTables extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('transfers')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'transfer_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'transfer_date' => [
                'type' => 'DATETIME',
            ],
            'from_warehouse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'to_warehouse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'total_qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addUniqueKey('transfer_no');
        $this->forge->addKey('transfer_date');
        $this->forge->addKey('from_warehouse_id');
        $this->forge->addKey('to_warehouse_id');
        $this->forge->addForeignKey('from_warehouse_id', 'warehouses', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('to_warehouse_id', 'warehouses', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('transfers');

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'transfer_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'product_variant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addKey('transfer_id');
        $this->forge->addKey('product_variant_id');
        $this->forge->addForeignKey('transfer_id', 'transfers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_variant_id', 'product_variants', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('transfer_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('transfer_items', true);
        $this->forge->dropTable('transfers', true);
    }
}
