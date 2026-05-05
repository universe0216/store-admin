<?php

namespace App\Controllers;

class Suppliers extends BaseController
{
    public function index(): string
    {
        return view('suppliers/index');
    }
}
