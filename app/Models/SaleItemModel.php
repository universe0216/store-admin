<?php

namespace App\Models;

class SaleItemModel extends BaseModel
{
    protected $table            = 'sale_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sale_id',
        'product_variant_id',
        'qty',
        'unit_price',
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
