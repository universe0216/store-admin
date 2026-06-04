<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPurchaseShippingAndItemAllocations extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('purchases') && ! $this->db->fieldExists('shipping_fee', 'purchases')) {
            $after = $this->db->fieldExists('transfer_fee', 'purchases') ? 'transfer_fee' : 'discount_total';
            $this->forge->addColumn('purchases', [
                'shipping_fee' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => $after,
                ],
            ]);
        }

        if (! $this->db->tableExists('purchase_items')) {
            return;
        }

        $columns = [];

        if (! $this->db->fieldExists('unit_price', 'purchase_items')) {
            $columns['unit_price'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,4',
                'default'    => 0.0000,
                'after'      => 'qty',
            ];
        }

        if (! $this->db->fieldExists('allocated_discount', 'purchase_items')) {
            $columns['allocated_discount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,4',
                'default'    => 0.0000,
                'after'      => $this->db->fieldExists('unit_price', 'purchase_items') ? 'unit_price' : 'unit_cost',
            ];
        }

        if (! $this->db->fieldExists('allocated_shipping', 'purchase_items')) {
            $columns['allocated_shipping'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,4',
                'default'    => 0.0000,
                'after'      => 'allocated_discount',
            ];
        }

        if (! $this->db->fieldExists('allocated_transfer_fee', 'purchase_items')) {
            $columns['allocated_transfer_fee'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,4',
                'default'    => 0.0000,
                'after'      => 'allocated_shipping',
            ];
        }

        if ($columns !== []) {
            $this->forge->addColumn('purchase_items', $columns);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('purchases') && $this->db->fieldExists('shipping_fee', 'purchases')) {
            $this->forge->dropColumn('purchases', 'shipping_fee');
        }

        if (! $this->db->tableExists('purchase_items')) {
            return;
        }

        $cols = [];
        foreach (['allocated_transfer_fee', 'allocated_shipping', 'allocated_discount', 'unit_price'] as $col) {
            if ($this->db->fieldExists($col, 'purchase_items')) {
                $cols[] = $col;
            }
        }

        if ($cols !== []) {
            $this->forge->dropColumn('purchase_items', $cols);
        }
    }
}
