var UserService = {
    init: function () {
        // Check if user is already logged in when app initializes
        var token = localStorage.getItem("user_token");
        
        // Update UI based on current login status
        this.updateUI();
        
        // Initialize form validations when views load
        this.initFormValidation();
    },

    initFormValidation: function() {
        // Use SPAPP's view loading events to initialize forms
        $(document).on('viewLoaded', function(e, view) {
            if (view === 'view_log_in') {
                UserService.initLoginForm();
            } else if (view === 'view_register') {
                UserService.initRegisterForm();
            }
        });

        // Fallback: check periodically if forms exist and initialize them
        setTimeout(() => {
            if ($("#login-form").length && !$("#login-form").hasClass('validation-initialized')) {
                UserService.initLoginForm();
            }
            if ($("#register-form").length && !$("#register-form").hasClass('validation-initialized')) {
                UserService.initRegisterForm();
            }
        }, 500);
    },

    initLoginForm: function() {
        if ($("#login-form").length) {
            $("#login-form").addClass('validation-initialized').validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    email: {
                        required: "Please enter your email",
                        email: "Please enter a valid email address"
                    },
                    password: {
                        required: "Please enter your password",
                        minlength: "Password must be at least 6 characters"
                    }
                },
                submitHandler: function (form) {
                    var entity = Object.fromEntries(new FormData(form).entries());
                    UserService.login(entity);
                }
            });
        }
    },

    initRegisterForm: function() {
        if ($("#register-form").length) {
            $("#register-form").addClass('validation-initialized').validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 6
                    },
                    first_name: {
                        required: true,
                        minlength: 2
                    },
                    last_name: {
                        required: true,
                        minlength: 2
                    },
                    city: {
                        required: true
                    },
                    address: {
                        required: true
                    },
                    zip: {
                        required: true
                    }
                },
                submitHandler: function (form) {
                    var entity = Object.fromEntries(new FormData(form).entries());
                    UserService.register(entity);
                }
            });
        }
    },

    login: function (entity) {
        $.ajax({
            url: "http://localhost/NedimHodzic/web-programming-project/backend/auth/login",
            type: "POST",
            data: JSON.stringify(entity),
            contentType: "application/json",
            dataType: "json",
            beforeSend: function() {
                // Show loading state
                $("#login-btn").prop('disabled', true).text('Logging in...');
            },
            success: function (result) {
                console.log(result);
                localStorage.setItem("user_token", result.data.token);
                localStorage.setItem("user_data", JSON.stringify(result.data.user));
                
                toastr.success('Login successful!');
                
                // Update UI immediately
                UserService.updateUI();
                
                // Navigate to home page using SPAPP
                setTimeout(function() {
                    window.location.href = 'index.html';
                }, 1000);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                var errorMessage = 'Login failed';
                try {
                    var response = JSON.parse(XMLHttpRequest.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = XMLHttpRequest.responseText || errorMessage;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Reset button state
                $("#login-btn").prop('disabled', false).text('Log in');
            }
        });
    },

    register: function (entity) {
        $.ajax({
            url: "http://localhost/NedimHodzic/web-programming-project/backend/auth/register",
            type: "POST",
            data: JSON.stringify(entity),
            contentType: "application/json",
            dataType: "json",
            beforeSend: function() {
                $("#register-btn").prop('disabled', true).text('Creating account...');
            },
            success: function (result) {
                console.log(result);
                toastr.success('Registration successful! Please log in.');
                
                // Navigate to login view using SPAPP
                setTimeout(function() {
                    window.location.href = 'login.html';
                }, 1500);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                var errorMessage = 'Registration failed';
                try {
                    var response = JSON.parse(XMLHttpRequest.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = XMLHttpRequest.responseText || errorMessage;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                $("#register-btn").prop('disabled', false).text('Create Account');
            }
        });
    },

    logout: function () {
        // Show confirmation dialog
        if (confirm('Are you sure you want to log out?')) {
            // Clear all stored data
            localStorage.clear();
            
            // Clear cart badge
            this.updateCartBadge(0);
            
            toastr.success('Logged out successfully');
            
            // Update UI to logged out state
            this.updateUI();
            
            // Navigate to home page using SPAPP
            $.spapp.load({
                route: '#view_1'
            });
        }
    },

    handleExpiredSession: function () {
        console.log("Session expired. Setting redirect flag and navigating.");
        
        localStorage.removeItem("user_token");
        localStorage.removeItem("user_data");
        
        // SET A FLAG to tell the next page to show a message.
        localStorage.setItem("session_expired_message", "true");
        
        // Redirect immediately. No setTimeout needed here anymore.
        $.spapp.load({ route: '#view_1' });
    },

    handleAccessDenied: function() {
        console.log("Access denied. Redirecting user.");
        // We don't clear localStorage because the user is still logged in.
        // We just set the message and redirect.
        localStorage.setItem("redirect_message", "Access Denied: You do not have permission to view that page.");
        localStorage.setItem("redirect_message_type", "error"); // We'll use this to show an error toast
        $.spapp.load({ route: '#view_1' });
    },

    updateUI: function() {
        const token = localStorage.getItem("user_token");
        const userData = localStorage.getItem("user_data");
        
        if (token && userData) {
            try {
                const user = JSON.parse(userData);
                this.showLoggedInState(user);
            } catch (e) {
                console.error('Error parsing user data:', e);
                this.showLoggedOutState();
            }
        } else {
            this.showLoggedOutState();
        }
    },

    showLoggedInState: function(user) {
        // Update login button to show user name and make it go to profile
        $('.btn[href="login.html"]').hide();
        
        // Show user profile button with user's name
        $('.btn[href="#view_user-profile"]').show().html(`
            <i class="bi bi-person-circle"></i> ${user.first_name}
        `);
        
        // Add logout functionality if not exists
        if (!$('#logout-btn').length) {
            $('.btn[href="#view_user-profile"]').after(`
                <button id="logout-btn" class="btn btn-outline-secondary ms-2" onclick="UserService.logout()">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            `);
        }

        // Show/hide admin panel based on role
        if (user.role === 'admin') {
            $('.btn[href="index.html#view_admin"]').show();
        } else {
            $('.btn[href="index.html#view_admin"]').hide();
        }
    },

    showLoggedOutState: function() {
        // Show login button, hide user-specific elements
        $('.btn[href="#view_log_in"]').show().text('Log in');
        $('.btn[href="#view_user-profile"]').hide();
        $('.btn[href="index.html#view_admin"]').hide();
        $('.btn[href="index.html#view_cart"]').hide();
        $('#logout-btn').remove();
    },

    getCurrentUser: function() {
        const userData = localStorage.getItem("user_data");
        if (userData) {
            try {
                return JSON.parse(userData);
            } catch (e) {
                console.error('Error parsing user data:', e);
                return null;
            }
        }
        return null;
    },

    isLoggedIn: function() {
        const token = localStorage.getItem("user_token");
        return token && token !== "undefined" && token !== null;
    },

    isAdmin: function() {
        const user = this.getCurrentUser();
        return user && user.role === 'admin';
    },

    requireLogin: function(callback) {
        if (this.isLoggedIn()) {
            if (callback) callback();
        } else {
            toastr.warning('Please log in to continue');
            $.spapp.load({
                route: '#view_log_in'
            });
        }
    },

    requireAdmin: function(callback) {
        if (this.isAdmin()) {
            if (callback) callback();
        } else {
            toastr.error('Admin access required');
            $.spapp.load({
                route: '#view_1'
            });
        }
    },

    updateCartBadge: function(count) {
        $('.badge').text(count || 0);
    },

    // Helper method to get authorization header for API calls
    getAuthHeader: function() {
        const token = localStorage.getItem("user_token");
        return token ? `Bearer ${token}` : null;
    },

    // SPAPP specific: Check if user should access certain views
    canAccessView: function(viewId) {
        switch(viewId) {
            case 'view_admin':
            case 'view_admin-products':
            case 'view_admin-orders':
                return this.isAdmin();
            case 'view_user-profile':
            case 'view_user-orders':
            case 'view_cart':
                return this.isLoggedIn();
            default:
                return true; // Public views
        }
    },

    // Handle view access control
    handleViewAccess: function(viewId) {
        if (!this.canAccessView(viewId)) {
            if (viewId.includes('admin')) {
                this.requireAdmin();
            } else {
                this.requireLogin();
            }
            return false;
        }
        return true;
    }
};