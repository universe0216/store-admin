<?php

use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Purchase<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .purchase-create-page {
        --purchase-control-height: 34px;
    }
    .purchase-create-page .form-control,
    .purchase-create-page .form-select,
    #purchaseConfirmModal .form-control,
    #purchaseConfirmModal .form-select,
    #exchangeRateModal .form-control {
        height: var(--purchase-control-height);
        min-height: var(--purchase-control-height);
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        font-size: 0.875rem;
        line-height: 1.25;
    }
    .purchase-create-page .jqx-dropdownlist,
    .purchase-create-page .jqx-datetimeinput,
    .purchase-create-page .jqx-numberinput {
        height: var(--purchase-control-height) !important;
    }
    .purchase-create-page .jqx-numberinput input,
    #discountTotalInput input,
    #paidAmountInput input,
    #shippingFeeInput input {
        height: 100% !important;
        line-height: var(--purchase-control-height) !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    .tag-chip-picker {
        min-height: 42px;
    }
    #purchaseTagsChips {
        min-height: 28px;
    }
    .tag-chip {
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.35rem 0.5rem;
    }
    .tag-chip-remove {
        width: 0.5rem;
        height: 0.5rem;
        opacity: 0.85;
    }
    #unitPriceRateLink {
        cursor: pointer;
        font-weight: 500;
    }
    #unitPriceUsdHint {
        font-weight: 700;
        font-size: 0.95rem;
        color: #212529;
    }
    #unitPriceInput {
        min-width: 0;
    }
    .purchase-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.4rem 0;
    }
    .purchase-summary-row .summary-label {
        color: #6c757d;
    }
    .purchase-summary-row .summary-value {
        min-width: 140px;
        text-align: right;
    }
    .purchase-summary-row .summary-value.summary-subtotal-group {
        min-width: 240px;
    }
    .purchase-summary-row .summary-subtotal-group {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .purchase-summary-row .summary-subtotal-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .purchase-summary-row .summary-subtotal-item .summary-subtotal-currency {
        color: #6c757d;
        font-size: 0.8125rem;
        white-space: nowrap;
    }
    .purchase-summary-row .summary-amount {
        font-weight: 700;
        color: #212529;
        white-space: nowrap;
    }
    .purchase-summary-row .summary-value .jqx-numberinput {
        text-align: right;
    }
    #purchaseSummaryForm .jqx-numberinput {
        width: 140px !important;
    }
    #purchaseSummaryForm hr {
        margin: 0.75rem 0;
        opacity: 0.15;
    }
    .size-selection-card {
        background: #fff;
    }
    .size-pack-table-wrap {
        overflow-x: auto;
    }
    .size-pack-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .size-pack-table th,
    .size-pack-table td {
        border: 1px solid #dee2e6;
        padding: 0.45rem 0.65rem;
        vertical-align: middle;
    }
    .size-pack-table thead th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    .size-pack-table tfoot td {
        background: #e7f1ff;
        font-weight: 700;
    }
    .size-pack-qty-control {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .size-pack-qty-control .btn {
        width: 28px;
        height: 28px;
        padding: 0;
        line-height: 1;
    }
    .size-pack-qty-input {
        width: 56px;
        text-align: center;
    }
    .size-pack-empty {
        color: #6c757d;
        font-size: 0.8125rem;
        padding: 0.75rem 0;
    }
    .product-search-dropdown {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 240px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: #fff;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .product-search-dropdown .list-group-item {
        border-left: 0;
        border-right: 0;
        cursor: pointer;
        font-size: 0.875rem;
    }
    .product-search-dropdown .list-group-item:first-child {
        border-top: 0;
    }
    .product-search-dropdown .list-group-item:last-child {
        border-bottom: 0;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5 purchase-create-page">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 fw-bold mb-0">New Purchase</h1>
                </div>
                <div id="purchaseHeaderForm" class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Supplier</label>
                        <div id="supplierDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Warehouse</label>
                        <div id="purchaseWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Purchase Date</label>
                        <div id="purchaseDate"></div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Add Product</h2>
                        <form id="purchaseItemForm" class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label text-secondary mb-1">Department</label>
                                <select id="productDepartmentSelect" class="form-select">
                                    <option value="">Select department</option>
                                    <?php foreach (Department::cases() as $case) : ?>
                                        <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-secondary mb-1">Gender</label>
                                <select id="productGenderSelect" class="form-select">
                                    <option value="">Select gender</option>
                                    <?php foreach (Gender::cases() as $case) : ?>
                                        <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-secondary mb-1">Season</label>
                                <select id="productSeasonSelect" class="form-select">
                                    <option value="">Select season</option>
                                    <?php foreach (Season::cases() as $case) : ?>
                                        <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-secondary mb-1">Category</label>
                                <select id="productCategorySelect" class="form-select" disabled>
                                    <option value="">Select category</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-9">
                                <label class="form-label text-secondary mb-1">Name</label>
                                <div class="position-relative">
                                    <input type="text" id="productNameInput" class="form-control" placeholder="Gender Category (Season)" autocomplete="off">
                                    <div id="productNameSearchDropdown" class="product-search-dropdown list-group d-none"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-secondary mb-1">Serial Number</label>
                                <div class="position-relative">
                                    <input type="text" id="productSerialInput" class="form-control" placeholder="Serial number" autocomplete="off">
                                    <div id="productSerialSearchDropdown" class="product-search-dropdown list-group d-none"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Brand</label>
                                <input type="text" id="productBrandInput" class="form-control" placeholder="Brand">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Style</label>
                                <input type="text" id="productStyleInput" class="form-control" placeholder="Style">
                            </div>
                            <div class="col-12">
                                <div class="size-selection-card border rounded p-3">
                                    <!-- <div class="fw-semibold mb-3">Size Selection</div> -->
                                    <div class="row g-2 align-items-end mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-secondary mb-1">Select Size Range</label>
                                            <div id="sizeSelector"></div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label text-secondary mb-1">
                                                Sets Count
                                                <span class="text-muted" title="Number of identical sets to order">?</span>
                                            </label>
                                            <div id="setsCountInput"></div>
                                        </div>
                                    </div>
                                    <div id="sizePackTableWrap">
                                        <div id="sizePackEmpty" class="size-pack-empty">Select one or more sizes to configure units per set.</div>
                                        <div class="size-pack-table-wrap d-none" id="sizePackTableContainer">
                                            <table class="size-pack-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 120px;">Size</th>
                                                        <th>Units Per Set</th>
                                                        <th style="width: 180px;">Total Units<br><span class="fw-normal small text-muted">(Units Per Set × Sets Count)</span></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sizePackTableBody"></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td></td>
                                                        <td class="text-end">Total Per Set: <span id="sizePackTotalPerSet">0</span></td>
                                                        <td class="text-end">Total Units: <span id="sizePackGrandTotal">0</span></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary mb-1">
                                    Unit Price
                                    <span id="unitPriceRateLink" class="text-primary ms-2 d-none" role="button" tabindex="0"></span>
                                </label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select id="unitPriceCurrency" class="form-select" style="max-width: 110px;">
                                        <option value="USD">USD</option>
                                    </select>
                                    <input type="number" id="unitPriceInput" class="form-control flex-grow-1" min="0" step="0.01" value="0" style="max-width: 160px;">
                                    <span id="unitPriceUsdHint" class="fw-bold text-nowrap d-none pl-4"></span>
                                </div>
                            </div>
                            <div class="col-3 col-md-3">
                                <label class="form-label text-secondary mb-1">Total Units</label>
                                <div id="totalUnitsInput"></div>
                            </div>
                            <div class="col-3 col-md-3">
                                <label class="form-label text-secondary mb-1">Total Price</label>
                                <div id="totalPriceInput"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">Tags</label>
                                <div class="tag-chip-picker border rounded bg-white p-2">
                                    <div id="purchaseTagsChips" class="d-flex flex-wrap gap-1 mb-2"></div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <div id="purchaseTagsDropdown" class="flex-grow-1" style="min-width: 200px;"></div>
                                        <input type="text" id="newTagNameInput" class="form-control" placeholder="New tag name" style="max-width: 160px;">
                                        <button type="button" id="createTagBtn" class="btn btn-sm btn-outline-primary text-nowrap">Add Tag</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="button" id="addProductsBtn" class="btn btn-primary">+ Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 fw-semibold mb-0">Purchase Items (<span id="purchaseItemsCount">0</span>)</h2>
                            <button type="button" id="deleteSelectedGridRowBtn" class="btn btn-sm btn-outline-danger">Delete Selected Row</button>
                        </div>
                        <div id="itemsGrid"></div>
                    </div>
                </div>

                <div id="purchaseSummaryForm" class="card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Purchase Summary</h2>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Subtotal</span>
                            <div class="summary-value summary-subtotal-group">
                                <div class="summary-subtotal-item">
                                    <span id="referenceSubTotalDisplay" class="summary-amount">0.00</span>
                                    <span class="summary-subtotal-currency">(<span id="referenceSubTotalLabel">$</span>)</span>
                                </div>
                                <div class="summary-subtotal-item">
                                    <span id="subTotalDisplay" class="summary-amount">0.00</span>
                                    <span class="summary-subtotal-currency">$</span>
                                </div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Discount</span>
                            <div class="summary-value">
                                <div id="discountTotalInput"></div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Paid Amount</span>
                            <div class="summary-value">
                                <div id="paidAmountInput"></div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Shipping Fee</span>
                            <div class="summary-value">
                                <div id="shippingFeeInput"></div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label fw-semibold text-dark">Grand Total (Paid + Shipping Fee)</span>
                            <div class="summary-value">
                                <span id="grandTotalDisplay" class="summary-amount">0.00</span>$
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-secondary mb-1">Notes</label>
                            <input id="notesInput" type="text" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" id="savePurchaseBtn" class="btn btn-primary">Save Purchase</button>
        </div>
    </div>

    <div class="modal fade" id="exchangeRateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Exchange Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="exchangeRateInput" class="form-label text-secondary mb-1">
                        1 USD = ?
                    </label>
                    <div class="input-group">
                        <input type="number" id="exchangeRateInput" class="form-control" min="0" step="any" placeholder="0.00">
                        <span class="input-group-text" id="exchangeRateCurrencyCode">CNY</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveExchangeRateBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchaseConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">Total Units Count: <span id="confirmTotalUnitsCount" class="fw-semibold">0</span></div>
                    <div class="mb-2">Subtotal (<span id="confirmRefCurrencyLabel">USD</span>): <span id="confirmReferenceSubTotal" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Subtotal (USD): <span id="confirmTotalPriceSum" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Discount: <span id="confirmDiscount" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Paid Total: <span id="confirmPaidAmount" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Shipping Fee: <span id="confirmShippingFee" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Grand Total (Paid + Shipping Fee): <span id="confirmGrandTotal" class="fw-semibold">0.00</span></div>
                    <hr>
                    <div class="mb-2 fw-semibold">Payment Methods</div>
                    <div id="confirmPaymentRows" class="d-flex flex-column gap-2 mb-2"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentRowBtn">+ Add Payment Method</button>
                    <div class="small text-muted mt-2">Payment amounts must equal the grand total.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSavePurchaseBtn">Confirm Save</button>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            suppliers: "<?= site_url('api/suppliers') ?>",
            categories: "<?= site_url('api/categories') ?>",
            products: "<?= site_url('api/products') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            purchases: "<?= site_url('api/purchases') ?>",
            tags: "<?= site_url('api/tags') ?>",
            currencies: "<?= site_url('api/currencies') ?>",
            exchangeRates: "<?= site_url('api/exchange-rates') ?>",
            paymentMethods: "<?= site_url('api/payment-methods') ?>"
        };

        const items = [];
        let suppliers = [];
        let paymentMethods = [];
        let confirmPaymentRows = [];
        let categories = [];
        let warehouses = [];
        let currencies = [];
        let allTags = [];
        let selectedTagIds = new Set();
        let tagSyncLock = false;
        let sizePackUnits = {};
        let selectedCategoryId = 0;
        let productNameManual = false;
        let selectedExistingProductId = 0;
        let productFillLock = false;
        let productSearchTimer = null;
        let productSearchRequest = null;
        let confirmPurchaseModal = null;
        let exchangeRateModal = null;
        let exchangeRatesByCurrency = {};
        let totalsCalcLock = false;
        const FIXED_SIZES = [220, 225, 230, 235, 240, 245, 250].map(size => ({
            label: String(size),
            value: String(size)
        }));
        const SIZE_PACK_TEMPLATES = {
            one_each: function (sizes) {
                const pack = {};
                sizes.forEach(function (size) {
                    pack[size] = 1;
                });
                return pack;
            },
            sample: function (sizes) {
                const defaults = { 220: 2, 230: 3 };
                const pack = {};
                sizes.forEach(function (size) {
                    pack[size] = Number(defaults[Number(size)] || 0);
                });
                return pack;
            },
            clear: function (sizes) {
                const pack = {};
                sizes.forEach(function (size) {
                    pack[size] = 0;
                });
                return pack;
            }
        };

        function getCheckedSizes() {
            return ($("#sizeSelector").jqxDropDownList("getCheckedItems") || [])
                .map(function (item) {
                    return String(item.value);
                })
                .sort(function (a, b) {
                    return Number(a) - Number(b);
                });
        }

        function getSizePackTotals() {
            const setsCount = Math.max(Number($("#setsCountInput").jqxNumberInput("val") || 0), 0);
            const sizes = getCheckedSizes();
            let totalPerSet = 0;
            let totalUnits = 0;

            sizes.forEach(function (size) {
                const unitsPerSet = Math.max(Number(sizePackUnits[size] || 0), 0);
                totalPerSet += unitsPerSet;
                totalUnits += unitsPerSet * setsCount;
            });

            return {
                sizes: sizes,
                setsCount: setsCount,
                totalPerSet: totalPerSet,
                totalUnits: totalUnits
            };
        }

        function buildSizePackObject() {
            const pack = {};
            getCheckedSizes().forEach(function (size) {
                pack[size] = Math.max(Number(sizePackUnits[size] || 0), 0);
            });
            return pack;
        }

        function formatSizeValueFromPack(pack) {
            return Object.keys(pack)
                .sort(function (a, b) { return Number(a) - Number(b); })
                .join(", ");
        }

        function renderSizePackTable() {
            const sizes = getCheckedSizes();
            const $body = $("#sizePackTableBody").empty();

            sizes.forEach(function (size) {
                if (!Object.prototype.hasOwnProperty.call(sizePackUnits, size)) {
                    sizePackUnits[size] = 1;
                }
            });
            Object.keys(sizePackUnits).forEach(function (size) {
                if (!sizes.includes(size)) {
                    delete sizePackUnits[size];
                }
            });

            if (sizes.length === 0) {
                $("#sizePackEmpty").removeClass("d-none");
                $("#sizePackTableContainer").addClass("d-none");
                $("#sizePackTotalPerSet").text("0");
                $("#sizePackGrandTotal").text("0");
                recalcTotalUnits();
                return;
            }

            if (Number($("#setsCountInput").jqxNumberInput("val") || 0) < 1) {
                $("#setsCountInput").jqxNumberInput("val", 1);
            }

            const setsCount = Math.max(Number($("#setsCountInput").jqxNumberInput("val") || 0), 0);

            $("#sizePackEmpty").addClass("d-none");
            $("#sizePackTableContainer").removeClass("d-none");

            sizes.forEach(function (size) {
                const unitsPerSet = Math.max(Number(sizePackUnits[size] || 0), 0);
                const rowTotal = unitsPerSet * setsCount;
                const $row = $(`
                    <tr data-size="${escapeHtml(size)}">
                        <td class="fw-semibold">${escapeHtml(size)}</td>
                        <td>
                            <div class="size-pack-qty-control">
                                <button type="button" class="btn btn-outline-secondary btn-xs size-pack-minus" aria-label="Decrease">−</button>
                                <input type="number" class="form-control form-control-xs size-pack-qty-input" min="0" step="1" value="${unitsPerSet}">
                                <button type="button" class="btn btn-outline-secondary btn-xs size-pack-plus" aria-label="Increase">+</button>
                            </div>
                        </td>
                        <td class="size-pack-row-total fw-semibold">${rowTotal}</td>
                    </tr>
                `);
                $body.append($row);
            });

            updateSizePackFooter();
        }

        function updateSizePackRowTotal($row) {
            const setsCount = Math.max(Number($("#setsCountInput").jqxNumberInput("val") || 0), 0);
            const size = String($row.data("size") || "");
            const unitsPerSet = Math.max(Number(sizePackUnits[size] || 0), 0);
            $row.find(".size-pack-qty-input").val(unitsPerSet);
            $row.find(".size-pack-row-total").text(unitsPerSet * setsCount);
        }

        function updateSizePackFooter() {
            const totals = getSizePackTotals();
            $("#sizePackTotalPerSet").text(totals.totalPerSet);
            $("#sizePackGrandTotal").text(totals.totalUnits);
            recalcTotalUnits();
        }

        function setSizePackUnits(size, value) {
            sizePackUnits[String(size)] = Math.max(Number(value || 0), 0);
            const $row = $("#sizePackTableBody tr").filter(function () {
                return String($(this).data("size")) === String(size);
            });
            if ($row.length) {
                updateSizePackRowTotal($row);
            }
            updateSizePackFooter();
        }

        function applySizePackTemplate(templateKey) {
            const sizes = getCheckedSizes();
            if (sizes.length === 0) {
                setMessage("Select at least one size before applying a pack template.");
                return;
            }

            const template = SIZE_PACK_TEMPLATES[templateKey];
            if (typeof template !== "function") {
                return;
            }

            sizePackUnits = template(sizes);
            renderSizePackTable();
        }

        function resetSizePackSelection() {
            sizePackUnits = {};
            if ($("#sizeSelector").data("jqxDropDownList")) {
                const items = $("#sizeSelector").jqxDropDownList("getItems") || [];
                items.forEach(function (_item, index) {
                    $("#sizeSelector").jqxDropDownList("uncheckIndex", index);
                });
            }
            renderSizePackTable();
        }

        function parseSizePack(row) {
            if (row.size_pack) {
                try {
                    return typeof row.size_pack === "string" ? JSON.parse(row.size_pack) : row.size_pack;
                } catch (error) {
                    return {};
                }
            }

            const pack = {};
            String(row.size_value || "")
                .split(",")
                .map(function (value) { return value.trim(); })
                .filter(function (value) { return value !== ""; })
                .forEach(function (size) {
                    pack[size] = 1;
                });
            return pack;
        }

        function getSelectedUnitCurrency() {
            return String($("#unitPriceCurrency").val() || "USD").toUpperCase();
        }

        function getExchangeRateForCurrency(code) {
            const currency = String(code || "").toUpperCase();
            if (currency === "USD") {
                return 1;
            }
            return Number(exchangeRatesByCurrency[currency] || 0);
        }

        function getUsdUnitPrice() {
            const currency = getSelectedUnitCurrency();
            const unitPrice = Number($("#unitPriceInput").val() || 0);
            if (currency === "USD") {
                return unitPrice;
            }

            const rate = getExchangeRateForCurrency(currency);
            if (rate <= 0) {
                return 0;
            }

            return Number((unitPrice / rate).toFixed(2));
        }

        function updateUnitPriceCurrencyUi() {
            const currency = getSelectedUnitCurrency();
            const $rateLink = $("#unitPriceRateLink");
            const $usdHint = $("#unitPriceUsdHint");

            if (currency === "USD") {
                $rateLink.addClass("d-none").text("");
                $usdHint.addClass("d-none").text("");
                return;
            }

            const rate = getExchangeRateForCurrency(currency);
            $rateLink.removeClass("d-none").text(rate > 0 ? `1 USD = ${rate} ${currency}` : "1 USD = ?");

            const usdUnit = getUsdUnitPrice();
            if (rate > 0) {
                $usdHint.removeClass("d-none").text(`$${usdUnit.toFixed(2)}`);
            } else {
                $usdHint.addClass("d-none").text("");
            }
        }

        function loadExchangeRateForCurrency(currency) {
            const code = String(currency || "").toUpperCase();
            if (code === "USD" || code === "") {
                updateUnitPriceCurrencyUi();
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(`${API_URLS.exchangeRates}/latest/${encodeURIComponent(code)}`).done(function (res) {
                const rate = Number(res.data?.rate || 0);
                if (rate > 0) {
                    exchangeRatesByCurrency[code] = rate;
                } else {
                    delete exchangeRatesByCurrency[code];
                }
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load exchange rate.");
            });
        }

        function openExchangeRateModal() {
            const currency = getSelectedUnitCurrency();
            if (currency === "USD") {
                return;
            }

            $("#exchangeRateCurrencyCode").text(currency);
            loadExchangeRateForCurrency(currency).always(function () {
                $("#exchangeRateInput").val(getExchangeRateForCurrency(currency) || "");
                exchangeRateModal.show();
            });
        }

        function saveExchangeRateFromModal() {
            const currency = getSelectedUnitCurrency();
            const rate = Number($("#exchangeRateInput").val() || 0);
            if (currency === "USD" || rate <= 0) {
                setMessage("Exchange rate must be greater than 0.");
                return;
            }

            $("#saveExchangeRateBtn").prop("disabled", true);
            $.ajax({
                url: API_URLS.exchangeRates,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    quote_currency: currency,
                    rate
                })
            }).done(function (res) {
                exchangeRatesByCurrency[currency] = rate;
                exchangeRateModal.hide();
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
                setMessage(res.message || "Exchange rate saved.", false);
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save exchange rate.");
            }).always(function () {
                $("#saveExchangeRateBtn").prop("disabled", false);
            });
        }

        function loadCurrencies() {
            return $.getJSON(API_URLS.currencies).done(function (res) {
                currencies = res.data || [];
                const $select = $("#unitPriceCurrency");
                const current = $select.val();
                $select.empty();

                if (!currencies.some(row => String(row.code).toUpperCase() === "USD")) {
                    $select.append('<option value="USD">USD</option>');
                }

                currencies.forEach(function (row) {
                    $select.append(
                        $("<option></option>").attr("value", row.code).text(row.code)
                    );
                });

                $select.val(current || "USD");
                updateUnitPriceCurrencyUi();
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load currencies.");
            });
        }

        function initWidgets() {
            $("#supplierDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select supplier" });
            $("#purchaseDate").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd" });
            $("#purchaseWarehouseDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select warehouse" });
            $("#shippingFeeInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 10,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#discountTotalInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: -999999999,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#paidAmountInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#sizeSelector").jqxDropDownList({
                width: "100%",
                height: 34,
                displayMember: "label",
                valueMember: "value",
                placeHolder: "Select size(s)",
                checkboxes: true,
                source: FIXED_SIZES
            });
            $("#setsCountInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 0,
                digits: 8,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#totalUnitsInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 0,
                digits: 10,
                min: 0,
                inputMode: "simple",
                spinButtons: false,
                readOnly: true,
                disabled: true,
                value: 0
            });
            $("#totalPriceInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: 0,
                inputMode: "simple",
                spinButtons: false,
                readOnly: true,
                disabled: true,
                value: 0
            });
            $("#savePurchaseBtn").jqxButton({ width: 160, height: 38, theme: "base" });
            $("#addProductsBtn").jqxButton({ width: 140, height: 34, theme: "base" });
            initTagsPicker();

            $("#itemsGrid").jqxGrid({
                width: "100%",
                height: 520,
                source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" }),
                editable: true,
                editmode: "click",
                selectionmode: "singlerow",
                columnsresize: true,
                showstatusbar: true,
                statusbarheight: 34,
                renderstatusbar: function (statusbar) {
                    const footer = $('<div id="itemsGridTotalsFooter" class="d-flex align-items-center h-100 px-2 small fw-semibold text-secondary"></div>');
                    statusbar.append(footer);
                    updateGridFooterTotals();
                },
                columns: [
                    {
                        text: "Product",
                        datafield: "product_name",
                        width: 160,
                        editable: false
                    },
                    { text: "Product Number", datafield: "sku", width: 120, editable: false },
                    { text: "Brand", datafield: "brand", width: 80, editable: false },
                    { text: "Style", datafield: "style", width: 80, editable: false },
                    { text: "Original Cost", datafield: "original_cost_display", width: 140, editable: false, cellsalign: "right" },
                    { text: "Unit Price", datafield: "unit_price", width: 100, cellsformat: "f4", editable: false, cellsalign: "right" },
                    { text: "Unit Cost", datafield: "unit_cost", width: 100, cellsformat: "f4", editable: false, cellsalign: "right" },
                    { text: "Size", datafield: "size_value", width: 120, editable: false },
                    { text: "Sets Count", datafield: "sets_count", width: 95, editable: false, cellsalign: "right" },
                    { text: "Units Count", datafield: "units_count", width: 95, editable: false, cellsalign: "right" },
                    { text: "Total Cost", datafield: "total_cost", width: 120, cellsformat: "f2", editable: false, cellsalign: "right" }
                ]
            });
        }

        function escapeHtml(text) {
            return String(text || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }

        function formatOriginalCost(referenceCost, referenceCurrency) {
            const amount = Number(referenceCost || 0).toFixed(2);
            const currency = String(referenceCurrency || "USD").toUpperCase();

            return `${amount} (${currency})`;
        }

        function getSelectedTagIds() {
            return Array.from(selectedTagIds);
        }

        function renderTagChips() {
            const $container = $("#purchaseTagsChips").empty();
            allTags
                .filter(tag => selectedTagIds.has(Number(tag.id)))
                .forEach(tag => {
                    const bg = tag.color || "#0d6efd";
                    const chip = $(`
                        <span class="tag-chip badge d-inline-flex align-items-center gap-1 pe-2" data-tag-id="${Number(tag.id)}" style="background-color:${bg};">
                            ${escapeHtml(tag.name)}
                            <button type="button" class="tag-chip-remove btn-close btn-close-white" aria-label="Remove"></button>
                        </span>
                    `);
                    chip.find(".tag-chip-remove").on("click", function () {
                        removeSelectedTag(Number(tag.id));
                    });
                    $container.append(chip);
                });
        }

        function removeSelectedTag(tagId) {
            selectedTagIds.delete(Number(tagId));
            syncTagDropdownChecks();
            renderTagChips();
        }

        function addSelectedTag(tagId) {
            if (Number(tagId) > 0) {
                selectedTagIds.add(Number(tagId));
                syncTagDropdownChecks();
                renderTagChips();
            }
        }

        function syncTagDropdownChecks() {
            if (!$("#purchaseTagsDropdown").data("jqxDropDownList")) {
                return;
            }

            tagSyncLock = true;
            const items = $("#purchaseTagsDropdown").jqxDropDownList("getItems") || [];
            items.forEach((item, index) => {
                const id = Number(item.value);
                if (selectedTagIds.has(id)) {
                    $("#purchaseTagsDropdown").jqxDropDownList("checkIndex", index);
                } else {
                    $("#purchaseTagsDropdown").jqxDropDownList("uncheckIndex", index);
                }
            });
            tagSyncLock = false;
        }

        function initTagsPicker() {
            $("#purchaseTagsDropdown").jqxDropDownList({
                width: "100%",
                height: 34,
                displayMember: "name",
                valueMember: "id",
                placeHolder: "Select tags",
                checkboxes: true,
                source: []
            });

            $("#purchaseTagsDropdown").on("checkChange", function (event) {
                if (tagSyncLock) {
                    return;
                }

                const id = Number(event.args?.item?.value || 0);
                if (id < 1) {
                    return;
                }

                if (event.args.checked) {
                    selectedTagIds.add(id);
                } else {
                    selectedTagIds.delete(id);
                }
                renderTagChips();
            });
        }

        function loadTags() {
            return $.getJSON(API_URLS.tags).done(function (res) {
                allTags = res.data || [];
                $("#purchaseTagsDropdown").jqxDropDownList({ source: allTags });
                syncTagDropdownChecks();
                renderTagChips();
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load tags.");
            });
        }

        function createTagFromInput() {
            const name = String($("#newTagNameInput").val() || "").trim();
            if (name === "") {
                return;
            }

            const existing = allTags.find(t => String(t.name).toLowerCase() === name.toLowerCase());
            if (existing) {
                addSelectedTag(Number(existing.id));
                $("#newTagNameInput").val("");
                return;
            }

            $("#createTagBtn").prop("disabled", true);
            $.ajax({
                url: API_URLS.tags,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({ name })
            }).done(function (res) {
                const tag = res.data;
                if (!tag?.id) {
                    return;
                }

                allTags.push(tag);
                allTags.sort((a, b) => String(a.name).localeCompare(String(b.name)));
                $("#purchaseTagsDropdown").jqxDropDownList({ source: allTags });
                addSelectedTag(Number(tag.id));
                $("#newTagNameInput").val("");
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to create tag.");
            }).always(function () {
                $("#createTagBtn").prop("disabled", false);
            });
        }

        function getCategoryPathLabel(categoryId) {
            const names = [];
            let current = categories.find(c => Number(c.id) === Number(categoryId));

            while (current) {
                names.unshift(String(current.name || "").trim());
                const parentId = current.parent_id ? Number(current.parent_id) : 0;
                current = parentId > 0
                    ? categories.find(c => Number(c.id) === parentId)
                    : null;
            }

            return names.join(" > ");
        }

        function getSelectedCategoryName() {
            const categoryId = Number($("#productCategorySelect").val() || 0);
            if (categoryId < 1) {
                return "";
            }

            const category = categories.find(c => Number(c.id) === categoryId);
            return category ? String(category.name || "").trim() : "";
        }

        function getNativeSelectLabel(selectId) {
            const $select = $(selectId);
            const value = String($select.val() || "").trim();
            if (value === "") {
                return "";
            }

            return String($select.find("option:selected").text() || "").trim();
        }

        function updateAutoProductName() {
            if (productNameManual) {
                return;
            }

            const department = String($("#productDepartmentSelect").val() || "").trim();
            const gender = getNativeSelectLabel("#productGenderSelect");
            const category = getSelectedCategoryName();
            const season = getNativeSelectLabel("#productSeasonSelect");

            if (!department || !gender || !category || !season) {
                if (!productNameManual) {
                    $("#productNameInput").val("");
                }
                return;
            }

            $("#productNameInput").val(`${gender} ${category} (${season})`);
        }

        function getSelectedDepartment() {
            return String($("#productDepartmentSelect").val() || "").trim();
        }

        function hideProductSearchDropdowns() {
            $("#productNameSearchDropdown, #productSerialSearchDropdown").addClass("d-none").empty();
        }

        function getProductSearchMeta(product) {
            const parts = [
                product.serial_number ? `SN: ${product.serial_number}` : "",
                product.department ? String(product.department) : "",
                product.gender ? String(product.gender) : "",
                product.season ? String(product.season) : ""
            ].filter(Boolean);

            return parts.join(" · ");
        }

        function renderProductSearchDropdown(dropdownId, products) {
            const $dropdown = $(dropdownId).empty();

            if (!products.length) {
                $dropdown
                    .append('<div class="list-group-item text-muted small">No matching products.</div>')
                    .removeClass("d-none");
                return;
            }

            products.forEach(function (product) {
                const $item = $(`
                    <button type="button" class="list-group-item list-group-item-action py-2">
                        <div class="fw-semibold">${escapeHtml(product.name || "")}</div>
                        <div class="small text-muted">${escapeHtml(getProductSearchMeta(product))}</div>
                    </button>
                `);
                $item.on("mousedown", function (event) {
                    event.preventDefault();
                    applyProductSelection(product);
                });
                $dropdown.append($item);
            });

            $dropdown.removeClass("d-none");
        }

        function searchProductsForField(field, searchTerm) {
            const term = String(searchTerm || "").trim();
            const dropdownId = field === "serial"
                ? "#productSerialSearchDropdown"
                : "#productNameSearchDropdown";
            const otherDropdownId = field === "serial"
                ? "#productNameSearchDropdown"
                : "#productSerialSearchDropdown";

            $(otherDropdownId).addClass("d-none").empty();

            if (term.length < 2) {
                $(dropdownId).addClass("d-none").empty();
                return;
            }

            if (productSearchRequest) {
                productSearchRequest.abort();
                productSearchRequest = null;
            }

            productSearchRequest = $.getJSON(API_URLS.products, {
                search: term,
                limit: 10
            }).done(function (res) {
                renderProductSearchDropdown(dropdownId, res.data || []);
            }).fail(function (_xhr, status) {
                if (status === "abort") {
                    return;
                }
                renderProductSearchDropdown(dropdownId, []);
            }).always(function () {
                productSearchRequest = null;
            });
        }

        function queueProductSearch(field) {
            if (productFillLock) {
                return;
            }

            clearTimeout(productSearchTimer);
            productSearchTimer = setTimeout(function () {
                const searchTerm = field === "serial"
                    ? $("#productSerialInput").val()
                    : $("#productNameInput").val();
                searchProductsForField(field, searchTerm);
            }, 300);
        }

        function applyProductSelection(product) {
            productFillLock = true;
            selectedExistingProductId = Number(product.id || 0);
            productNameManual = true;

            $("#productNameInput").val(String(product.name || ""));
            $("#productSerialInput").val(String(product.serial_number || ""));
            $("#productBrandInput").val(String(product.brand || ""));

            if (product.department) {
                $("#productDepartmentSelect").val(String(product.department));
            }
            populateCategorySelect();

            if (product.gender) {
                $("#productGenderSelect").val(String(product.gender));
            }
            if (product.season) {
                $("#productSeasonSelect").val(String(product.season));
            }
            if (product.category_id) {
                $("#productCategorySelect").val(String(product.category_id));
                selectedCategoryId = Number(product.category_id);
            }

            const currency = String(product.reference_currency || "USD").toUpperCase();
            const referenceCost = Number(product.reference_cost || 0);
            $("#unitPriceCurrency").val(currency);
            $("#unitPriceInput").val(referenceCost);

            hideProductSearchDropdowns();

            loadExchangeRateForCurrency(currency).always(function () {
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
                productFillLock = false;
            });
        }

        function appendProductRowToGrid(productId, rowData) {
            rowData.product_id = productId;
            $("#itemsGrid").jqxGrid("addrow", null, rowData);
            updateGridFooterTotals();
            clearProductFormPartial();
            setMessage("Product added to grid.", false);
        }

        function buildPurchaseGridRowData(params) {
            return {
                product_id: params.productId,
                product_variant_id: 0,
                product_name: params.name,
                sku: params.serialNumber,
                brand: params.brand,
                style: params.styleValue,
                warehouse_id: params.warehouseId,
                unit_price: Number(params.unitCost.toFixed(4)),
                unit_cost: Number(params.unitCost.toFixed(4)),
                reference_currency: params.selectedCurrency,
                reference_cost: Number(params.referenceUnitCost.toFixed(4)),
                original_cost_display: formatOriginalCost(params.referenceUnitCost, params.selectedCurrency),
                exchange_rate: params.exchangeRate > 0 ? params.exchangeRate : 1,
                reference_total_price: Number(params.referenceTotalPrice.toFixed(2)),
                size_value: formatSizeValueFromPack(params.sizePack),
                size_pack: JSON.stringify(params.sizePack),
                sets_count: params.setsCount,
                units_count: params.unitsCount,
                total_cost: Number(params.totalPrice.toFixed(2))
            };
        }

        function populateCategorySelect() {
            const $select = $("#productCategorySelect");
            const previousValue = String($select.val() || "");
            const department = getSelectedDepartment();

            $select.find("option:not(:first)").remove();

            if (department === "") {
                $select.prop("disabled", true).val("");
                selectedCategoryId = 0;
                return;
            }

            $select.prop("disabled", false);

            const filtered = categories.filter(row => {
                return String(row.department || "").trim() === department;
            });

            const sorted = [...filtered].sort((a, b) => {
                return getCategoryPathLabel(a.id).localeCompare(getCategoryPathLabel(b.id));
            });

            sorted.forEach(row => {
                const categoryId = Number(row.id || 0);
                if (categoryId < 1) {
                    return;
                }

                $select.append(
                    $("<option></option>")
                        .attr("value", categoryId)
                        .text(getCategoryPathLabel(categoryId))
                );
            });

            if (previousValue !== "" && $select.find(`option[value="${previousValue}"]`).length) {
                $select.val(previousValue);
            } else {
                $select.val("");
            }

            selectedCategoryId = Number($select.val() || 0);
        }

        function refreshItemsGrid() {
            const source = { localdata: items, datatype: "array" };
            $("#itemsGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
            updateGridFooterTotals();
        }

        function getRowTotalCost(row) {
            const unitCost = Number(row.unit_cost ?? row.unit_price ?? 0);
            const unitsCount = Math.max(Number(row.units_count || 0), 0);

            return Number((unitCost * unitsCount).toFixed(2));
        }

        function getRowMerchandiseTotal(row) {
            const unitPrice = Number(row.unit_price ?? row.unit_cost ?? 0);
            const unitsCount = Math.max(Number(row.units_count || 0), 0);

            return Number((unitPrice * unitsCount).toFixed(2));
        }

        function updateGridFooterTotals() {
            const totals = getGridTotals();
            $("#purchaseItemsCount").text(totals.totalCount);
            $("#itemsGridTotalsFooter").text(
                `Total Counts: ${totals.totalCount} | Sum Total Cost: ${totals.totalCostSum.toFixed(2)}`
            );
            recalcPurchaseTotals("items");
        }

        function getPurchaseBaseAmount() {
            const totals = getGridTotals();
            const shippingFee = Math.max(Number($("#shippingFeeInput").jqxNumberInput("val") || 0), 0);

            return {
                subTotal: totals.merchandiseSubTotal,
                shippingFee
            };
        }

        function allocatePurchaseItemCosts(rows, discountTotal, shippingFee) {
            const discount = Number(discountTotal || 0);
            const shipping = Math.max(0, Number(shippingFee || 0));
            const transferFee = 0;
            const weighted = rows.map(function (row) {
                const unitPrice = Number(row.unit_price ?? row.unit_cost ?? 0);
                const unitsCount = Math.max(Number(row.units_count || 0), 0);
                const lineWeight = getRowMerchandiseTotal(row);

                return {
                    row: row,
                    unitPrice: unitPrice,
                    unitsCount: unitsCount,
                    lineWeight: lineWeight
                };
            });
            const subTotal = weighted.reduce(function (sum, entry) {
                return sum + entry.lineWeight;
            }, 0);

            if (subTotal <= 0) {
                return [];
            }

            let allocatedDiscount = 0;
            let allocatedShipping = 0;
            let allocatedTransfer = 0;
            const lastIndex = weighted.length - 1;

            return weighted.map(function (entry, index) {
                const isLast = index === lastIndex;
                const ratio = entry.lineWeight / subTotal;
                const lineDiscount = isLast
                    ? Number((discount - allocatedDiscount).toFixed(4))
                    : Number((discount * ratio).toFixed(4));
                const lineShipping = isLast
                    ? Number((shipping - allocatedShipping).toFixed(4))
                    : Number((shipping * ratio).toFixed(4));
                const lineTransfer = isLast
                    ? Number((transferFee - allocatedTransfer).toFixed(4))
                    : Number((transferFee * ratio).toFixed(4));

                allocatedDiscount += lineDiscount;
                allocatedShipping += lineShipping;
                allocatedTransfer += lineTransfer;

                const perUnitDiscount = entry.unitsCount > 0
                    ? Number((lineDiscount / entry.unitsCount).toFixed(4))
                    : 0;
                const perUnitShipping = entry.unitsCount > 0
                    ? Number((lineShipping / entry.unitsCount).toFixed(4))
                    : 0;
                const perUnitTransfer = entry.unitsCount > 0
                    ? Number((lineTransfer / entry.unitsCount).toFixed(4))
                    : 0;
                const unitCost = Number((
                    entry.unitPrice
                    - perUnitDiscount
                    + perUnitShipping
                    + perUnitTransfer
                ).toFixed(4));
                const sizePack = parseSizePack(entry.row);
                const setsCount = Math.max(Number(entry.row.sets_count || 0), 0);
                const sizeQtys = {};
                const sizes = Object.keys(sizePack)
                    .filter(function (size) {
                        return Math.max(Number(sizePack[size] || 0), 0) > 0;
                    })
                    .sort(function (a, b) { return Number(a) - Number(b); });
                sizes.forEach(function (size) {
                    sizeQtys[size] = Math.max(Number(sizePack[size] || 0), 0) * setsCount;
                });
                const lineTotal = Number((unitCost * entry.unitsCount).toFixed(2));

                return {
                    product_id: Number(entry.row.product_id || 0),
                    sizes: sizes,
                    size_qtys: sizeQtys,
                    sets_count: setsCount,
                    qty: Math.max(Number(entry.row.units_count || 0), 0),
                    warehouse_id: Number(entry.row.warehouse_id || 0),
                    unit_price: entry.unitPrice,
                    unit_cost: unitCost,
                    allocated_discount: perUnitDiscount,
                    allocated_shipping: perUnitShipping,
                    allocated_transfer_fee: perUnitTransfer,
                    line_total: lineTotal,
                    reference_currency: String(entry.row.reference_currency || "USD"),
                    reference_cost: Number(entry.row.reference_cost || entry.unitPrice || 0),
                    exchange_rate: Number(entry.row.exchange_rate || 1),
                    discount_amount: 0,
                    style: String(entry.row.style || "")
                };
            });
        }

        function applyAllocatedCostsToGrid(rows, allocated) {
            rows.forEach(function (row, index) {
                const item = allocated[index];
                if (!item) {
                    return;
                }
                const rowId = $("#itemsGrid").jqxGrid("getrowid", index);
                $("#itemsGrid").jqxGrid("setcellvalue", rowId, "unit_cost", item.unit_cost);
                $("#itemsGrid").jqxGrid("setcellvalue", rowId, "total_cost", getRowTotalCost({
                    unit_cost: item.unit_cost,
                    units_count: row.units_count
                }));
            });
        }

        function recalcPurchaseTotals(changedField) {
            if (totalsCalcLock) {
                return;
            }

            const { subTotal, shippingFee } = getPurchaseBaseAmount();
            const rows = $("#itemsGrid").jqxGrid("getrows") || [];
            totalsCalcLock = true;

            let discount = Number($("#discountTotalInput").jqxNumberInput("val") || 0);
            let paid = Number($("#paidAmountInput").jqxNumberInput("val") || 0);

            if (changedField === "items") {
                paid = subTotal;
                discount = 0;
                $("#paidAmountInput").jqxNumberInput("val", paid);
                $("#discountTotalInput").jqxNumberInput("val", discount);
            } else if (changedField === "paid") {
                discount = Number((subTotal - paid).toFixed(2));
                $("#discountTotalInput").jqxNumberInput("val", discount);
            } else if (changedField === "discount") {
                paid = Number((subTotal - discount).toFixed(2));
                $("#paidAmountInput").jqxNumberInput("val", paid);
            }

            if (rows.length > 0) {
                const allocated = allocatePurchaseItemCosts(rows, discount, shippingFee);
                applyAllocatedCostsToGrid(rows, allocated);
            }

            const gridTotals = getGridTotals();
            const grandTotal = Number((Math.max(0, paid) + shippingFee).toFixed(2));

            $("#subTotalDisplay").text(subTotal.toFixed(2));
            $("#referenceSubTotalDisplay").text(gridTotals.referenceTotalSum.toFixed(2));
            $("#referenceSubTotalLabel").text(gridTotals.referenceCurrency);
            $("#grandTotalDisplay").text(grandTotal.toFixed(2));
            $("#itemsGridTotalsFooter").text(
                `Total Counts: ${gridTotals.totalCount} | Sum Total Cost: ${gridTotals.totalCostSum.toFixed(2)}`
            );

            totalsCalcLock = false;

            return {
                subTotal,
                totalCostSum: gridTotals.totalCostSum,
                referenceSubTotal: gridTotals.referenceTotalSum,
                referenceCurrency: gridTotals.referenceCurrency,
                shippingFee,
                discount,
                paid,
                grandTotal
            };
        }

        function getGridTotals() {
            const rows = $("#itemsGrid").jqxGrid("getrows") || [];
            const currencies = new Set(
                rows.map(r => String(r.reference_currency || "USD").toUpperCase()).filter(Boolean)
            );
            const referenceCurrency = currencies.size === 1 ? Array.from(currencies)[0] : "—";

            return {
                totalCount: rows.length,
                totalUnitsCount: rows.reduce((sum, r) => sum + Number(r.units_count || 0), 0),
                merchandiseSubTotal: rows.reduce((sum, r) => sum + getRowMerchandiseTotal(r), 0),
                totalCostSum: rows.reduce((sum, r) => sum + getRowTotalCost(r), 0),
                referenceTotalSum: rows.reduce((sum, r) => sum + Number(r.reference_total_price || 0), 0),
                referenceCurrency
            };
        }

        function submitPurchase(payload) {
            $.ajax({
                url: API_URLS.purchases,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                setMessage(res.message || "Purchase saved.", false);
                setTimeout(function () {
                    window.location.href = "<?= site_url('purchases') ?>";
                }, 500);
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.error
                    ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                    : (xhr.responseJSON?.message || "Failed to save purchase.");
                setMessage(msg, true);
            });
        }

        function applySupplierCurrency(supplierId) {
            const supplier = suppliers.find(row => Number(row.id) === Number(supplierId));
            const currency = String(supplier?.default_currency || "USD").toUpperCase();
            $("#unitPriceCurrency").val(currency);
            loadExchangeRateForCurrency(currency);
        }

        function loadSuppliers() {
            return $.getJSON(API_URLS.suppliers).done(function(res) {
                suppliers = res.data || [];
                $("#supplierDropdown").jqxDropDownList({ source: suppliers });
                $("#supplierDropdown").off("select").on("select", function () {
                    const item = $("#supplierDropdown").jqxDropDownList("getSelectedItem");
                    if (item) {
                        applySupplierCurrency(Number(item.value));
                    }
                });
                if (suppliers.length === 0) {
                    setMessage("No suppliers found. Please add supplier data first.");
                }
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load suppliers.";
                setMessage(msg);
            });
        }

        function loadCategories() {
            return $.getJSON(API_URLS.categories).done(function(res) {
                categories = res.data || [];
                populateCategorySelect();
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load categories.";
                setMessage(msg);
            });
        }

        function loadWarehouses() {
            return $.getJSON(API_URLS.warehouses).done(function(res) {
                warehouses = res.data || [];
                $("#purchaseWarehouseDropdown").jqxDropDownList({ source: warehouses });
                if (warehouses.length > 0) {
                    $("#purchaseWarehouseDropdown").jqxDropDownList("selectIndex", 0);
                }
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load warehouses.";
                setMessage(msg);
            });
        }

        function clearProductForm() {
            productNameManual = false;
            selectedExistingProductId = 0;
            hideProductSearchDropdowns();
            $("#productDepartmentSelect").val("");
            $("#productGenderSelect").val("");
            $("#productSeasonSelect").val("");
            selectedCategoryId = 0;
            populateCategorySelect();
            $("#productNameInput").val("");
            $("#productSerialInput").val("");
            $("#productBrandInput").val("");
            $("#productStyleInput").val("");
            $("#unitPriceInput").val(0);
            resetSizePackSelection();
            if ($("#setsCountInput").data("jqxNumberInput")) {
                $("#setsCountInput").jqxNumberInput("val", 0);
            }
            recalcTotalUnits();
        }

        function clearProductFormPartial() {
            // $("#productNameInput").val("");
            selectedExistingProductId = 0;
            hideProductSearchDropdowns();
            $("#productSerialInput").val("");
            $("#productBrandInput").val("");
            $("#productStyleInput").val("");
            $("#unitPriceInput").val(0);
            recalcTotalUnits();
        }

        function recalcTotalUnits() {
            const totals = getSizePackTotals();
            $("#totalUnitsInput").jqxNumberInput("val", totals.totalUnits);
            recalcTotalPrice();
        }

        function recalcTotalPrice() {
            const totalUnits = Number($("#totalUnitsInput").jqxNumberInput("val") || 0);
            const usdUnitPrice = getUsdUnitPrice();
            const totalPrice = Number((Math.max(usdUnitPrice, 0) * Math.max(totalUnits, 0)).toFixed(2));
            $("#totalPriceInput").jqxNumberInput("val", totalPrice);
            updateUnitPriceCurrencyUi();
        }

        function addProductToGrid() {
            const department = String($("#productDepartmentSelect").val() || "").trim();
            const gender = String($("#productGenderSelect").val() || "").trim();
            const season = String($("#productSeasonSelect").val() || "").trim();
            const name = String($("#productNameInput").val() || "").trim();
            const serialNumber = String($("#productSerialInput").val() || "").trim();
            const brand = String($("#productBrandInput").val() || "").trim();
            const styleValue = String($("#productStyleInput").val() || "").trim();
            const checkedSizes = getCheckedSizes();
            const sizePack = buildSizePackObject();
            const packTotals = getSizePackTotals();
            const setsCount = packTotals.setsCount;
            const unitsCount = packTotals.totalUnits;
            const selectedCurrency = getSelectedUnitCurrency();
            if (selectedCurrency !== "USD" && getExchangeRateForCurrency(selectedCurrency) <= 0) {
                setMessage("Please set exchange rate.");
                openExchangeRateModal();
                return;
            }
            const referenceUnitCost = Number($("#unitPriceInput").val() || 0);
            const unitCost = getUsdUnitPrice();
            const exchangeRate = getExchangeRateForCurrency(selectedCurrency);
            const totalPrice = Number((unitCost * unitsCount).toFixed(2));
            const referenceTotalPrice = Number((referenceUnitCost * unitsCount).toFixed(2));
            const selectedWarehouse = $("#purchaseWarehouseDropdown").jqxDropDownList("getSelectedItem");
            const warehouseId = Number(selectedWarehouse?.value || 0);

            if (!department) {
                setMessage("Department is required.");
                return;
            }
            if (!gender) {
                setMessage("Gender is required.");
                return;
            }
            if (!season) {
                setMessage("Season is required.");
                return;
            }
            selectedCategoryId = Number($("#productCategorySelect").val() || 0);
            if (!selectedCategoryId) {
                setMessage("Category is required.");
                return;
            }
            if (!name) {
                setMessage("Product name is required.");
                return;
            }
            if (!serialNumber) {
                setMessage("Serial number is required.");
                return;
            }
            if (referenceUnitCost <= 0) {
                setMessage("Unit price is required before adding the product.");
                return;
            }
            if (unitCost <= 0) {
                setMessage("Please set a valid unit price and exchange rate.");
                return;
            }
            if (checkedSizes.length === 0) {
                setMessage("Please select at least one size.");
                return;
            }
            if (packTotals.totalPerSet <= 0) {
                setMessage("Units per set must be greater than 0 for at least one size.");
                return;
            }
            if (!warehouseId) {
                setMessage("Please select warehouse.");
                return;
            }
            if (setsCount <= 0) {
                setMessage("Sets count must be greater than 0.");
                return;
            }

            $("#addProductsBtn").prop("disabled", true);

            const rowParams = {
                productId: selectedExistingProductId,
                name,
                serialNumber,
                brand,
                styleValue,
                warehouseId,
                unitCost,
                selectedCurrency,
                referenceUnitCost,
                exchangeRate,
                referenceTotalPrice,
                sizePack,
                setsCount,
                unitsCount,
                totalPrice
            };
            const rowData = buildPurchaseGridRowData(rowParams);

            if (selectedExistingProductId > 0) {
                appendProductRowToGrid(selectedExistingProductId, rowData);
                $("#addProductsBtn").prop("disabled", false);
                return;
            }

            $.ajax({
                url: API_URLS.products,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    name,
                    serial_number: serialNumber,
                    category_id: Number(selectedCategoryId),
                    brand: brand || null,
                    department,
                    gender,
                    season,
                    tags: getSelectedTagIds(),
                    reference_currency: selectedCurrency,
                    reference_cost: referenceUnitCost
                })
            }).done(function (res) {
                const createdId = Number(res.data?.id || 0);
                if (createdId < 1) {
                    setMessage("Failed to register product.");
                    return;
                }

                appendProductRowToGrid(createdId, rowData);
                setMessage("Product registered and added to grid.", false);
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to register product.");
            }).always(function () {
                $("#addProductsBtn").prop("disabled", false);
            });
        }

        function removeSelectedRow() {
            const index = $("#itemsGrid").jqxGrid("getselectedrowindex");
            if (index === -1) {
                setMessage("Select an item row to remove.");
                return;
            }
            const rowId = $("#itemsGrid").jqxGrid("getrowid", index);
            $("#itemsGrid").jqxGrid("deleterow", rowId);
            updateGridFooterTotals();
        }

        function getValidItems() {
            const rows = ($("#itemsGrid").jqxGrid("getrows") || [])
                .filter(function (r) {
                    return Number(r.product_id) > 0 && Number(r.sets_count) > 0;
                });
            const discount = Number($("#discountTotalInput").jqxNumberInput("val") || 0);
            const shipping = Math.max(Number($("#shippingFeeInput").jqxNumberInput("val") || 0), 0);

            return allocatePurchaseItemCosts(rows, discount, shipping);
        }

        function loadPaymentMethods() {
            return $.getJSON(API_URLS.paymentMethods).done(function (res) {
                paymentMethods = (res.data || []).filter(row => Number(row.is_active ?? 1) === 1);
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load payment methods.");
            });
        }

        function buildPaymentMethodOptions(selectedCode) {
            const options = paymentMethods.map(row => {
                const code = String(row.code || "");
                const selected = code === selectedCode ? " selected" : "";
                return `<option value="${escapeHtml(code)}"${selected}>${escapeHtml(row.name || code)}</option>`;
            }).join("");

            if (options === "") {
                return `
                    <option value="cash"${selectedCode === "cash" ? " selected" : ""}>Cash</option>
                    <option value="bank_transfer"${selectedCode === "bank_transfer" ? " selected" : ""}>Bank Transfer</option>
                `;
            }

            return options;
        }

        function getConfirmPaymentRowsData() {
            const rows = [];
            $("#confirmPaymentRows .confirm-payment-row").each(function () {
                const method = String($(this).find(".confirm-payment-method").val() || "").trim();
                const amount = Number($(this).find(".confirm-payment-amount").val() || 0);
                if (method !== "" && amount > 0) {
                    rows.push({ payment_method: method, amount: Number(amount.toFixed(2)) });
                }
            });
            return rows;
        }

        function redistributePaymentRemainder() {
            const grandTotal = Number($("#confirmGrandTotal").text() || 0);
            const $rowEls = $("#confirmPaymentRows .confirm-payment-row");
            let allocated = 0;

            $rowEls.each(function (index) {
                const $amount = $(this).find(".confirm-payment-amount");
                const amount = Number($amount.val() || 0);
                allocated += amount;

                if (index === $rowEls.length - 1) {
                    const remaining = Number((grandTotal - allocated + amount).toFixed(2));
                    if (remaining >= 0 && Math.abs(remaining - amount) > 0.009) {
                        $amount.val(remaining.toFixed(2));
                    }
                }
            });
        }

        function renderConfirmPaymentRows(grandTotal) {
            const $container = $("#confirmPaymentRows").empty();
            const rows = confirmPaymentRows.length > 0
                ? confirmPaymentRows
                : [{ payment_method: "cash", amount: grandTotal }];

            rows.forEach(function (row, index) {
                const rowEl = $(`
                    <div class="confirm-payment-row d-flex gap-2 align-items-center">
                        <select class="form-select confirm-payment-method" style="max-width: 180px;">
                            ${buildPaymentMethodOptions(String(row.payment_method || "cash"))}
                        </select>
                        <input type="number" class="form-control confirm-payment-amount text-end" min="0" step="0.01" value="${Number(row.amount || 0).toFixed(2)}">
                        <button type="button" class="btn btn-sm btn-outline-danger confirm-remove-payment-row"${index === 0 ? " disabled" : ""}>Remove</button>
                    </div>
                `);
                $container.append(rowEl);
            });

            $container.off("input", ".confirm-payment-amount").on("input", ".confirm-payment-amount", function () {
                const $rows = $("#confirmPaymentRows .confirm-payment-row");
                const idx = $rows.index($(this).closest(".confirm-payment-row"));
                const grand = Number($("#confirmGrandTotal").text() || 0);
                let allocated = 0;

                $rows.each(function (rowIndex) {
                    const amount = Number($(this).find(".confirm-payment-amount").val() || 0);
                    allocated += amount;
                    if (rowIndex === idx && rowIndex < $rows.length - 1) {
                        const remaining = Number((grand - allocated).toFixed(2));
                        const $nextAmount = $rows.eq(rowIndex + 1).find(".confirm-payment-amount");
                        if (remaining > 0) {
                            $nextAmount.val(remaining.toFixed(2));
                        }
                    }
                });
            });

            $container.off("click", ".confirm-remove-payment-row").on("click", ".confirm-remove-payment-row", function () {
                $(this).closest(".confirm-payment-row").remove();
                redistributePaymentRemainder();
            });
        }

        function savePurchase() {
            const selectedSupplier = $("#supplierDropdown").jqxDropDownList("getSelectedItem");
            const supplierId = selectedSupplier ? Number(selectedSupplier.value) : 0;
            if (!supplierId) {
                setMessage("Please select a supplier.");
                return;
            }

            const validItems = getValidItems();
            if (validItems.length === 0) {
                setMessage("Supplier and at least 1 item are required.");
                return;
            }

            const purchaseDate = $("#purchaseDate").jqxDateTimeInput("getText");
            if (!purchaseDate) {
                setMessage("Purchase date is required.");
                return;
            }

            const { subTotal, shippingFee } = getPurchaseBaseAmount();
            const totalsSummary = recalcPurchaseTotals("discount");
            const totals = getGridTotals();
            const payload = {
                supplier_id: supplierId,
                purchase_date: purchaseDate,
                notes: $("#notesInput").val(),
                transfer_fee: 0,
                shipping_fee: shippingFee,
                discount_total: totalsSummary.discount,
                paid_total: totalsSummary.paid,
                payment_method: "cash",
                items: validItems
            };

            $("#confirmTotalUnitsCount").text(totals.totalUnitsCount);
            $("#confirmReferenceSubTotal").text(totalsSummary.referenceSubTotal.toFixed(2));
            $("#confirmRefCurrencyLabel").text(totalsSummary.referenceCurrency);
            $("#confirmTotalPriceSum").text(subTotal.toFixed(2));
            $("#confirmShippingFee").text(shippingFee.toFixed(2));
            $("#confirmDiscount").text(totalsSummary.discount.toFixed(2));
            $("#confirmPaidAmount").text(totalsSummary.paid.toFixed(2));
            $("#confirmGrandTotal").text(totalsSummary.grandTotal.toFixed(2));

            confirmPaymentRows = [{ payment_method: "cash", amount: totalsSummary.grandTotal }];
            renderConfirmPaymentRows(totalsSummary.grandTotal);

            $("#confirmSavePurchaseBtn").off("click").on("click", function () {
                const confirmGrandTotal = Number($("#confirmGrandTotal").text() || 0);
                const confirmPaid = Number($("#confirmPaidAmount").text() || 0);
                const confirmDiscount = Number($("#confirmDiscount").text() || 0);
                const payments = getConfirmPaymentRowsData();
                const paymentSum = payments.reduce((sum, row) => sum + row.amount, 0);
                if (Math.abs(paymentSum - confirmGrandTotal) > 0.01) {
                    setMessage("Payment amounts must equal the grand total.");
                    return;
                }

                payload.paid_total = confirmPaid;
                payload.discount_total = confirmDiscount;
                payload.payments = payments;
                payload.payment_method = payments[0]?.payment_method || "cash";

                if (confirmPurchaseModal) {
                    confirmPurchaseModal.hide();
                }
                submitPurchase(payload);
            });
            if (confirmPurchaseModal) {
                confirmPurchaseModal.show();
            }
        }

        $(function() {
            initWidgets();
            loadSuppliers();
            loadCategories();
            loadWarehouses();
            loadTags();
            loadCurrencies();
            loadPaymentMethods();
            confirmPurchaseModal = new bootstrap.Modal(document.getElementById("purchaseConfirmModal"));
            exchangeRateModal = new bootstrap.Modal(document.getElementById("exchangeRateModal"));

            $("#unitPriceCurrency").on("change", function () {
                loadExchangeRateForCurrency(getSelectedUnitCurrency());
            });
            $("#unitPriceRateLink").on("click keydown", function (event) {
                if (event.type === "keydown" && event.key !== "Enter" && event.key !== " ") {
                    return;
                }
                event.preventDefault();
                openExchangeRateModal();
            });
            $("#saveExchangeRateBtn").on("click", saveExchangeRateFromModal);
            $("#exchangeRateInput").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    saveExchangeRateFromModal();
                }
            });

            $("#productDepartmentSelect").on("change", function () {
                populateCategorySelect();
                updateAutoProductName();
            });
            $("#productGenderSelect, #productSeasonSelect, #productCategorySelect").on("change", function () {
                selectedCategoryId = Number($("#productCategorySelect").val() || 0);
                updateAutoProductName();
            });
            $("#productNameInput").on("input", function () {
                if (!productFillLock) {
                    productNameManual = true;
                    selectedExistingProductId = 0;
                    queueProductSearch("name");
                }
            });
            $("#productSerialInput").on("input", function () {
                if (!productFillLock) {
                    selectedExistingProductId = 0;
                    queueProductSearch("serial");
                }
            });
            $("#productNameInput, #productSerialInput").on("focus", function () {
                const field = this.id === "productSerialInput" ? "serial" : "name";
                queueProductSearch(field);
            });
            $(document).on("mousedown", function (event) {
                if ($(event.target).closest("#productNameInput, #productSerialInput, #productNameSearchDropdown, #productSerialSearchDropdown").length) {
                    return;
                }
                hideProductSearchDropdowns();
            });

            $("#sizeSelector").on("checkChange", function () {
                renderSizePackTable();
            });

            $("#sizePackTableBody").on("click", ".size-pack-minus", function () {
                const size = String($(this).closest("tr").data("size") || "");
                setSizePackUnits(size, Math.max(Number(sizePackUnits[size] || 0) - 1, 0));
            });
            $("#sizePackTableBody").on("click", ".size-pack-plus", function () {
                const size = String($(this).closest("tr").data("size") || "");
                setSizePackUnits(size, Math.max(Number(sizePackUnits[size] || 0) + 1, 0));
            });
            $("#sizePackTableBody").on("input change", ".size-pack-qty-input", function () {
                const size = String($(this).closest("tr").data("size") || "");
                setSizePackUnits(size, Math.max(Number($(this).val() || 0), 0));
            });
            $(".size-pack-template-btn").on("click", function () {
                applySizePackTemplate(String($(this).data("template") || ""));
            });

            $("#setsCountInput").on("valueChanged", function () {
                $("#sizePackTableBody tr").each(function () {
                    updateSizePackRowTotal($(this));
                });
                updateSizePackFooter();
            });
            $("#unitPriceInput").on("input change", function () {
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
            });
            $("#shippingFeeInput").on("valueChanged", function () {
                recalcPurchaseTotals("shipping");
            });
            $("#discountTotalInput").on("valueChanged", function () {
                recalcPurchaseTotals("discount");
            });
            $("#paidAmountInput").on("valueChanged", function () {
                recalcPurchaseTotals("paid");
            });

            $("#deleteSelectedGridRowBtn").on("click", removeSelectedRow);
            $("#addProductsBtn").on("click", addProductToGrid);
            $("#createTagBtn").on("click", createTagFromInput);
            $("#newTagNameInput").on("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    createTagFromInput();
                }
            });
            recalcPurchaseTotals("items");
            $("#savePurchaseBtn").on("click", savePurchase);
            $("#addPaymentRowBtn").on("click", function () {
                const payments = getConfirmPaymentRowsData();
                const grandTotal = Number($("#confirmGrandTotal").text() || 0);
                const allocated = payments.reduce((sum, row) => sum + row.amount, 0);
                const remaining = Number(Math.max(0, grandTotal - allocated).toFixed(2));
                confirmPaymentRows = payments;
                confirmPaymentRows.push({
                    payment_method: paymentMethods[1]?.code || "bank_transfer",
                    amount: remaining
                });
                renderConfirmPaymentRows(grandTotal);
            });
        });
</script>
<?= $this->endSection() ?>
