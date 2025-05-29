

var CategoryService = {
    _getAuthToken: function() {
        return localStorage.getItem("user_token");
    },

    _getUserRole: function() {
        const token = this._getAuthToken();
        if (token) {
            try {
                return Utils.parseJwt(token).user.role;
            } catch (e) {
                console.error("Error parsing JWT for category service:", e);
                UserService.logout(); // Assumes UserService.logout() is globally accessible
                return null;
            }
        }
        return null;
    },

    // Fetch all categories (public)
    getAllCategories: function(callback) {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/categories", // From routes_category.php
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            success: function(result) {
                if (callback) callback(result.data);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                toastr.error(XMLHttpRequest?.responseText ? JSON.parse(XMLHttpRequest.responseText).message : 'Error fetching categories');
            }
        });
    },

    // Create a new category (admin only)
    createCategory: function(categoryData, successCallback, errorCallback) {
        const token = this._getAuthToken();
        if (!token) {
            toastr.error("Authentication required.");
            if (errorCallback) errorCallback({ status: 401, responseText: "Authentication required." });
            return;
        }

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/categories", // From routes_category.php
            type: "POST",
            data: JSON.stringify(categoryData),
            contentType: "application/json",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            },
            success: function(result) {
                toastr.success("Category created successfully!");
                if (successCallback) successCallback(result);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                toastr.error(XMLHttpRequest?.responseText ? JSON.parse(XMLHttpRequest.responseText).message : 'Error creating category');
                if (errorCallback) errorCallback(XMLHttpRequest);
            }
        });
    },
    
    // Delete a category (admin only)
    deleteCategory: function(categoryId, successCallback, errorCallback) {
        const token = this._getAuthToken();
        // Basic confirmation
        if (!confirm("Are you sure you want to delete this category?")) return;

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/categories/" + categoryId, // From routes_category.php
            type: "DELETE",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            },
            success: function(result) {
                toastr.success("Category deleted successfully!");
                if (successCallback) successCallback(result);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                toastr.error(XMLHttpRequest?.responseText ? JSON.parse(XMLHttpRequest.responseText).message : 'Error deleting category');
                if (errorCallback) errorCallback(XMLHttpRequest);
            }
        });
    },

    loadCategories: function() {
        CategoryService.getAllCategories(function(categories) {
            CategoryService.renderCategories(categories);
        });
    },

    renderCategories: function(categories) {
        const userRole = CategoryService._getUserRole();
        let categoriesHtml = '';

        if (userRole === Constants.ADMIN_ROLE) {
            categoriesHtml += `
                <div class="mb-3">
                    <h4>Add New Category (Admin)</h4>
                    <input type="text" id="new-category-name" placeholder="Category Name" class="form-control mb-2">
                    <button class="btn btn-success btn-sm" onclick="CategoryService.handleAddCategory()">Add Category</button>
                </div>
                <hr>
            `;
        }
        
        categoriesHtml += '<h4>Categories:</h4><ul class="list-group">';
        if (!categories || categories.length === 0) {
            categoriesHtml += '<li class="list-group-item">No categories found.</li>';
        } else {
            categories.forEach(function(category) {
                categoriesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${Utils.escapeHtml(category.name)}
                                    ${userRole === Constants.ADMIN_ROLE ? 
                                        `<button class="btn btn-danger btn-sm" onclick="CategoryService.deleteCategory(${category.id}, CategoryService.loadCategories)">Delete</button>` : 
                                        ''}
                                 </li>`;
            });
        }
        categoriesHtml += '</ul>';

        $("#categories-container").html(categoriesHtml);
    },
    
    handleAddCategory: function() {
        const categoryName = $("#new-category-name").val();
        if (!categoryName.trim()) {
            toastr.warning("Category name cannot be empty.");
            return;
        }
        CategoryService.createCategory({ name: categoryName }, function() {
            $("#new-category-name").val(''); // Clear input
            CategoryService.loadCategories(); // Reload categories
        });
    }
};