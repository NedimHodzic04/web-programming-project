   var AdminProductService = {
       currentEditProductId: null,

       init: function() {
           console.log("AdminProductService: init() called."); // Log: Init started
           if (!UserService.isAdmin()) {
               toastr.error("You do not have permission to view this page.");
               // Handle redirect
               if (typeof $.spapp !== 'undefined' && typeof $.spapp.load === 'function') {
                   $.spapp.load({ route: '#view_1' }); 
               } else {
                   window.location.href = 'index.html';
               }
               return;
           }
           this.loadProducts();
           this.setupEventListeners();
           this.populateCategoryDropdown();
           console.log("AdminProductService: init() completed."); // Log: Init ended
       },

       setupEventListeners: function() {
           console.log("AdminProductService: setupEventListeners() called."); // Log: Setup listeners started

           $('#addProductModal').off('show.bs.modal').on('show.bs.modal', function (event) { // Added .off()
               console.log("AdminProductService: addProductModal 'show.bs.modal' event triggered."); // Log
               var button = $(event.relatedTarget); 
               var action = button.data('action'); 
               var modal = $(this);
               var form = modal.find('form'); 
               form.trigger("reset"); 
               AdminProductService.currentEditProductId = null; 

               if (action === 'edit') {
                   var productId = button.data('id');
                   AdminProductService.currentEditProductId = productId;
                   modal.find('.modal-title').text('Edit Product');
                   AdminProductService.populateEditForm(productId);
               } else {
                   modal.find('.modal-title').text('Add New Product');
               }
           });

           // Detach any existing click handlers before attaching a new one to prevent multiple bindings
           $("#saveProductBtn").off('click').on('click', function(e) {
               e.preventDefault(); 
               console.log("AdminProductService: #saveProductBtn CLICKED!"); // Log: Button click

               // var form = $('#addProductModal').find('form')[0]; // Not strictly needed if accessing inputs by ID
               var productData = {
                   name: $("#productNameInput").val(),
                   price: parseFloat($("#priceInput").val()),
                   stock_quantity: parseInt($("#stockQuantityInput").val()),
                   category_id: $("#selectionInput").val(), // This should be the ID of the category
                   description: $("#descriptionInput").val(), // If you add a description field
               };
               console.log("AdminProductService: Product data collected from form:", productData); // Log: Data

               if (!productData.name || isNaN(productData.price) || productData.price <= 0 || isNaN(productData.stock_quantity) || productData.stock_quantity < 0 || !productData.category_id || productData.category_id === "") {
                   toastr.warning("Please fill in all required fields with valid data: Name, Price (>0), Stock (>=0), and Category.");
                   console.log("AdminProductService: Client-side validation failed for product data."); // Log
                   return;
               }

               if (AdminProductService.currentEditProductId) {
                   console.log("AdminProductService: Calling updateProduct for ID:", AdminProductService.currentEditProductId); // Log
                   AdminProductService.updateProduct(AdminProductService.currentEditProductId, productData);
               } else {
                   console.log("AdminProductService: Calling createProduct with data:", productData); // Log
                   AdminProductService.createProduct(productData);
               }
           });
           console.log("AdminProductService: setupEventListeners() completed."); // Log: Setup listeners ended
       },

       loadProducts: function() {
           console.log("AdminProductService: loadProducts() called."); // Log
           // ... (rest of loadProducts as in admin_product_service_js Canvas)
           $.ajax({
               url: Constants.PROJECT_BASE_URL + "api/products",
               type: "GET",
               success: function(response) {
                   if (response.data) {
                       AdminProductService.renderProductsTable(response.data);
                   } else {
                       $("#adminProductsTB").html('<tr><td colspan="7">No products found.</td></tr>');
                   }
               },
               error: function(xhr) {
                   console.error("Error loading products:", xhr.responseText);
                   toastr.error("Error loading products: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
               }
           });
       },

       renderProductsTable: function(products) {
           console.log("AdminProductService: renderProductsTable() called with", products.length, "products."); // Log
           // ... (rest of renderProductsTable as in admin_product_service_js Canvas, ensure Utils.escapeHtml is available) ...
           var tableBody = $("#adminProductsTB");
           tableBody.empty(); 
           const escape = (typeof Utils !== 'undefined' && typeof Utils.escapeHtml === 'function') ? Utils.escapeHtml : function(s) { return s; };

           if (products.length === 0) {
               tableBody.append('<tr><td colspan="7">No products found.</td></tr>');
               return;
           }

           products.forEach(function(product) {
               let categoryDisplay = product.category_name || product.category_id || 'N/A'; 
               let imageDisplay = product.image_url ? `<img src="${escape(product.image_url)}" alt="${escape(product.name)}" style="width: 50px; height: auto;">` : 'No Image';

               var row = `<tr>
                   <td>${product.id}</td>
                   <td>${escape(product.name)}</td>
                   <td>${parseFloat(product.price).toFixed(2)}</td>
                   <td>${product.stock_quantity}</td>
                   <td>${escape(categoryDisplay.toString())}</td>
                   <td>${imageDisplay}</td>
                   <td>
                       <button class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#addProductModal" data-action="edit" data-id="${product.id}">
                           <i class="bi bi-pencil-square"></i> Edit
                       </button>
                       <button class="btn btn-sm btn-danger" onclick="AdminProductService.deleteProduct(${product.id})">
                           <i class="bi bi-trash"></i> Delete
                       </button>
                   </td>
               </tr>`;
               tableBody.append(row);
           });
       },
       
       populateCategoryDropdown: function() {
           console.log("AdminProductService: populateCategoryDropdown() called."); // Log
            // ... (rest of populateCategoryDropdown as in admin_product_service_js Canvas) ...
           $.ajax({
               url: Constants.PROJECT_BASE_URL + "api/categories",
               type: "GET",
               success: function(response) {
                   if (response.data) {
                       var categorySelect = $("#selectionInput"); 
                       categorySelect.empty().append('<option selected value="">Select Category</option>'); 
                       response.data.forEach(function(category) {
                           categorySelect.append(`<option value="${category.id}">${Utils.escapeHtml(category.name)}</option>`);
                       });
                   }
               },
               error: function(xhr) {
                   toastr.error("Could not load categories for the form.");
                   console.error("Error loading categories:", xhr.responseText);
               }
           });
       },

       createProduct: function(productData) {
           console.log("AdminProductService: createProduct() CALLED. Data:", productData); // Log
           var token = UserService.getAuthHeader();
           console.log("AdminProductService: Token for createProduct:", token); // Log

           if (!token) {
               toastr.error("Authentication error (token not found for createProduct). Please log in again.");
               console.log("AdminProductService: createProduct - No token found, aborting."); // Log
               return; 
           }

           console.log("AdminProductService: createProduct - Preparing AJAX call to api/products..."); // Log
           $.ajax({
               url: Constants.PROJECT_BASE_URL + "api/products",
               type: "POST",
               contentType: "application/json",
               data: JSON.stringify(productData),
               beforeSend: function(xhr) {
                   xhr.setRequestHeader('Authorization', token);
               },
               success: function(response) {
                   console.log("AdminProductService: createProduct AJAX SUCCESS:", response); // Log
                   toastr.success("Product added successfully!");
                   $('#addProductModal').modal('hide'); 
                   AdminProductService.loadProducts(); 
               },
               error: function(xhr, status, error) {
                   console.error("AdminProductService: createProduct AJAX ERROR:", status, error, xhr.responseText); // Log
                   toastr.error("Error adding product: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error or invalid response"));
               }
           });
       },

       populateEditForm: function(productId) {
           console.log("AdminProductService: populateEditForm() for productId:", productId); // Log
           // ... (rest of populateEditForm as in admin_product_service_js Canvas) ...
           $.ajax({
               url: Constants.PROJECT_BASE_URL + "api/products/" + productId,
               type: "GET",
               success: function(response) {
                   if (response.data) {
                       var product = response.data;
                       $("#productNameInput").val(product.name);
                       $("#priceInput").val(product.price);
                       $("#stockQuantityInput").val(product.stock_quantity);
                       $("#selectionInput").val(product.category_id);
                       $("#descriptionInput").val(product.description);
                   } else {
                        toastr.error("Could not fetch product details for editing.");
                   }
               },
               error: function(xhr) {
                   toastr.error("Error fetching product details: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
                   $('#addProductModal').modal('hide');
               }
           });
       },

       updateProduct: function(productId, productData) {
           console.log("AdminProductService: updateProduct() CALLED for ID:", productId, "Data:", productData); // Log
           var token = UserService.getAuthHeader();
           console.log("AdminProductService: Token for updateProduct:", token); // Log
           if (!token) {
               toastr.error("Authentication error (token not found for updateProduct). Please log in again.");
               return;
           }
           console.log("AdminProductService: updateProduct - Preparing AJAX call..."); // Log
           // ... (rest of updateProduct AJAX call as in admin_product_service_js Canvas, add console.log for success/error) ...
           $.ajax({
               url: Constants.PROJECT_BASE_URL + "api/products/" + productId,
               type: "PUT",
               contentType: "application/json",
               data: JSON.stringify(productData),
               beforeSend: function(xhr) {
                   xhr.setRequestHeader('Authorization', token);
               },
               success: function(response) {
                   console.log("AdminProductService: updateProduct AJAX SUCCESS:", response);
                   toastr.success("Product updated successfully!");
                   $('#addProductModal').modal('hide');
                   AdminProductService.loadProducts();
               },
               error: function(xhr, status, error) {
                   console.error("AdminProductService: updateProduct AJAX ERROR:", status, error, xhr.responseText);
                   toastr.error("Error updating product: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
               }
           });
       },

       deleteProduct: function(productId) {
           console.log("AdminProductService: deleteProduct() CALLED for ID:", productId); // Log
           // ... (rest of deleteProduct as in admin_product_service_js Canvas, add console.log for success/error) ...
           if (confirm("Are you sure you want to delete this product? This action cannot be undone.")) {
               var token = UserService.getAuthHeader();
               if (!token) {
                   toastr.error("Authentication error (token not found for deleteProduct). Please log in again.");
                   return;
               }
               $.ajax({
                   url: Constants.PROJECT_BASE_URL + "api/products/" + productId,
                   type: "DELETE",
                   beforeSend: function(xhr) {
                       xhr.setRequestHeader('Authorization', token);
                   },
                   success: function(response) {
                       console.log("AdminProductService: deleteProduct AJAX SUCCESS:", response);
                       toastr.success("Product deleted successfully!");
                       AdminProductService.loadProducts(); 
                   },
                   error: function(xhr, status, error) {
                       console.error("AdminProductService: deleteProduct AJAX ERROR:", status, error, xhr.responseText);
                       toastr.error("Error deleting product: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
                   }
               });
           }
       }
   };
   