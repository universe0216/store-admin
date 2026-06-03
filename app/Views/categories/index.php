<?php

use App\Enums\Department;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Categories<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Categories</h1>
        <p class="text-muted mb-0">Manage product categories.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 fw-semibold mb-0">Category List</h2>
                        <div class="d-flex gap-2">
                            <button type="button" id="addRootBtn" class="btn btn-sm btn-primary">Add Root</button>
                            <button type="button" id="addChildBtn" class="btn btn-sm btn-outline-primary">Add Child</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Name</th>
                                    <th style="width: 140px;">Department</th>
                                    <th>Parent</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3" id="formTitle">New Category</h2>
                    <form id="categoryForm" class="row g-3">
                        <input type="hidden" id="categoryId">

                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input id="nameInput" type="text" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Department</label>
                            <select id="departmentSelect" class="form-select" required>
                                <?php foreach (Department::cases() as $case) : ?>
                                    <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Parent Category</label>
                            <select id="parentSelect" class="form-select">
                                <option value="">None (root)</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                            <button type="button" class="btn btn-outline-secondary" id="cancelEditBtn">Cancel Edit</button>
                            <button type="button" class="btn btn-outline-danger" id="deleteCategoryBtn">Delete</button>
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
    const CATEGORIES_API_URL = "<?= site_url('api/categories') ?>";
    const DEFAULT_DEPARTMENT = "<?= esc(Department::Apparel->value, 'js') ?>";
    let categoriesData = [];
    let selectedCategoryId = 0;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function getDepartmentLabel(value) {
        const department = String(value || "").trim();
        const label = $("#departmentSelect option").filter(function () {
            return String($(this).val()) === department;
        }).first().text();

        return label || department;
    }

    function getCategoryPathLabel(categoryId) {
        const names = [];
        let current = categoriesData.find(c => Number(c.id) === Number(categoryId));

        while (current) {
            names.unshift(String(current.name || "").trim());
            const parentId = current.parent_id ? Number(current.parent_id) : 0;
            current = parentId > 0
                ? categoriesData.find(c => Number(c.id) === parentId)
                : null;
        }

        return names.join(" > ");
    }

    function getParentLabel(parentId) {
        if (!parentId) {
            return "—";
        }

        return getCategoryPathLabel(parentId);
    }

    function buildTreeOrderedRows(rows) {
        const byParent = new Map();

        rows.forEach(row => {
            const parentKey = row.parent_id ? Number(row.parent_id) : 0;
            if (!byParent.has(parentKey)) {
                byParent.set(parentKey, []);
            }
            byParent.get(parentKey).push(row);
        });

        const ordered = [];

        function walk(parentKey, depth) {
            const children = (byParent.get(parentKey) || []).sort((a, b) => {
                return String(a.name || "").localeCompare(String(b.name || ""));
            });

            children.forEach(row => {
                ordered.push({ ...row, depth });
                walk(Number(row.id), depth + 1);
            });
        }

        walk(0, 0);

        return ordered;
    }

    function highlightSelectedRow(id) {
        $("#categoriesTableBody tr").removeClass("table-active");
        if (id > 0) {
            $(`#categoriesTableBody tr[data-id="${id}"]`).addClass("table-active");
        }
    }

    function populateParentSelect(excludeId) {
        const $select = $("#parentSelect");
        const previousValue = String($select.val() || "");

        $select.find("option:not(:first)").remove();

        const sorted = [...categoriesData].sort((a, b) => {
            return getCategoryPathLabel(a.id).localeCompare(getCategoryPathLabel(b.id));
        });

        sorted.forEach(row => {
            const categoryId = Number(row.id || 0);
            if (categoryId < 1 || categoryId === Number(excludeId || 0)) {
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
        }
    }

    function resetForm() {
        $("#categoryId").val("");
        $("#nameInput").val("");
        $("#departmentSelect").val(DEFAULT_DEPARTMENT);
        $("#parentSelect").val("").prop("disabled", false);
        $("#formTitle").text("New Category");
        $("#saveBtn").text("Save");
        $("#deleteCategoryBtn").prop("disabled", true);
        selectedCategoryId = 0;
        highlightSelectedRow(0);
        populateParentSelect(0);
        setMessage("");
    }

    function setFormForNewRoot() {
        resetForm();
        $("#formTitle").text("New Root Category");
        $("#parentSelect").val("").prop("disabled", true);
    }

    function setFormForNewChild(parentId) {
        resetForm();
        const parent = categoriesData.find(c => Number(c.id) === Number(parentId));
        selectedCategoryId = Number(parentId);
        highlightSelectedRow(selectedCategoryId);
        $("#formTitle").text("New Child Category");
        populateParentSelect(0);
        $("#parentSelect").val(String(parentId)).prop("disabled", true);
        if (parent && parent.department) {
            $("#departmentSelect").val(parent.department);
        }
    }

    function fillFormFromRow(row) {
        if (!row) {
            return;
        }

        const id = Number(row.id || 0);
        $("#categoryId").val(id > 0 ? id : "");
        $("#nameInput").val(row.name || "");
        $("#departmentSelect").val(row.department || DEFAULT_DEPARTMENT);
        populateParentSelect(id > 0 ? id : 0);
        $("#parentSelect")
            .val(row.parent_id ? String(row.parent_id) : "")
            .prop("disabled", false);
        $("#formTitle").text(id > 0 ? "Edit Category" : "New Category");
        $("#saveBtn").text(id > 0 ? "Update" : "Save");
        $("#deleteCategoryBtn").prop("disabled", !(id > 0));
        selectedCategoryId = id > 0 ? id : 0;
        highlightSelectedRow(selectedCategoryId);
        setMessage("");
    }

    function renderRows(rows) {
        categoriesData = (rows || []).map(row => ({
            id: Number(row.id),
            name: row.name || "",
            parent_id: row.parent_id !== null && row.parent_id !== undefined && row.parent_id !== ""
                ? Number(row.parent_id)
                : null,
            department: row.department || DEFAULT_DEPARTMENT
        }));

        const tbody = $("#categoriesTableBody");
        tbody.empty();

        if (categoriesData.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No categories found.</td></tr>');
            populateParentSelect(0);
            return;
        }

        buildTreeOrderedRows(categoriesData).forEach(row => {
            const indent = Number(row.depth || 0) * 1.25;
            const tr = $(`<tr data-id="${row.id}"></tr>`);
            if (Number(row.id) === Number(selectedCategoryId)) {
                tr.addClass("table-active");
            }

            tr.append(`<td>${row.id}</td>`);
            tr.append(
                `<td><span style="padding-left:${indent}rem;display:inline-block;">${escapeHtml(row.name)}</span></td>`
            );
            tr.append(`<td>${escapeHtml(getDepartmentLabel(row.department))}</td>`);
            tr.append(`<td>${escapeHtml(getParentLabel(row.parent_id))}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="${row.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Delete</button>
                </td>`
            );
            tbody.append(tr);
        });

        populateParentSelect(Number($("#categoryId").val() || 0));
    }

    function loadCategories() {
        return $.getJSON(CATEGORIES_API_URL)
            .done(function(res) {
                renderRows(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load categories.", true);
            });
    }

    function editCategory(id) {
        $.getJSON(`${CATEGORIES_API_URL}/${id}`)
            .done(function(res) {
                fillFormFromRow(res.data || {});
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load category.", true);
            });
    }

    function getPayload() {
        const parentValue = String($("#parentSelect").val() || "").trim();

        return {
            name: String($("#nameInput").val() || "").trim(),
            department: String($("#departmentSelect").val() || "").trim(),
            parent_id: parentValue !== "" ? Number(parentValue) : null
        };
    }

    function deleteCategory(id) {
        if (!confirm("Delete this category?")) {
            return;
        }

        $.ajax({
            url: `${CATEGORIES_API_URL}/${Number(id)}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Category deleted.");
            resetForm();
            loadCategories();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete category.", true);
        });
    }

    $(function() {
        resetForm();
        loadCategories();

        $("#categoryForm").on("submit", function(e) {
            e.preventDefault();

            const id = Number($("#categoryId").val() || 0);
            const payload = getPayload();

            if (!payload.name) {
                setMessage("Category name is required.", true);
                return;
            }

            if (!payload.department) {
                setMessage("Department is required.", true);
                return;
            }

            $.ajax({
                url: id > 0 ? `${CATEGORIES_API_URL}/${id}` : CATEGORIES_API_URL,
                method: id > 0 ? "PUT" : "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                const savedId = Number(res.data?.id || id || 0);
                setMessage(res.message || (id > 0 ? "Category updated." : "Category created."));
                loadCategories().done(function () {
                    if (savedId > 0) {
                        editCategory(savedId);
                    } else {
                        resetForm();
                    }
                });
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save category.", true);
            });
        });

        $("#cancelEditBtn").on("click", function() {
            resetForm();
        });

        $("#addRootBtn").on("click", setFormForNewRoot);

        $("#addChildBtn").on("click", function() {
            if (selectedCategoryId < 1) {
                setMessage("Select a parent category first (Edit on a row).", true);
                return;
            }

            setFormForNewChild(selectedCategoryId);
        });

        $("#deleteCategoryBtn").on("click", function() {
            const id = Number($("#categoryId").val() || 0);
            if (id < 1) {
                setMessage("Select a category to delete.", true);
                return;
            }

            deleteCategory(id);
        });

        $(document).on("click", ".edit-btn", function() {
            editCategory($(this).data("id"));
        });

        $(document).on("click", ".delete-btn", function() {
            deleteCategory($(this).data("id"));
        });

        $(document).on("click", "#categoriesTableBody tr[data-id]", function(e) {
            if ($(e.target).closest("button").length) {
                return;
            }

            const id = Number($(this).data("id") || 0);
            if (id > 0) {
                editCategory(id);
            }
        });
    });
</script>
<?= $this->endSection() ?>
