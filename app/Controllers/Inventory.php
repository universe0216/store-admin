<?php

namespace App\Controllers;

class Inventory extends BaseController
{
    public function index(): string
    {
        return view('inventory/index');
    }
}
