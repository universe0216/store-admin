<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase List<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Purchase List</h1>
            </div>
            <a href="<?= site_url('purchases/create') ?>" class="btn btn-primary">New Purchase</a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <!-- <h2 class="h5 fw-semibold mb-3">Purchase List</h2> -->
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-12 col-md-3">
                                <input type="text" id="purchaseProductSearch" class="form-control" placeholder="Search by product name">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center gap-2">
                                <div id="purchaseDateFrom"></div> 
                                <span class="text-secondary">~</span>
                                <div id="purchaseDateTo"></div>
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2 justify-content-end">
                                <button type="button" id="applyPurchaseFiltersBtn" class="btn btn-primary btn-sm">Search</button>
                                <button type="button" id="clearPurchaseFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                        <div id="purchaseFilterMessage" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Purchases</span>
                                    <div id="metricTotalPurchases" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Products</span>
                                    <div id="metricTotalProducts" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Variants</span>
                                    <div id="metricTotalVariants" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Paid</span>
                                    <div id="metricTotalPaid" class="fw-semibold">0.00</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Transfer Fee</span>
                                    <div id="metricTotalTransferFee" class="fw-semibold">0.00</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Grand Total</span>
                                    <div id="metricTotalGrandTotal" class="fw-semibold">0.00</div>
                                </div>
                            </div>
                            <div id="purchaseFilterError" class="text-danger mt-2"></div>
                        </div>
                       
                        <div id="purchasesGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4"> 
                        <!-- <h2 class="h5 fw-semibold mb-3">Purchase Items</h2> -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" id="deletePurchaseBtn" class="btn btn-sm btn-outline-danger">Delete</button>
                        </div>
                        <div id="purchaseInfoPanel" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Date</span>
                                    <div id="infoPurchaseDate" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Supplier</span>
                                    <div id="infoSupplier" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Discount</span>
                                    <div id="infoDiscount" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Transfer Fee</span>
                                    <div id="infoTransferFee" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Sub Total</span>
                                    <div id="infoSubTotal" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Grand Total</span>
                                    <div id="infoGrandTotal" class="fw-semibold">—</div>
                                </div>
                            </div>
                        </div>
                        <div id="purchaseItemsGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            purchases: "<?= site_url('api/purchases') ?>"
        };
        const PURCHASE_PAGE_SIZE = 20;
        let purchaseListPage = 0;
        let purchaseListPageSize = PURCHASE_PAGE_SIZE;
        let purchaseListTotal = 0;
        let purchaseListLoading = false;
        let suppressPurchasePageEvent = false;

        const purchaseGridSource = {
            localdata: [],
            datatype: "array",
            totalrecords: 0,
            datafields: [
                { name: "id", type: "number" },
                { name: "purchase_no", type: "string" },
                { name: "purchase_date", type: "string" },
                { name: "supplier_name", type: "string" },
                { name: "status", type: "string" },
                { name: "grand_total", type: "number" },
                { name: "discount_total", type: "number" },
                { name: "transfer_fee", type: "number" },
                { name: "sub_total", type: "number" }
            ]
        };
        let purchaseGridAdapter = null;

        function renderPurchaseSummary(summary) {
            const s = summary || {};
            $("#metricTotalPurchases").text(Number(s.total_purchases || 0));
            $("#metricTotalProducts").text(Number(s.total_products || 0));
            $("#metricTotalVariants").text(Number(s.total_product_variants || 0));
            $("#metricTotalPaid").text(formatMoney(s.total_paid_total));
            $("#metricTotalTransferFee").text(formatMoney(s.total_transfer_fee));
            $("#metricTotalGrandTotal").text(formatMoney(s.total_grand_total));
            $("#purchaseFilterError").text("");
        }

        function setFilterError(msg) {
            $("#purchaseFilterError").text(msg || "");
        }

        function formatMoney(value) {
            return Number(value || 0).toFixed(2);
        }

        function clearPurchaseInfo() {
            $("#infoPurchaseDate").text("—");
            $("#infoSupplier").text("—");
            $("#infoDiscount").text("—");
            $("#infoTransferFee").text("—");
            $("#infoSubTotal").text("—");
            $("#infoGrandTotal").text("—");
        }

        function setPurchaseInfo(purchase) {
            if (!purchase) {
                clearPurchaseInfo();
                return;
            }

            $("#infoPurchaseDate").text(purchase.purchase_date || "—");
            $("#infoSupplier").text(purchase.supplier_name || "—");
            $("#infoDiscount").text(formatMoney(purchase.discount_total));
            $("#infoTransferFee").text(formatMoney(purchase.transfer_fee));
            $("#infoSubTotal").text(formatMoney(purchase.sub_total));
            $("#infoGrandTotal").text(formatMoney(purchase.grand_total));
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
                page: (page ?? purchaseListPage) + 1,
                per_page: perPage ?? purchaseListPageSize
            };
            const productName = String($("#purchaseProductSearch").val() || "").trim();
            const dateFrom = getDateFilterValue("#purchaseDateFrom");
            const dateTo = getDateFilterValue("#purchaseDateTo");

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
            const firstDayOfMonth = new Date();
            firstDayOfMonth.setDate(1);
            firstDayOfMonth.setHours(0, 0, 0, 0);

            $("#purchaseDateFrom").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true, value: firstDayOfMonth });
            $("#purchaseDateTo").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });

            purchaseGridAdapter = new $.jqx.dataAdapter(purchaseGridSource);

            $("#purchasesGrid").jqxGrid({
                width: "100%",
                height: 380,
                columnsresize: true,
                selectionmode: "singlerow",
                pageable: true,
                pagesize: PURCHASE_PAGE_SIZE,
                pagesizeoptions: ["10", "20", "50", "100"],
                virtualmode: true,
                rendergridrows: function () {
                    return purchaseGridSource.localdata;
                },
                source: purchaseGridAdapter,
                columns: [
                    { text: "ID", datafield: "id", width: 50 },
                    { text: "Purchase No", datafield: "purchase_no", width: 180 },
                    { text: "Date", datafield: "purchase_date", width: 100 },
                    { text: "Supplier", datafield: "supplier_name", width: 150 },
                    { text: "Status", datafield: "status", width: 100 },
                    { text: "Grand Total", datafield: "grand_total", cellsformat: "f2" }
                ]
            });

            $("#purchaseItemsGrid").jqxGrid({
                width: "100%",
                height: 320,
                columnsresize: true,
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    { text: "Product", datafield: "product_name", width: 180 },
                    { text: "Product Number", datafield: "product_number", width: 140 },
                    { text: "Brand", datafield: "brand", width: 120 },
                    { text: "Style", datafield: "style", width: 110 },
                    { text: "Unit Cost", datafield: "unit_cost", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Size", datafield: "size_value", width: 90 },
                    { text: "Sets Count", datafield: "sets_count", width: 90, cellsalign: "right" },
                    { text: "Units Count", datafield: "units_count", width: 90, cellsalign: "right" },
                    { text: "Total Cost", datafield: "total_cost", cellsformat: "f2", cellsalign: "right" }
                ]
            });
        }

        function updatePurchasesGridSource(rows, total, syncPage) {
            purchaseListTotal = total;
            suppressPurchasePageEvent = true;

            purchaseGridSource.localdata = rows;
            purchaseGridSource.totalrecords = total;
            purchaseGridAdapter.dataBind();
            $("#purchasesGrid").jqxGrid("updatebounddata");

            if (syncPage) {
                const paging = $("#purchasesGrid").jqxGrid("getpaginginformation");
                if (!paging || paging.pagenum !== purchaseListPage) {
                    $("#purchasesGrid").jqxGrid("gotopage", purchaseListPage);
                }
            }

            window.setTimeout(function () {
                suppressPurchasePageEvent = false;
            }, 0);
        }

        function loadPurchases(page, perPage, resetPage) {
            setFilterError("");

            if (resetPage) {
                purchaseListPage = 0;
                if ($("#purchasesGrid").data("jqxGrid")) {
                    suppressPurchasePageEvent = true;
                    $("#purchasesGrid").jqxGrid("gotopage", 0);
                    window.setTimeout(function () {
                        suppressPurchasePageEvent = false;
                    }, 0);
                }
            } else if (page !== undefined && page !== null) {
                purchaseListPage = page;
            }
            if (perPage !== undefined && perPage !== null) {
                purchaseListPageSize = perPage;
            }

            const requestPage = purchaseListPage;
            const requestSize = purchaseListPageSize;
            purchaseListLoading = true;

            return $.getJSON(API_URLS.purchases, getFilterParams(requestPage, requestSize))
                .done(function(res) {
                    const rows = res.data || [];
                    const pagination = res.pagination || {};
                    const total = Number(pagination.total || 0);

                    purchaseListPage = Math.max(0, Number(pagination.page || 1) - 1);
                    purchaseListPageSize = Number(pagination.per_page || requestSize);
                    updatePurchasesGridSource(rows, total, true);
                    renderPurchaseSummary(res.summary);

                    if (rows.length > 0) {
                        $("#purchasesGrid").jqxGrid("selectrow", 0);
                        loadPurchaseItems(Number(rows[0].id), rows[0]);
                    } else {
                        clearPurchaseInfo();
                        $("#purchaseItemsGrid").jqxGrid({
                            source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                        });
                    }
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || "Failed to load purchases.";
                    setFilterError(msg);
                    renderPurchaseSummary(null);
                    console.error(msg);
                })
                .always(function () {
                    purchaseListLoading = false;
                });
        }

        function loadPurchaseItems(purchaseId, purchaseRow) {
            if (!purchaseId) {
                clearPurchaseInfo();
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
                return;
            }

            if (purchaseRow) {
                setPurchaseInfo(purchaseRow);
            }

            $.getJSON(`${API_URLS.purchases}/${purchaseId}`).done(function(res) {
                const purchase = res.data?.purchase;
                if (purchase) {
                    setPurchaseInfo(purchase);
                }

                const items = (res.data?.items || []).map(item => ({
                    product_id: Number(item.product_id || 0),
                    product_name: item.product_name || "",
                    product_number: item.product_number || "",
                    brand: item.brand || "",
                    style: item.style || "",
                    unit_cost: Number(item.unit_cost || 0),
                    size_value: item.size_value || "",
                    sets_count: Number(item.sets_count || 0),
                    units_count: Number(item.units_count || 0),
                    total_cost: Number(item.total_cost ?? item.total_price ?? 0)
                }));
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" })
                });
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load purchase items.";
                console.error(msg);
                clearPurchaseInfo();
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
            });
        }

        function clearFilters() {
            $("#purchaseProductSearch").val("");
            $("#purchaseDateFrom").jqxDateTimeInput("val", null);
            $("#purchaseDateTo").jqxDateTimeInput("val", null);
            loadPurchases(0, purchaseListPageSize, true);
        }

        function deleteSelectedPurchase() {
            const rowIndex = $("#purchasesGrid").jqxGrid("getselectedrowindex");
            if (rowIndex < 0) {
                setFilterError("Select a purchase to delete.");
                return;
            }

            const row = $("#purchasesGrid").jqxGrid("getrowdata", rowIndex);
            if (!row?.id) {
                return;
            }

            const label = row.purchase_no || ("#" + row.id);
            if (!confirm(`Delete purchase ${label}? This will reverse inventory and stock movements.`)) {
                return;
            }

            setFilterError("");
            $.ajax({
                url: `${API_URLS.purchases}/${row.id}`,
                method: "DELETE"
            }).done(function (res) {
                setFilterError("");
                loadPurchases(purchaseListPage, purchaseListPageSize, false);
            }).fail(function (xhr) {
                setFilterError(xhr.responseJSON?.message || "Failed to delete purchase.");
            });
        }

        $(function() {
            initWidgets();
            clearPurchaseInfo();
            renderPurchaseSummary(null);
            loadPurchases(0, PURCHASE_PAGE_SIZE, true);

            $("#applyPurchaseFiltersBtn").on("click", function () {
                loadPurchases(0, purchaseListPageSize, true);
            });
            $("#clearPurchaseFiltersBtn").on("click", clearFilters);
            $("#purchaseProductSearch").on("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadPurchases(0, purchaseListPageSize, true);
                }
            });

            $("#purchasesGrid").on("pagechanged", function (event) {
                if (suppressPurchasePageEvent) {
                    return;
                }
                loadPurchases(event.args.pagenum, event.args.pagesize, false);
            });

            $("#purchasesGrid").on("pagesizechanged", function (event) {
                if (suppressPurchasePageEvent) {
                    return;
                }
                loadPurchases(0, event.args.pagesize, true);
            });

            $("#purchasesGrid").on("rowselect", function (event) {
                const row = event.args?.row;
                loadPurchaseItems(Number(row?.id || 0), row);
            });
            $("#deletePurchaseBtn").on("click", deleteSelectedPurchase);
        });
    </script>
<?= $this->endSection() ?>
