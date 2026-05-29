<?php

namespace App\Models;

class TransferModel extends BaseModel
{
    protected $table            = 'transfers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'transfer_no',
        'transfer_date',
        'from_warehouse_id',
        'to_warehouse_id',
        'total_qty',
        'notes',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
