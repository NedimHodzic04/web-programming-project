/**
 * This file contains all client-side logic for authentication and authorization.
 * It depends on utils.js and user-service.js.
 */

/**
 * Checks if a user token exists and is not expired.
 * @returns {boolean}
 */
function checkTokenValidity() {
    let token = localStorage.getItem("user_token");
    if (!token) return false;

    try {
        let decodedToken = Utils.parseJwt(token);
        let expirationTime = decodedToken.exp * 1000;
        return Date.now() < expirationTime;
    } catch (e) {
        return false;
    }
}

/**
 * A helper function to create routes that are protected by both a login check
 * AND an admin role check.
 * @param {object} app - The SPAPP application instance.
 * @param {object} routeOptions - The route configuration object.
 */
function createAdminRoute(app, routeOptions) {
    const originalOnCreate = routeOptions.onCreate || function() {};

    routeOptions.onCreate = function() {
        // 1. Check for a valid token
        if (!checkTokenValidity()) {
            UserService.handleExpiredSession();
            return;
        }

        // 2. Check for an admin role
        if (!UserService.isAdmin()) {
            // MODIFIED: Call the new handler instead of showing a toast directly
            UserService.handleAccessDenied();
            return;
        }

        // If all checks pass, run the original page setup logic
        originalOnCreate.apply(this, arguments);
    };

    app.route(routeOptions);
}


// This part handles the "session expired" toastr message after a redirect
$(document).ready(function() {
    if (localStorage.getItem("session_expired_message") === "true") {
        toastr.info('Your session has expired. Please log in again.');
        localStorage.removeItem("session_expired_message");
    }

    
});