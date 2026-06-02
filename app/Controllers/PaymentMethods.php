<?php

namespace App\Controllers;

class PaymentMethods extends BaseController
{
    public function index(): string
    {
        return view('payment_methods/index');
    }
}
