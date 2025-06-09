var DashboardService = {
    init: function() {
        this.getStats();
    },

    getStats: function() {
        $.ajax({
            url: Constants.PROJECT_BASE_URL + "api/dashboard",
            type: "GET",
            headers: {
                'Authorization': UserService.getAuthHeader()
            },
            success: function(response) {
                if (response.status === 'success') {
                    DashboardService.renderStats(response.data);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load dashboard statistics.');
                console.error(xhr.responseText);
            }
        });
    },

    renderStats: function(stats) {
        $("#total-users").text("Total: " + stats.users);
        $("#total-orders").text("Total: " + stats.orders);
        $("#total-products").text("In Stock: " + stats.products);
        $("#total-categories").text("Total: " + stats.categories);
    }
};