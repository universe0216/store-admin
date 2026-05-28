<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWarehousesTable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('warehouses')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'location' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'is_deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
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
            $this->forge->addKey('name');
            $this->forge->addKey('is_deleted');
            $this->forge->createTable('warehouses');

            return;
        }

        if (! $this->db->fieldExists('is_deleted', 'warehouses')) {
            $this->forge->addColumn('warehouses', [
                'is_deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'location',
                ],
            ]);
        }

        if (! $this->db->fieldExists('created_at', 'warehouses')) {
            $this->forge->addColumn('warehouses', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'is_deleted',
                ],
            ]);
        }

        if (! $this->db->fieldExists('updated_at', 'warehouses')) {
            $this->forge->addColumn('warehouses', [
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'created_at',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('warehouses')) {
            return;
        }

        if ($this->db->fieldExists('is_deleted', 'warehouses')) {
            $this->forge->dropColumn('warehouses', 'is_deleted');
        }

        if ($this->db->fieldExists('created_at', 'warehouses')) {
            $this->forge->dropColumn('warehouses', 'created_at');
        }

        if ($this->db->fieldExists('updated_at', 'warehouses')) {
            $this->forge->dropColumn('warehouses', 'updated_at');
        }
    }
}
