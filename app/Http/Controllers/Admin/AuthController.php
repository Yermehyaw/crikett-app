<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\UpdateProfileRequest;
use App\Http\Requests\Admin\Auth\UploadAvatarRequest;
use App\Services\Admin\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Admin Login
     *
     * Authenticate an admin user with email and password. Returns authentication token and user details.
     *
     * @group Admin Management
     *
     * @subgroup Authentication
     *
     * @bodyParam email string required The admin's email address. Example: admin@example.com
     * @bodyParam password string required The admin's password. Example: password123
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Login successful",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "Admin",
     *             "last_name": "User",
     *             "date_of_birth": "1990-01-01",
     *             "email": "admin@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "ADMIN"
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
     * @response 403 scenario=NotAuthorized {
     *     "code": 403,
     *     "message": "You are not authorized to access the admin panel."
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
     * Admin Logout
     *
     * Logout the authenticated admin user and revoke the current access token.
     *
     * @group Admin Management
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
     * Verify the admin user's email address using the verification link.
     *
     * @group Admin Management
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
     * Resend email verification notification to the authenticated admin user.
     *
     * @group Admin Management
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
     * Get Profile
     *
     * Get the authenticated admin user's profile information.
     *
     * @group Admin Management
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
     *             "first_name": "Admin",
     *             "last_name": "User",
     *             "date_of_birth": "1990-01-01",
     *             "email": "admin@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "ADMIN"
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
     * Update the authenticated admin user's profile information.
     *
     * @group Admin Management
     *
     * @subgroup Profile
     *
     * @authenticated
     *
     * @bodyParam first_name string optional The admin's first name. Example: Admin
     * @bodyParam last_name string optional The admin's last name. Example: User
     * @bodyParam date_of_birth string optional The admin's date of birth. Example: 1990-01-01
     * @bodyParam phone string optional The admin's phone number. Example: +1234567890
     * @bodyParam address string optional The admin's address. Example: 123 Main St
     * @bodyParam city string optional The admin's city. Example: New York
     * @bodyParam state string optional The admin's state. Example: NY
     * @bodyParam email string optional The admin's email address. Example: admin@example.com
     *
     * @response 200 scenario=Success {
     *     "code": 200,
     *     "message": "Profile updated successfully",
     *     "data": {
     *         "user": {
     *             "id": "550e8400-e29b-41d4-a716-446655440000",
     *             "first_name": "Admin",
     *             "last_name": "User",
     *             "date_of_birth": "1990-01-01",
     *             "email": "admin@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "ADMIN"
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
     * Upload an avatar image for the authenticated admin user.
     *
     * @group Admin Management
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
     *             "first_name": "Admin",
     *             "last_name": "User",
     *             "date_of_birth": "1990-01-01",
     *             "email": "admin@example.com",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "city": "New York",
     *             "state": "NY",
     *             "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *             "avatar": "http://localhost/storage/avatars/avatar.jpg",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "role": {
     *                 "name": "ADMIN"
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
