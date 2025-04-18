<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'subscription_id',
        'subscription_status',
        'plan_id',
        'role_id', // Add role attribute
        'billing_address', // Add billing address attribute
        'domain_forwarding_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Check if user can subscribe to the given plan
     */
    public function canSubscribeToPlan(Plan $plan): bool
    {
        // If user already has an active subscription, they can't subscribe
        if ($this->subscription_status === 'active') {
            return false;
        }
        
        // Add any other business rules for plan eligibility here
        
        return true;
    }
}
