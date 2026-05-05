<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Categories<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Categories</h1>
        <p class="text-muted mb-0">Manage product categories.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-semibold mb-0">Category Tree</h2>
                <div class="d-flex gap-2">
                    <button type="button" id="addRootBtn" class="btn btn-sm btn-primary">Add Root</button>
                    <button type="button" id="addChildBtn" class="btn btn-sm btn-outline-primary">Add Child</button>
                    <button type="button" id="deleteCategoryBtn" class="btn btn-sm btn-danger">Delete Selected</button>
                </div>
            </div>
            <div id="categoriesTreeGrid"></div>
            <div id="messageBox" class="small fw-semibold mt-3"></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const CATEGORIES_API_URL = "<?= site_url('api/categories') ?>";
    let categoriesData = [];

    function setMessage(message, isError = false) {
        const box = $("#messageBox");
        box.text(message || "");
        box.removeClass("text-success text-danger");
        box.addClass(isError ? "text-danger" : "text-success");
    }

    function initTreeGrid(rows) {
        categoriesData = (rows || []).map(row => ({
            id: Number(row.id),
            name: row.name || "",
            parent_id: row.parent_id ? Number(row.parent_id) : null
        }));

        const source = {
            dataType: "json",
            dataFields: [
                { name: "id", type: "number" },
                { name: "name", type: "string" },
                { name: "parent_id", type: "number" }
            ],
            hierarchy: {
                keyDataField: { name: "id" },
                parentDataField: { name: "parent_id" }
            },
            id: "id",
            localData: categoriesData
        };

        const dataAdapter = new $.jqx.dataAdapter(source);
        $("#categoriesTreeGrid").jqxTreeGrid({
            width: "100%",
            source: dataAdapter,
            editable: true,
            selectionMode: "singleRow",
            columnsResize: true,
            columns: [
                { text: "ID", dataField: "id", width: 100, editable: false },
                { text: "Category Name", dataField: "name" }
            ]
        });
    }

    function loadCategories() {
        $.getJSON(CATEGORIES_API_URL)
            .done(function(res) {
                initTreeGrid(res.data || []);
            })
            .fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load categories.", true);
            });
    }

    function addRootCategory() {
        const tempId = `tmp-${Date.now()}`;
        $("#categoriesTreeGrid").jqxTreeGrid("addRow", tempId, { id: tempId, name: "", parent_id: null }, "last", null);
        $("#categoriesTreeGrid").jqxTreeGrid("selectRow", tempId);
        $("#categoriesTreeGrid").jqxTreeGrid("beginRowEdit", tempId);
    }

    function addChildCategory() {
        const selected = $("#categoriesTreeGrid").jqxTreeGrid("getSelection");
        if (!selected || selected.length === 0) {
            setMessage("Select a parent category first.", true);
            return;
        }

        const parent = selected[0];
        const parentId = parent.id;
        const tempId = `tmp-${Date.now()}`;

        $("#categoriesTreeGrid").jqxTreeGrid("expandRow", parentId);
        $("#categoriesTreeGrid").jqxTreeGrid(
            "addRow",
            tempId,
            { id: tempId, name: "", parent_id: Number(parentId) },
            "last",
            parentId
        );
        $("#categoriesTreeGrid").jqxTreeGrid("selectRow", tempId);
        $("#categoriesTreeGrid").jqxTreeGrid("beginRowEdit", tempId);
    }

    function deleteSelectedCategory() {
        const selected = $("#categoriesTreeGrid").jqxTreeGrid("getSelection");
        if (!selected || selected.length === 0) {
            setMessage("Select a category row first.", true);
            return;
        }

        const row = selected[0];
        const id = row.id;

        if (String(id).startsWith("tmp-")) {
            $("#categoriesTreeGrid").jqxTreeGrid("deleteRow", id);
            return;
        }

        if (!confirm("Delete this category?")) {
            return;
        }

        $.ajax({
            url: `${CATEGORIES_API_URL}/${Number(id)}`,
            method: "DELETE"
        }).done(function(res) {
            setMessage(res.message || "Category deleted.");
            loadCategories();
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to delete category.", true);
        });
    }

    $(function() {
        loadCategories();
        $("#addRootBtn").on("click", addRootCategory);
        $("#addChildBtn").on("click", addChildCategory);
        $("#deleteCategoryBtn").on("click", deleteSelectedCategory);

        $("#categoriesTreeGrid").on("rowEndEdit", function (event) {
            const row = event.args.row;
            const id = row.id;
            const name = String(row.name || "").trim();
            const parentId = row.parent_id ? Number(row.parent_id) : 0;

            if (name === "") {
                setMessage("Category name is required.", true);
                loadCategories();
                return;
            }

            if (String(id).startsWith("tmp-")) {
                $.ajax({
                    url: CATEGORIES_API_URL,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ name, parent_id: parentId > 0 ? parentId : null })
                }).done(function (res) {
                    setMessage(res.message || "Category created.");
                    loadCategories();
                }).fail(function (xhr) {
                    setMessage(xhr.responseJSON?.message || "Failed to create category.", true);
                    loadCategories();
                });
                return;
            }

            $.ajax({
                url: `${CATEGORIES_API_URL}/${Number(id)}`,
                method: "PUT",
                contentType: "application/json",
                data: JSON.stringify({ name, parent_id: parentId > 0 ? parentId : null })
            }).done(function (res) {
                setMessage(res.message || "Category updated.");
                loadCategories();
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to update category.", true);
                loadCategories();
            });
        });
    });
</script>
<?= $this->endSection() ?>
