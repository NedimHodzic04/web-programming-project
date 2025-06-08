var CategoryService = {
    init: function() {
        this.fetchCategories();
    },

    fetchCategories: function() {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/categories",
            type: "GET",
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    CategoryService.renderCategoriesDropdown(response.data);
                }
            },
            error: function(xhr) {
                console.error("Error loading categories:", xhr.responseText);
            }
        });
    },

    renderCategoriesDropdown: function(categories) {
        var dropdownMenu = $("#category-dropdown-menu");
        dropdownMenu.find(".dynamic-category-item").remove();

        categories.forEach(function(category) {
            let categoryHtml = `
                <li class="dynamic-category-item">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="CategoryService.navigateTo(${category.id})">${category.name}</a>
                </li>
            `;
            dropdownMenu.append(categoryHtml);
        });
    },

    navigateTo: function(categoryId) {
        let newUrl = categoryId 
            ? `index.html?category_id=${categoryId}#view_1` 
            : 'index.html#view_1';

        history.pushState({}, '', newUrl);
        ProductService.init(categoryId);
    }
};

// Admin extension for category CRUD
CategoryService.admin = {
    init: function() {
        this.fetchAndRender();
        this.bindEvents();
    },

    fetchAndRender: function() {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/categories/admin",
            type: "GET",
            headers: {
        'Authorization': UserService.getAuthHeader()
    },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    CategoryService.admin.renderTable(response.data);
                } else {
                    $('#categoriesTable tbody').html('<tr><td colspan="4" class="text-center">No categories found.</td></tr>');
                }
            },
            error: function() {
                $('#categoriesTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Failed to load categories.</td></tr>');
            }
        });
    },

    renderTable: function(categories) {
        const tbody = $('#categoriesTable tbody');
        tbody.empty();

        categories.forEach(category => {
            const row = `
                <tr>
                    <td>${category.id}</td>
                    <td>${category.name}</td>
                    <td>${category.product_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" data-id="${category.id}" data-name="${category.name}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${category.id}">Delete</button>
                    </td>
                </tr>`;
            tbody.append(row);
        });
    },

    bindEvents: function() {
        $('#addCategoryBtn').on('click', () => {
            $('#categoryName').val('');
            $('#addCategoryModal').modal('show');

            $('#saveCategoryBtn').off('click').on('click', function() {
                const name = $('#categoryName').val().trim();
                if (!name) return;

                $.ajax({
                    url: Constants.PROJECT_BASE_URL + "api/categories",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ name }),
                    success: function() {
                        toastr.success("Category added successfully!");
                        $('#addCategoryModal').modal('hide');
                        CategoryService.admin.fetchAndRender();
                    }
                });
            });
        });

        $('#categoriesTable').on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            if (confirm("Are you sure you want to delete this category?")) {
                $.ajax({
                    url: Constants.PROJECT_BASE_URL + `api/categories/${id}`,
                    type: "DELETE",
                    success: function() {
                        toastr.success("Category deleted successfully!");
                        CategoryService.admin.fetchAndRender();
                    }
                });
            }
        });

        $('#categoriesTable').on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#categoryName').val(name);
            $('#addCategoryModal').modal('show');

            $('#saveCategoryBtn').off('click').on('click', function() {
                const newName = $('#categoryName').val().trim();
                if (!newName) return;

                $.ajax({
                    url: Constants.PROJECT_BASE_URL + `api/categories/${id}`,
                    type: "PUT",
                    contentType: "application/json",
                    data: JSON.stringify({ name: newName }),
                    success: function() {
                        toastr.success("Category edited successfully!");
                        $('#addCategoryModal').modal('hide');
                        CategoryService.admin.fetchAndRender();
                    }
                });
            });
        });
    }
};