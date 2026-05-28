<?php

namespace App\Controllers;

class Stock extends BaseController
{
    public function index(): string
    {
        return view('stock/index');
    }
}
