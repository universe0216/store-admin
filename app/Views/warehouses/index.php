<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Warehouses<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Warehouses</h1>
        <p class="text-muted mb-0">Manage warehouse locations.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Warehouse List</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th style="width: 90px;">Can Store</th>
                                    <th style="width: 90px;">Can Sell</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="warehousesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Warehouse</h2>
                    <form id="warehouseForm" class="row g-3">
                        <input type="hidden" id="warehouseId">

                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input id="nameInput" type="text" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input id="locationInput" type="text" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canStoreInput" checked>
                                <label class="form-check-label" for="canStoreInput">Can Store</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canSellInput" checked>
                                <label class="form-check-label" for="canSellInput">Can Sell</label>
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
    const WAREHOUSES_API_URL = "<?= site_url('api/warehouses') ?>";

    function boolLabel(value) {
        return Number(value) === 1 ? "Yes" : "No";
    }

    function resetForm() {
        $("#warehouseId").val("");
        $("#nameInput").val("");
        $("#locationInput").val("");
        $("#canStoreInput").prop("checked", true);
        $("#canSellInput").prop("checked", true);
        $("#formTitle").text("New Warehouse");
        $("#saveBtn").text("Save");
    }

    function renderRows(rows) {
        const tbody = $("#warehousesTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No warehouses found.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const tr = $("<tr>");
            tr.append(`<td>${row.id}</td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            tr.append(`<td>${row.location || ""}</td>`);
            tr.append(`<td>${boolLabel(row.can_store)}</td>`);
            tr.append(`<td>${boolLabel(row.can_sell)}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="${row.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadWarehouses() {
        $.getJSON(WAREHOUSES_API_URL)
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load warehouses.", true);
            });
    }

    function getPayload() {
        return {
            name: $("#nameInput").val().trim(),
            location: $("#locationInput").val().trim(),
            can_store: $("#canStoreInput").is(":checked"),
            can_sell: $("#canSellInput").is(":checked")
        };
    }

    function editWarehouse(id) {
        $.getJSON(`${WAREHOUSES_API_URL}/${id}`)
            .done(function(res) {
                const row = res.data;
                $("#warehouseId").val(row.id);
                $("#nameInput").val(row.name || "");
                $("#locationInput").val(row.location || "");
                $("#canStoreInput").prop("checked", Number(row.can_store) === 1);
                $("#canSellInput").prop("checked", Number(row.can_sell) === 1);
                $("#formTitle").text("Edit Warehouse");
                $("#saveBtn").text("Update");
                setMessage("");
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load warehouse data.", true);
            });
    }

    function deleteWarehouse(id) {
        if (!confirm("Delete this warehouse?")) {
            return;
        }

        $.ajax({
            url: `${WAREHOUSES_API_URL}/${id}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Warehouse deleted.");
            loadWarehouses();
            resetForm();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete warehouse.", true);
        });
    }

    $(function() {
        loadWarehouses();
        resetForm();

        $("#warehouseForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#warehouseId").val() || 0);
            const payload = getPayload();

            if (!payload.name) {
                setMessage("Warehouse name is required.", true);
                return;
            }

            $.ajax({
                url: id > 0 ? `${WAREHOUSES_API_URL}/${id}` : WAREHOUSES_API_URL,
                method: id > 0 ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                setMessage(res.message || (id > 0 ? "Warehouse updated." : "Warehouse created."));
                loadWarehouses();
                resetForm();
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save warehouse.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
            setMessage("");
        });

        $(document).on("click", ".edit-btn", function() {
            editWarehouse($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteWarehouse($(this).data("id"));
        });
    });
</script>
<?= $this->endSection() ?>
