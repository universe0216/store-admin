<?php

namespace App\Models;

class PurchaseItemModel extends BaseModel
{
    protected $table            = 'purchase_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'purchase_id',
        'product_variant_id',
        'qty',
        'unit_price',
        'allocated_discount',
        'allocated_shipping',
        'allocated_transfer_fee',
        'unit_cost',
        'reference_currency',
        'reference_cost',
        'exchange_rate',
        'discount_amount',
        'line_total',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
