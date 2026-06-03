<?php
use App\Models\AccountModel;
?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Accounts<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Accounts</h1>
        <p class="text-muted mb-0">Manage chart of accounts for ledger transactions.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Account List</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Code</th>
                                    <th>Name</th>
                                    <th style="width: 120px;">Type</th>
                                    <th style="width: 140px;">Tags</th>
                                    <th style="width: 80px;">Currency</th>
                                    <th style="width: 90px;">Active</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="accountsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Account</h2>
                    <form id="accountForm" class="row g-3">
                        <input type="hidden" id="accountId" value="">

                        <div class="col-12">
                            <label class="form-label">Code</label>
                            <input id="codeInput" type="text" class="form-control" maxlength="20" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input id="nameInput" type="text" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Currency</label>
                            <select id="currencyCodeInput" class="form-select" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tags</label>
                            <div id="tagsInputGroup" class="border rounded p-2 d-flex flex-wrap gap-2"></div>
                            <div class="form-text">Select one or more tags.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Type</label>
                            <select id="accountTypeInput" class="form-select" required>
                                <option value="ASSET">Asset</option>
                                <option value="LIABILITY">Liability</option>
                                <option value="EQUITY">Equity</option>
                                <option value="REVENUE">Revenue</option>
                                <option value="EXPENSE">Expense</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input id="isActiveInput" class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label" for="isActiveInput">Active</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                            <button type="button" class="btn btn-outline-secondary" id="cancelEditBtn">Cancel Edit</button>
                        </div>
                        <div class="col-12">
                            <div id="messageBox" class="small fw-semibold"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const ACCOUNTS_API_URL = "<?= site_url('api/accounts') ?>";
    const CURRENCIES_API_URL = "<?= site_url('api/currencies') ?>";
    const ACCOUNT_TAGS = <?= json_encode(AccountModel::ACCOUNT_TAGS) ?>;
    const DEFAULT_ACCOUNT_TAG = <?= json_encode(AccountModel::DEFAULT_ACCOUNT_TAG) ?>;
    let currenciesList = [];
    let accountTags = ACCOUNT_TAGS.slice();

    function normalizeTagsList(raw) {
        if (Array.isArray(raw)) {
            return raw.filter(function (t) { return String(t || "").trim() !== ""; });
        }
        if (raw) {
            return [String(raw)];
        }
        return [DEFAULT_ACCOUNT_TAG];
    }

    function renderTagCheckboxes(selectedTags) {
        const selected = new Set(normalizeTagsList(selectedTags));
        const group = $("#tagsInputGroup");
        group.empty();
        accountTags.forEach(function (tag) {
            const id = "accountTag_" + tag.replace(/[^A-Za-z0-9]/g, "_");
            const checked = selected.has(tag) ? " checked" : "";
            group.append(
                `<div class="form-check form-check-inline mb-0">
                    <input class="form-check-input account-tag-check" type="checkbox" id="${id}" value="${tag}"${checked}>
                    <label class="form-check-label" for="${id}">${tag}</label>
                </div>`
            );
        });
    }

    function getSelectedTags() {
        return $(".account-tag-check:checked").map(function () {
            return String($(this).val() || "");
        }).get();
    }

    function formatTagsDisplay(tags) {
        const list = normalizeTagsList(tags);
        if (!list.length) {
            return `<span class="badge bg-secondary">${DEFAULT_ACCOUNT_TAG}</span>`;
        }
        return list.map(function (tag) {
            return `<span class="badge bg-secondary me-1">${tag}</span>`;
        }).join("");
    }

    function populateCurrencySelect(selectedCode) {
        const select = $("#currencyCodeInput");
        select.empty();
        if (!currenciesList.length) {
            select.append('<option value="">No currencies</option>');
            return;
        }
        currenciesList.forEach(c => {
            const code = c.code || "";
            select.append(`<option value="${code}">${code} — ${c.name || ""}</option>`);
        });
        const code = selectedCode || (currenciesList[0]?.code || "");
        if (code) {
            select.val(code);
        }
    }

    function loadCurrencies() {
        return $.getJSON(CURRENCIES_API_URL)
            .done(function(res) {
                currenciesList = res.data || [];
                populateCurrencySelect();
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load currencies.", true);
            });
    }

    function resetForm() {
        $("#accountId").val("");
        $("#codeInput").val("").prop("readonly", false);
        $("#nameInput").val("");
        $("#accountTypeInput").val("ASSET");
        renderTagCheckboxes([DEFAULT_ACCOUNT_TAG]);
        populateCurrencySelect();
        $("#isActiveInput").prop("checked", true);
        $("#formTitle").text("New Account");
        $("#saveBtn").text("Save");
    }

    function renderRows(rows) {
        const tbody = $("#accountsTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center text-muted">No accounts found.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const active = Number(row.is_active) === 1;
            const tr = $("<tr>");
            tr.append(`<td>${row.code || ""}</td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            tr.append(`<td>${row.account_type || ""}</td>`);
            tr.append(`<td>${formatTagsDisplay(row.tags)}</td>`);
            tr.append(`<td>${row.currency_code || ""}</td>`);
            tr.append(`<td>${active ? "Yes" : "No"}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="${row.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadAccounts() {
        $.getJSON(ACCOUNTS_API_URL)
            .done(function(res) {
                if (Array.isArray(res.meta?.tags) && res.meta.tags.length) {
                    accountTags = res.meta.tags;
                }
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load accounts.", true);
            });
    }

    function getPayload() {
        return {
            code: $("#codeInput").val().trim(),
            name: $("#nameInput").val().trim(),
            account_type: $("#accountTypeInput").val(),
            tags: getSelectedTags(),
            currency_code: $("#currencyCodeInput").val(),
            is_active: $("#isActiveInput").is(":checked") ? 1 : 0
        };
    }

    function editAccount(id) {
        $.getJSON(`${ACCOUNTS_API_URL}/${id}`)
            .done(function(res) {
                const row = res.data;
                $("#accountId").val(row.id);
                $("#codeInput").val(row.code || "").prop("readonly", true);
                $("#nameInput").val(row.name || "");
                $("#accountTypeInput").val(row.account_type || "ASSET");
                renderTagCheckboxes(row.tags || row.tag || [DEFAULT_ACCOUNT_TAG]);
                populateCurrencySelect(row.currency_code || "");
                $("#isActiveInput").prop("checked", Number(row.is_active) === 1);
                $("#formTitle").text("Edit Account");
                $("#saveBtn").text("Update");
                setMessage("");
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load account data.", true);
            });
    }

    function deleteAccount(id) {
        if (!confirm("Delete this account?")) {
            return;
        }

        $.ajax({
            url: `${ACCOUNTS_API_URL}/${id}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Account deleted.");
            loadAccounts();
            resetForm();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete account.", true);
        });
    }

    $(function() {
        renderTagCheckboxes([DEFAULT_ACCOUNT_TAG]);
        loadCurrencies().always(function() {
            loadAccounts();
            resetForm();
        });

        $("#accountForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#accountId").val() || 0);
            const payload = getPayload();
            const isEdit = id > 0;

            if (!isEdit && !/^[A-Za-z0-9_-]{1,20}$/.test(payload.code)) {
                setMessage("Account code must be 1-20 characters (letters, numbers, underscore, hyphen).", true);
                return;
            }
            if (!payload.name) {
                setMessage("Account name is required.", true);
                return;
            }

            if (!payload.currency_code) {
                setMessage("Currency is required.", true);
                return;
            }
            if (!payload.tags.length) {
                setMessage("Select at least one tag.", true);
                return;
            }

            const body = isEdit
                ? {
                    name: payload.name,
                    account_type: payload.account_type,
                    tags: payload.tags,
                    currency_code: payload.currency_code,
                    is_active: payload.is_active
                }
                : payload;

            $.ajax({
                url: isEdit ? `${ACCOUNTS_API_URL}/${id}` : ACCOUNTS_API_URL,
                method: isEdit ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(body)
            }).done(function(res) {
                setMessage(res.message || (isEdit ? "Account updated." : "Account created."));
                loadAccounts();
                resetForm();
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save account.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
            setMessage("");
        });

        $(document).on("click", ".edit-btn", function() {
            editAccount($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteAccount($(this).data("id"));
        });
    });
</script>
<?= $this->endSection() ?>
