var CartService = {
    userCartId: null, // Stores the logged-in user's active cart ID
    cartItems: [],    // Stores the currently fetched cart items

    /**
     * Initializes the cart view. Fetches the user's cart and then its items.
     */
    init: function() {
        console.log("Loaded")
        this.fetchUserCartAndItems();
        this.bindEvents();
    },

    /**
     * Adds a product to the cart. This function is designed to be called from the ProductService
     * when a user clicks "Add to cart" on a product detail page.
     * It handles sending the item to the backend API.
     * @param {number} productId - The ID of the product to add.
     * @param {number} quantity - The quantity of the product to add.
     */
    addToCart: function(productId, quantity) {
        const loggedInUser = UserService.getCurrentUser(); // Assuming this function exists and returns user object
        if (!loggedInUser || !loggedInUser.id) {
            toastr.error("Please log in to add items to your cart.");
            return;
        }

        // The backend's /api/cart-items POST route (from routes_cart_items.php)
        // is designed to either add an item or update its quantity if it already exists in the user's cart.
        // It also creates a cart for the user if one doesn't exist.
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/cart-items", // Backend endpoint for adding/updating cart items
            type: "POST", // HTTP POST request
            contentType: "application/json",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            data: JSON.stringify({
                product_id: productId,
                quantity: quantity
            }),
            success: function(response) {
                if (response.status === 'success') { // Check for success status from backend
                    toastr.success("Product added to cart successfully!");
                    // If the user is currently on the cart page, refresh the cart view
                    // This check ensures we only refresh the cart view if it's visible.
                    if (window.location.hash.includes('view_cart')) { // Assuming view_cart is accessed via hash
                        CartService.fetchUserCartAndItems();
                    }
                    // You might also want to update a cart item count in a header here.
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
    },

    /**
     * Fetches the logged-in user's cart ID, then fetches the items in that cart.
     */
    fetchUserCartAndItems: function() {
        const loggedInUser = UserService.getCurrentUser(); // Assuming this function exists and returns user object
        if (!loggedInUser || !loggedInUser.id) {
            toastr.info("Please log in to view your cart.");
            $('#cartTable tbody').html('<tr><td colspan="5" class="text-center">Please log in to view your cart.</td></tr>');
            CartService.renderCartSummary([]); // Ensure summary is updated for empty state
            return;
        }

        // First, get the user's cart ID (or create one if it doesn't exist, though backend POST /cart-items does this)
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/carts/" + loggedInUser.id,
            type: "GET",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    CartService.userCartId = response.data.id;
                    CartService.fetchCartItems(CartService.userCartId);
                } else {
                    // Cart not found for user (backend returns null data if no cart)
                    toastr.info("Your cart is empty or not yet created.");
                    $('#cartTable tbody').html('<tr><td colspan="5" class="text-center">Your cart is empty.</td></tr>');
                    CartService.renderCartSummary([]);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Failed to load user cart.";
                toastr.error("Error fetching user cart: " + errorMsg);
                $('#cartTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load cart.</td></tr>');
                console.error("Error fetching user cart:", xhr.responseText);
            }
        });
    },

    /**
     * Fetches all items for a given cart ID and renders them.
     * @param {number} cartId - The ID of the cart to fetch items for.
     */
    fetchCartItems: function(cartId) {
        if (!cartId) {
            toastr.error("Invalid cart ID to fetch items.");
            return;
        }

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/cart-items/" + cartId,
            type: "GET",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    CartService.cartItems = response.data; // Store items locally
                    CartService.renderCartTable(response.data);
                } else {
                    CartService.cartItems = []; // No items or error
                    $('#cartTable tbody').html('<tr><td colspan="5" class="text-center">Your cart is empty.</td></tr>');
                    CartService.renderCartSummary([]);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Failed to load cart items.";
                toastr.error("Error fetching cart items: " + errorMsg);
                $('#cartTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load cart items.</td></tr>');
                console.error("Error fetching cart items:", xhr.responseText);
            }
        });
    },

    /**
     * Renders the cart items into the cart table.
     * @param {Array} items - An array of cart item objects.
     */
    renderCartTable: function(items) {
        const tbody = $('#cartTable tbody');
        tbody.empty(); // Clear existing rows

        if (items.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center">Your cart is empty.</td></tr>');
        } else {
            items.forEach(item => {
                const itemTotal = item.quantity * item.price;
                const row = `
                    <tr data-product-id="${item.product_id}">
                        <td>${item.name}</td>
                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm quantity-input" value="${item.quantity}" min="1" data-product-id="${item.product_id}" style="width: 70px;">
                        </td>
                        <td>$${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-danger btn-sm remove-item-btn" data-product-id="${item.product_id}">Remove</button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        CartService.renderCartSummary(items); // Always update summary after rendering table
    },

    /**
     * Calculates and renders the cart subtotal and total price.
     * @param {Array} items - An array of cart item objects.
     */
    renderCartSummary: function(items) {
        let subtotal = 0;
        items.forEach(item => {
            subtotal += item.quantity * item.price;
        });
        const shipping = 0; // Assuming free shipping for simplicity, adjust as needed
        const total = subtotal + shipping;

        $('#subtotal').text(subtotal.toFixed(2));
        $('#totalPrice').text(total.toFixed(2));
    },

    /**
     * Binds event listeners for quantity changes, item removal, and checkout.
     */
    bindEvents: function() {
        // Event listener for quantity input changes
        $('#cartTable').on('change', '.quantity-input', function() {
            const productId = $(this).data('product-id');
            const newQuantity = parseInt($(this).val());

            if (isNaN(newQuantity) || newQuantity <= 0) {
                toastr.error("Quantity must be a positive number.");
                CartService.fetchCartItems(CartService.userCartId); // Revert to original quantity on invalid input
                return;
            }

            if (!CartService.userCartId) {
                toastr.error("Cannot update quantity: Cart ID not found.");
                return;
            }

            $.ajax({
                url: Constants.PROJECT_BASE_URL + `api/cart-items/${CartService.userCartId}/${productId}`,
                type: "PUT",
                contentType: "application/json",
                headers: {
                    'Authorization': UserService.getAuthHeader()
                },
                data: JSON.stringify({ quantity: newQuantity }),
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success("Quantity updated successfully!");
                        CartService.fetchCartItems(CartService.userCartId); // Re-fetch to update table and summary
                    } else {
                        toastr.error("Failed to update quantity: " + (response.message || "Unknown error."));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Network error or server issue.";
                    toastr.error("Error updating quantity: " + errorMsg);
                    console.error("Error updating quantity:", xhr.responseText);
                    CartService.fetchCartItems(CartService.userCartId); // Revert on error
                }
            });
        });

        // Event listener for remove item buttons
        $('#cartTable').on('click', '.remove-item-btn', function() {
            if (!confirm("Are you sure you want to remove this item from your cart?")) {
                return;
            }

            const productId = $(this).data('product-id');
            if (!CartService.userCartId) {
                toastr.error("Cannot remove item: Cart ID not found.");
                return;
            }

            $.ajax({
                url: Constants.PROJECT_BASE_URL + `api/cart-items/${CartService.userCartId}/${productId}`,
                type: "DELETE",
                headers: {
                    'Authorization': UserService.getAuthHeader()
                },
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success("Item removed from cart!");
                        CartService.fetchCartItems(CartService.userCartId); // Re-fetch to update table and summary
                    } else {
                        toastr.error("Failed to remove item: " + (response.message || "Unknown error."));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Network error or server issue.";
                    toastr.error("Error removing item: " + errorMsg);
                    console.error("Error removing item:", xhr.responseText);
                }
            });
        });

        // Event listener for the "Proceed to Checkout" button
        $('#checkoutBtn').on('click', function() {
            if (CartService.cartItems.length === 0) {
                toastr.warning("Your cart is empty. Please add items before checking out.");
                return;
            }

            const totalPrice = parseFloat($('#totalPrice').text()); // Get the calculated total price from the summary

            if (isNaN(totalPrice) || totalPrice <= 0) {
                toastr.error("Invalid total price for checkout.");
                return;
            }

            if (!CartService.userCartId) {
                toastr.error("Cannot proceed to checkout: Cart ID not found.");
                return;
            }

            // Assuming your existing backend has a POST /api/orders endpoint
            // that handles creating the order from the current user's cart and clearing it.
            // Adjust the URL to your actual order creation endpoint.
            $.ajax({
                url: Constants.PROJECT_BASE_URL + "api/orders", // Your actual order creation endpoint
                type: "POST",
                contentType: "application/json",
                headers: {
                    'Authorization': UserService.getAuthHeader()
                },
                data: JSON.stringify({
                    // The backend should derive user_id from the token
                    // Sending total_price is good practice for verification on backend
                    total_price: totalPrice
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success("Order placed successfully! Your order ID is " + response.order_id);
                        // Clear the cart display and refresh
                        CartService.userCartId = null; // Invalidate current cart ID
                        CartService.cartItems = [];
                        CartService.renderCartTable([]); // Render empty table
                        CartService.renderCartSummary([]); // Render empty summary
                        // Optional: Redirect to an order confirmation page or home page
                        // window.location.href = 'index.html#order_confirmation?order_id=' + response.order_id;
                    } else {
                        toastr.error("Failed to place order: " + (response.message || "Unknown error."));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Network error or server issue.";
                    toastr.error("Error placing order: " + errorMsg);
                    console.error("Error placing order:", xhr.responseText);
                }
            });
        });
    }
};