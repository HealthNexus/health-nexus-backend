# Password Reset API Documentation

## Overview

The Health Nexus Backend provides a complete password reset functionality that allows users to reset their passwords via email verification.

## API Endpoints

### 1. Request Password Reset

**Endpoint:** `POST /api/password/forgot`

**Description:** Sends a password reset email to the user's registered email address.

**Request Body:**

```json
{
    "email": "user@example.com"
}
```

**Validation Rules:**

-   `email`: Required, must be a valid email format, must exist in the users table

**Success Response:**

```json
{
    "status": "success",
    "message": "Password reset link sent to your email address."
}
```

**Error Response:**

```json
{
    "status": "error",
    "message": "Unable to send password reset link. Please try again."
}
```

**Example cURL:**

```bash
curl -X POST http://localhost:8000/api/password/forgot \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "user@example.com"}'
```

---

### 2. Verify Reset Token

**Endpoint:** `POST /api/password/verify-token`

**Description:** Verifies if a password reset token is valid and not expired.

**Request Body:**

```json
{
    "email": "user@example.com",
    "token": "abc123def456"
}
```

**Validation Rules:**

-   `email`: Required, must be a valid email format
-   `token`: Required

**Success Response:**

```json
{
    "status": "success",
    "message": "Token is valid."
}
```

**Error Response:**

```json
{
    "status": "error",
    "message": "Invalid or expired reset token."
}
```

**Example cURL:**

```bash
curl -X POST http://localhost:8000/api/password/verify-token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "user@example.com", "token": "abc123def456"}'
```

---

### 3. Reset Password

**Endpoint:** `POST /api/password/reset`

**Description:** Resets the user's password using the provided token.

**Request Body:**

```json
{
    "email": "user@example.com",
    "token": "abc123def456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Validation Rules:**

-   `email`: Required, must be a valid email format
-   `token`: Required
-   `password`: Required, must be confirmed, must meet password requirements
-   `password_confirmation`: Required, must match password

**Success Response:**

```json
{
    "status": "success",
    "message": "Password has been reset successfully."
}
```

**Error Response:**

```json
{
    "status": "error",
    "message": "Invalid or expired reset token."
}
```

**Example cURL:**

```bash
curl -X POST http://localhost:8000/api/password/reset \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "token": "abc123def456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }'
```

---

## Configuration

### Email Configuration

The system uses the mail configuration in `config/mail.php`. Currently configured for Mailtrap for testing:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=a33a427b41e493
MAIL_PASSWORD=6eb382bf1d051a
MAIL_FROM_ADDRESS="support@admin.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Frontend URL Configuration

The password reset email contains a link to your frontend application:

```env
APP_FRONTEND_URL=http://localhost:3000
```

### Password Reset Settings

Configure in `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // Token expires in 60 minutes
        'throttle' => 60, // Throttle requests to 60 seconds
    ],
],
```

---

## Email Template

The system sends a custom email using the `ResetPasswordNotification` class. The email includes:

-   Personalized greeting
-   Clear explanation of the password reset request
-   Secure reset button/link
-   Expiration time information
-   Security notice
-   Professional signature

The reset URL format: `{FRONTEND_URL}/reset-password?token={TOKEN}&email={EMAIL}`

---

## Security Features

1. **Token Expiration**: Reset tokens expire after 60 minutes
2. **Request Throttling**: Users can only request one reset email per minute
3. **Token Validation**: Tokens are cryptographically secure and validated
4. **Single Use**: Tokens are invalidated after successful password reset
5. **Session Revocation**: All existing user sessions are terminated after password reset

---

## Frontend Integration

### 1. Forgot Password Form

Create a form that submits to `/api/password/forgot`:

```javascript
const forgotPassword = async (email) => {
    const response = await fetch("/api/password/forgot", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({ email }),
    });

    const data = await response.json();
    return data;
};
```

### 2. Password Reset Form

Create a form that handles the reset token from email:

```javascript
const resetPassword = async (email, token, password, passwordConfirmation) => {
    const response = await fetch("/api/password/reset", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({
            email,
            token,
            password,
            password_confirmation: passwordConfirmation,
        }),
    });

    const data = await response.json();
    return data;
};
```

### 3. Token Verification (Optional)

Verify token before showing reset form:

```javascript
const verifyToken = async (email, token) => {
    const response = await fetch("/api/password/verify-token", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({ email, token }),
    });

    const data = await response.json();
    return data;
};
```

---

## Testing

Use the provided test script `test_password_reset.php` to verify all endpoints work correctly:

```bash
php test_password_reset.php
```

Make sure to:

1. Start your Laravel development server: `php artisan serve`
2. Have a test user in your database
3. Check Mailtrap for received emails

---

## Troubleshooting

### Common Issues

1. **Email not sent**: Check mail configuration and Mailtrap credentials
2. **Token invalid**: Ensure tokens haven't expired (60 minutes)
3. **Frontend URL incorrect**: Verify `APP_FRONTEND_URL` in `.env`
4. **Database errors**: Ensure `password_reset_tokens` table exists

### Debug Mode

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```
