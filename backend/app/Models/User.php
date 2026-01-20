<?php

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'password',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Set the user's role by detaching existing roles and assigning a new role.
     *
     * @param  string|RoleEnum  $role  The name of the role to assign to the user.
     */
    public function setRole(string|RoleEnum $role): void
    {
        $roleName = $role instanceof RoleEnum ? $role->name() : $role;
        $this->syncRoles($roleName);
    }

    /**
     * Get user role
     */
    public function getRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Get all permission names assigned to the user.
     */
    public function getAllPermissionNames(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has a permission
     *
     * @param  string|PermissionEnum  $permission  name or PermissionEnum case
     */
    public function hasPermission(string|PermissionEnum $permission): bool
    {
        $permissionName = $permission instanceof PermissionEnum ? $permission->name() : $permission;

        return $this->hasPermissionTo($permissionName);
    }

    /**
     * Send the email verification notification.
     *
     * Overrides the default method to use role-specific verification routes.
     */
    public function sendEmailVerificationNotification(): void
    {
        $routeName = $this->hasRole('ADMIN') || $this->hasRole('OWNER')
            ? 'admin.auth.verification.verify'
            : 'user.auth.verification.verify';

        $verificationUrl = URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        $this->notify(new VerifyEmailNotification($verificationUrl));
    }
}
