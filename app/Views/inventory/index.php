<?php

use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Inventory<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #inventoryGrid .jqx-grid-cell-edit .jqx-numberinput {
        height: 100% !important;
        margin: 0 !important;
    }
    #inventoryGrid .jqx-grid-cell-edit .jqx-numberinput input {
        height: 100% !important;
        margin: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        box-sizing: border-box;
    }
    .inventory-metric .metric-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Inventory</h1>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-2 mb-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <select id="filterWarehouse" class="form-select">
                            <option value="">All warehouses</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filterDepartment" class="form-select">
                            <option value="">All departments</option>
                            <?php foreach (Department::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filterGender" class="form-select">
                            <option value="">All genders</option>
                            <?php foreach (Gender::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filterSeason" class="form-select">
                            <option value="">All seasons</option>
                            <?php foreach (Season::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-12 col-md-5">
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by product name, serial number, or style">
                    </div>
                    <div class="col-12 col-md-4">
                        <div id="filterTags"></div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="searchInventoryBtn" class="btn btn-primary">Search</button>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="resetInventoryFilterBtn" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="splitWarehouseDetails" value="1">
                                <label class="form-check-label" for="splitWarehouseDetails">Show details for Warehouses</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="splitSizeDetails" value="1">
                                <label class="form-check-label" for="splitSizeDetails">Show details for sizes</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="border rounded bg-white px-3 py-2 inventory-metric">
                            <div class="small text-muted">Total Products</div>
                            <div id="metricTotalProducts" class="metric-value">0</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded bg-white px-3 py-2 inventory-metric">
                            <div class="small text-muted">Total Units</div>
                            <div id="metricTotalUnits" class="metric-value">0</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded bg-white px-3 py-2 inventory-metric">
                            <div class="small text-muted">Total Value</div>
                            <div id="metricTotalValue" class="metric-value text-primary">0.00</div>
                        </div>
                    </div>
                </div>
                <div id="inventoryGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            inventory: "<?= site_url('api/inventory') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            tags: "<?= site_url('api/tags') ?>"
        };

        const DEPARTMENT_LABELS = <?= json_encode(Department::labels(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const GENDER_LABELS = <?= json_encode(Gender::labels(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const SEASON_LABELS = <?= json_encode(Season::labels(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let savingSellingPrice = false;
        let filterTagIds = new Set();
        let filterTagSyncLock = false;

        function enumLabel(map, value) {
            const key = String(value || "").trim();
            return map[key] || key;
        }

        function formatNumber(value, fractionDigits = 0) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: fractionDigits,
                maximumFractionDigits: fractionDigits
            });
        }

        function updateInventoryMetrics(rows) {
            const productIds = new Set();
            let totalUnits = 0;
            let totalValue = 0;

            (rows || []).forEach(function (row) {
                const productId = Number(row.product_id || 0);
                const qty = Number(row.quantity || 0);
                const cost = Number(row.cost_price || 0);

                if (productId > 0) {
                    productIds.add(productId);
                }

                totalUnits += qty;
                totalValue += qty * cost;
            });

            $("#metricTotalProducts").text(formatNumber(productIds.size));
            $("#metricTotalUnits").text(formatNumber(totalUnits));
            $("#metricTotalValue").text(formatNumber(totalValue, 2));
        }

        function saveSellingPrice(rowIndex, sellingPrice) {
            if (savingSellingPrice) {
                return;
            }

            const row = $("#inventoryGrid").jqxGrid("getrowdata", rowIndex);
            const productId = Number(row?.product_id || 0);
            const variantId = Number(row?.variant_id || 0);
            const warehouseId = Number(row?.warehouse_id || 0);
            const price = Math.max(0, Number(sellingPrice || 0));

            if (productId < 1 && variantId < 1) {
                return;
            }

            const splitWarehouse = $("#splitWarehouseDetails").is(":checked");
            const splitSize = $("#splitSizeDetails").is(":checked");
            const useVariantPrice = splitWarehouse && splitSize && variantId > 0;
            const url = useVariantPrice
                ? `${API_URLS.inventory}/variant/${variantId}/selling-price`
                : `${API_URLS.inventory}/product/${productId}/selling-price`;
            const payload = useVariantPrice
                ? { selling_price: price }
                : {
                    selling_price: price,
                    warehouse_id: splitWarehouse && warehouseId > 0 ? warehouseId : null
                };

            savingSellingPrice = true;
            $.ajax({
                url: url,
                method: "PUT",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function () {
                setMessage("Selling price saved.", false);
                loadInventory();
            }).fail(function (xhr) {
                const msg = xhr.responseJSON?.message || "Failed to update selling price.";
                setMessage(msg, true);
                loadInventory();
            }).always(function () {
                savingSellingPrice = false;
            });
        }

        function initWidgets() {
            $("#inventoryGrid").jqxGrid({
                width: "100%",
                height: 520,
                columnsresize: true,
                filterable: true,
                showfilterrow: true,
                editable: true,
                editmode: "click",
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    {
                        text: "#",
                        width: 50,
                        editable: false,
                        sortable: false,
                        filterable: false,
                        cellsrenderer: function (row) {
                            return `<div class="px-2 py-1">${row + 1}</div>`;
                        }
                    },
                    { text: "Product", datafield: "product_name", width: 200 },
                    { text: "Product No.", datafield: "product_number", width: 120 },
                    { text: "Brand", datafield: "brand", width: 100 },
                    { text: "Style", datafield: "style", width: 120 },
                    { text: "Sizes", datafield: "sizes", width: 140 },
                    { text: "Warehouse", datafield: "warehouse_name", width: 120 },
                    {
                        text: "Department",
                        datafield: "department",
                        width: 100,
                        editable: false,
                        cellsrenderer: function (row, column, value) {
                            return `<div class="px-2 py-1">${enumLabel(DEPARTMENT_LABELS, value)}</div>`;
                        }
                    },
                    {
                        text: "Gender",
                        datafield: "gender",
                        width: 90,
                        editable: false,
                        cellsrenderer: function (row, column, value) {
                            return `<div class="px-2 py-1">${enumLabel(GENDER_LABELS, value)}</div>`;
                        }
                    },
                    {
                        text: "Season",
                        datafield: "season",
                        width: 90,
                        editable: false,
                        cellsrenderer: function (row, column, value) {
                            return `<div class="px-2 py-1">${enumLabel(SEASON_LABELS, value)}</div>`;
                        }
                    },
                    { text: "Qty", datafield: "quantity", width: 70, cellsalign: "right", editable: false },
                    { text: "Cost Price", datafield: "cost_price", width: 100, cellsformat: "f2", cellsalign: "right", editable: false },
                    {
                        text: "Selling Price",
                        datafield: "selling_price",
                        width: 110,
                        cellsformat: "f2",
                        cellsalign: "right",
                        columntype: "numberinput",
                        editable: true
                    }
                ]
            });

            function onSellingPriceEdited(event) {
                if (event.args.datafield !== "selling_price") {
                    return;
                }
                saveSellingPrice(event.args.rowindex, event.args.value);
            }

            $("#inventoryGrid").on("cellendedit", onSellingPriceEdited);
            $("#inventoryGrid").on("cellvaluechanged", onSellingPriceEdited);
        }

        function getSelectedFilterTagIds() {
            return Array.from(filterTagIds);
        }

        function syncFilterTagChecks() {
            if (!$("#filterTags").data("jqxDropDownList")) {
                return;
            }

            filterTagSyncLock = true;
            const items = $("#filterTags").jqxDropDownList("getItems") || [];
            items.forEach(function (item, index) {
                const id = Number(item.value || 0);
                if (filterTagIds.has(id)) {
                    $("#filterTags").jqxDropDownList("checkIndex", index);
                } else {
                    $("#filterTags").jqxDropDownList("uncheckIndex", index);
                }
            });
            filterTagSyncLock = false;
        }

        function initFilterTags() {
            $("#filterTags").jqxDropDownList({
                width: "100%",
                height: 38,
                displayMember: "name",
                valueMember: "id",
                placeHolder: "Filter by tags",
                checkboxes: true,
                source: []
            });

            $("#filterTags").on("checkChange", function (event) {
                if (filterTagSyncLock) {
                    return;
                }

                const id = Number(event.args?.item?.value || 0);
                if (id < 1) {
                    return;
                }

                if (event.args.checked) {
                    filterTagIds.add(id);
                } else {
                    filterTagIds.delete(id);
                }
            });
        }

        function loadTags() {
            return $.getJSON(API_URLS.tags).done(function (res) {
                $("#filterTags").jqxDropDownList({ source: res.data || [] });
                syncFilterTagChecks();
            });
        }

        function loadWarehouses() {
            return $.getJSON(API_URLS.warehouses).done(function (res) {
                const $select = $("#filterWarehouse");
                const current = $select.val();
                $select.find("option:not(:first)").remove();
                (res.data || []).forEach(function (row) {
                    $select.append(
                        $("<option></option>").attr("value", row.id).text(row.name || "")
                    );
                });
                $select.val(current || "");
            });
        }

        function loadInventory() {
            const search = String($("#filterSearch").val() || "").trim();
            const warehouseId = String($("#filterWarehouse").val() || "").trim();
            const department = String($("#filterDepartment").val() || "").trim();
            const gender = String($("#filterGender").val() || "").trim();
            const season = String($("#filterSeason").val() || "").trim();
            const tagIds = getSelectedFilterTagIds();
            const params = {};

            if (search) {
                params.q = search;
            }
            if (warehouseId) {
                params.warehouse_id = warehouseId;
            }
            if (department) {
                params.department = department;
            }
            if (gender) {
                params.gender = gender;
            }
            if (season) {
                params.season = season;
            }
            if (tagIds.length) {
                params.tag_ids = tagIds.join(",");
            }
            if ($("#splitWarehouseDetails").is(":checked")) {
                params.split_warehouse = "1";
            }
            if ($("#splitSizeDetails").is(":checked")) {
                params.split_size = "1";
            }

            return $.getJSON(API_URLS.inventory, params).done(function(res) {
                const rows = (res.data || []).map(function (row) {
                    return {
                        ...row,
                        variant_id: Number(row.variant_id || 0),
                        cost_price: Number(row.cost_price || 0),
                        selling_price: Number(row.selling_price || 0),
                        quantity: Number(row.quantity || 0)
                    };
                });
                const source = { localdata: rows, datatype: "array" };
                $("#inventoryGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
                updateInventoryMetrics(rows);
            }).fail(function(xhr) {
                updateInventoryMetrics([]);
                setMessage(xhr.responseJSON?.message || "Failed to load inventory.", true);
            });
        }

        $(function() {
            initWidgets();
            initFilterTags();
            $.when(loadWarehouses(), loadTags()).always(loadInventory);

            $("#filterWarehouse, #filterDepartment, #filterGender, #filterSeason").on("change", loadInventory);
            $("#searchInventoryBtn").on("click", loadInventory);
            $("#resetInventoryFilterBtn").on("click", function () {
                $("#filterSearch").val("");
                $("#filterWarehouse").val("");
                $("#filterDepartment").val("");
                $("#filterGender").val("");
                $("#filterSeason").val("");
                filterTagIds.clear();
                syncFilterTagChecks();
                $("#splitWarehouseDetails").prop("checked", false);
                $("#splitSizeDetails").prop("checked", false);
                loadInventory();
            });
            $("#splitWarehouseDetails, #splitSizeDetails").on("change", loadInventory);
            $("#filterSearch").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    loadInventory();
                }
            });
        });
</script>
<?= $this->endSection() ?>
