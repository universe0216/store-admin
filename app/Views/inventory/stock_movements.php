<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Stock Movements<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Stock Movements</h1>
                <p class="text-muted mb-0 small">Purchase, sale, and transfer inventory changes.</p>
            </div>
            <a href="<?= site_url('inventory') ?>" class="btn btn-outline-secondary btn-sm">Back to Inventory</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary mb-1 small">Search</label>
                        <input type="text" id="movementSearchInput" class="form-control" placeholder="Product name, serial, style">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Movement Type</label>
                        <select id="movementTypeFilter" class="form-select">
                            <option value="">All types</option>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                            <option value="transfer_in">Transfer In</option>
                            <option value="transfer_out">Transfer Out</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Reference</label>
                        <select id="referenceTypeFilter" class="form-select">
                            <option value="">All references</option>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">From</label>
                        <div id="movementDateFrom"></div>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">To</label>
                        <div id="movementDateTo"></div>
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-2">
                        <button type="button" id="applyMovementFiltersBtn" class="btn btn-primary btn-sm w-100">Search</button>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mb-2">
                    <button type="button" id="clearMovementFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                </div>
                <div id="movementListError" class="text-danger small mb-2"></div>
                <div id="stockMovementsGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        stockMovements: "<?= site_url('api/stock-movements') ?>"
    };
    const MOVEMENT_PAGE_SIZE = 20;
    let movementListPage = 0;
    let movementListPageSize = MOVEMENT_PAGE_SIZE;
    let movementListLoading = false;
    let suppressMovementPageEvent = false;

    const movementGridSource = {
        localdata: [],
        datatype: "array",
        totalrecords: 0,
        datafields: [
            { name: "id", type: "number" },
            { name: "movement_date", type: "string" },
            { name: "product_name", type: "string" },
            { name: "product_number", type: "string" },
            { name: "size_value", type: "string" },
            { name: "style", type: "string" },
            { name: "movement_label", type: "string" },
            { name: "qty_change", type: "number" },
            { name: "reference_label", type: "string" },
            { name: "reference_no", type: "string" },
            { name: "notes", type: "string" }
        ]
    };
    let movementGridAdapter = null;

    function setListError(msg) {
        $("#movementListError").text(msg || "");
    }

    function getDateFilterValue(selector) {
        const date = $(selector).jqxDateTimeInput("getDate");
        if (!date) {
            return "";
        }
        return $(selector).jqxDateTimeInput("getText");
    }

    function getFilterParams(page, perPage) {
        const params = {
            page: (page ?? movementListPage) + 1,
            per_page: perPage ?? movementListPageSize
        };
        const search = String($("#movementSearchInput").val() || "").trim();
        const movementType = String($("#movementTypeFilter").val() || "").trim();
        const referenceType = String($("#referenceTypeFilter").val() || "").trim();
        const dateFrom = getDateFilterValue("#movementDateFrom");
        const dateTo = getDateFilterValue("#movementDateTo");

        if (search !== "") {
            params.search = search;
        }
        if (movementType !== "") {
            params.movement_type = movementType;
        }
        if (referenceType !== "") {
            params.reference_type = referenceType;
        }
        if (dateFrom) {
            params.date_from = dateFrom;
        }
        if (dateTo) {
            params.date_to = dateTo;
        }
        return params;
    }

    function clearFilters() {
        $("#movementSearchInput").val("");
        $("#movementTypeFilter").val("");
        $("#referenceTypeFilter").val("");
        $("#movementDateFrom").jqxDateTimeInput("val", null);
        $("#movementDateTo").jqxDateTimeInput("val", null);
        loadStockMovements(0, movementListPageSize, true);
    }

    function updateMovementsGridSource(rows, total, syncPage) {
        suppressMovementPageEvent = true;
        movementGridSource.localdata = rows;
        movementGridSource.totalrecords = total;
        movementGridAdapter.dataBind();
        $("#stockMovementsGrid").jqxGrid("updatebounddata");

        if (syncPage) {
            const paging = $("#stockMovementsGrid").jqxGrid("getpaginginformation");
            if (!paging || paging.pagenum !== movementListPage) {
                $("#stockMovementsGrid").jqxGrid("gotopage", movementListPage);
            }
        }

        window.setTimeout(function () {
            suppressMovementPageEvent = false;
        }, 0);
    }

    function loadStockMovements(page, perPage, resetPage) {
        setListError("");

        if (resetPage) {
            movementListPage = 0;
            if ($("#stockMovementsGrid").data("jqxGrid")) {
                suppressMovementPageEvent = true;
                $("#stockMovementsGrid").jqxGrid("gotopage", 0);
                window.setTimeout(function () {
                    suppressMovementPageEvent = false;
                }, 0);
            }
        } else if (page !== undefined && page !== null) {
            movementListPage = page;
        }
        if (perPage !== undefined && perPage !== null) {
            movementListPageSize = perPage;
        }

        movementListLoading = true;

        return $.getJSON(API_URLS.stockMovements, getFilterParams(movementListPage, movementListPageSize))
            .done(function (res) {
                const rows = res.data || [];
                const pagination = res.pagination || {};
                const total = Number(pagination.total || 0);

                movementListPage = Math.max(0, Number(pagination.page || 1) - 1);
                movementListPageSize = Number(pagination.per_page || movementListPageSize);
                updateMovementsGridSource(rows, total, true);
            })
            .fail(function (xhr) {
                setListError(xhr.responseJSON?.message || "Failed to load stock movements.");
                updateMovementsGridSource([], 0, true);
            })
            .always(function () {
                movementListLoading = false;
            });
    }

    function initWidgets() {
        const firstDayOfMonth = new Date();
        firstDayOfMonth.setDate(1);
        firstDayOfMonth.setHours(0, 0, 0, 0);

        $("#movementDateFrom").jqxDateTimeInput({
            width: "100%",
            height: 34,
            formatString: "yyyy-MM-dd",
            allowNullDate: true,
            value: firstDayOfMonth
        });
        $("#movementDateTo").jqxDateTimeInput({
            width: "100%",
            height: 34,
            formatString: "yyyy-MM-dd",
            allowNullDate: true
        });

        movementGridAdapter = new $.jqx.dataAdapter(movementGridSource);

        $("#stockMovementsGrid").jqxGrid({
            width: "100%",
            height: 560,
            columnsresize: true,
            pageable: true,
            pagesize: MOVEMENT_PAGE_SIZE,
            pagesizeoptions: ["10", "20", "50", "100"],
            virtualmode: true,
            rendergridrows: function () {
                return movementGridSource.localdata;
            },
            source: movementGridAdapter,
            columns: [
                { text: "ID", datafield: "id", width: 60 },
                { text: "Date", datafield: "movement_date", width: 130 },
                { text: "Product", datafield: "product_name", width: 180 },
                { text: "Serial No.", datafield: "product_number", width: 120 },
                { text: "Size", datafield: "size_value", width: 70 },
                { text: "Style", datafield: "style", width: 90 },
                { text: "Type", datafield: "movement_label", width: 100 },
                {
                    text: "Qty Change",
                    datafield: "qty_change",
                    width: 90,
                    cellsalign: "right",
                    cellsrenderer: function (row, column, value, defaultHtml) {
                        const qty = Number(value || 0);
                        const el = $(defaultHtml);
                        el.text(qty > 0 ? "+" + qty : String(qty));
                        if (qty > 0) {
                            el.css("color", "#027a48");
                        } else if (qty < 0) {
                            el.css("color", "#b42318");
                        }
                        return el[0].outerHTML;
                    }
                },
                { text: "Reference", datafield: "reference_label", width: 90 },
                { text: "Reference No.", datafield: "reference_no", width: 150 },
                { text: "Notes", datafield: "notes", width: 180 }
            ]
        });
    }

    $(function () {
        initWidgets();
        loadStockMovements(0, MOVEMENT_PAGE_SIZE, true);

        $("#applyMovementFiltersBtn").on("click", function () {
            loadStockMovements(0, movementListPageSize, true);
        });
        $("#clearMovementFiltersBtn").on("click", clearFilters);
        $("#movementSearchInput").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                loadStockMovements(0, movementListPageSize, true);
            }
        });
        $("#movementTypeFilter, #referenceTypeFilter").on("change", function () {
            loadStockMovements(0, movementListPageSize, true);
        });

        $("#stockMovementsGrid").on("pagechanged", function (event) {
            if (suppressMovementPageEvent || movementListLoading) {
                return;
            }
            loadStockMovements(event.args.pagenum, event.args.pagesize, false);
        });

        $("#stockMovementsGrid").on("pagesizechanged", function (event) {
            if (suppressMovementPageEvent || movementListLoading) {
                return;
            }
            loadStockMovements(0, event.args.pagesize, true);
        });
    });
</script>
<?= $this->endSection() ?>
