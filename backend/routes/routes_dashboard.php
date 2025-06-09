<?php
// Route for getting dashboard statistics
Flight::route('GET /api/dashboard', function() {
    // AUTHORIZATION: Only admins can access dashboard stats
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    $stats = (new DashboardService())->getDashboardStats();
    Flight::json(['status' => 'success', 'data' => $stats], 200);
});