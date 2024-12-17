// Authentication JavaScript

// Handle Google Sign-In response
function handleCredentialResponse(response) {
    // Send the credential to our backend
    fetch('/backend/api/auth/google.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            credential: response.credential
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.token) {
            // Store the token
            localStorage.setItem('auth_token', data.token);
            // Store user data
            localStorage.setItem('user', JSON.stringify(data.user));
            // Redirect to dashboard
            window.location.href = 'dashboard.html';
        } else {
            throw new Error(data.error || 'Authentication failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Authentication failed: ' + error.message);
    });
}

// Handle login form submission
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            email: this.email.value,
            password: this.password.value
        };

        fetch('/backend/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                // Store the token
                localStorage.setItem('auth_token', data.token);
                // Store user data
                localStorage.setItem('user', JSON.stringify(data.user));
                // Store remember me preference
                if (this.rememberMe.checked) {
                    localStorage.setItem('remember_me', 'true');
                }
                // Redirect to dashboard
                window.location.href = 'dashboard.html';
            } else {
                throw new Error(data.error || 'Login failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Login failed: ' + error.message);
        });
    });
}

// Handle registration form submission
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (this.password.value !== this.confirmPassword.value) {
            alert('Passwords do not match');
            return;
        }

        const formData = {
            name: this.name.value,
            email: this.email.value,
            password: this.password.value
        };

        fetch('/backend/api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Registration successful! Please check your email to verify your account.');
                // Redirect to login page
                window.location.href = 'login.html';
            } else {
                throw new Error(data.error || 'Registration failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Registration failed: ' + error.message);
        });
    });
}

// Handle forgot password form submission
const forgotPasswordForm = document.getElementById('forgotPasswordForm');
if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            email: this.email.value
        };

        fetch('/backend/api/auth/forgot-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            } else {
                throw new Error(data.error || 'Request failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Request failed: ' + error.message);
        });
    });
}

// Handle reset password form submission
const resetPasswordForm = document.getElementById('resetPasswordForm');
if (resetPasswordForm) {
    resetPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (this.password.value !== this.confirmPassword.value) {
            alert('Passwords do not match');
            return;
        }

        const formData = {
            token: this.token.value,
            password: this.password.value
        };

        fetch('/backend/api/auth/reset-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            } else {
                throw new Error(data.error || 'Password reset failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Password reset failed: ' + error.message);
        });
    });
}

// Handle resend verification email form submission
const resendVerificationForm = document.getElementById('resendVerificationForm');
if (resendVerificationForm) {
    resendVerificationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            email: this.email.value
        };

        fetch('/backend/api/auth/resend-verification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('resendSuccessModal'));
                successModal.show();
            } else {
                throw new Error(data.error || 'Request failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Request failed: ' + error.message);
        });
    });
}

// Check authentication status on protected pages
function checkAuth() {
    const token = localStorage.getItem('auth_token');
    if (!token && !window.location.pathname.endsWith('login.html')) {
        window.location.href = 'login.html';
    }
    return token;
}

// Handle logout
function logout() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    localStorage.removeItem('remember_me');
    window.location.href = 'login.html';
}

// Add authentication headers to fetch requests
function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('auth_token');
    if (token) {
        options.headers = {
            ...options.headers,
            'Authorization': `Bearer ${token}`
        };
    }
    return fetch(url, options);
} 