<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'domain_forwarding_url',
        'password',
        'role_id',
        'status',
        'phone',
        'profile_image',
        'chargebee_customer_id',
        'billing_company',
        'billing_address',
        'billing_address2',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_zip',
        'billing_landmark',
        'billing_address_syn',
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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

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

    public function logs()
    {
        return $this->hasMany(Log::class, 'performed_by');
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function ticketReplies()
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Get the user's latest order with plan and reorder info
     */
    public function latestOrder()
    {
        return $this->orders()->with(['plan', 'reorderInfo', 'subscription'])->latest()->first();
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

    public function panels()
{
    return $this->belongsToMany(Panel::class)
                ->withTimestamps()
                ->withPivot('accepted_at', 'released_at');
}

}
