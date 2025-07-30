# Quick API Reference - Password Reset

## Base URLs

-   **Backend API:** `http://localhost:8000/api`
-   **Frontend URL:** `http://localhost:9000` (configured in backend)

## API Endpoints

### 1. Send Reset Link

```http
POST /api/password/forgot
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Success Response (200):**

```json
{
    "status": "success",
    "message": "Password reset link sent to your email address."
}
```

### 2. Verify Token (Optional)

```http
POST /api/password/verify-token
Content-Type: application/json

{
  "email": "user@example.com",
  "token": "abc123def456"
}
```

**Success Response (200):**

```json
{
    "status": "success",
    "message": "Token is valid."
}
```

### 3. Reset Password

```http
POST /api/password/reset
Content-Type: application/json

{
  "email": "user@example.com",
  "token": "abc123def456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Success Response (200):**

```json
{
    "status": "success",
    "message": "Password has been reset successfully."
}
```

## Email Link Format

```
http://localhost:9000/reset-password?token={TOKEN}&email={EMAIL}
```

## JavaScript Fetch Examples

### Send Reset Link

```javascript
const sendResetLink = async (email) => {
    const response = await fetch("http://localhost:8000/api/password/forgot", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({ email }),
    });
    return response.json();
};
```

### Reset Password

```javascript
const resetPassword = async (email, token, password, passwordConfirmation) => {
    const response = await fetch("http://localhost:8000/api/password/reset", {
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
    return response.json();
};
```

## cURL Examples

### Send Reset Link

```bash
curl -X POST http://localhost:8000/api/password/forgot \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "user@example.com"}'
```

### Reset Password

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

## Frontend Pages Needed

1. **Forgot Password Form** (`/forgot-password`) - Submit email
2. **Reset Password Form** (`/reset-password`) - Accept token & email from URL, submit new password

## Security Notes

-   Tokens expire in 60 minutes
-   Rate limit: 1 request per minute per email
-   Password must be at least 8 characters
-   All user sessions are revoked after password reset
