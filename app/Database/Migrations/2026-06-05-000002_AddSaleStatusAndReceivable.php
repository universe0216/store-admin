<?php

namespace App\Database\Migrations;

use App\Enums\SaleStatus;
use CodeIgniter\Database\Migration;

class AddSaleStatusAndReceivable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('sales')) {
            if (! $this->db->fieldExists('status', 'sales')) {
                $this->forge->addColumn('sales', [
                    'status' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 20,
                        'default'    => SaleStatus::Completed->value,
                        'after'      => 'payment_method',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('paid_total', 'sales')) {
                $this->forge->addColumn('sales', [
                    'paid_total' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '12,2',
                        'default'    => 0.00,
                        'after'      => 'grand_total',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('unpaid_total', 'sales')) {
                $this->forge->addColumn('sales', [
                    'unpaid_total' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '12,2',
                        'default'    => 0.00,
                        'after'      => 'paid_total',
                    ],
                ]);
            }

            $this->backfillSaleBalances();
        }

        if ($this->db->tableExists('accounts')) {
            $exists = $this->db->table('accounts')->where('code', '1100')->countAllResults() > 0;
            if (! $exists) {
                $row = [
                    'code'         => '1100',
                    'name'         => 'Accounts Receivable (Unpaid Sales)',
                    'account_type' => 'ASSET',
                    'is_active'    => 1,
                ];
                if ($this->db->fieldExists('currency_code', 'accounts')) {
                    $row['currency_code'] = 'USD';
                }
                $this->db->table('accounts')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('sales')) {
            if ($this->db->fieldExists('unpaid_total', 'sales')) {
                $this->forge->dropColumn('sales', 'unpaid_total');
            }
            if ($this->db->fieldExists('paid_total', 'sales')) {
                $this->forge->dropColumn('sales', 'paid_total');
            }
            if ($this->db->fieldExists('status', 'sales')) {
                $this->forge->dropColumn('sales', 'status');
            }
        }

        if ($this->db->tableExists('accounts')) {
            $this->db->table('accounts')->where('code', '1100')->delete();
        }
    }

    private function backfillSaleBalances(): void
    {
        $rows = $this->db->table('sales')->get()->getResultArray();

        foreach ($rows as $sale) {
            $amountDue = round(max(0, (float) ($sale['sub_total'] ?? 0) - (float) ($sale['discount_total'] ?? 0)), 2);
            $paidTotal = round((float) ($sale['paid_total'] ?? $sale['grand_total'] ?? 0), 2);

            if ($paidTotal <= 0 && $amountDue > 0) {
                $paidTotal = round((float) ($sale['grand_total'] ?? 0), 2);
            }

            $unpaidTotal = round(max(0, $amountDue - $paidTotal), 2);
            $status      = $unpaidTotal <= 0.01 ? SaleStatus::Completed->value : SaleStatus::Incomplete->value;

            $this->db->table('sales')->where('id', (int) ($sale['id'] ?? 0))->update([
                'grand_total'  => $amountDue,
                'paid_total'   => $paidTotal,
                'unpaid_total' => $unpaidTotal,
                'status'       => $status,
            ]);
        }
    }
}
