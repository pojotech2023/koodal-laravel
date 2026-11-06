<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class CmsPage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    /**
     * On the model, not in the view, for the reason PROGRESS.md §6 gives about
     * bare `Str::` in Blade: this skeleton has no `aliases` array in
     * config/app.php, so an unqualified `Storage::` inside a compiled view
     * resolves against the global namespace and throws at render time. Same
     * shape as SuccessStory::photoUrl().
     */
    public function ogImageUrl(): ?string
    {
        return $this->og_image_path
            ? Storage::disk(config('filesystems.default'))->url($this->og_image_path)
            : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --- Scopes ------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
