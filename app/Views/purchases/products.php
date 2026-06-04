<?php

use App\Enums\Department;
use App\Enums\Gender;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase Products<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Products</h1>
                <p class="text-muted mb-0 small">Products with reference cost and first purchase details.</p>
            </div>
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary btn-sm">Back to Purchases</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary mb-1 small">Search</label>
                        <input type="text" id="productSearchInput" class="form-control" placeholder="Name or serial number">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary mb-1 small">Supplier</label>
                        <select id="productSupplierFilter" class="form-select">
                            <option value="">All suppliers</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Department</label>
                        <select id="productDepartmentFilter" class="form-select">
                            <option value="">All departments</option>
                            <?php foreach (Department::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Gender</label>
                        <select id="productGenderFilter" class="form-select">
                            <option value="">All genders</option>
                            <?php foreach (Gender::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="button" id="applyProductSearchBtn" class="btn btn-primary">Search</button>
                        <button type="button" id="clearProductSearchBtn" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </div>
                <div id="productListError" class="text-danger small mb-2"></div>
                <div id="purchaseProductsGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        purchaseProducts: "<?= site_url('api/purchase-products') ?>",
        suppliers: "<?= site_url('api/suppliers') ?>"
    };
    const PRODUCT_PAGE_SIZE = 20;
    let productListPage = 0;
    let productListPageSize = PRODUCT_PAGE_SIZE;
    let productListTotal = 0;
    let productListLoading = false;
    let suppressProductPageEvent = false;

    const productGridSource = {
        localdata: [],
        datatype: "array",
        totalrecords: 0,
        datafields: [
            { name: "id", type: "number" },
            { name: "name", type: "string" },
            { name: "serial_number", type: "string" },
            { name: "reference_cost", type: "number" },
            { name: "reference_currency", type: "string" },
            { name: "department", type: "string" },
            { name: "gender", type: "string" },
            { name: "supplier_name", type: "string" },
            { name: "first_purchase_date", type: "string" }
        ]
    };
    let productGridAdapter = null;

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function formatReferenceCost(row) {
        const amount = formatMoney(row.reference_cost);
        const currency = String(row.reference_currency || "USD").toUpperCase();
        return `${amount} (${currency})`;
    }

    function setListError(msg) {
        $("#productListError").text(msg || "");
    }

    function getFilterParams(page, perPage) {
        const params = {
            page: (page ?? productListPage) + 1,
            per_page: perPage ?? productListPageSize
        };
        const search = String($("#productSearchInput").val() || "").trim();
        const supplierId = Number($("#productSupplierFilter").val() || 0);
        const department = String($("#productDepartmentFilter").val() || "").trim();
        const gender = String($("#productGenderFilter").val() || "").trim();

        if (search !== "") {
            params.search = search;
        }
        if (supplierId > 0) {
            params.supplier_id = supplierId;
        }
        if (department !== "") {
            params.department = department;
        }
        if (gender !== "") {
            params.gender = gender;
        }
        return params;
    }

    function clearFilters() {
        $("#productSearchInput").val("");
        $("#productSupplierFilter").val("");
        $("#productDepartmentFilter").val("");
        $("#productGenderFilter").val("");
        loadPurchaseProducts(0, productListPageSize, true);
    }

    function loadSuppliers() {
        return $.getJSON(API_URLS.suppliers).done(function (res) {
            const $select = $("#productSupplierFilter");
            const current = $select.val();
            $select.find("option:not(:first)").remove();
            (res.data || []).forEach(function (row) {
                $select.append(
                    $("<option></option>").attr("value", row.id).text(row.name || ("#" + row.id))
                );
            });
            if (current !== "" && $select.find(`option[value="${current}"]`).length) {
                $select.val(current);
            }
        });
    }

    function updateProductsGridSource(rows, total, syncPage) {
        productListTotal = total;
        suppressProductPageEvent = true;

        productGridSource.localdata = rows;
        productGridSource.totalrecords = total;
        productGridAdapter.dataBind();
        $("#purchaseProductsGrid").jqxGrid("updatebounddata");

        if (syncPage) {
            const paging = $("#purchaseProductsGrid").jqxGrid("getpaginginformation");
            if (!paging || paging.pagenum !== productListPage) {
                $("#purchaseProductsGrid").jqxGrid("gotopage", productListPage);
            }
        }

        window.setTimeout(function () {
            suppressProductPageEvent = false;
        }, 0);
    }

    function loadPurchaseProducts(page, perPage, resetPage) {
        setListError("");

        if (resetPage) {
            productListPage = 0;
            if ($("#purchaseProductsGrid").data("jqxGrid")) {
                suppressProductPageEvent = true;
                $("#purchaseProductsGrid").jqxGrid("gotopage", 0);
                window.setTimeout(function () {
                    suppressProductPageEvent = false;
                }, 0);
            }
        } else if (page !== undefined && page !== null) {
            productListPage = page;
        }
        if (perPage !== undefined && perPage !== null) {
            productListPageSize = perPage;
        }

        const requestPage = productListPage;
        const requestSize = productListPageSize;
        productListLoading = true;

        return $.getJSON(API_URLS.purchaseProducts, getFilterParams(requestPage, requestSize))
            .done(function (res) {
                const rows = res.data || [];
                const pagination = res.pagination || {};
                const total = Number(pagination.total || 0);

                productListPage = Math.max(0, Number(pagination.page || 1) - 1);
                productListPageSize = Number(pagination.per_page || requestSize);
                updateProductsGridSource(rows, total, true);
            })
            .fail(function (xhr) {
                setListError(xhr.responseJSON?.message || "Failed to load products.");
                updateProductsGridSource([], 0, true);
            })
            .always(function () {
                productListLoading = false;
            });
    }

    function initWidgets() {
        productGridAdapter = new $.jqx.dataAdapter(productGridSource);

        $("#purchaseProductsGrid").jqxGrid({
            width: "100%",
            height: 520,
            columnsresize: true,
            pageable: true,
            pagesize: PRODUCT_PAGE_SIZE,
            pagesizeoptions: ["10", "20", "50", "100"],
            virtualmode: true,
            rendergridrows: function () {
                return productGridSource.localdata;
            },
            source: productGridAdapter,
            columns: [
                { text: "ID", datafield: "id", width: 60 },
                { text: "Name", datafield: "name", width: 180 },
                { text: "Serial Number", datafield: "serial_number", width: 120 },
                {
                    text: "Reference Cost",
                    datafield: "reference_cost",
                    width: 120,
                    cellsalign: "right",
                    cellsrenderer: function (row, column, value, defaultHtml, columnSettings, rowData) {
                        const el = $(defaultHtml);
                        el.text(formatReferenceCost(rowData));
                        return el[0].outerHTML;
                    }
                },
                { text: "Department", datafield: "department", width: 100 },
                { text: "Gender", datafield: "gender", width: 90 },
                { text: "Supplier", datafield: "supplier_name", width: 140 },
                { text: "First Purchase", datafield: "first_purchase_date", width: 110 }
            ]
        });
    }

    $(function () {
        initWidgets();
        loadSuppliers().always(function () {
            loadPurchaseProducts(0, PRODUCT_PAGE_SIZE, true);
        });

        $("#applyProductSearchBtn").on("click", function () {
            loadPurchaseProducts(0, productListPageSize, true);
        });
        $("#clearProductSearchBtn").on("click", clearFilters);
        $("#productSearchInput").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                loadPurchaseProducts(0, productListPageSize, true);
            }
        });
        $("#productSupplierFilter, #productDepartmentFilter, #productGenderFilter").on("change", function () {
            loadPurchaseProducts(0, productListPageSize, true);
        });

        $("#purchaseProductsGrid").on("pagechanged", function (event) {
            if (suppressProductPageEvent || productListLoading) {
                return;
            }
            loadPurchaseProducts(event.args.pagenum, event.args.pagesize, false);
        });

        $("#purchaseProductsGrid").on("pagesizechanged", function (event) {
            if (suppressProductPageEvent || productListLoading) {
                return;
            }
            loadPurchaseProducts(0, event.args.pagesize, true);
        });
    });
</script>
<?= $this->endSection() ?>
