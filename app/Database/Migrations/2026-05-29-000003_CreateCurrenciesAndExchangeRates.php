<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCurrenciesAndExchangeRates extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('currencies')) {
            $this->forge->addField([
                'code' => [
                    'type'       => 'CHAR',
                    'constraint' => 3,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'symbol' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                ],
                'decimals' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 2,
                ],
            ]);
            $this->forge->addKey('code', true);
            $this->forge->createTable('currencies');
        }

        if (! $this->db->tableExists('exchange_rates')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'base_currency' => [
                    'type'       => 'CHAR',
                    'constraint' => 3,
                ],
                'quote_currency' => [
                    'type'       => 'CHAR',
                    'constraint' => 3,
                ],
                'rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '20,8',
                ],
                'effective_at' => [
                    'type' => 'DATETIME',
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['base_currency', 'quote_currency', 'effective_at']);
            $this->forge->addForeignKey('base_currency', 'currencies', 'code', 'RESTRICT', 'CASCADE');
            $this->forge->addForeignKey('quote_currency', 'currencies', 'code', 'RESTRICT', 'CASCADE');
            $this->forge->createTable('exchange_rates');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('exchange_rates')) {
            $this->forge->dropTable('exchange_rates', true);
        }

        if ($this->db->tableExists('currencies')) {
            $this->forge->dropTable('currencies', true);
        }
    }
}
