let ProductDetailService = {
    _currentProductId: null, // Variable to hold the ID

    /**
     * Stores the product ID before the page transition occurs.
     * @param {string|number} productId 
     */
    setCurrentProductId: function(productId) {
        this._currentProductId = productId;
    },

    /**
     * Initializes the detail page by loading the specified product's data.
     */
    // This is the new, correct code
init: function() {
    // Read the 'product' parameter from the URL's query string
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('product');

    if (!productId) {
        toastr.error("Could not identify the product to load.");
        window.location.hash = 'view_1';
        return;
    }
    this.loadProductDetails(productId);
},
    /**
     * Fetches a single product's data from the API.
     */
    loadProductDetails: function(productId) {
        $.ajax({
        url: Constants.PROJECT_BASE_URL + "api/products/" + productId,
        type: "GET",
        dataType: "json",
        // Add this 'headers' property to include the token
        headers: {
            'Authorization': UserService.getAuthHeader()
        },
        success: function(response) {
            if (response.status === 'success' && response.data) {
                ProductDetailService.renderProductDetails(response.data);
            } else {
                toastr.error("Product not found.");
                console.error("Product not found from API:", response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 401) {
                // Handle unauthorized access specifically
                toastr.error("Your session may have expired. Please log in again.");
                UserService.handleExpiredSession();
            } else {
                console.error("Error loading product details:", xhr.responseText);
                toastr.error("Error loading product details.");
            }
        }
    });
    },

    /**
     * Fills the HTML template with the fetched product data.
     */
    renderProductDetails: function(product) {
        // Use the IDs from the HTML template to update the content
        $("#detail-product-image").attr("src", product.image_url || 'assets/images/placeholder.jpg');
        $("#detail-product-sku").text("ID: " + product.id);
        $("#detail-product-name").text(Utils.escapeHtml(product.name));
        $("#detail-product-price").text("$" + parseFloat(product.price).toFixed(2));
        $("#detail-product-description").text(Utils.escapeHtml(product.description || 'No description available.'));
        $("#detail-product-category").text(Utils.escapeHtml(product.category_name || 'N/A'));
        $("#detail-product-stock").text(product.stock_quantity);

        // Make the "Add to Cart" button functional
        $("#detail-add-to-cart-btn").off('click').on('click', function() {
            let quantity = parseInt($("#detail-inputQuantity").val());
            if (quantity > 0) {
                CartService.addToCart(product.id, quantity);
            }
        });
    }
};