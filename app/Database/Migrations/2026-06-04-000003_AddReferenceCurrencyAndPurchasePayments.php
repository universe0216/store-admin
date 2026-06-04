<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferenceCurrencyAndPurchasePayments extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('products')) {
            if (! $this->db->fieldExists('reference_currency', 'products')) {
                $this->forge->addColumn('products', [
                    'reference_currency' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'null'       => true,
                        'after'      => 'is_active',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('reference_cost', 'products')) {
                $this->forge->addColumn('products', [
                    'reference_cost' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '12,4',
                        'null'       => true,
                        'after'      => 'reference_currency',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('purchase_items')) {
            if (! $this->db->fieldExists('reference_currency', 'purchase_items')) {
                $this->forge->addColumn('purchase_items', [
                    'reference_currency' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'null'       => true,
                        'after'      => 'unit_cost',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('reference_cost', 'purchase_items')) {
                $this->forge->addColumn('purchase_items', [
                    'reference_cost' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '12,4',
                        'null'       => true,
                        'after'      => 'reference_currency',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('exchange_rate', 'purchase_items')) {
                $this->forge->addColumn('purchase_items', [
                    'exchange_rate' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '18,8',
                        'null'       => true,
                        'after'      => 'reference_cost',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('suppliers') && ! $this->db->fieldExists('default_currency', 'suppliers')) {
            $this->forge->addColumn('suppliers', [
                'default_currency' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'USD',
                    'after'      => 'address',
                ],
            ]);
        }

        if (! $this->db->tableExists('purchase_payments')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'purchase_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'payment_method' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
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
            $this->forge->addKey('purchase_id');
            $this->forge->addForeignKey('purchase_id', 'purchases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('purchase_payments');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('purchase_payments')) {
            $this->forge->dropTable('purchase_payments', true);
        }

        if ($this->db->tableExists('suppliers') && $this->db->fieldExists('default_currency', 'suppliers')) {
            $this->forge->dropColumn('suppliers', 'default_currency');
        }

        if ($this->db->tableExists('purchase_items')) {
            $cols = [];
            if ($this->db->fieldExists('exchange_rate', 'purchase_items')) {
                $cols[] = 'exchange_rate';
            }
            if ($this->db->fieldExists('reference_cost', 'purchase_items')) {
                $cols[] = 'reference_cost';
            }
            if ($this->db->fieldExists('reference_currency', 'purchase_items')) {
                $cols[] = 'reference_currency';
            }
            if ($cols !== []) {
                $this->forge->dropColumn('purchase_items', $cols);
            }
        }

        if ($this->db->tableExists('products')) {
            $cols = [];
            if ($this->db->fieldExists('reference_cost', 'products')) {
                $cols[] = 'reference_cost';
            }
            if ($this->db->fieldExists('reference_currency', 'products')) {
                $cols[] = 'reference_currency';
            }
            if ($cols !== []) {
                $this->forge->dropColumn('products', $cols);
            }
        }
    }
}
