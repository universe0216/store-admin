<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase List<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Purchase List</h1>
                <p class="text-muted mb-0">Monitor purchase history.</p>
            </div>
            <a href="<?= site_url('purchases/create') ?>" class="btn btn-primary">New Purchase</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold mb-3">Purchase List</h2>
                <div id="purchasesGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            purchases: "<?= site_url('api/purchases') ?>"
        };

        function initWidgets() {
            $("#purchasesGrid").jqxGrid({
                width: "100%",
                height: 300,
                columnsresize: true,
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    { text: "ID", datafield: "id", width: 70 },
                    { text: "Purchase No", datafield: "purchase_no", width: 180 },
                    { text: "Date", datafield: "purchase_date", width: 180 },
                    { text: "Supplier", datafield: "supplier_name", width: 220 },
                    { text: "Status", datafield: "status", width: 100 },
                    { text: "Grand Total", datafield: "grand_total", cellsformat: "f2" }
                ]
            });
        }

        function loadPurchases() {
            return $.getJSON(API_URLS.purchases).done(function(res) {
                const source = { localdata: res.data || [], datatype: "array" };
                $("#purchasesGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load purchases.";
                console.error(msg);
            });
        }

        $(function() {
            initWidgets();
            loadPurchases();
        });
    </script>
<?= $this->endSection() ?>
