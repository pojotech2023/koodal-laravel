<?php

namespace App\Http\Requests\Admin;

use App\Support\CmsResources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One FormRequest over four content types, matching the single controller the
 * routes point at. The rules are keyed on the type resolved from the matched
 * route — see `CmsResources` for why that is where the type comes from.
 *
 * Every field is `sometimes` on a PATCH/PUT and required on a POST, computed
 * once in `requiredOnCreate()`. Writing `required` outright would make every
 * edit resend the whole record, and a publish toggle that has to resend a
 * 40 KB page body is one that will eventually resend a stale one.
 */
class CmsContentRequest extends FormRequest
{
    public function rules(): array
    {
        $type = CmsResources::typeFor($this);

        return match ($type) {
            'pages' => $this->pageRules(),
            'faqs' => $this->faqRules(),
            'banners' => $this->bannerRules(),
            'success-stories' => $this->successStoryRules(),
        };
    }

    // --- Per-type rules ----------------------------------------------------

    protected function pageRules(): array
    {
        return [
            'slug' => [
                $this->requiredOnCreate(), 'string', 'max:100', 'alpha_dash',
                // Pages are looked up by slug on the public route, so a
                // duplicate is not a cosmetic clash — it is two rows competing
                // for one URL.
                Rule::unique('cms_pages', 'slug')->ignore($this->routeId()),
            ],
            'title' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:200000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_image_path' => ['nullable', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function faqRules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:60'],
            'question' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'answer' => [$this->requiredOnCreate(), 'string', 'max:20000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    protected function bannerRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'image_path' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'mobile_image_path' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'position' => [$this->requiredOnCreate(), Rule::in(['home_hero', 'home_mid', 'app_home'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'starts_at' => ['nullable', 'date'],
            // A window that ends before it starts is a banner that never shows,
            // and nobody notices until the campaign is over.
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function successStoryRules(): array
    {
        return [
            'groom_name' => [$this->requiredOnCreate(), 'string', 'max:120'],
            'bride_name' => [$this->requiredOnCreate(), 'string', 'max:120'],
            'groom_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'bride_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'marriage_date' => ['nullable', 'date', 'before_or_equal:today'],
            'story' => ['nullable', 'string', 'max:20000'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:120'],
            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    // --- Internals ---------------------------------------------------------

    protected function requiredOnCreate(): string
    {
        return $this->isMethod('POST') ? 'required' : 'sometimes';
    }

    /** The bound id, whatever the apiResource named its parameter. */
    protected function routeId(): ?int
    {
        $parameters = array_values($this->route()?->parameters() ?? []);

        return isset($parameters[0]) ? (int) $parameters[0] : null;
    }
}
