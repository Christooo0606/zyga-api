<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleType::class,
            'user_roles',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class, 'user_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    public function assistanceRequests(): HasMany
    {
        return $this->hasMany(AssistanceRequest::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(UserSubscriptionPlan::class, 'user_id');
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles->contains('code', $roleCode);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }
}
