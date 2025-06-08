/**
 * Main application script for SPAPP routing and security checks.
 *
 * This script assumes that the following files have already been loaded in the HTML:
 * - jQuery, Toastr, SPAPP Plugin
 * - utils.js (containing Utils.parseJwt)
 * - user-service.js (containing UserService.handleExpiredSession and UserService.isAdmin)
 * - auth-guard.js (containing checkTokenValidity)
 */


    // This block checks for the "flash message" that gets set when a user is automatically logged out.
    // It runs on every page load to display the notification on the destination page.
    if (localStorage.getItem("session_expired_message") === "true") {
        toastr.info('Your session has expired. Please log in again.');
        // Important: Remove the message from storage so it doesn't show again on the next refresh.
        localStorage.removeItem("session_expired_message");
    }

    // Initialize the SPAPP application
    var app = $.spapp({
        defaultView: "#view_1",
        templateDir: "./tpl/",
        pageNotFound: "error_404"
    });

    /**
     * Helper for routes that require ANY logged-in user (authentication only).
     */
    function protectRoute(routeOptions) {
        const originalOnCreate = routeOptions.onCreate || function() {};
        routeOptions.onCreate = function() {
            if (!checkTokenValidity()) {
                UserService.handleExpiredSession();
                return; 
            }
            originalOnCreate.apply(this, arguments);
        };
        app.route(routeOptions);
    }

    /**
     * NEW HELPER: For routes that require an ADMIN user (authentication AND authorization).
     */
    function createAdminRoute(routeOptions) {
        const originalOnCreate = routeOptions.onCreate || function() {};
        routeOptions.onCreate = function() {
            // 1. First, check if the user is logged in with a valid token
            if (!checkTokenValidity()) {
                UserService.handleExpiredSession();
                return;
            }

            // 2. Second, check if the logged-in user has the 'admin' role
            if (!UserService.isAdmin()) {
                toastr.error("Access Denied: You do not have permission to view this page.");
                $.spapp.load({ route: '#view_1' }); // Redirect non-admins to the home page
                return;
            }

            // If both checks pass, run the page's setup logic
            originalOnCreate.apply(this, arguments);
        };
        app.route(routeOptions);
    }

    // =================================================================
    //                       ROUTE DEFINITIONS
    // =================================================================

    // --- Public Routes (no login required) ---
    app.route({ 
    view: 'view_1',
    onCreate: function(){
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('category_id');

        // --- START DEBUGGING ---
        console.log("Current URL search part:", window.location.search);
        console.log("Extracted categoryId:", categoryId);
        // --- END DEBUGGING ---

        ProductService.init(categoryId);
    }
});
    app.route({ view: 'view_2',                  load: 'view_2.html' });
    app.route({ view: 'view_log_in',             load: 'view_log_in.html' });
    app.route({ view: 'view_register',           load: 'view_register.html' });
    app.route({
    view: 'view_product',
    load: 'view_product.html',
    onCreate: function() {
        // The ID is already stored, so we just call init()
        ProductDetailService.init(); 
    }
});
    app.route({ view: 'view_exhaust',            load: 'view_exhaust.html' });
    app.route({ view: 'view_rims',               load: 'view_rims.html' });
    app.route({ view: 'view_steering-wheels',    load: 'view_steering-wheels.html' });
    app.route({ view: 'view_seats',              load: 'view_seats.html' });


    // --- Protected Routes (for ANY logged-in user) ---
    protectRoute({ 
        view: 'view_cart',
        load: 'view_cart.html',
        onCreate: function(){
            CartService.init();
        }
    
    });
    protectRoute({ view: 'view_user-profile',       load: 'view_user-profile.html' });
    protectRoute({ view: 'view_user-orders',        load: 'view_user-orders.html' });


    // --- Admin-Only Protected Routes (must be logged in AND be an admin) ---
    // MODIFIED: These now use the stricter createAdminRoute helper.
    createAdminRoute({
        view: 'view_admin',
        load: 'view_admin.html',
        onCreate: function() {
            console.log("Admin dashboard loaded for an authorized ADMIN user.");
            DashboardService.init();
        }
    });

    createAdminRoute({
        view: 'view_admin-products',
        load: 'view_admin-products.html',
        onCreate: function() {
            console.log("Admin products page loaded for an authorized ADMIN user.");
            // AdminProductService.init();
        }
    });

    createAdminRoute({
        view: 'view_admin-orders',
        load: 'view_admin-orders.html',
        onCreate: function() {
            console.log("Admin orders page loaded for an authorized ADMIN user.");
            OrderManagementService.init();
        }
    });

    createAdminRoute({
        view: 'view_admin-categories',
        load: 'view_admin-categories.html',
        onCreate: function() {
            console.log("Admin categories page loaded for an authorized ADMIN user.");
            CategoryService.admin.init();
        }
    });


    // Start the router. This must always be the last step.
    app.run();
