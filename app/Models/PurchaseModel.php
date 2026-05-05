<?php

namespace App\Models;

class PurchaseModel extends BaseModel
{
    protected $table            = 'purchases';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'purchase_no',
        'purchase_date',
        'supplier_id',
        'status',
        'sub_total',
        'discount_total',
        'grand_total',
        'paid_total',
        'notes',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
