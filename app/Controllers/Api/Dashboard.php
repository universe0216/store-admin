<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends BaseController
{
    public function index(): ResponseInterface
    {
        $metrics = (new DashboardModel())->getMetrics();

        return $this->response->setJSON(['data' => $metrics]);
    }
}
