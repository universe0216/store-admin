<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sales History<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Sales History</h1>
                <p class="text-muted mb-0">Monitor sales transactions.</p>
            </div>
            <a href="<?= site_url('sells/create') ?>" class="btn btn-primary">New Sale</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label class="form-label text-secondary mb-1" for="saleSearchInput">Product</label>
                        <input type="text" id="saleSearchInput" class="form-control" placeholder="Product name or product number">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Since</label>
                        <div id="saleDateFrom"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Until</label>
                        <div id="saleDateTo"></div>
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button type="button" id="applySaleFiltersBtn" class="btn btn-primary flex-grow-1">Search</button>
                        <button type="button" id="clearSaleFiltersBtn" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </div>
                <div id="saleFilterMessage" class="small fw-semibold mt-2"></div>
           
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

    function setFilterMessage(msg, isError = false) {
        const box = $("#saleFilterMessage");
        box.text(msg || "");
        box.removeClass("text-success text-danger");
        if (msg) {
            box.addClass(isError ? "text-danger" : "text-success");
        }
    }

    function initWidgets() {
        $("#saleDateFrom").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });
        $("#saleDateTo").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });

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

    function getDateFilterValue(selector) {
        const date = $(selector).jqxDateTimeInput("getDate");
        if (!date) {
            return "";
        }
        return $(selector).jqxDateTimeInput("getText");
    }

    function getFilterParams() {
        const params = {};
        const q = String($("#saleSearchInput").val() || "").trim();
        const dateFrom = getDateFilterValue("#saleDateFrom");
        const dateTo = getDateFilterValue("#saleDateTo");

        if (q !== "") {
            params.q = q;
        }
        if (dateFrom) {
            params.date_from = dateFrom;
        }
        if (dateTo) {
            params.date_to = dateTo;
        }

        return params;
    }

    function loadSales() {
        setFilterMessage("");

        return $.getJSON(API_URLS.sales, getFilterParams()).done(function (res) {
            const rows = res.data || [];
            $("#salesGrid").jqxGrid({
                source: new $.jqx.dataAdapter({ localdata: rows, datatype: "array" })
            });
            setFilterMessage(rows.length ? `${rows.length} sale(s) found.` : "No sales match your filters.");
        }).fail(function (xhr) {
            const msg = xhr.responseJSON?.message || "Failed to load sales.";
            setFilterMessage(msg, true);
        });
    }

    function clearFilters() {
        $("#saleSearchInput").val("");
        $("#saleDateFrom").jqxDateTimeInput("val", null);
        $("#saleDateTo").jqxDateTimeInput("val", null);
        loadSales();
    }

    $(function() {
        initWidgets();
        loadSales();

        $("#applySaleFiltersBtn").on("click", loadSales);
        $("#clearSaleFiltersBtn").on("click", clearFilters);
        $("#saleSearchInput").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                loadSales();
            }
        });
    });
</script>
<?= $this->endSection() ?>
