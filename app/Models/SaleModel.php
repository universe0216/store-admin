<?php

namespace App\Models;

class SaleModel extends BaseModel
{
    protected $table            = 'sales';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sale_no',
        'sale_date',
        'customer_name',
        'warehouse_id',
        'sub_total',
        'discount_total',
        'grand_total',
        'payment_method',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
