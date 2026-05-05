<?php

namespace App\Models;

class ProductVariantModel extends BaseModel
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'stock_qty',
        'is_active',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
