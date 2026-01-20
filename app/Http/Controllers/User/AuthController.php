<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ForgotPasswordRequest;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\Auth\ResetPasswordRequest;
use App\Http\Requests\User\Auth\UpdateProfileRequest;
use App\Http\Requests\User\Auth\UploadAvatarRequest;
use App\Services\User\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * User Registration
     *
     * Register a new user account. Returns authentication token and user details.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @bodyParam first_name string required The user's first name. Example: John
     * @bodyParam last_name string required The user's last name. Example: Doe
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The user's password. Example: password123
     * @bodyParam password_confirmation string required The password confirmation. Example: password123
     *
     * @response 201 scenario=Success {
     *     "code": 201,
     *     "message": "Registration successful. Please verify your email.",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "John",
     *             "last_name": "Doe",
     *             "date_of_birth": null,
     *             "email": "john.doe@example.com",
     *             "phone": null,
     *             "address": null,
     *             "city": null,
     *             "state": null,
     *             "email_verified_at": null,
     *             "avatar": null,
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "USER"
     *             },
     *             "permissions": []
     *         },
     *         "token": "2|..."
     *     }
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "first_name": ["The first name is required."],
     *         "last_name": ["The last name is required."],
     *         "email": ["The email address is required.", "This email address is already registered."],
     *         "password": ["The password is required.", "The password confirmation does not match."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred during registration",
     *     "error": "Error message details"
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->authService->register($request)->toJson();
    }

    /**
     * User Login
     *
     * Authenticate a user with email and password. Returns authentication token and user details.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Login successful",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "John",
     *             "last_name": "Doe",
     *             "date_of_birth": "1990-01-01",
     *             "email": "john.doe@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "USER"
     *             },
     *             "permissions": []
     *         },
     *         "token": "2|..."
     *     }
     * }
     * @response 401 scenario=InvalidCredentials {
     *     "code": 401,
     *     "message": "The provided credentials are incorrect."
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "email": ["The email address is required."],
     *         "password": ["The password is required."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred during authentication",
     *     "error": "Error message details"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->authService->login($request)->toJson();
    }

    /**
     * User Logout
     *
     * Logout the authenticated user and revoke the current access token.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Logged out successfully"
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred during logout",
     *     "error": "Error message details"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->authService->logout($request)->toJson();
    }

    /**
     * Verify Email
     *
     * Verify the user's email address using the verification link.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @urlParam id string required The user's ID. Example: 1
     * @urlParam hash string required The email verification hash. Example: abc123
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Email verified successfully"
     * }
     * @response 400 scenario=AlreadyVerified {
     *     "code": 400,
     *     "message": "Email already verified."
     * }
     * @response 400 scenario=InvalidLink {
     *     "code": 400,
     *     "message": "Invalid verification link."
     * }
     * @response 404 scenario=UserNotFound {
     *     "code": 404,
     *     "message": "User not found."
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred during email verification",
     *     "error": "Error message details"
     * }
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        return $this->authService->verifyEmail($request)->toJson();
    }

    /**
     * Resend Verification Email
     *
     * Resend email verification notification to the authenticated user.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Verification email sent successfully"
     * }
     * @response 400 scenario=AlreadyVerified {
     *     "code": 400,
     *     "message": "Email already verified."
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while sending verification email",
     *     "error": "Error message details"
     * }
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        return $this->authService->resendVerificationEmail($request)->toJson();
    }

    /**
     * Forgot Password
     *
     * Send a password reset link to the user's email address.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Password reset link sent to your email."
     * }
     * @response 400 scenario=UnableToSend {
     *     "code": 400,
     *     "message": "Unable to send password reset link. Please try again."
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "email": ["The email address is required."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while sending password reset link",
     *     "error": "Error message details"
     * }
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->authService->forgotPassword($request)->toJson();
    }

    /**
     * Reset Password
     *
     * Reset the user's password using the reset token.
     *
     * @group User Management
     *
     * @subgroup Authentication
     *
     * @bodyParam token string required The password reset token. Example: abc123xyz
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The new password. Example: newpassword123
     * @bodyParam password_confirmation string required The password confirmation. Example: newpassword123
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Password reset successfully."
     * }
     * @response 400 scenario=InvalidToken {
     *     "code": 400,
     *     "message": "Invalid or expired reset token."
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "token": ["The reset token is required."],
     *         "email": ["The email address is required."],
     *         "password": ["The password is required.", "The password confirmation does not match."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while resetting password",
     *     "error": "Error message details"
     * }
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->authService->resetPassword($request)->toJson();
    }

    /**
     * Get Profile
     *
     * Get the authenticated user's profile information.
     *
     * @group User Management
     *
     * @subgroup Profile
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Profile retrieved successfully",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "John",
     *             "last_name": "Doe",
     *             "date_of_birth": "1990-01-01",
     *             "email": "john.doe@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "USER"
     *             },
     *             "permissions": []
     *         }
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while retrieving profile",
     *     "error": "Error message details"
     * }
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->authService->profile($request)->toJson();
    }

    /**
     * Update Profile
     *
     * Update the authenticated user's profile information.
     *
     * @group User Management
     *
     * @subgroup Profile
     *
     * @authenticated
     *
     * @bodyParam first_name string optional The user's first name. Example: John
     * @bodyParam last_name string optional The user's last name. Example: Doe
     * @bodyParam date_of_birth string optional The user's date of birth. Example: 1990-01-01
     * @bodyParam phone string optional The user's phone number. Example: +1234567890
     * @bodyParam address string optional The user's address. Example: 123 Main St
     * @bodyParam city string optional The user's city. Example: New York
     * @bodyParam state string optional The user's state. Example: NY
     * @bodyParam email string optional The user's email address. Example: john.doe@example.com
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Profile updated successfully",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "John",
     *             "last_name": "Doe",
     *             "date_of_birth": "1990-01-01",
     *             "email": "john.doe@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "USER"
     *             },
     *             "permissions": []
     *         }
     *     }
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "email": ["This email address is already taken."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while updating profile",
     *     "error": "Error message details"
     * }
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->authService->updateProfile($request)->toJson();
    }

    /**
     * Upload Avatar
     *
     * Upload an avatar image for the authenticated user.
     *
     * @group User Management
     *
     * @subgroup Profile
     *
     * @authenticated
     *
     * @bodyParam avatar file required The avatar image file. Must be jpeg, png, jpg, or gif. Max size: 2MB.
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Avatar uploaded successfully",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "John",
     *             "last_name": "Doe",
     *             "date_of_birth": "1990-01-01",
     *             "email": "john.doe@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "USER"
     *             },
     *             "permissions": []
     *         }
     *     }
     * }
     * @response 422 scenario=ValidationError {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "avatar": ["The avatar image is required."]
     *     }
     * }
     * @response 500 scenario=ServerError {
     *     "code": 500,
     *     "message": "An error occurred while uploading avatar",
     *     "error": "Error message details"
     * }
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        return $this->authService->uploadAvatar($request)->toJson();
    }
}
