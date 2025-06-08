// For general users to view products
var ProductService = {
    currentProductId: null, // To store the ID of the product currently displayed on view_product.html

    /**
     * Initializes the product listing. It can load all products or filter by a specific category.
     * If a product ID is passed via URL (e.g., ?product=ID), it loads that specific product.
     * @param {number|null} categoryId - The ID of the category to filter by. If null, loads all products.
     */
    init: function(categoryId = null) {
        console.log("ProductService: Initializing for category ID:", categoryId || "all");

        // Check if a specific product is requested in the URL for the detail view
        const urlParams = new URLSearchParams(window.location.search);
        const productIdForDetail = urlParams.get('product');

        if (productIdForDetail) {
            ProductService.currentProductId = productIdForDetail; // Set the current product ID
            this.fetchProductDetails(productIdForDetail); // Load and display single product details
            this.bindAddToCartEvent(); // Bind add to cart for the detail page
        } else {
            // Otherwise, load product list (e.g., home page view)
            let apiUrl = Constants.PROJECT_BASE_URL + "api/products";
            if (categoryId) {
                apiUrl = Constants.PROJECT_BASE_URL + "api/products/category/" + categoryId;
            }

            let containerId = "#product-list-container"; // IMPORTANT: This ID must match the ID of the container div in your HTML template.

            $(containerId).html('<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            $.ajax({
                url: apiUrl,
                type: "GET",
                dataType: "json",
                headers: {
                    'Authorization': UserService.getAuthHeader()
                },
                success: function(response) {
                    console.log("ProductService: Products loaded successfully", response);
                    if (response.status === 'success' && response.data) {
                        ProductService.renderProducts(response.data, containerId);
                    } else {
                        $(containerId).html('<div class="col-12"><p class="text-center">No products found.</p></div>');
                    }
                },
                error: function(xhr) {
                    console.error("ProductService: Error loading products", xhr.responseText);
                    $(containerId).html('<div class="col-12"><p class="text-center text-danger">Error loading products. Please try again later.</p></div>');
                    toastr.error("Could not load products: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
                }
            });
        }
    },

    /**
     * Fetches and displays details for a single product on the product detail page (view_product.html).
     * @param {number} productId - The ID of the product to fetch.
     */
    fetchProductDetails: function(productId) {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/products/" + productId, // Assuming an API endpoint like /api/products/{id}
            type: "GET",
            dataType: "json",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const product = response.data;
                    ProductService.currentProductId = product.id; // Store for add to cart

                    // Populate view_product.html elements
                    $('#detail-product-image').attr('src', product.image_url || 'assets/images/placeholder.jpg');
                    $('#detail-product-sku').text('SKU: ' + product.id);
                    $('#detail-product-name').text(product.name);
                    $('#detail-product-price').text('$' + parseFloat(product.price).toFixed(2));
                    $('#detail-product-description').text(product.description);
                    $('#detail-product-category').text(product.category_name || 'N/A'); // Assuming category_name might be joined
                    $('#detail-product-stock').text(product.stock || 0);

                } else {
                    $('#detail-product-name').text('Product not found.');
                    toastr.error("Product not found.");
                }
            },
            error: function(xhr) {
                console.error("ProductService: Error loading product details", xhr.responseText);
                $('#detail-product-name').text('Failed to load product details.');
                toastr.error("Could not load product details: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
            }
        });
    },

    bindAddToCartEvent: function() {
        $('#detail-add-to-cart-btn').off('click').on('click', function() {
            if (!ProductService.currentProductId) {
                toastr.error("No product selected to add to cart.");
                return;
            }
            const productId = ProductService.currentProductId;
            const quantity = parseInt($('#detail-inputQuantity').val());

            if (isNaN(quantity) || quantity <= 0) {
                toastr.error("Please enter a valid quantity.");
                return;
            }

            $.ajax({
                url: Constants.PROJECT_BASE_URL + "api/cart-items", // Your backend endpoint to add/update cart items
                type: "POST",
                contentType: "application/json",
                headers: {
                    'Authorization': UserService.getAuthHeader()
                },
                data: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success("Product added to cart successfully!");
                        // Optionally update a small cart icon counter here
                    } else {
                        toastr.error("Failed to add product to cart: " + (response.message || "Unknown error."));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Network error or server issue.";
                    toastr.error("Error adding to cart: " + errorMsg);
                    console.error("Error adding to cart:", xhr.responseText);
                }
            });
        });
    },

    // This is the new, correct code for navigating to product detail view
    viewProduct: function(productId) {
        window.location.href = `index.html?product=${productId}#view_product`;
    },

    /**
     * Renders the product cards into the specified container.
     * This now matches the HTML structure from your home page.
     * @param {Array} products - An array of product objects from the API.
     * @param {string} containerId - The ID of the element to render the products into.
     */
    renderProducts: function(products, containerId) {
        console.log("ProductService: Rendering products...");
        var container = $(containerId);
        container.empty(); // Clear loading spinner

        if (products.length === 0) {
            container.html('<div class="col-12"><p class="text-center">No products were found.</p></div>');
            return;
        }

        const escape = (typeof Utils !== 'undefined' && typeof Utils.escapeHtml === 'function') ? Utils.escapeHtml : function(s) { return s; };

        products.forEach(function(product) {
            // Use a placeholder if the image path from the DB is empty
            let imageUrl = product.image_url ? product.image_url : 'assets/images/placeholder.jpg';
            let safeName = escape(product.name);

            let productCardHtml = `
                <div class="col mb-5">
                    <div class="card h-100">
                        <img class="card-img-top" src="${imageUrl}" alt="${safeName}" style="height: 200px; object-fit: cover;" />
                        <div class="card-body p-4">
                            <div class="text-center">
                                <h5 class="fw-bolder">${safeName}</h5>
                                $${parseFloat(product.price).toFixed(2)}
                            </div>
                        </div>
                        <div class="card-footer p-4 pt-0 border-top-0 bg-transparent">
                            <div class="text-center">
                                <button class="btn btn-outline-dark mt-auto" onclick="ProductService.viewProduct(${product.id})">
                                    View Product
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(productCardHtml);
        });
    }
};