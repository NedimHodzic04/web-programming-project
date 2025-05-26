// services/product-service.js
// For general users to view products

var ProductService = {
    init: function(viewId) { // viewId might be passed by SPAPP's onReady or onCreate context if needed
        console.log("ProductService: Initializing for view:", viewId || "public products");
        this.loadAllProducts();
    },

    loadAllProducts: function() {
        console.log("ProductService: Attempting to load all products...");
        // Assumes #public-products-container exists in the HTML for the products view
        $("#public-products-container").html("<p>Loading products...</p>");

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/products", // Public endpoint from routes_products.php
            type: "GET",
            dataType: "json",
            success: function(response) {
                console.log("ProductService: Products loaded successfully", response);
                if (response.data && response.data.length > 0) {
                    ProductService.renderProducts(response.data);
                } else {
                    $("#public-products-container").html("<p>No products found.</p>");
                }
            },
            error: function(xhr, status, error) {
                console.error("ProductService: Error loading products", xhr.responseText);
                $("#public-products-container").html("<p>Error loading products. Please try again later.</p>");
                toastr.error("Could not load products: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
            }
        });
    },

    renderProducts: function(products) {
        console.log("ProductService: Rendering products...");
        var productsHtml = '<div class="row">';
        const escape = (typeof Utils !== 'undefined' && typeof Utils.escapeHtml === 'function') ? Utils.escapeHtml : function(s) { return s; };

        products.forEach(function(product) {
            // Basic card display - customize as needed
            productsHtml += `
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${escape(product.name)}</h5>
                            <p class="card-text">${escape(product.description || '')}</p>
                            <p class="card-text"><strong>Price:</strong> $${parseFloat(product.price).toFixed(2)}</p>
                            <p class="card-text">Stock: ${product.stock_quantity}</p>
                        </div>
                        <div class="card-footer text-center">
                            <button class="btn btn-primary btn-sm" onclick="CartService.addItemToCart(${product.id}, 1)">Add to Cart</button>
                            </div>
                    </div>
                </div>
            `;
        });
        productsHtml += '</div>';
        $("#public-products-container").html(productsHtml);
    }
};
