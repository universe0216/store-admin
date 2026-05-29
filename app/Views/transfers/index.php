<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Transfer History<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Transfer History</h1>
            </div>
            <a href="<?= site_url('transfers/create') ?>" class="btn btn-primary">New Transfer</a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-12 col-md-3">
                                <input type="text" id="transferProductSearch" class="form-control" placeholder="Search by product name">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center gap-2">
                                <div id="transferDateFrom"></div>
                                <span class="text-secondary">~</span>
                                <div id="transferDateTo"></div>
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2 justify-content-end">
                                <button type="button" id="applyTransferFiltersBtn" class="btn btn-primary btn-sm">Search</button>
                                <button type="button" id="clearTransferFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                        <div id="transferFilterMessage" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Transfers</span>
                                    <div id="metricTotalTransfers" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Transfer Items</span>
                                    <div id="metricTotalTransferItems" class="fw-semibold">0</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Qty</span>
                                    <div id="metricTotalQty" class="fw-semibold">0</div>
                                </div>
                            </div>
                            <div id="transferFilterError" class="text-danger mt-2"></div>
                        </div>
                        <div id="transfersGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" id="deleteTransferBtn" class="btn btn-sm btn-outline-danger">Delete</button>
                        </div>
                        <div id="transferInfoPanel" class="border rounded bg-light p-3 mb-3 small">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">From Warehouse</span>
                                    <div id="infoFromWarehouse" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">To Warehouse</span>
                                    <div id="infoToWarehouse" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Date</span>
                                    <div id="infoTransferDate" class="fw-semibold">—</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="text-secondary">Total Qty</span>
                                    <div id="infoTotalQty" class="fw-semibold">—</div>
                                </div>
                                <div class="col-12 col-md-8">
                                    <span class="text-secondary">Notes</span>
                                    <div id="infoNotes" class="fw-semibold">—</div>
                                </div>
                            </div>
                        </div>
                        <div id="transferItemsGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            transfers: "<?= site_url('api/transfers') ?>"
        };
        const TRANSFER_PAGE_SIZE = 20;
        let transferListPage = 0;
        let transferListPageSize = TRANSFER_PAGE_SIZE;
        let transferListTotal = 0;
        let transferListLoading = false;
        let suppressTransferPageEvent = false;

        const transfersGridSource = {
            localdata: [],
            datatype: "array",
            totalrecords: 0,
            datafields: [
                { name: "id", type: "number" },
                { name: "transfer_no", type: "string" },
                { name: "transfer_date", type: "string" },
                { name: "from_warehouse_name", type: "string" },
                { name: "to_warehouse_name", type: "string" },
                { name: "total_qty", type: "number" },
                { name: "notes", type: "string" }
            ]
        };
        let transfersGridAdapter = null;

        function renderTransferSummary(summary) {
            const s = summary || {};
            $("#metricTotalTransfers").text(Number(s.total_transfers || 0));
            $("#metricTotalTransferItems").text(Number(s.total_transfer_items || 0));
            $("#metricTotalQty").text(Number(s.total_qty || 0));
            $("#transferFilterError").text("");
        }

        function setFilterError(msg) {
            $("#transferFilterError").text(msg || "");
        }

        function clearTransferInfo() {
            $("#infoFromWarehouse").text("—");
            $("#infoToWarehouse").text("—");
            $("#infoTransferDate").text("—");
            $("#infoTotalQty").text("—");
            $("#infoNotes").text("—");
        }

        function setTransferInfo(transfer) {
            if (!transfer) {
                clearTransferInfo();
                return;
            }

            $("#infoFromWarehouse").text(transfer.from_warehouse_name || "—");
            $("#infoToWarehouse").text(transfer.to_warehouse_name || "—");
            $("#infoTransferDate").text(transfer.transfer_date || "—");
            $("#infoTotalQty").text(Number(transfer.total_qty || 0));
            $("#infoNotes").text(transfer.notes || "—");
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
                page: (page ?? transferListPage) + 1,
                per_page: perPage ?? transferListPageSize
            };
            const productName = String($("#transferProductSearch").val() || "").trim();
            const dateFrom = getDateFilterValue("#transferDateFrom");
            const dateTo = getDateFilterValue("#transferDateTo");

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
            $("#transferDateFrom").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });
            $("#transferDateTo").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });

            transfersGridAdapter = new $.jqx.dataAdapter(transfersGridSource);

            $("#transfersGrid").jqxGrid({
                width: "100%",
                height: 380,
                columnsresize: true,
                selectionmode: "singlerow",
                pageable: true,
                pagesize: TRANSFER_PAGE_SIZE,
                pagesizeoptions: ["10", "20", "50", "100"],
                virtualmode: true,
                rendergridrows: function () {
                    return transfersGridSource.localdata;
                },
                source: transfersGridAdapter,
                columns: [
                    { text: "ID", datafield: "id", width: 50 },
                    { text: "Transfer No", datafield: "transfer_no", width: 180 },
                    { text: "Date", datafield: "transfer_date", width: 100 },
                    { text: "From", datafield: "from_warehouse_name", width: 120 },
                    { text: "To", datafield: "to_warehouse_name", width: 120 },
                    { text: "Total Qty", datafield: "total_qty", width: 90, cellsalign: "right" }
                ]
            });

            $("#transferItemsGrid").jqxGrid({
                width: "100%",
                height: 320,
                columnsresize: true,
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    { text: "Product", datafield: "product_name", width: 180 },
                    { text: "Product Number", datafield: "product_number", width: 120 },
                    { text: "Brand", datafield: "brand", width: 100 },
                    { text: "SKU", datafield: "sku", width: 100 },
                    { text: "Size", datafield: "size_value", width: 80 },
                    { text: "Qty", datafield: "qty", width: 70, cellsalign: "right" }
                ]
            });
        }

        function updateTransfersGridSource(rows, total, syncPage) {
            transferListTotal = total;
            suppressTransferPageEvent = true;

            transfersGridSource.localdata = rows;
            transfersGridSource.totalrecords = total;
            transfersGridAdapter.dataBind();
            $("#transfersGrid").jqxGrid("updatebounddata");

            if (syncPage) {
                const paging = $("#transfersGrid").jqxGrid("getpaginginformation");
                if (!paging || paging.pagenum !== transferListPage) {
                    $("#transfersGrid").jqxGrid("gotopage", transferListPage);
                }
            }

            window.setTimeout(function () {
                suppressTransferPageEvent = false;
            }, 0);
        }

        function loadTransfers(page, perPage, resetPage) {
            setFilterError("");

            if (resetPage) {
                transferListPage = 0;
                if ($("#transfersGrid").data("jqxGrid")) {
                    suppressTransferPageEvent = true;
                    $("#transfersGrid").jqxGrid("gotopage", 0);
                    window.setTimeout(function () {
                        suppressTransferPageEvent = false;
                    }, 0);
                }
            } else if (page !== undefined && page !== null) {
                transferListPage = page;
            }
            if (perPage !== undefined && perPage !== null) {
                transferListPageSize = perPage;
            }

            const requestPage = transferListPage;
            const requestSize = transferListPageSize;
            transferListLoading = true;

            return $.getJSON(API_URLS.transfers, getFilterParams(requestPage, requestSize))
                .done(function (res) {
                    const rows = res.data || [];
                    const pagination = res.pagination || {};
                    const total = Number(pagination.total || 0);

                    transferListPage = Math.max(0, Number(pagination.page || 1) - 1);
                    transferListPageSize = Number(pagination.per_page || requestSize);
                    updateTransfersGridSource(rows, total, true);
                    renderTransferSummary(res.summary);

                    if (rows.length > 0) {
                        $("#transfersGrid").jqxGrid("selectrow", 0);
                        loadTransferItems(Number(rows[0].id), rows[0]);
                    } else {
                        clearTransferInfo();
                        $("#transferItemsGrid").jqxGrid({
                            source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                        });
                    }
                })
                .fail(function (xhr) {
                    const msg = xhr.responseJSON?.message || "Failed to load transfers.";
                    setFilterError(msg);
                    renderTransferSummary(null);
                })
                .always(function () {
                    transferListLoading = false;
                });
        }

        function loadTransferItems(transferId, transferRow) {
            if (!transferId) {
                clearTransferInfo();
                $("#transferItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
                return;
            }

            if (transferRow) {
                setTransferInfo(transferRow);
            }

            $.getJSON(`${API_URLS.transfers}/${transferId}`).done(function (res) {
                const transfer = res.data?.transfer;
                if (transfer) {
                    setTransferInfo(transfer);
                }

                const items = (res.data?.items || []).map(item => ({
                    product_name: item.product_name || "",
                    product_number: item.product_number || "",
                    brand: item.brand || "",
                    sku: item.sku || "",
                    size_value: item.size_value || "",
                    qty: Number(item.qty || 0)
                }));
                $("#transferItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" })
                });
            }).fail(function () {
                clearTransferInfo();
                $("#transferItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
            });
        }

        function clearFilters() {
            $("#transferProductSearch").val("");
            $("#transferDateFrom").jqxDateTimeInput("val", null);
            $("#transferDateTo").jqxDateTimeInput("val", null);
            loadTransfers(0, transferListPageSize, true);
        }

        function deleteSelectedTransfer() {
            const rowIndex = $("#transfersGrid").jqxGrid("getselectedrowindex");
            if (rowIndex < 0) {
                setFilterError("Select a transfer to delete.");
                return;
            }

            const row = $("#transfersGrid").jqxGrid("getrowdata", rowIndex);
            if (!row?.id) {
                return;
            }

            const label = row.transfer_no || ("#" + row.id);
            if (!confirm(`Delete transfer ${label}? This will reverse inventory movements.`)) {
                return;
            }

            setFilterError("");
            $.ajax({
                url: `${API_URLS.transfers}/${row.id}`,
                method: "DELETE"
            }).done(function () {
                setFilterError("");
                loadTransfers(transferListPage, transferListPageSize, false);
            }).fail(function (xhr) {
                setFilterError(xhr.responseJSON?.message || "Failed to delete transfer.");
            });
        }

        $(function () {
            initWidgets();
            clearTransferInfo();
            renderTransferSummary(null);
            loadTransfers(0, TRANSFER_PAGE_SIZE, true);

            $("#applyTransferFiltersBtn").on("click", function () {
                loadTransfers(0, transferListPageSize, true);
            });
            $("#clearTransferFiltersBtn").on("click", clearFilters);
            $("#transferProductSearch").on("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadTransfers(0, transferListPageSize, true);
                }
            });

            $("#transfersGrid").on("pagechanged", function (event) {
                if (suppressTransferPageEvent) {
                    return;
                }
                loadTransfers(event.args.pagenum, event.args.pagesize, false);
            });

            $("#transfersGrid").on("pagesizechanged", function (event) {
                if (suppressTransferPageEvent) {
                    return;
                }
                loadTransfers(0, event.args.pagesize, true);
            });

            $("#transfersGrid").on("rowselect", function (event) {
                const row = event.args?.row;
                loadTransferItems(Number(row?.id || 0), row);
            });
            $("#deleteTransferBtn").on("click", deleteSelectedTransfer);
        });
    </script>
<?= $this->endSection() ?>
