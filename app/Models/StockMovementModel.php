<?php

namespace App\Models;

class StockMovementModel extends BaseModel
{
    protected $table            = 'stock_movements';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_variant_id',
        'movement_type',
        'qty_change',
        'reference_type',
        'reference_id',
        'notes',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
