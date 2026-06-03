<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Payment Methods<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Payment Methods</h1>
        <p class="text-muted mb-0">Manage payment options for purchases and sales.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Payment Method List</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">Code</th>
                                    <th>Name</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th style="width: 80px;">Active</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="paymentMethodsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Payment Method</h2>
                    <form id="paymentMethodForm" class="row g-3">
                        <input type="hidden" id="paymentMethodId" value="">

                        <div class="col-12">
                            <label class="form-label">Code</label>
                            <input id="codeInput" type="text" class="form-control" maxlength="50" required>
                            <div class="form-text">Lowercase, e.g. cash, bank_transfer</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input id="nameInput" type="text" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ledger Account</label>
                            <select id="accountIdInput" class="form-select">
                                <option value="">— None —</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input id="descriptionInput" type="text" class="form-control" maxlength="255">
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
    const PAYMENT_METHODS_API_URL = "<?= site_url('api/payment-methods') ?>";
    const ACCOUNTS_API_URL = "<?= site_url('api/accounts') ?>";
    let accountsList = [];

    function populateAccountSelect(selectedId) {
        const select = $("#accountIdInput");
        select.find("option:not(:first)").remove();
        accountsList.forEach(a => {
            const id = Number(a.id);
            const label = `${a.code || ""} — ${a.name || ""}`;
            select.append(`<option value="${id}">${label}</option>`);
        });
        if (selectedId) {
            select.val(String(selectedId));
        } else {
            select.val("");
        }
    }

    function loadAccounts() {
        return $.getJSON(ACCOUNTS_API_URL).done(function(res) {
            accountsList = (res.data || []).filter(a => Number(a.is_active) === 1);
            populateAccountSelect();
        });
    }

    function resetForm() {
        $("#paymentMethodId").val("");
        $("#codeInput").val("").prop("readonly", false);
        $("#nameInput").val("");
        $("#descriptionInput").val("");
        populateAccountSelect();
        $("#isActiveInput").prop("checked", true);
        $("#formTitle").text("New Payment Method");
        $("#saveBtn").text("Save");
    }

    function renderRows(rows) {
        const tbody = $("#paymentMethodsTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No payment methods found.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const active = Number(row.is_active) === 1;
            const tr = $("<tr>");
            tr.append(`<td><code>${row.code || ""}</code></td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            const accountLabel = row.account_code
                ? `${row.account_code} — ${row.account_name || ""}`
                : "";
            tr.append(`<td>${accountLabel}</td>`);
            tr.append(`<td>${row.description || ""}</td>`);
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

    function loadPaymentMethods() {
        $.getJSON(PAYMENT_METHODS_API_URL)
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load payment methods.", true);
            });
    }

    function getPayload() {
        return {
            code: $("#codeInput").val().trim().toLowerCase(),
            name: $("#nameInput").val().trim(),
            description: $("#descriptionInput").val().trim(),
            account_id: Number($("#accountIdInput").val() || 0) || null,
            is_active: $("#isActiveInput").is(":checked") ? 1 : 0
        };
    }

    function editPaymentMethod(id) {
        $.getJSON(`${PAYMENT_METHODS_API_URL}/${id}`)
            .done(function(res) {
                const row = res.data;
                $("#paymentMethodId").val(row.id);
                $("#codeInput").val(row.code || "").prop("readonly", true);
                $("#nameInput").val(row.name || "");
                $("#descriptionInput").val(row.description || "");
                populateAccountSelect(row.account_id ? Number(row.account_id) : "");
                $("#isActiveInput").prop("checked", Number(row.is_active) === 1);
                $("#formTitle").text("Edit Payment Method");
                $("#saveBtn").text("Update");
                setMessage("");
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load payment method.", true);
            });
    }

    function deletePaymentMethod(id) {
        if (!confirm("Delete this payment method?")) {
            return;
        }

        $.ajax({
            url: `${PAYMENT_METHODS_API_URL}/${id}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Payment method deleted.");
            loadPaymentMethods();
            resetForm();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete payment method.", true);
        });
    }

    $(function() {
        loadAccounts().always(function() {
            loadPaymentMethods();
            resetForm();
        });

        $("#paymentMethodForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#paymentMethodId").val() || 0);
            const payload = getPayload();
            const isEdit = id > 0;

            if (!isEdit && !/^[a-z][a-z0-9_]{0,49}$/.test(payload.code)) {
                setMessage("Code must start with a letter and use lowercase letters, numbers, or underscores.", true);
                return;
            }
            if (!payload.name) {
                setMessage("Name is required.", true);
                return;
            }

            const body = isEdit
                ? {
                    name: payload.name,
                    description: payload.description,
                    account_id: payload.account_id,
                    is_active: payload.is_active
                }
                : payload;

            $.ajax({
                url: isEdit ? `${PAYMENT_METHODS_API_URL}/${id}` : PAYMENT_METHODS_API_URL,
                method: isEdit ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(body)
            }).done(function(res) {
                setMessage(res.message || (isEdit ? "Payment method updated." : "Payment method created."));
                loadPaymentMethods();
                resetForm();
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save payment method.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
            setMessage("");
        });

        $(document).on("click", ".edit-btn", function() {
            editPaymentMethod($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deletePaymentMethod($(this).data("id"));
        });
    });
</script>
<?= $this->endSection() ?>
