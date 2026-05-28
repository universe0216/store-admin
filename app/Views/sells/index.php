<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sales History<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Sales History</h1>
                <p class="text-muted mb-0">Monitor sales transactions.</p>
            </div>
            <a href="<?= site_url('sells/create') ?>" class="btn btn-primary">New Sale</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div id="salesGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        sales: "<?= site_url('api/sales') ?>"
    };

    function initWidgets() {
        $("#salesGrid").jqxGrid({
            width: "100%",
            height: 520,
            columnsresize: true,
            source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
            columns: [
                { text: "ID", datafield: "id", width: 80 },
                { text: "Sale No", datafield: "sale_no", width: 180 },
                { text: "Date", datafield: "sale_date", width: 180 },
                { text: "Customer", datafield: "customer_name", width: 180 },
                { text: "Sub Total", datafield: "sub_total", width: 120, cellsformat: "f2", cellsalign: "right" },
                { text: "Grand Total", datafield: "grand_total", cellsformat: "f2", cellsalign: "right" }
            ]
        });
    }

    function loadSales() {
        $.getJSON(API_URLS.sales).done(function (res) {
            $("#salesGrid").jqxGrid({
                source: new $.jqx.dataAdapter({ localdata: res.data || [], datatype: "array" })
            });
        }).fail(function (xhr) {
            console.error(xhr.responseJSON?.message || "Failed to load sales.");
        });
    }

    $(function() {
        initWidgets();
        loadSales();
    });
</script>
<?= $this->endSection() ?>
