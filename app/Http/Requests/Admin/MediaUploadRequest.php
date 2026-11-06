<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The rules are read from `config('matrimony.media.upload')` rather than
 * written here, because the list of accepted types is a security decision
 * documented next to the values — see that config block on why `svg` is
 * absent, which is the one change to this request that would matter.
 *
 * Both `image` and `mimes` on purpose. `mimes` checks the extension against
 * the guessed MIME type; `image` additionally rejects anything PHP cannot
 * read as an image at all, which is what stops a file whose first bytes were
 * made to look like a JPEG header.
 */
class MediaUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', config('matrimony.media.upload.mimes')),
                'max:'.config('matrimony.media.upload.max_size_kb'),
            ],
            'collection' => ['nullable', Rule::in(config('matrimony.media.upload.collections'))],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a '.implode(', ', config('matrimony.media.upload.mimes')).' image. Other formats — SVG included — are not accepted here.',
            'file.max' => 'That file is over '.round(config('matrimony.media.upload.max_size_kb') / 1024, 1).' MB.',
        ];
    }

    public function collection(): string
    {
        return $this->input('collection') ?: 'general';
    }
}
