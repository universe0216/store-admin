<?php

use App\Enums\Department;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sizes<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Sizes</h1>
        <p class="text-muted mb-0">Manage size options for each department.</p>
    </div>

    <ul class="nav nav-pills mb-4 flex-wrap gap-2" id="departmentTabs">
        <?php foreach ($departments as $index => $case) : ?>
            <li class="nav-item">
                <button
                    type="button"
                    class="nav-link<?= $index === 0 ? ' active' : '' ?>"
                    data-department="<?= esc($case->value, 'attr') ?>"
                ><?= esc($case->label()) ?></button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 fw-semibold mb-0"><span id="listDepartmentLabel"><?= esc($departments[0]->label()) ?></span> Sizes</h2>
                        <button type="button" id="addSizeBtn" class="btn btn-sm btn-primary">Add Size</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">Order</th>
                                    <th>Size</th>
                                    <th style="width: 80px;">Active</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sizesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Size</h2>
                    <form id="sizeForm" class="row g-3">
                        <input type="hidden" id="sizeId">

                        <div class="col-12">
                            <label class="form-label">Department</label>
                            <input id="departmentDisplay" type="text" class="form-control" readonly>
                            <input id="departmentInput" type="hidden">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Size</label>
                            <input id="valueInput" type="text" class="form-control" maxlength="50" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sort order</label>
                            <input id="sortOrderInput" type="number" class="form-control" min="0" step="1" value="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input id="isActiveInput" class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label" for="isActiveInput">Active</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                            <button type="button" class="btn btn-outline-secondary" id="cancelEditBtn">Cancel Edit</button>
                            <button type="button" class="btn btn-outline-danger" id="deleteSizeBtn">Delete</button>
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
    const SIZES_API_URL = "<?= site_url('api/department-sizes') ?>";
    const DEFAULT_DEPARTMENT = "<?= esc($departments[0]->value ?? Department::Footwear->value, 'js') ?>";
    const departmentLabels = {
        <?php foreach ($departments as $case) : ?>
        "<?= esc($case->value, 'js') ?>": "<?= esc($case->label(), 'js') ?>",
        <?php endforeach; ?>
    };

    let selectedDepartment = DEFAULT_DEPARTMENT;
    let sizesData = [];

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function getDepartmentLabel(value) {
        return departmentLabels[String(value || "")] || String(value || "");
    }

    function setActiveDepartment(department) {
        selectedDepartment = String(department || DEFAULT_DEPARTMENT);
        $("#departmentTabs .nav-link").removeClass("active");
        $(`#departmentTabs .nav-link[data-department="${selectedDepartment}"]`).addClass("active");
        $("#listDepartmentLabel").text(getDepartmentLabel(selectedDepartment));
        resetForm();
    }

    function resetForm() {
        $("#sizeId").val("");
        $("#departmentInput").val(selectedDepartment);
        $("#departmentDisplay").val(getDepartmentLabel(selectedDepartment));
        $("#valueInput").val("");
        $("#sortOrderInput").val("0");
        $("#isActiveInput").prop("checked", true);
        $("#formTitle").text("New Size");
        $("#saveBtn").text("Save");
        $("#deleteSizeBtn").prop("disabled", true);
        $("#sizesTableBody tr").removeClass("table-active");
        setMessage("");
    }

    function fillFormFromRow(row) {
        if (!row) {
            return;
        }

        const id = Number(row.id || 0);
        $("#sizeId").val(id > 0 ? id : "");
        $("#departmentInput").val(row.department || selectedDepartment);
        $("#departmentDisplay").val(getDepartmentLabel(row.department || selectedDepartment));
        $("#valueInput").val(row.value || "");
        $("#sortOrderInput").val(Number(row.sort_order || 0));
        $("#isActiveInput").prop("checked", Number(row.is_active || 0) === 1);
        $("#formTitle").text(id > 0 ? "Edit Size" : "New Size");
        $("#saveBtn").text(id > 0 ? "Update" : "Save");
        $("#deleteSizeBtn").prop("disabled", !(id > 0));

        $("#sizesTableBody tr").removeClass("table-active");
        if (id > 0) {
            $(`#sizesTableBody tr[data-id="${id}"]`).addClass("table-active");
        }

        setMessage("");
    }

    function renderRows(rows) {
        sizesData = rows || [];
        const tbody = $("#sizesTableBody");
        tbody.empty();

        if (sizesData.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted">No sizes found for this department.</td></tr>');
            return;
        }

        sizesData.forEach(row => {
            const id = Number(row.id || 0);
            const tr = $(`<tr data-id="${id}"></tr>`);
            tr.append(`<td>${Number(row.sort_order || 0)}</td>`);
            tr.append(`<td>${escapeHtml(row.value || "")}</td>`);
            tr.append(`<td>${Number(row.is_active || 0) === 1 ? "Yes" : "No"}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="${id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${id}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadSizes() {
        return $.getJSON(SIZES_API_URL, { department: selectedDepartment })
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load sizes.", true);
            });
    }

    function editSize(id) {
        $.getJSON(`${SIZES_API_URL}/${Number(id)}`)
            .done(function(res) {
                fillFormFromRow(res.data || {});
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load size.", true);
            });
    }

    function getPayload() {
        return {
            department: String($("#departmentInput").val() || selectedDepartment).trim(),
            value: String($("#valueInput").val() || "").trim(),
            sort_order: Number($("#sortOrderInput").val() || 0),
            is_active: $("#isActiveInput").is(":checked") ? 1 : 0
        };
    }

    function deleteSize(id) {
        if (!confirm("Delete this size?")) {
            return;
        }

        $.ajax({
            url: `${SIZES_API_URL}/${Number(id)}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Size deleted.");
            resetForm();
            loadSizes();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete size.", true);
        });
    }

    $(function() {
        setActiveDepartment(DEFAULT_DEPARTMENT);
        loadSizes();

        $("#departmentTabs").on("click", ".nav-link", function() {
            setActiveDepartment($(this).data("department"));
            loadSizes();
        });

        $("#addSizeBtn").on("click", function() {
            resetForm();
        });

        $("#sizeForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#sizeId").val() || 0);
            const payload = getPayload();

            if (!payload.value) {
                setMessage("Size value is required.", true);
                return;
            }

            $.ajax({
                url: id > 0 ? `${SIZES_API_URL}/${id}` : SIZES_API_URL,
                method: id > 0 ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                const savedId = Number(res.data?.id || id || 0);
                setMessage(res.message || (id > 0 ? "Size updated." : "Size created."));
                loadSizes().done(function() {
                    if (savedId > 0) {
                        editSize(savedId);
                    } else {
                        resetForm();
                    }
                });
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save size.", true);
            });
        });

        $("#cancelEditBtn").on("click", resetForm);

        $("#deleteSizeBtn").on("click", function() {
            const id = Number($("#sizeId").val() || 0);
            if (id < 1) {
                setMessage("Select a size to delete.", true);
                return;
            }

            deleteSize(id);
        });

        $(document).on("click", ".edit-btn", function() {
            editSize($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteSize($(this).data("id"));
        });

        $(document).on("click", "#sizesTableBody tr[data-id]", function(e) {
            if ($(e.target).closest("button").length) {
                return;
            }

            const id = Number($(this).data("id") || 0);
            if (id > 0) {
                editSize(id);
            }
        });
    });
</script>
<?= $this->endSection() ?>
