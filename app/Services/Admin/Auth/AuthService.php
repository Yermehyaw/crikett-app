<?php

namespace App\Services\Admin\Auth;

use LaravelEasyRepository\BaseService;

interface AuthService extends BaseService
{
    public function login($request);

    public function logout($request);

    public function verifyEmail($request);

    public function resendVerificationEmail($request);

    public function profile($request);

    public function updateProfile($request);

    public function uploadAvatar($request);
}
