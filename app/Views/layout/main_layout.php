<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?: 'Purchase Management' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/styles/jqx.base.css">

    <style>
        .jqx-widget,
        .jqx-widget * {
            box-sizing: border-box;
        }
    </style>

    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-light text-dark">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Store App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('suppliers') ?>">Suppliers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('categories') ?>">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('purchases') ?>">Purchase</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Sells</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Stock</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?= $this->renderSection('content') ?>

    <footer class="text-center text-muted small py-3">
        <div class="container">
            Page rendered in {elapsed_time} seconds. Memory: {memory_usage} MB.
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxcore.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxdata.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxbuttons.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxscrollbar.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxlistbox.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxpanel.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxdropdownbutton.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxtree.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxdropdownlist.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxmenu.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxgrid.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxgrid.edit.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxdatatable.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxtreegrid.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxgrid.selection.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxgrid.columnsresize.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxdatetimeinput.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxcalendar.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxnumberinput.js"></script>
    <script src="https://unpkg.com/jqwidgets-scripts@16.0.0/jqwidgets/jqxinput.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?= $this->renderSection('pageScripts') ?>
</body>
</html>
