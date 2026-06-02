<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyFieldsToTransactions extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }

        if (! $this->db->fieldExists('original_amount', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'original_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'null'       => true,
                    'after'      => 'credit',
                ],
            ]);
        }

        if (! $this->db->fieldExists('currency', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'currency' => [
                    'type'       => 'CHAR',
                    'constraint' => 3,
                    'null'       => true,
                    'after'      => 'original_amount',
                ],
            ]);
        }

        if (! $this->db->fieldExists('exchange_rate', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'exchange_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '18,8',
                    'null'       => true,
                    'after'      => 'currency',
                ],
            ]);
        }

        if ($this->db->tableExists('currencies') && $this->db->fieldExists('currency', 'transactions')) {
            $this->forge->addForeignKey('currency', 'currencies', 'code', 'RESTRICT', 'CASCADE');
            $this->forge->processIndexes('transactions');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }

        if ($this->db->tableExists('currencies') && $this->db->fieldExists('currency', 'transactions')) {
            $this->forge->dropForeignKey('transactions', 'transactions_currency_foreign');
        }

        $columns = [];
        if ($this->db->fieldExists('exchange_rate', 'transactions')) {
            $columns[] = 'exchange_rate';
        }
        if ($this->db->fieldExists('currency', 'transactions')) {
            $columns[] = 'currency';
        }
        if ($this->db->fieldExists('original_amount', 'transactions')) {
            $columns[] = 'original_amount';
        }

        if ($columns !== []) {
            $this->forge->dropColumn('transactions', $columns);
        }
    }
}
