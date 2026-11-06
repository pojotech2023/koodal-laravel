<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Authentication identity only. Everything descriptive lives on UserProfile.
 *
 * `gender` is the exception that stays here: every search inverts on it, so it
 * sits in the hottest WHERE clause in the system and must not require a join.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'aadhaar_verified_at' => 'datetime',
            'is_aadhaar_verified' => 'boolean',
            'last_active_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function privacySettings(): HasOne
    {
        return $this->hasOne(PrivacySetting::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('sort_order');
    }

    public function jadhagams(): HasMany
    {
        return $this->hasMany(UserJadhagam::class)->latest();
    }

    public function approvedJadhagams(): HasMany
    {
        return $this->hasMany(UserJadhagam::class)->approved()->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latestOfMany();
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BlockedUser::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function contactApprovalsRequested(): HasMany
    {
        return $this->hasMany(ContactApproval::class, 'requester_id');
    }

    public function contactApprovalsReceived(): HasMany
    {
        return $this->hasMany(ContactApproval::class, 'owner_id');
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function profileViewsReceived(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_id');
    }

    public function profileViewsMade(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewer_id');
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    // --- Accessors ---------------------------------------------------------

    /** Derived, never stored — a stored age is wrong within a year. */
    public function age(): int
    {
        return $this->date_of_birth?->age ?? 0;
    }

    /** The gender this user should be shown. Search never accepts it as input. */
    public function oppositeGender(): string
    {
        return $this->gender === 'male' ? 'female' : 'male';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    public function isStaff(): bool
    {
        return $this->role && $this->role->name !== 'user';
    }

    public function hasPaidPlan(): bool
    {
        try {
            return ($this->activeSubscription?->plan?->price ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // --- Authorisation -----------------------------------------------------

    /**
     * Permissions, never role names. `$user->hasPermission('users.suspend')`
     * survives a reorganisation of roles; `$user->role->name === 'admin'` does
     * not.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role?->permissions->contains('name', $permission) ?? false;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role?->name, $roles, true);
    }

    // --- Relationship checks -----------------------------------------------

    public function hasBlocked(User $other): bool
    {
        return $this->blocks()->where('blocked_user_id', $other->id)->exists();
    }

    public function isMatchedWith(User $other): bool
    {
        [$one, $two] = $this->id < $other->id ? [$this->id, $other->id] : [$other->id, $this->id];

        return MatchRecord::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->where('status', 'active')
            ->exists();
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->whereHas('role', fn (Builder $q) => $q->where('name', 'user'));
    }

    /** Age filters are expressed as date-of-birth ranges so the index is used. */
    public function scopeAgeBetween(Builder $query, ?int $min, ?int $max): Builder
    {
        if ($min !== null) {
            $query->where('date_of_birth', '<=', Carbon::today()->subYears($min));
        }

        if ($max !== null) {
            $query->where('date_of_birth', '>=', Carbon::today()->subYears($max + 1)->addDay());
        }

        return $query;
    }
}
