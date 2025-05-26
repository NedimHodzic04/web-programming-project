// services/cart-service.js
// For logged-in users to manage their shopping cart

var CartService = {
    init: function(viewId) {
        console.log("CartService: Initializing for view:", viewId || "cart");
        if (!UserService.isLoggedIn()) {
            toastr.warning("Please log in to view your cart.");
            // SPAPP should ideally handle redirecting to login if this view requires login
            // For example, in the SPAPP route definition for #view_cart:
            // onCreate: function() { if (!UserService.isLoggedIn()) { $.spapp.load({route: "#view_log_in"}); return; } CartService.init(); }
            $("#cart-items-container").html("<p>Please log in to view your cart.</p>");
            return;
        }
        this.loadCart();
    },

    _getUserCart: function(callback) {
        // Helper to get the user's cart (which contains the cart_id)
        const currentUser = UserService.getCurrentUser();
        if (!currentUser || !currentUser.id) {
            console.error("CartService: Cannot get cart, user not fully available.");
            if (callback) callback(null);
            return;
        }

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/carts/user/" + currentUser.id, // From routes_cart.php
            type: "GET",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', UserService.getAuthHeader());
            },
            success: function(response) {
                if (response.data && response.data.id) { // response.data is the cart object which has an 'id'
                    if (callback) callback(response.data); // Pass the whole cart object
                } else if (response.data === null && response.message === 'Cart not found for this user.') {
                    // Cart doesn't exist, might need to create one or handle as empty
                    console.log("CartService: Cart not found for user, attempting to create.");

                    if (callback) callback(null); // No existing cart
                } else {
                    if (callback) callback(null);
                }
            },
            error: function(xhr) {
                console.error("CartService: Error fetching user cart", xhr.responseText);
                toastr.error("Could not fetch your cart details.");
                if (callback) callback(null);
            }
        });
    },
    
    // Simplified loadCart for skeletal version
    loadCart: function() {
        console.log("CartService: Attempting to load cart items...");
        $("#cart-items-container").html("<p>Loading cart...</p>");

        this._getUserCart(function(cart) {
            if (cart && cart.id) {
                // Now fetch items for this cart_id
                $.ajax({
                    url: Constants.PROJECT_BASE_URL + "api/cart-items/" + cart.id, // From routes_cart_items.php
                    type: "GET",
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', UserService.getAuthHeader());
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log("CartService: Cart items loaded", response);
                        if (response.data) {
                            CartService.renderCart(response.data, cart.id);
                            CartService.updateCartBadge(response.data.length);
                        } else {
                             $("#cart-items-container").html("<p>Your cart is currently empty.</p>");
                             CartService.updateCartBadge(0);
                        }
                    },
                    error: function(xhr) {
                        console.error("CartService: Error loading cart items", xhr.responseText);
                        $("#cart-items-container").html("<p>Error loading cart items.</p>");
                        toastr.error("Could not load cart items: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
                        CartService.updateCartBadge(0);
                    }
                });
            } else {
                 $("#cart-items-container").html("<p>Your cart is empty or could not be loaded.</p>");
                 CartService.updateCartBadge(0);
            }
        });
    },

    renderCart: function(cartItems, cartId) {
        console.log("CartService: Rendering cart items...");
        let cartHtml = '<h3>Your Shopping Cart</h3>';
        const escape = (typeof Utils !== 'undefined' && typeof Utils.escapeHtml === 'function') ? Utils.escapeHtml : function(s) { return s; };

        if (!cartItems || cartItems.length === 0) {
            cartHtml += "<p>Your cart is empty.</p>";
        } else {
            cartHtml += '<ul class="list-group mb-3">';
            let subtotal = 0;
            cartItems.forEach(function(item) {

                const itemTotal = (item.price || 0) * (item.quantity || 0);
                subtotal += itemTotal;
                cartHtml += `
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <div>
                            <h6 class="my-0">${escape(item.product_name || 'Product Name Missing')}</h6>
                            <small class="text-muted">Quantity: ${item.quantity}</small>
                        </div>
                        <span class="text-muted">$${itemTotal.toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="CartService.removeItemFromCart(${cartId}, ${item.product_id})">X</button>
                    </li>
                `;
            });
            cartHtml += `<li class="list-group-item d-flex justify-content-between">
                            <span>Total (USD)</span>
                            <strong>$${subtotal.toFixed(2)}</strong>
                         </li>`;
            cartHtml += '</ul>';
            cartHtml += '<button class="btn btn-primary btn-lg btn-block" onclick="CartService.proceedToCheckout()">Proceed to Checkout</button>';
        }
        $("#cart-items-container").html(cartHtml);
    },

    addItemToCart: function(productId, quantity) {
        if (!UserService.isLoggedIn()) {
            toastr.warning("Please log in to add items to your cart.");
            // Optionally redirect to login: if (typeof $.spapp !== 'undefined') $.spapp.load({route: '#view_log_in'});
            return;
        }
        console.log(`CartService: Adding product ID ${productId}, quantity ${quantity} to cart.`);
        
        // POST /api/cart-items (backend handles getting/creating user's cart)
        // From routes_cart_items.php
        const itemData = { product_id: productId, quantity: quantity };
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/cart-items",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify(itemData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', UserService.getAuthHeader());
            },
            success: function(response) {
                toastr.success("Item added to cart!");
                console.log("Item added response: ", response);
                CartService.loadCart(); // Refresh cart view
            },
            error: function(xhr) {
                toastr.error("Error adding item to cart: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
                console.error("Error adding item to cart:", xhr.responseText);
            }
        });
    },

    removeItemFromCart: function(cartId, productId) {
        console.log(`CartService: Removing product ID ${productId} from cart ID ${cartId}.`);
        if (!confirm("Remove this item from your cart?")) return;

        $.ajax({
            url: Constants.PROJECT_BASE_URL + `api/cart-items/${cartId}/${productId}`, // From routes_cart_items.php
            type: "DELETE",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', UserService.getAuthHeader());
            },
            success: function(response) {
                toastr.info("Item removed from cart.");
                CartService.loadCart(); // Refresh
            },
            error: function(xhr) {
                toastr.error("Error removing item: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
            }
        });
    },

    proceedToCheckout: function() {
        // Placeholder for checkout logic
        toastr.info("Checkout process would start here!");
        console.log("Proceeding to checkout...");

    },
    
    updateCartBadge: function(count) {

        $('.cart-badge').text(count || 0); 
    }
};
