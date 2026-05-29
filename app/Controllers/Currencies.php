<?php

namespace App\Controllers;

class Currencies extends BaseController
{
    public function index(): string
    {
        return view('currencies/index');
    }
}
