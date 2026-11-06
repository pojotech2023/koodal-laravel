<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The wide table, read on nearly every screen.
 *
 * `$guarded = []` is safe here because every write arrives through a sectioned
 * FormRequest with an explicit rule set — mass assignment is guarded at the
 * door rather than by a fillable list that drifts from the schema.
 */
class UserProfile extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    /** The sections a completion percentage is assembled from. */
    public const SECTIONS = [
        'basic', 'photos', 'religious', 'education', 'professional',
        'location', 'family', 'lifestyle', 'about', 'preferences',
    ];

    protected function casts(): array
    {
        return [
            'hobbies' => 'array',
            'interests' => 'array',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'completion_percentage' => 'integer',
            'profile_views_count' => 'integer',
            'shortlisted_count' => 'integer',
            'interests_received_count' => 'integer',
        ];
    }

    /**
     * The `time` column comes back from MySQL as `H:i:s`. The wizard form and
     * `UpdateReligiousRequest`'s `date_format:H:i` rule both work in plain
     * `H:i` — trimming seconds here, once, keeps every reader (this form,
     * OwnProfileResource) in that same format instead of each guessing at it.
     */
    protected function birthTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? substr($value, 0, 5) : $value,
        );
    }

    // --- Relations ---------------------------------------------------------

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function subCommunity(): BelongsTo
    {
        return $this->belongsTo(SubCommunity::class);
    }

    public function motherTongue(): BelongsTo
    {
        return $this->belongsTo(MotherTongue::class);
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    public function incomeRange(): BelongsTo
    {
        return $this->belongsTo(IncomeRange::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function workCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'work_country_id');
    }

    public function workState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'work_state_id');
    }

    public function workCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'work_city_id');
    }

    public function primaryPhoto(): BelongsTo
    {
        return $this->belongsTo(ProfilePhoto::class, 'primary_photo_id');
    }

    /**
     * Photos belong to the *user*, not to this row, so the relation is keyed on
     * user_id at both ends. It exists here because every profile payload needs
     * them and going through `profile->user->photos` costs an extra hop on the
     * hottest read in the product.
     *
     * Approved only: a pending or rejected photo is the owner's business, and
     * the owner's own album comes from User::photos().
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class, 'user_id', 'user_id')
            ->approved()
            ->orderBy('sort_order');
    }

    /** Every relation the profile endpoints need, in one place. */
    public const WITH_LOOKUPS = [
        'maritalStatus:id,name', 'religion:id,name', 'community:id,name',
        'subCommunity:id,name', 'motherTongue:id,name', 'education:id,name',
        'occupation:id,name', 'incomeRange:id,name', 'country:id,name',
        'state:id,name', 'city:id,name',
    ];

    // --- Accessors ---------------------------------------------------------

    /**
     * The owning User, with the inverse `profile` relation already wired.
     *
     * **Not a relation** — a plain accessor, despite the name. `$this->user`
     * is the relation; this returns the same object after hanging `$this` back
     * off it as `user->profile`.
     *
     * Why it has to exist. ProfileVisibility::isDiscoverable() reads
     * `$owner->profile?->approval_status` on every visibility question, and it
     * is asked once per card. Nothing eager-loads `user.profile` — it would be
     * a second fetch of rows the query already has — so without this the call
     * lazy-loads per row, which is a 20-query listing page in production and a
     * LazyLoadingViolationException everywhere else.
     *
     * ProfileResource has done exactly this inline since it was written; the
     * Blade cards (search, matches, interests, shortlist and the signed-out
     * preview) each ask the same question through <x-profile.card> and none of
     * them did. Moving it onto the model is the same call as
     * displayNameFor() — a rule both front doors need belongs here, not
     * duplicated into a Resource and five views.
     */
    public function owner(): ?User
    {
        $owner = $this->user;

        if ($owner && ! $owner->relationLoaded('profile')) {
            $owner->setRelation('profile', $this);
        }

        return $owner;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Surnames are masked for viewers who have not connected — a full name is
     * enough to find someone on social media, which is the most common
     * complaint on platforms like this. Shared between `ProfileResource` (API)
     * and every `Web\*` profile card/detail view, so the rule cannot drift
     * between the two front doors.
     *
     * `viewer_is_matched` is read first when present — `ViewerState::hydrate`
     * sets it for a whole page in three queries, and falling back to a query
     * here would mean one extra round trip per card on every list.
     */
    public function displayNameFor(?User $viewer): string
    {
        $owner = $this->user;

        $matched = isset($this->viewer_is_matched)
            ? (bool) $this->viewer_is_matched
            : ($viewer && $viewer->isMatchedWith($owner));

        if ($matched) {
            return $owner->name;
        }

        $parts = explode(' ', trim($owner->name));
        $first = array_shift($parts);

        return $parts === [] ? $first : $first.' '.strtoupper(substr($parts[count($parts) - 1], 0, 1)).'.';
    }

    /** Height rendered the way Indian users state it: 5' 7" (170 cm). */
    public function heightLabel(): ?string
    {
        if (! $this->height_cm) {
            return null;
        }

        $inches = (int) round($this->height_cm / 2.54);

        return sprintf("%d' %d\" (%d cm)", intdiv($inches, 12), $inches % 12, $this->height_cm);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithPhoto(Builder $query): Builder
    {
        return $query->whereNotNull('primary_photo_id');
    }

    /**
     * JSON multi-select preferences resolve to an IN, and an empty array must
     * mean "no constraint" rather than "match nothing" — the difference between
     * a full result set and a blank screen.
     */
    public function scopeWhereIdIn(Builder $query, string $column, ?array $ids): Builder
    {
        return empty($ids) ? $query : $query->whereIn($column, $ids);
    }
}
