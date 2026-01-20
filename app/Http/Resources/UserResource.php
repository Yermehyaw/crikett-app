<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->roles->first();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'avatar' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'role' => $role ? [
                'name' => $role->name,
            ] : null,
            'permissions' => $this->getAllPermissionNames(),
        ];
    }
}
