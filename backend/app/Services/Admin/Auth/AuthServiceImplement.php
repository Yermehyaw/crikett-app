<?php

namespace App\Services\Admin\Auth;

use App\Enums\ResponseCode;
use App\Enums\RoleEnum;
use App\Http\Resources\UserResource;
use App\Repositories\Admin\User\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use LaravelEasyRepository\ServiceApi;

class AuthServiceImplement extends ServiceApi implements AuthService
{
    /**
     * Initialize the service with user repository dependency.
     *
     * @param  UserRepository  $mainRepository  The user repository instance
     */
    public function __construct(
        protected UserRepository $mainRepository
    ) {}

    /**
     * Authenticate an admin user with email and password.
     *
     * Validates user credentials, checks if user has admin or owner role,
     * revokes existing tokens, and generates a new authentication token.
     *
     * @param  mixed  $request  The validated form request containing email and password
     * @return AuthServiceImplement Returns service response with user data and token on success, or error on failure
     */
    public function login($request): AuthServiceImplement
    {
        try {
            DB::beginTransaction();

            $user = $this->mainRepository->findByEmail($request->email);

            if (! $user || ! Hash::check($request->password, $user->password)) {
                DB::rollBack();

                return $this->setCode(ResponseCode::UNAUTHORIZED->value)
                    ->setMessage('The provided credentials are incorrect.');
            }

            $userRole = $user->getRole();
            if (! in_array($userRole, [RoleEnum::ADMIN->name(), RoleEnum::OWNER->name()])) {
                DB::rollBack();

                return $this->setCode(ResponseCode::UNAUTHORIZED->value)
                    ->setMessage('The provided credentials are incorrect.');
            }

            if (! $user->is_active) {
                DB::rollBack();

                return $this->setCode(ResponseCode::FORBIDDEN->value)
                    ->setMessage('Your account is suspended. Please contact support.');
            }

            $user->tokens()->delete();
            $token = $user->createToken('admin_auth_token')->plainTextToken;

            DB::commit();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Login successful')
                ->setData([
                    'user' => new UserResource($user),
                    'token' => $token,
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred during authentication')
                ->setError($e->getMessage());
        }
    }

    /**
     * Logout the authenticated admin user.
     *
     * Revokes the current access token for the authenticated user.
     *
     * @param  mixed  $request  The request instance containing the authenticated user
     * @return AuthServiceImplement Returns service response indicating successful logout
     */
    public function logout($request): AuthServiceImplement
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Logged out successfully');
        } catch (\Exception $e) {
            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred during logout')
                ->setError($e->getMessage());
        }
    }

    /**
     * Verify the user's email address.
     *
     * Verifies the user's email using the signed URL parameters.
     *
     * @param  mixed  $request  The request instance containing user ID and hash
     * @return AuthServiceImplement Returns service response indicating email verification status
     */
    public function verifyEmail($request): AuthServiceImplement
    {
        try {
            $user = $this->mainRepository->findById($request->route('id'));

            if (! $user) {
                return $this->setCode(ResponseCode::NOT_FOUND->value)
                    ->setMessage('User not found.');
            }

            if ($user->hasVerifiedEmail()) {
                return $this->setCode(ResponseCode::BAD_REQUEST->value)
                    ->setMessage('Email already verified.');
            }

            if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                return $this->setCode(ResponseCode::BAD_REQUEST->value)
                    ->setMessage('Invalid verification link.');
            }

            if ($user->markEmailAsVerified()) {
                event(new \Illuminate\Auth\Events\Verified($user));
            }

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Email verified successfully');
        } catch (\Exception $e) {
            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred during email verification')
                ->setError($e->getMessage());
        }
    }

    /**
     * Resend email verification notification.
     *
     * Sends a new email verification notification to the authenticated user.
     *
     * @param  mixed  $request  The request instance containing the authenticated user
     * @return AuthServiceImplement Returns service response indicating notification sent status
     */
    public function resendVerificationEmail($request): AuthServiceImplement
    {
        try {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                return $this->setCode(ResponseCode::BAD_REQUEST->value)
                    ->setMessage('Email already verified.');
            }

            $user->sendEmailVerificationNotification();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Verification email sent successfully');
        } catch (\Exception $e) {
            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred while sending verification email')
                ->setError($e->getMessage());
        }
    }

    /**
     * Get the authenticated admin user's profile.
     *
     * Retrieves the profile information of the authenticated user.
     *
     * @param  mixed  $request  The request instance containing the authenticated user
     * @return AuthServiceImplement Returns service response with user profile data
     */
    public function profile($request): AuthServiceImplement
    {
        try {
            $user = $request->user();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Profile retrieved successfully')
                ->setData([
                    'user' => new UserResource($user),
                ]);
        } catch (\Exception $e) {
            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred while retrieving profile')
                ->setError($e->getMessage());
        }
    }

    /**
     * Update the authenticated admin user's profile.
     *
     * Updates the profile information of the authenticated user.
     *
     * @param  mixed  $request  The validated form request containing profile update data
     * @return AuthServiceImplement Returns service response with updated user profile data
     */
    public function updateProfile($request): AuthServiceImplement
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $user->update($request->only(['first_name', 'last_name', 'date_of_birth', 'phone', 'address', 'city', 'state', 'email']));
            $user->refresh();

            DB::commit();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Profile updated successfully')
                ->setData([
                    'user' => new UserResource($user),
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred while updating profile')
                ->setError($e->getMessage());
        }
    }

    /**
     * Upload avatar for the authenticated admin user.
     *
     * Uploads and stores an avatar image for the authenticated user.
     *
     * @param  mixed  $request  The validated form request containing the avatar file
     * @return AuthServiceImplement Returns service response with updated user profile data
     */
    public function uploadAvatar($request): AuthServiceImplement
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatarPath]);
            $user->refresh();

            DB::commit();

            return $this->setCode(ResponseCode::SUCCESS->value)
                ->setMessage('Avatar uploaded successfully')
                ->setData([
                    'user' => new UserResource($user),
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->setCode(ResponseCode::SERVER_ERROR->value)
                ->setMessage('An error occurred while uploading avatar')
                ->setError($e->getMessage());
        }
    }
}
