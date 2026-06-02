<?php

namespace App\Models;

class TransactionModel extends BaseModel
{
    protected $table         = 'transactions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'transaction_date',
        'account_code',
        'reference_no',
        'description',
        'debit',
        'credit',
        'original_amount',
        'currency',
        'exchange_rate',
        'created_at',
    ];
    protected $useTimestamps = false;
}
