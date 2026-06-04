<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?: 'Purchase Management' ?></title>

    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/jqwidgets/styles/jqx.base.css') ?>">

    <style>
        .jqx-widget,
        .jqx-widget * {
            box-sizing: border-box;
        }
        .app-toast-container {
            z-index: 2000;
        }
    </style>

    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-light text-dark">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid px-5">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Store App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/') ?>">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Primary
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('suppliers') ?>">Suppliers</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('categories') ?>">Categories</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('warehouses') ?>">Warehouses</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('currencies') ?>">Currencies</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('exchange-rates') ?>">Exchange Rates</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('accounts') ?>">Accounts</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('payment-methods') ?>">Payment Methods</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Purchase
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('purchases') ?>">Purchase</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('purchases/create') ?>">New Purchase</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('purchases/products') ?>">Products</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Sells
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('sells') ?>">Sells</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('sells/create') ?>">New Sell</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('inventory/sales-statistics') ?>">Sales Monthly Statistics</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Stock
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('inventory') ?>">Inventory</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('inventory/stock-movements') ?>">Stock Movements</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('transfers') ?>">Transfer</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('transfers/create') ?>">New Transfer</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Finance
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('finance') ?>">Transactions</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('finance/balances') ?>">Balances</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?= $this->renderSection('content') ?>

    <div class="toast-container app-toast-container position-fixed bottom-0 end-0 p-3">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted small py-3">
        <div class="container">
            Page rendered in {elapsed_time} seconds. Memory: {memory_usage} MB.
        </div>
    </footer>

    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxcore.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxdata.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxbuttons.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxscrollbar.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxlistbox.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxpanel.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxdropdownbutton.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxtree.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxdropdownlist.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxmenu.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.pager.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.edit.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.filter.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxdatatable.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxtreegrid.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.selection.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxgrid.columnsresize.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxdatetimeinput.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxcalendar.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxnumberinput.js') ?>"></script>
    <script src="<?= base_url('assets/jqwidgets/jqxinput.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/utils.js') ?>"></script>

    <?= $this->renderSection('pageScripts') ?>
</body>
</html>
