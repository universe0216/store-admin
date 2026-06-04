<?php

namespace App\Controllers;

class Purchases extends BaseController
{
    public function index(): string
    {
        return view('purchases/index');
    }

    public function create(): string
    {
        return view('purchases/create');
    }

    public function products(): string
    {
        return view('purchases/products');
    }
}
