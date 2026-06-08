<?php

namespace App\Controllers;

use App\Enums\Department;

class Sizes extends BaseController
{
    public function index(): string
    {
        return view('sizes/index', [
            'departments' => Department::cases(),
        ]);
    }
}
