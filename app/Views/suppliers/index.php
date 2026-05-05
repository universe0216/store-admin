<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Suppliers<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Suppliers</h1>
        <p class="text-muted mb-0">Manage supplier records.</p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold mb-3" id="formTitle">New Supplier</h2>
            <form id="supplierForm" class="row g-3">
                <input type="hidden" id="supplierId">

                <div class="col-12 col-md-4">
                    <label class="form-label">Name</label>
                    <input id="nameInput" type="text" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Phone</label>
                    <input id="phoneInput" type="text" class="form-control">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Email</label>
                    <input id="emailInput" type="email" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea id="addressInput" class="form-control" rows="2"></textarea>
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

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold mb-3">Supplier List</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Name</th>
                            <th style="width: 170px;">Phone</th>
                            <th style="width: 240px;">Email</th>
                            <th>Address</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const SUPPLIERS_API_URL = "<?= site_url('api/suppliers') ?>";

    function setMessage(message, isError = false) {
        const box = $("#messageBox");
        box.text(message || "");
        box.removeClass("text-success text-danger");
        box.addClass(isError ? "text-danger" : "text-success");
    }

    function resetForm() {
        $("#supplierId").val("");
        $("#nameInput").val("");
        $("#phoneInput").val("");
        $("#emailInput").val("");
        $("#addressInput").val("");
        $("#formTitle").text("New Supplier");
        $("#saveBtn").text("Save");
    }

    function renderRows(rows) {
        const tbody = $("#suppliersTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No suppliers found.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const tr = $("<tr>");
            tr.append(`<td>${row.id}</td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            tr.append(`<td>${row.phone || ""}</td>`);
            tr.append(`<td>${row.email || ""}</td>`);
            tr.append(`<td>${row.address || ""}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="${row.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadSuppliers() {
        $.getJSON(SUPPLIERS_API_URL)
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load suppliers.", true);
            });
    }

    function getPayload() {
        return {
            name: $("#nameInput").val().trim(),
            phone: $("#phoneInput").val().trim(),
            email: $("#emailInput").val().trim(),
            address: $("#addressInput").val().trim()
        };
    }

    function editSupplier(id) {
        $.getJSON(`${SUPPLIERS_API_URL}/${id}`)
            .done(function(res) {
                const row = res.data;
                $("#supplierId").val(row.id);
                $("#nameInput").val(row.name || "");
                $("#phoneInput").val(row.phone || "");
                $("#emailInput").val(row.email || "");
                $("#addressInput").val(row.address || "");
                $("#formTitle").text("Edit Supplier");
                $("#saveBtn").text("Update");
                setMessage("");
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load supplier data.", true);
            });
    }

    function deleteSupplier(id) {
        if (!confirm("Delete this supplier?")) {
            return;
        }

        $.ajax({
            url: `${SUPPLIERS_API_URL}/${id}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Supplier deleted.");
            loadSuppliers();
            resetForm();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete supplier.", true);
        });
    }

    $(function() {
        loadSuppliers();
        resetForm();

        $("#supplierForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#supplierId").val() || 0);
            const payload = getPayload();

            if (!payload.name) {
                setMessage("Supplier name is required.", true);
                return;
            }

            $.ajax({
                url: id > 0 ? `${SUPPLIERS_API_URL}/${id}` : SUPPLIERS_API_URL,
                method: id > 0 ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                setMessage(res.message || (id > 0 ? "Supplier updated." : "Supplier created."));
                loadSuppliers();
                resetForm();
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save supplier.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
            setMessage("");
        });

        $(document).on("click", ".edit-btn", function() {
            editSupplier($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteSupplier($(this).data("id"));
        });
    });
</script>
<?= $this->endSection() ?>
