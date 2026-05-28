<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase List<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Purchase List</h1>
                <!-- <p class="text-muted mb-0">Monitor purchase history.</p> -->
            </div>
            <a href="<?= site_url('purchases/create') ?>" class="btn btn-primary">New Purchase</a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Purchase List</h2>
                        <div id="purchasesGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Purchase Items</h2>
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

        function initWidgets() {
            $("#purchasesGrid").jqxGrid({
                width: "100%",
                height: 420,
                columnsresize: true,
                selectionmode: "singlerow",
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
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
                height: 420,
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
                    { text: "Total Price", datafield: "total_price", cellsformat: "f2", cellsalign: "right" }
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

        function loadPurchaseItems(purchaseId) {
            if (!purchaseId) {
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
                return;
            }

            $.getJSON(`${API_URLS.purchases}/${purchaseId}`).done(function(res) {
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
                    total_price: Number(item.total_price || 0)
                }));
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" })
                });
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load purchase items.";
                console.error(msg);
                $("#purchaseItemsGrid").jqxGrid({
                    source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" })
                });
            });
        }

        $(function() {
            initWidgets();
            loadPurchases().done(function() {
                const firstRow = $("#purchasesGrid").jqxGrid("getrowdata", 0);
                if (firstRow?.id) {
                    $("#purchasesGrid").jqxGrid("selectrow", 0);
                    loadPurchaseItems(Number(firstRow.id));
                }
            });

            $("#purchasesGrid").on("rowselect", function (event) {
                const row = event.args?.row;
                loadPurchaseItems(Number(row?.id || 0));
            });
        });
    </script>
<?= $this->endSection() ?>
