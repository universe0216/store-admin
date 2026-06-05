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
                            <div class="col-12 col-md-3">
                                <select id="saleStatusFilter" class="form-select">
                                    <option value="">All statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="incomplete">Incomplete (unpaid)</option>
                                </select>
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
                        <div class="d-flex justify-content-end gap-2 mb-4">
                            <button type="button" id="addSalePaymentBtn" class="btn btn-sm btn-primary" style="display: none;">Add Payment</button>
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
                        <div id="salePaymentPanel" class="border rounded bg-light p-3 mb-3 small">
                            <h3 class="h6 fw-semibold mb-2">Payment Details</h3>
                            <div class="row g-2 mb-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Subtotal</span>
                                    <div id="infoSubTotal" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Discount</span>
                                    <div id="infoDiscount" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Grand Total</span>
                                    <div id="infoGrandTotal" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Paid</span>
                                    <div id="infoPaidTotal" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Unpaid</span>
                                    <div id="infoUnpaidTotal" class="fw-semibold text-danger">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Status</span>
                                    <div id="infoSaleStatus" class="fw-semibold">—</div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Payment Method</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="salePaymentsBody">
                                        <tr>
                                            <td colspan="3" class="text-secondary">Select a sale to view payments.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="saleItemsGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSalePaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 small">Sale: <span id="addPaymentSaleNo" class="fw-semibold">—</span></div>
                    <div class="mb-2 small">Unpaid balance: <span id="addPaymentUnpaid" class="fw-semibold text-danger">0.00</span></div>
                    <div class="mb-3">
                        <label class="form-label text-secondary mb-1">Payment Date</label>
                        <input type="date" id="addPaymentDate" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary mb-1">Payment Method</label>
                        <select id="addPaymentMethod" class="form-select"></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-secondary mb-1">Amount</label>
                        <input type="number" id="addPaymentAmount" class="form-control text-end" min="0.01" step="0.01">
                    </div>
                    <div id="addPaymentError" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAddSalePaymentBtn">Record Payment</button>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            sales: "<?= site_url('api/sales') ?>",
            paymentMethods: "<?= site_url('api/payment-methods') ?>"
        };
        const SALE_PAGE_SIZE = 20;
        let saleListPage = 0;
        let saleListPageSize = SALE_PAGE_SIZE;
        let saleListTotal = 0;
        let saleListLoading = false;
        let suppressSalePageEvent = false;
        let paymentMethods = [];
        let selectedSaleId = 0;
        let selectedSaleUnpaid = 0;
        let addSalePaymentModal = null;

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
                { name: "sub_total", type: "number" },
                { name: "paid_total", type: "number" },
                { name: "unpaid_total", type: "number" },
                { name: "status", type: "string" }
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

        function formatSaleStatus(status) {
            const value = String(status || "").toLowerCase();
            if (value === "completed") {
                return "Completed";
            }
            if (value === "incomplete") {
                return "Incomplete";
            }
            return "—";
        }

        function clearSaleInfo() {
            selectedSaleId = 0;
            selectedSaleUnpaid = 0;
            $("#addSalePaymentBtn").hide();
            $("#infoWarehouse").text("—");
            $("#infoSaleDate").text("—");
            $("#infoCustomer").text("—");
            $("#infoTotalAmount").text("—");
            $("#infoTotalCost").text("—");
            $("#infoProfit").text("—");
            $("#infoSubTotal").text("—");
            $("#infoDiscount").text("—");
            $("#infoGrandTotal").text("—");
            $("#infoPaidTotal").text("—");
            $("#infoUnpaidTotal").text("—");
            $("#infoSaleStatus").text("—");
            renderSalePayments([]);
        }

        function formatPaymentDate(value) {
            const text = String(value || "").trim();
            if (text === "") {
                return "—";
            }
            return text.length >= 10 ? text.slice(0, 10) : text;
        }

        function formatPaymentMethodLabel(code, name) {
            const label = String(name || "").trim();
            if (label !== "") {
                return label;
            }
            return String(code || "")
                .replace(/_/g, " ")
                .replace(/\b\w/g, ch => ch.toUpperCase()) || "—";
        }

        function renderSalePayments(payments) {
            const rows = payments || [];
            const $body = $("#salePaymentsBody").empty();

            if (rows.length === 0) {
                $body.append(`
                    <tr>
                        <td colspan="3" class="text-secondary">No payment details.</td>
                    </tr>
                `);
                return;
            }

            rows.forEach(function (row) {
                $body.append(`
                    <tr>
                        <td>${formatPaymentDate(row.payment_date)}</td>
                        <td>${formatPaymentMethodLabel(row.payment_method, row.payment_method_name)}</td>
                        <td class="text-end fw-semibold">${formatMoney(row.amount)}</td>
                    </tr>
                `);
            });
        }

        function setSalePaymentSummary(sale) {
            if (!sale) {
                $("#infoSubTotal").text("—");
                $("#infoDiscount").text("—");
                $("#infoGrandTotal").text("—");
                $("#infoPaidTotal").text("—");
                $("#infoUnpaidTotal").text("—");
                $("#infoSaleStatus").text("—");
                return;
            }

            const unpaid = Number(sale.unpaid_total ?? Math.max(0, Number(sale.grand_total || 0) - Number(sale.paid_total || 0)));

            $("#infoSubTotal").text(formatMoney(sale.sub_total));
            $("#infoDiscount").text(formatMoney(sale.discount_total));
            $("#infoGrandTotal").text(formatMoney(sale.grand_total));
            $("#infoPaidTotal").text(formatMoney(sale.paid_total));
            $("#infoUnpaidTotal").text(formatMoney(unpaid));
            $("#infoSaleStatus").text(formatSaleStatus(sale.status));

            selectedSaleId = Number(sale.id || 0);
            selectedSaleUnpaid = unpaid;
            if (String(sale.status || "").toLowerCase() === "incomplete" && unpaid > 0.009) {
                $("#addSalePaymentBtn").show();
            } else {
                $("#addSalePaymentBtn").hide();
            }
        }

        function setSaleInfo(sale, metrics, payments) {
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
            setSalePaymentSummary(sale);
            if (payments !== undefined) {
                renderSalePayments(payments);
            }
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
            const status = String($("#saleStatusFilter").val() || "").trim();
            if (status !== "") {
                params.status = status;
            }

            return params;
        }

        function loadPaymentMethods() {
            return $.getJSON(API_URLS.paymentMethods).done(function (res) {
                paymentMethods = (res.data || []).filter(row => Number(row.is_active ?? 1) === 1);
                const $select = $("#addPaymentMethod").empty();
                paymentMethods.forEach(function (row) {
                    const code = String(row.code || "");
                    const name = String(row.name || code);
                    $select.append(`<option value="${code}">${name}</option>`);
                });
                if ($select.children().length === 0) {
                    $select.append('<option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option>');
                }
            });
        }

        function openAddPaymentModal() {
            if (!selectedSaleId || selectedSaleUnpaid <= 0) {
                setFilterError("Select an incomplete sale with unpaid balance.");
                return;
            }

            const rowIndex = $("#salesGrid").jqxGrid("getselectedrowindex");
            const row = rowIndex >= 0 ? $("#salesGrid").jqxGrid("getrowdata", rowIndex) : null;

            $("#addPaymentSaleNo").text(row?.sale_no || ("#" + selectedSaleId));
            $("#addPaymentUnpaid").text(formatMoney(selectedSaleUnpaid));
            $("#addPaymentAmount").attr("max", selectedSaleUnpaid).val(selectedSaleUnpaid.toFixed(2));
            $("#addPaymentError").text("");

            const saleDate = String(row?.sale_date || "").slice(0, 10);
            $("#addPaymentDate").val(saleDate || new Date().toISOString().slice(0, 10));

            if (addSalePaymentModal) {
                addSalePaymentModal.show();
            }
        }

        function submitAddSalePayment() {
            const amount = Number($("#addPaymentAmount").val() || 0);
            const method = String($("#addPaymentMethod").val() || "cash");
            const paymentDate = String($("#addPaymentDate").val() || "");

            if (!selectedSaleId) {
                $("#addPaymentError").text("No sale selected.");
                return;
            }
            if (amount <= 0) {
                $("#addPaymentError").text("Enter a payment amount greater than 0.");
                return;
            }
            if (amount > selectedSaleUnpaid + 0.01) {
                $("#addPaymentError").text("Amount cannot exceed the unpaid balance.");
                return;
            }

            $("#addPaymentError").text("");
            $("#confirmAddSalePaymentBtn").prop("disabled", true);

            $.ajax({
                url: `${API_URLS.sales}/${selectedSaleId}/payments`,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    payment_method: method,
                    amount: amount,
                    payment_date: paymentDate
                })
            }).done(function () {
                if (addSalePaymentModal) {
                    addSalePaymentModal.hide();
                }
                setFilterError("");
                const saleId = selectedSaleId;
                loadSales(saleListPage, saleListPageSize, false);
                if (saleId) {
                    loadSaleItems(saleId);
                }
            }).fail(function (xhr) {
                $("#addPaymentError").text(xhr.responseJSON?.message || "Failed to record payment.");
            }).always(function () {
                $("#confirmAddSalePaymentBtn").prop("disabled", false);
            });
        }

        function initWidgets() {
            const firstDayOfMonth = new Date();
            firstDayOfMonth.setDate(1);
            firstDayOfMonth.setHours(0, 0, 0, 0);

            $("#saleDateFrom").jqxDateTimeInput({ width: 120, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true, value: firstDayOfMonth });
            $("#saleDateTo").jqxDateTimeInput({ width: 120, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });

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
                    { text: "Sale No", datafield: "sale_no", width: 160 },
                    { text: "Date", datafield: "sale_date", width: 100 },
                    { text: "Status", datafield: "status", width: 95, cellsrenderer: function (row, column, value) {
                        const label = formatSaleStatus(value);
                        const cls = String(value || "").toLowerCase() === "incomplete" ? "text-danger fw-semibold" : "text-success";
                        return `<div class="d-flex align-items-center h-100 px-1 ${cls}">${label}</div>`;
                    }},
                    { text: "Warehouse", datafield: "warehouse_name", width: 110 },
                    { text: "Grand Total", datafield: "grand_total", width: 95, cellsformat: "f2", cellsalign: "right" },
                    { text: "Paid", datafield: "paid_total", width: 80, cellsformat: "f2", cellsalign: "right" },
                    { text: "Unpaid", datafield: "unpaid_total", width: 80, cellsformat: "f2", cellsalign: "right" },
                    { text: "Customer", datafield: "customer_name", width: 110 }
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
                    { text: "Unit Cost", datafield: "unit_cost", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Qty", datafield: "qty", width: 70, cellsalign: "right" },
                    { text: "Unit Price", datafield: "unit_price", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Discount", datafield: "discount_amount", width: 100, cellsformat: "f2", cellsalign: "right" },
                    { text: "Line Total", datafield: "line_total", width: 100, cellsformat: "f2", cellsalign: "right" }
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
                }, []);
            }

            $.getJSON(`${API_URLS.sales}/${saleId}`).done(function (res) {
                const sale = res.data?.sale;
                const metrics = res.data?.metrics;
                const payments = res.data?.payments || [];
                if (sale) {
                    setSaleInfo(sale, metrics, payments);
                } else {
                    renderSalePayments(payments);
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
                    discount_amount: Number(item.discount_amount || 0),
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
            $("#saleStatusFilter").val("");
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
            loadPaymentMethods();
            addSalePaymentModal = new bootstrap.Modal(document.getElementById("addSalePaymentModal"));
            clearSaleInfo();
            renderSaleSummary(null);
            loadSales(0, SALE_PAGE_SIZE, true);

            $("#applySaleFiltersBtn").on("click", function () {
                loadSales(0, saleListPageSize, true);
            });
            $("#clearSaleFiltersBtn").on("click", clearFilters);
            $("#saleStatusFilter").on("change", function () {
                loadSales(0, saleListPageSize, true);
            });
            $("#addSalePaymentBtn").on("click", openAddPaymentModal);
            $("#confirmAddSalePaymentBtn").on("click", submitAddSalePayment);
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
