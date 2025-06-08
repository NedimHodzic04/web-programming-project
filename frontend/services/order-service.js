var OrderManagementService = {
    init: function() {
        OrderManagementService.getAllOrders();
        // Event listener for view order modal button
        // Attach listener to a static parent element like document or #ordersTable for dynamic content
        $('#ordersTable tbody').on('click', '.view-order-btn', function() {
            var orderId = $(this).data('id');
            OrderManagementService.viewOrderDetails(orderId);
        });
    },

    getAllOrders: function() {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/orders/all",
            type: "GET",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success') {
                    OrderManagementService.renderOrdersTable(response.data);
                } else {
                    toastr.error('Failed to load orders: ' + response.message);
                    console.error('Error loading orders:', response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load orders.');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    },

    renderOrdersTable: function(orders) {
        var tbody = $('#ordersTable tbody');
        tbody.empty(); // Clear existing rows

        if (orders.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center">No orders found.</td></tr>');
            return;
        }

        $.each(orders, function(index, order) {
            var row = `
                <tr>
                    <td>${order.order_id}</td>
                    <td>${order.first_name} ${order.last_name}</td>
                    <td>${order.order_date}</td>
                    <td>$${parseFloat(order.total_price).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-primary btn-sm view-order-btn" data-id="${order.order_id}"
                                data-bs-toggle="modal" data-bs-target="#viewOrderModal">View</button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    },

    // *** IMPLEMENTATION FOR VIEW ORDER MODAL DETAILS ***
    viewOrderDetails: function(orderId) {
        // Clear previous modal content while loading
        $('#modal-order-id').text('Loading...');
        $('#modal-customer-name').text('Loading...');
        $('#modal-order-date').text('Loading...');
        $('#modal-total-price').text('Loading...');
        $('#modal-shipping-address').text('Loading...');
        $('#modal-order-status-row').hide(); // Hide status if it's not present
        $('#modal-ordered-items').empty().append('<li>Loading items...</li>');

        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/orders/" + orderId, // Use the specific order ID endpoint
            type: "GET",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success') {
                    var order = response.data; // This is the detailed order object
                    
                    // Populate basic order details
                    $('#viewOrderModalLabel').text('Order Details (ID: ' + order.order_id + ')');
                    $('#modal-order-id').text(order.order_id);
                    $('#modal-customer-name').text(order.customer_name);
                    $('#modal-order-date').text(order.order_date);
                    $('#modal-total-price').text('$' + parseFloat(order.total_price).toFixed(2));
                    $('#modal-shipping-address').text(order.shipping_address);
                    
                    // Since 'status' is not in your 'orders' table, ensure this row stays hidden
                    $('#modal-order-status-row').hide(); 
                    // If you later add a status column to 'orders', you would use:
                    // $('#modal-order-status-row').show();
                    // $('#modal-order-status').text(order.status); 

                    // Populate ordered items
                    var itemsList = $('#modal-ordered-items');
                    itemsList.empty(); // Clear "Loading items..."
                    if (order.items && order.items.length > 0) {
                        $.each(order.items, function(index, item) {
                            itemsList.append(`
                                <li>
                                    ${item.product_name} - ${item.quantity} x $${parseFloat(item.product_price).toFixed(2)} (Total: $${parseFloat(item.item_total).toFixed(2)})
                                </li>
                            `);
                        });
                    } else {
                        itemsList.append('<li>No items found for this order.</li>');
                    }

                    // The modal should open automatically due to data-bs-toggle in the button.
                    // If you trigger it manually, you might call:
                    // $('#viewOrderModal').modal('show');

                } else {
                    toastr.error('Failed to load order details: ' + response.message);
                    console.error('Error loading order details:', response.message);
                    // Clear and show error in modal if it was open
                    $('#modal-ordered-items').empty().append('<li>Failed to load order items.</li>');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load order details.');
                console.error('AJAX Error:', xhr.responseText);
                // Clear and show error in modal if it was open
                $('#modal-ordered-items').empty().append('<li>Error fetching order items.</li>');
            }
        });
    }
};

// Ensure this OrderManagementService.init() is called when your #view_admin-orders
// content is loaded and active. This typically happens via your SPA router.
// Example:
// $(document).ready(function() {
//    // Assume your SPA loads content dynamically.
//    // When #view_admin-orders is shown, call OrderManagementService.init();
//    // For direct page load, you might do:
//    // OrderManagementService.init();
// });