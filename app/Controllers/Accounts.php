<?php

namespace App\Controllers;

class Accounts extends BaseController
{
    public function index(): string
    {
        return view('accounts/index');
    }
}
