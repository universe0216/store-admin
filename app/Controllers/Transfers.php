<?php

namespace App\Controllers;

class Transfers extends BaseController
{
    public function index(): string
    {
        return view('transfers/index');
    }

    public function create(): string
    {
        return view('transfers/create');
    }
}
