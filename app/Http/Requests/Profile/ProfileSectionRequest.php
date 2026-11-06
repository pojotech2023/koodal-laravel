<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for the eight sectioned profile updates.
 *
 * The sectioning is deliberate: the app edits one card at a time, validation
 * messages map to the visible form, and a partial save can never blank a
 * section the user did not open — which one fat PUT with `sometimes` rules
 * gets wrong the first time a client omits a field.
 *
 * `section()` names the config key used by ProfileCompletion so the two cannot
 * drift apart.
 */
abstract class ProfileSectionRequest extends FormRequest
{
    abstract public function section(): string;

    /** Only the keys this section owns. Anything else is silently dropped. */
    public function sectionData(): array
    {
        return $this->safe()->all();
    }
}
