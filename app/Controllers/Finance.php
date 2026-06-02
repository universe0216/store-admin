<?php

namespace App\Controllers;

class Finance extends BaseController
{
    public function index(): string
    {
        return view('finance/transactions');
    }

    public function balances(): string
    {
        return view('finance/balances');
    }
}
