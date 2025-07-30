# Frontend Developer Guide: Forgot Password Implementation

## 🎯 Overview

This guide provides everything needed to implement the forgot password functionality on the frontend. The backend APIs are fully implemented and ready to use.

## 📡 API Endpoints

### Base URL

```
Backend API: http://localhost:8000/api
Frontend URL: http://localhost:9000 (configured in backend)
```

---

## 🔗 API Endpoints Details

### 1. Send Password Reset Link

**Endpoint:** `POST /api/password/forgot`

**Purpose:** Send a password reset email to the user

**Request:**

```javascript
{
  "email": "user@example.com"
}
```

**Response (Success - 200):**

```javascript
{
  "status": "success",
  "message": "Password reset link sent to your email address."
}
```

**Response (Error - 422):**

```javascript
{
  "status": "error",
  "message": "Unable to send password reset link. Please try again."
}
// OR validation errors
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

### 2. Verify Reset Token (Optional)

**Endpoint:** `POST /api/password/verify-token`

**Purpose:** Check if a reset token is still valid before showing the reset form

**Request:**

```javascript
{
  "email": "user@example.com",
  "token": "abc123def456..."
}
```

**Response (Success - 200):**

```javascript
{
  "status": "success",
  "message": "Token is valid."
}
```

**Response (Error - 422):**

```javascript
{
  "status": "error",
  "message": "Invalid or expired reset token."
}
```

---

### 3. Reset Password

**Endpoint:** `POST /api/password/reset`

**Purpose:** Actually reset the user's password

**Request:**

```javascript
{
  "email": "user@example.com",
  "token": "abc123def456...",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response (Success - 200):**

```javascript
{
  "status": "success",
  "message": "Password has been reset successfully."
}
```

**Response (Error - 422):**

```javascript
{
  "status": "error",
  "message": "Invalid or expired reset token."
}
// OR validation errors
{
  "message": "The password field confirmation does not match.",
  "errors": {
    "password": ["The password field confirmation does not match."]
  }
}
```

---

## 🎨 Frontend Implementation

### Page 1: Forgot Password Form

**Route:** `/forgot-password`

**UI Requirements:**

-   Email input field
-   Submit button
-   Loading state
-   Success/error message display

**Example React Component:**

```jsx
import React, { useState } from "react";

const ForgotPassword = () => {
    const [email, setEmail] = useState("");
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [isSuccess, setIsSuccess] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setMessage("");

        try {
            const response = await fetch(
                "http://localhost:8000/api/password/forgot",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ email }),
                }
            );

            const data = await response.json();

            if (response.ok) {
                setIsSuccess(true);
                setMessage(data.message);
                setEmail(""); // Clear form
            } else {
                setIsSuccess(false);
                setMessage(
                    data.message || "An error occurred. Please try again."
                );
            }
        } catch (error) {
            setIsSuccess(false);
            setMessage("Network error. Please check your connection.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="forgot-password-container">
            <h2>Forgot Password</h2>
            <p>
                Enter your email address and we'll send you a link to reset your
                password.
            </p>

            <form onSubmit={handleSubmit}>
                <div className="form-group">
                    <label htmlFor="email">Email Address:</label>
                    <input
                        type="email"
                        id="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        disabled={loading}
                        placeholder="Enter your email address"
                    />
                </div>

                <button type="submit" disabled={loading || !email}>
                    {loading ? "Sending..." : "Send Reset Link"}
                </button>
            </form>

            {message && (
                <div className={`message ${isSuccess ? "success" : "error"}`}>
                    {message}
                </div>
            )}

            <div className="back-to-login">
                <a href="/login">Back to Login</a>
            </div>
        </div>
    );
};

export default ForgotPassword;
```

---

### Page 2: Reset Password Form

**Route:** `/reset-password`

**URL Parameters Expected:**

-   `token` - The reset token from email
-   `email` - The user's email address

**Example URL from email:**

```
http://localhost:9000/reset-password?token=abc123def456&email=user@example.com
```

**UI Requirements:**

-   Email field (pre-filled and readonly)
-   Token field (hidden, auto-filled from URL)
-   New password field
-   Confirm password field
-   Submit button
-   Loading state
-   Success/error message display

**Example React Component:**

```jsx
import React, { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";

const ResetPassword = () => {
    const [formData, setFormData] = useState({
        email: "",
        token: "",
        password: "",
        password_confirmation: "",
    });
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [isSuccess, setIsSuccess] = useState(false);
    const [tokenValid, setTokenValid] = useState(null);

    const location = useLocation();
    const navigate = useNavigate();

    useEffect(() => {
        // Extract token and email from URL parameters
        const urlParams = new URLSearchParams(location.search);
        const token = urlParams.get("token");
        const email = urlParams.get("email");

        if (token && email) {
            setFormData((prev) => ({ ...prev, token, email }));
            verifyToken(token, email);
        } else {
            setMessage(
                "Invalid reset link. Please request a new password reset."
            );
            setIsSuccess(false);
        }
    }, [location]);

    const verifyToken = async (token, email) => {
        try {
            const response = await fetch(
                "http://localhost:8000/api/password/verify-token",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ token, email }),
                }
            );

            const data = await response.json();
            setTokenValid(response.ok);

            if (!response.ok) {
                setMessage(data.message || "Invalid or expired reset token.");
                setIsSuccess(false);
            }
        } catch (error) {
            setTokenValid(false);
            setMessage("Unable to verify reset token. Please try again.");
            setIsSuccess(false);
        }
    };

    const handleChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setMessage("");

        // Client-side validation
        if (formData.password !== formData.password_confirmation) {
            setMessage("Passwords do not match.");
            setIsSuccess(false);
            setLoading(false);
            return;
        }

        if (formData.password.length < 8) {
            setMessage("Password must be at least 8 characters long.");
            setIsSuccess(false);
            setLoading(false);
            return;
        }

        try {
            const response = await fetch(
                "http://localhost:8000/api/password/reset",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify(formData),
                }
            );

            const data = await response.json();

            if (response.ok) {
                setIsSuccess(true);
                setMessage(data.message);
                // Redirect to login after 3 seconds
                setTimeout(() => {
                    navigate("/login");
                }, 3000);
            } else {
                setIsSuccess(false);
                setMessage(
                    data.message || "An error occurred. Please try again."
                );
            }
        } catch (error) {
            setIsSuccess(false);
            setMessage("Network error. Please check your connection.");
        } finally {
            setLoading(false);
        }
    };

    if (tokenValid === false) {
        return (
            <div className="reset-password-container">
                <h2>Invalid Reset Link</h2>
                <div className="message error">{message}</div>
                <div className="actions">
                    <a href="/forgot-password">Request New Reset Link</a>
                    <a href="/login">Back to Login</a>
                </div>
            </div>
        );
    }

    if (tokenValid === null) {
        return (
            <div className="reset-password-container">
                <h2>Verifying Reset Link...</h2>
                <div className="loading">
                    Please wait while we verify your reset link.
                </div>
            </div>
        );
    }

    return (
        <div className="reset-password-container">
            <h2>Reset Password</h2>
            <p>Enter your new password below.</p>

            <form onSubmit={handleSubmit}>
                <div className="form-group">
                    <label htmlFor="email">Email Address:</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value={formData.email}
                        readOnly
                        className="readonly"
                    />
                </div>

                <div className="form-group">
                    <label htmlFor="password">New Password:</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        value={formData.password}
                        onChange={handleChange}
                        required
                        disabled={loading}
                        placeholder="Enter new password (min 8 characters)"
                        minLength="8"
                    />
                </div>

                <div className="form-group">
                    <label htmlFor="password_confirmation">
                        Confirm Password:
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        value={formData.password_confirmation}
                        onChange={handleChange}
                        required
                        disabled={loading}
                        placeholder="Confirm new password"
                        minLength="8"
                    />
                </div>

                <button
                    type="submit"
                    disabled={
                        loading ||
                        !formData.password ||
                        !formData.password_confirmation
                    }
                >
                    {loading ? "Resetting..." : "Reset Password"}
                </button>
            </form>

            {message && (
                <div className={`message ${isSuccess ? "success" : "error"}`}>
                    {message}
                    {isSuccess && <div>Redirecting to login page...</div>}
                </div>
            )}
        </div>
    );
};

export default ResetPassword;
```

---

## 🎨 CSS Styling Examples

```css
.forgot-password-container,
.reset-password-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.form-group input.readonly {
    background-color: #f5f5f5;
    color: #666;
}

button {
    width: 100%;
    background-color: #007bff;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
}

button:hover:not(:disabled) {
    background-color: #0056b3;
}

button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

.message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
}

.message.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.message.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.back-to-login,
.actions {
    text-align: center;
    margin-top: 20px;
}

.back-to-login a,
.actions a {
    color: #007bff;
    text-decoration: none;
    margin: 0 10px;
}

.back-to-login a:hover,
.actions a:hover {
    text-decoration: underline;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #666;
}
```

---

## 🔀 Router Configuration

### React Router Example:

```jsx
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import ForgotPassword from "./components/ForgotPassword";
import ResetPassword from "./components/ResetPassword";

function App() {
    return (
        <Router>
            <Routes>
                <Route path="/forgot-password" element={<ForgotPassword />} />
                <Route path="/reset-password" element={<ResetPassword />} />
                {/* Other routes */}
            </Routes>
        </Router>
    );
}
```

### Next.js Pages Router:

```
pages/
  forgot-password.js
  reset-password.js
```

### Vue Router Example:

```javascript
const routes = [
    { path: "/forgot-password", component: ForgotPassword },
    { path: "/reset-password", component: ResetPassword },
];
```

---

## 📧 Email Flow Understanding

1. **User clicks "Forgot Password"** → Submits email → Backend sends email
2. **User receives email** with link like: `http://localhost:9000/reset-password?token=abc123&email=user@example.com`
3. **User clicks email link** → Frontend extracts token & email from URL
4. **Frontend verifies token** (optional but recommended)
5. **User enters new password** → Frontend submits reset request
6. **Password reset success** → Redirect to login

---

## ⚠️ Important Security Notes

1. **HTTPS in Production:** Always use HTTPS for password reset functionality
2. **Token Expiration:** Tokens expire in 60 minutes
3. **Rate Limiting:** Users can only request reset once per minute
4. **Validation:** Frontend should validate password strength
5. **CORS:** Ensure CORS is properly configured for your frontend domain

---

## 🔧 Configuration Required

### Backend Configuration (Already Done):

-   ✅ API endpoints implemented
-   ✅ Email configuration set up
-   ✅ Frontend URL configured: `http://localhost:9000`

### Frontend Configuration Needed:

1. **Update API Base URL** if different from `http://localhost:8000/api`
2. **Configure routes** for forgot-password and reset-password pages
3. **Add CORS headers** if needed for API calls
4. **Style components** to match your app's design

---

## 🧪 Testing Checklist

### Test Cases:

-   [ ] Forgot password with valid email
-   [ ] Forgot password with invalid email
-   [ ] Forgot password with empty email
-   [ ] Reset password with valid token
-   [ ] Reset password with expired token
-   [ ] Reset password with mismatched passwords
-   [ ] Reset password with weak password
-   [ ] URL without token/email parameters
-   [ ] Network error handling

### Test Tools:

-   Use the test page: `http://localhost:8000/password-reset-test.html`
-   Check emails in Mailtrap: https://mailtrap.io/
-   Use browser network tab to inspect API calls

---

## 🆘 Common Issues & Solutions

### Issue: "CORS Error"

**Solution:** Add CORS headers to Laravel backend or use a proxy

### Issue: "Token Invalid"

**Solution:** Check URL parameters are being parsed correctly

### Issue: "Email not received"

**Solution:** Check Mailtrap inbox and backend email configuration

### Issue: "Network Error"

**Solution:** Verify backend is running on correct port (8000)

---

## 📞 Support

If you encounter any issues during implementation:

1. Check the browser console for JavaScript errors
2. Check the network tab for API call details
3. Verify the backend is running and accessible
4. Test with the provided test page first

The backend is fully implemented and ready to use! 🚀
