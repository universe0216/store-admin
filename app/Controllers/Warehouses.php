<?php

namespace App\Controllers;

class Warehouses extends BaseController
{
    public function index(): string
    {
        return view('warehouses/index');
    }
}
