<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Currencies<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Currencies</h1>
        <p class="text-muted mb-0">Manage currency codes and display settings.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Currency List</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Code</th>
                                    <th>Name</th>
                                    <th style="width: 90px;">Symbol</th>
                                    <th style="width: 90px;">Decimals</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="currenciesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Currency</h2>
                    <form id="currencyForm" class="row g-3">
                        <input type="hidden" id="editMode" value="0">

                        <div class="col-12">
                            <label class="form-label">Code</label>
                            <input id="codeInput" type="text" class="form-control text-uppercase" maxlength="3" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input id="nameInput" type="text" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Symbol</label>
                            <input id="symbolInput" type="text" class="form-control" maxlength="10" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Decimals</label>
                            <input id="decimalsInput" type="number" class="form-control" min="0" max="8" value="2" required>
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
    const CURRENCIES_API_URL = "<?= site_url('api/currencies') ?>";

    function resetForm() {
        $("#editMode").val("0");
        $("#codeInput").val("").prop("readonly", false);
        $("#nameInput").val("");
        $("#symbolInput").val("");
        $("#decimalsInput").val(2);
        $("#formTitle").text("New Currency");
        $("#saveBtn").text("Save");
    }

    function renderRows(rows) {
        const tbody = $("#currenciesTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No currencies found.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const code = row.code || "";
            const tr = $("<tr>");
            tr.append(`<td>${code}</td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            tr.append(`<td>${row.symbol || ""}</td>`);
            tr.append(`<td>${row.decimals ?? 0}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-code="${code}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-code="${code}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadCurrencies() {
        $.getJSON(CURRENCIES_API_URL)
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load currencies.", true);
            });
    }

    function getPayload() {
        return {
            code: $("#codeInput").val().trim().toUpperCase(),
            name: $("#nameInput").val().trim(),
            symbol: $("#symbolInput").val().trim(),
            decimals: Number($("#decimalsInput").val() || 0)
        };
    }

    function editCurrency(code) {
        $.getJSON(`${CURRENCIES_API_URL}/${encodeURIComponent(code)}`)
            .done(function(res) {
                const row = res.data;
                $("#editMode").val("1");
                $("#codeInput").val(row.code || "").prop("readonly", true);
                $("#nameInput").val(row.name || "");
                $("#symbolInput").val(row.symbol || "");
                $("#decimalsInput").val(row.decimals ?? 2);
                $("#formTitle").text("Edit Currency");
                $("#saveBtn").text("Update");
                setMessage("");
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load currency data.", true);
            });
    }

    function deleteCurrency(code) {
        if (!confirm(`Delete currency ${code}?`)) {
            return;
        }

        $.ajax({
            url: `${CURRENCIES_API_URL}/${encodeURIComponent(code)}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Currency deleted.");
            loadCurrencies();
            resetForm();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete currency.", true);
        });
    }

    $(function() {
        loadCurrencies();
        resetForm();

        $("#currencyForm").on("submit", function(e) {
            e.preventDefault();

            const isEdit = $("#editMode").val() === "1";
            const payload = getPayload();

            if (!/^[A-Z]{3}$/.test(payload.code)) {
                setMessage("Currency code must be exactly 3 letters.", true);
                return;
            }
            if (!payload.name) {
                setMessage("Currency name is required.", true);
                return;
            }
            if (!payload.symbol) {
                setMessage("Currency symbol is required.", true);
                return;
            }

            const url = isEdit
                ? `${CURRENCIES_API_URL}/${encodeURIComponent(payload.code)}`
                : CURRENCIES_API_URL;
            const body = isEdit
                ? { name: payload.name, symbol: payload.symbol, decimals: payload.decimals }
                : payload;

            $.ajax({
                url,
                method: isEdit ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(body)
            }).done(function(res) {
                setMessage(res.message || (isEdit ? "Currency updated." : "Currency created."));
                loadCurrencies();
                resetForm();
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save currency.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
            setMessage("");
        });

        $(document).on("click", ".edit-btn", function() {
            editCurrency($(this).data("code"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteCurrency($(this).data("code"));
        });
    });
</script>
<?= $this->endSection() ?>
