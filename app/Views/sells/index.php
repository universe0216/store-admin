<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sales History<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Sales History</h1>
            </div>
            <a href="<?= site_url('sells/create') ?>" class="btn btn-primary">New Sale</a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-12 col-md-3">
                                <input type="text" id="saleProductSearch" class="form-control" placeholder="Search by product name">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center gap-2">
                                <div id="saleDateFrom"></div>
                                <span class="text-secondary">~</span>
                                <div id="saleDateTo"></div>
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2 justify-content-end">
                                <button type="button" id="applySaleFiltersBtn" class="btn btn-primary btn-sm">Search</button>
                                <button type="button" id="clearSaleFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                        <div id="saleFilterMessage" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Sales</span>
                                    <div id="metricTotalSales" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Sale Items</span>
                                    <div id="metricTotalSaleItems" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Amount</span>
                                    <div id="metricTotalAmount" class="fw-semibold">0.00</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Cost</span>
                                    <div id="metricTotalCost" class="fw-semibold">0.00</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Profit</span>
                                    <div id="metricTotalProfit" class="fw-semibold">0.00</div>
                                </div>
                            </div>
                            <div id="saleFilterError" class="text-danger mt-2"></div>
                        </div>
                        <div id="salesGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" id="deleteSaleBtn" class="btn btn-sm btn-outline-danger">Delete</button>
                        </div>
                        <div id="saleInfoPanel" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Warehouse</span>
                                    <div id="infoWarehouse" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Date</span>
                                    <div id="infoSaleDate" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Customer</span>
                                    <div id="infoCustomer" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Amount</span>
                                    <div id="infoTotalAmount" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Cost</span>
                                    <div id="infoTotalCost" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Profit</span>
                                    <div id="infoProfit" class="fw-semibold">—</div>
                                </div>
                            </div>
                        </div>
                        <div id="saleItemsGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            sales: "<?= site_url('api/sales') ?>"
        };
        const SALE_PAGE_SIZE = 20;
        let saleListPage = 0;
        let saleListPageSize = SALE_PAGE_SIZE;
        let saleListTotal = 0;
        let saleListLoading = false;
        let suppressSalePageEvent = false;

        const salesGridSource = {
            localdata: [],
            datatype: "array",
            totalrecords: 0,
            datafields: [
                { name: "id", type: "number" },
                { name: "sale_no", type: "string" },
                { name: "sale_date", type: "string" },
                { name: "customer_name", type: "string" },
                { name: "warehouse_name", type: "string" },
                { name: "grand_total", type: "number" },
                { name: "sub_total", type: "number" }
            ]
        };
        let salesGridAdapter = null;

        function formatMoney(value) {
            return Number(value || 0).toFixed(2);
        }

        function renderSaleSummary(summary) {
            const s = summary || {};
            $("#metricTotalSales").text(Number(s.total_sales || 0));
            $("#metricTotalSaleItems").text(Number(s.total_sale_items || 0));
            $("#metricTotalAmount").text(formatMoney(s.total_amount));
            $("#metricTotalCost").text(formatMoney(s.total_cost));
            $("#metricTotalProfit").text(formatMoney(s.total_profit));
            $("#saleFilterError").text("");
        }

        function setFilterError(msg) {
            $("#saleFilterError").text(msg || "");
        }

        function clearSaleInfo() {
            $("#infoWarehouse").text("—");
            $("#infoSaleDate").text("—");
            $("#infoCustomer").text("—");
            $("#infoTotalAmount").text("—");
            $("#infoTotalCost").text("—");
            $("#infoProfit").text("—");
        }

        function setSaleInfo(sale, metrics) {
            if (!sale) {
                clearSaleInfo();
                return;
            }

            const m = metrics || {};
            $("#infoWarehouse").text(sale.warehouse_name || "—");
            $("#infoSaleDate").text(sale.sale_date || "—");
            $("#infoCustomer").text(sale.customer_name || "—");
            $("#infoTotalAmount").text(formatMoney(m.total_amount ?? sale.grand_total));
            $("#infoTotalCost").text(formatMoney(m.total_cost));
            $("#infoProfit").text(formatMoney(m.total_profit));
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
                page: (page ?? saleListPage) + 1,
                per_page: perPage ?? saleListPageSize
            };
            const productName = String($("#saleProductSearch").val() || "").trim();
            const dateFrom = getDateFilterValue("#saleDateFrom");
            const dateTo = getDateFilterValue("#saleDateTo");

            if (productName !== "") {
                params.product_name = productName;
            }
            if (dateFrom) {
                params.date_from = dateFrom;
            }
            if (dateTo) {
                params.date_to = dateTo;
            }

            return params;
        }

        function initWidgets() {
            $("#saleDateFrom").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });
            $("#saleDateTo").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });

            salesGridAdapter = new $.jqx.dataAdapter(salesGridSource);

            $("#salesGrid").jqxGrid({
                width: "100%",
                height: 380,
                columnsresize: true,
                selectionmode: "singlerow",
                pageable: true,
                pagesize: SALE_PAGE_SIZE,
                pagesizeoptions: ["10", "20", "50", "100"],
                virtualmode: true,
                rendergridrows: function () {
                    return salesGridSource.localdata;
                },
                source: salesGridAdapter,
                columns: [
                    { text: "ID", datafield: "id", width: 50 },
                    { text: "Sale No", datafield: "sale_no", width: 180 },
                    { text: "Date", datafield: "sale_date", width: 100 },
                    { text: "Customer", datafield: "customer_name", width: 140 },
                    { text: "Warehouse", datafield: "warehouse_name", width: 120 },
                    { text: "Grand Total", datafield: "grand_total", cellsformat: "f2", cellsalign: "right" }
                ]
            });

            $("#saleItemsGrid").jqxGrid({
                width: "100%",
                height: 320,
                columnsresize: true,
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    { text: "Product", datafield: "product_name", width: 160 },
                    { text: "Product Number", datafield: "product_number", width: 120 },
                    { text: "Brand", datafield: "brand", width: 100 },
                    { text: "SKU", datafield: "sku", width: 100 },
                    { text: "Size", datafield: "size_value", width: 80 },
                    { text: "Qty", datafield: "qty", width: 70, cellsalign: "right" },
                    { text: "Unit Price", datafield: "unit_price", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Unit Cost", datafield: "unit_cost", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Line Total", datafield: "line_total", cellsformat: "f2", cellsalign: "right" }
                ]
            });
        }

        function updateSalesGridSource(rows, total, syncPage) {
            saleListTotal = total;
            suppressSalePageEvent = true;

            salesGridSource.localdata = rows;
            salesGridSource.totalrecords = total;
            salesGridAdapter.dataBind();
            $("#salesGrid").jqxGrid("updatebounddata");

            if (syncPage) {
                const paging = $("#salesGrid").jqxGrid("getpaginginformation");
                if (!paging || paging.pagenum !== saleListPage) {
                    $("#salesGrid").jqxGrid("gotopage", saleListPage);
                }
            }

            window.setTimeout(function () {
                suppressSalePageEvent = false;
            }, 0);
        }

        function loadSales(page, perPage, resetPage) {
            setFilterError("");

            if (resetPage) {
                saleListPage = 0;
                if ($("#salesGrid").data("jqxGrid")) {
                    suppressSalePageEvent = true;
                    $("#salesGrid").jqxGrid("gotopage", 0);
                    window.setTimeout(function () {
                        suppressSalePageEvent = false;
                    }, 0);
                }
            } else if (page !== undefined && page !== null) {
                saleListPage = page;
            }
            if (perPage !== undefined && perPage !== null) {
                saleListPageSize = perPage;
            }

            const requestPage = saleListPage;
            const requestSize = saleListPageSize;
            saleListLoading = true;

            return $.getJSON(API_URLS.sales, getFilterParams(requestPage, requestSize))
                .done(function (res) {
                    const rows = res.data || [];
                    const pagination = res.pagination || {};
                    const total = Number(pagination.total || 0);

                    saleListPage = Math.max(0, Number(pagination.page || 1) - 1);
                    saleListPageSize = Number(pagination.per_page || requestSize);
                    updateSalesGridSource(rows, total, true);
                    renderSaleSummary(res.summary);

                    if (rows.length > 0) {
                        $("#salesGrid").jqxGrid("selectrow", 0);
                        loadSaleItems(Number(rows[0].id), rows[0]);
                    } else {
                        clearSaleInfo();
                        $("#saleItemsGrid").jqxGrid({
                            source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                        });
                    }
                })
                .fail(function (xhr) {
                    const msg = xhr.responseJSON?.message || "Failed to load sales.";
                    setFilterError(msg);
                    renderSaleSummary(null);
                })
                .always(function () {
                    saleListLoading = false;
                });
        }

        function loadSaleItems(saleId, saleRow) {
            if (!saleId) {
                clearSaleInfo();
                $("#saleItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
                return;
            }

            if (saleRow) {
                setSaleInfo(saleRow, {
                    total_amount: saleRow.grand_total,
                    total_cost: saleRow.total_cost,
                    total_profit: saleRow.total_profit
                });
            }

            $.getJSON(`${API_URLS.sales}/${saleId}`).done(function (res) {
                const sale = res.data?.sale;
                const metrics = res.data?.metrics;
                if (sale) {
                    setSaleInfo(sale, metrics);
                }

                const items = (res.data?.items || []).map(item => ({
                    product_name: item.product_name || "",
                    product_number: item.product_number || "",
                    brand: item.brand || "",
                    sku: item.sku || "",
                    size_value: item.size_value || "",
                    qty: Number(item.qty || 0),
                    unit_price: Number(item.unit_price || 0),
                    unit_cost: Number(item.unit_cost || 0),
                    line_total: Number(item.line_total || 0)
                }));
                $("#saleItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" })
                });
            }).fail(function () {
                clearSaleInfo();
                $("#saleItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
            });
        }

        function clearFilters() {
            $("#saleProductSearch").val("");
            $("#saleDateFrom").jqxDateTimeInput("val", null);
            $("#saleDateTo").jqxDateTimeInput("val", null);
            loadSales(0, saleListPageSize, true);
        }

        function deleteSelectedSale() {
            const rowIndex = $("#salesGrid").jqxGrid("getselectedrowindex");
            if (rowIndex < 0) {
                setFilterError("Select a sale to delete.");
                return;
            }

            const row = $("#salesGrid").jqxGrid("getrowdata", rowIndex);
            if (!row?.id) {
                return;
            }

            const label = row.sale_no || ("#" + row.id);
            if (!confirm(`Delete sale ${label}? This will reverse inventory and stock movements.`)) {
                return;
            }

            setFilterError("");
            $.ajax({
                url: `${API_URLS.sales}/${row.id}`,
                method: "DELETE"
            }).done(function () {
                setFilterError("");
                loadSales(saleListPage, saleListPageSize, false);
            }).fail(function (xhr) {
                setFilterError(xhr.responseJSON?.message || "Failed to delete sale.");
            });
        }

        $(function () {
            initWidgets();
            clearSaleInfo();
            renderSaleSummary(null);
            loadSales(0, SALE_PAGE_SIZE, true);

            $("#applySaleFiltersBtn").on("click", function () {
                loadSales(0, saleListPageSize, true);
            });
            $("#clearSaleFiltersBtn").on("click", clearFilters);
            $("#saleProductSearch").on("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadSales(0, saleListPageSize, true);
                }
            });

            $("#salesGrid").on("pagechanged", function (event) {
                if (suppressSalePageEvent) {
                    return;
                }
                loadSales(event.args.pagenum, event.args.pagesize, false);
            });

            $("#salesGrid").on("pagesizechanged", function (event) {
                if (suppressSalePageEvent) {
                    return;
                }
                loadSales(0, event.args.pagesize, true);
            });

            $("#salesGrid").on("rowselect", function (event) {
                const row = event.args?.row;
                loadSaleItems(Number(row?.id || 0), row);
            });
            $("#deleteSaleBtn").on("click", deleteSelectedSale);
        });
    </script>
<?= $this->endSection() ?>
