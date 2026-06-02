<?php

namespace App\Controllers;

class ExchangeRates extends BaseController
{
    public function index(): string
    {
        return view('exchange_rates/index');
    }
}
