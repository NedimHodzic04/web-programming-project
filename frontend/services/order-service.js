// services/order-service.js
// For logged-in users to view their order history

var OrderService = {
    init: function(viewId) {
        console.log("OrderService: Initializing for view:", viewId || "user orders");
        if (!UserService.isLoggedIn()) {
            toastr.warning("Please log in to view your orders.");
            // SPAPP should ideally handle redirecting to login if this view requires login
            $("#user-orders-container").html("<p>Please log in to view your orders.</p>");
            return;
        }
        this.loadUserOrders();
    },

    loadUserOrders: function() {
        console.log("OrderService: Attempting to load user orders...");
        const currentUser = UserService.getCurrentUser(); // From user-service.js

        if (!currentUser || !currentUser.id) {
            console.error("OrderService: Current user not found. Cannot load orders.");
            $("#user-orders-container").html("<p>Could not load your orders. User not identified.</p>");
            toastr.error("Could not load your orders. Please log in again.");
            return;
        }
        
        $("#user-orders-container").html("<p>Loading your orders...</p>");

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/orders/user/" + currentUser.id, // From routes_order.php
            type: "GET",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', UserService.getAuthHeader()); // From user-service.js
            },
            success: function(response) {
                console.log("OrderService: User orders loaded", response);
                if (response.data && response.data.length > 0) {
                    OrderService.renderUserOrders(response.data);
                } else {
                    $("#user-orders-container").html("<p>You have no orders yet.</p>");
                }
            },
            error: function(xhr, status, error) {
                console.error("OrderService: Error loading user orders", xhr.responseText);
                $("#user-orders-container").html("<p>Error loading your orders. Please try again later.</p>");
                toastr.error("Could not load your orders: " + (xhr.responseJSON ? xhr.responseJSON.message : "Server error"));
            }
        });
    },

    renderUserOrders: function(orders) {
        console.log("OrderService: Rendering user orders...");
        let ordersHtml = '<h3>Your Orders</h3>';
        const escape = (typeof Utils !== 'undefined' && typeof Utils.escapeHtml === 'function') ? Utils.escapeHtml : function(s) { return s; };

        if (orders.length === 0) {
            ordersHtml += "<p>You haven't placed any orders yet.</p>";
        } else {
            ordersHtml += '<ul class="list-group">';
            orders.forEach(function(order) {
                ordersHtml += `
                    <li class="list-group-item">
                        <strong>Order ID:</strong> ${order.id} <br>
                        <strong>Date:</strong> ${new Date(order.order_date).toLocaleDateString()} <br>
                        <strong>Total:</strong> $${parseFloat(order.total_price).toFixed(2)} <br>
                        <strong>Status:</strong> ${escape(order.status || 'Processing')}
                        </li>
                `;
            });
            ordersHtml += '</ul>';
        }
        $("#user-orders-container").html(ordersHtml);
    },

    viewOrderDetails: function(orderId) {
        // Placeholder for viewing detailed order information
        console.log("OrderService: View details for order ID:", orderId);
        toastr.info("Order details functionality for order " + orderId + " would be shown here.");
    }
};
