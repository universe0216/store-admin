<?php

namespace App\Controllers;

class Sells extends BaseController
{
    public function index(): string
    {
        return view('sells/index');
    }

    public function create(): string
    {
        return view('sells/create');
    }
}
