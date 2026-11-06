<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\StoreMediaUpload;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\Banner;
use App\Models\CmsPage;
use App\Models\MediaUpload;
use App\Models\SuccessStory;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The media library — the admin panel's only file upload, and the answer to
 * the gap docs/06 §5 left open: banner images, success-story photos and CMS
 * OG images were fields you typed a path into, with nothing in the panel able
 * to put a file at that path.
 *
 * It has **no `Api\V1\Admin` sibling**, unlike every other controller in this
 * namespace. That is not an oversight: the API exists for Flutter and
 * webhooks, neither of which authors a banner. If one is ever needed, the
 * upload itself is already in `StoreMediaUpload` and the controller would be
 * four lines.
 *
 * Two rules this class exists to enforce, both of which outlive it:
 *
 * - **The public disk only.** Everything here is world-readable by design.
 *   Verification documents and report evidence are private-disk files behind
 *   short-lived signed URLs (`VerificationController::show`,
 *   `ReportController::evidence`) and must never be moved through this
 *   library to make them "easier to link" — the whole point of those two is
 *   that a link to them expires.
 * - **A referenced file is not deletable.** Nothing holds a foreign key to
 *   `media_uploads` — `banners.image_path` and friends are plain strings, the
 *   same as before this table existed — so the database cannot refuse the
 *   delete and a missing file surfaces as a broken image on the home page,
 *   not as an error anybody sees. `destroy()` therefore does the check the
 *   schema cannot, the same way `MasterDataController` refuses to delete a
 *   lookup row members still reference.
 */
class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('cms.view');

        $request->validate([
            'collection' => ['nullable', 'string', 'max:40'],
        ]);

        return view('admin.media.index', [
            'uploads' => MediaUpload::query()
                ->with('uploader:id,name')
                ->inCollection($request->string('collection')->toString() ?: null)
                ->latest('id')
                ->paginate(24)
                ->withQueryString(),
            'collection' => $request->string('collection')->toString(),
            'collections' => config('matrimony.media.upload.collections', []),
        ]);
    }

    /**
     * One route, two callers — the same shape as the photo queue's
     * `decided()`, and for the same reason.
     *
     * The plain path is a real `<form enctype="multipart/form-data">` on the
     * library page: pick a file, submit, land back on the library with the
     * new path visible and copyable. That path works with the script blocked,
     * which is the rule every enhancement in this codebase follows.
     *
     * The JSON path is what `<x-admin.image-field>` uses from the CMS form,
     * so choosing an image fills the path field in place instead of
     * navigating away and discarding whatever else was typed into that form.
     * `ApiResponse` rather than `response()->json()`, per PROGRESS.md §5 —
     * the envelope rule is about never hand-rolling a JSON shape, wherever
     * the JSON is emitted.
     */
    public function store(MediaUploadRequest $request, StoreMediaUpload $store): RedirectResponse|JsonResponse
    {
        $this->authorize('cms.manage');

        $upload = $store($request->file('file'), $request->user(), $request->collection());

        if ($request->wantsJson()) {
            return ApiResponse::created([
                'path' => $upload->path,
                'url' => $upload->url(),
                'original_name' => $upload->original_name,
                'dimensions' => $upload->dimensions(),
            ], 'Uploaded.');
        }

        return back()->with('status', "Uploaded. The path is {$upload->path} — paste it into the image field that needs it.");
    }

    public function destroy(Request $request, MediaUpload $upload, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('cms.manage');

        if ($used = $this->referencedBy($upload->path)) {
            return back()->with('status', "That file is still in use by {$used}. Point it somewhere else first — deleting it now would leave a broken image on a live page.");
        }

        // The whole row into the trail before it goes, same reasoning as
        // CmsController::destroy: the file is gone from the disk immediately
        // afterwards and "which file did we delete" is asked with the
        // expectation of getting it back.
        $audit->log(
            'media.deleted',
            $upload,
            oldValues: $upload->getAttributes(),
            description: 'Media deleted from the library',
            actor: $request->user(),
        );

        Storage::disk($upload->disk)->delete($upload->path);
        $upload->delete();

        return back()->with('status', 'File deleted.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Every column in the schema that holds one of these paths as a bare
     * string. **A new such column has to be added here** — there is no
     * foreign key to make the database catch it, which is exactly why this
     * list is written out rather than derived.
     */
    protected function referencedBy(string $path): ?string
    {
        $uses = [];

        if (Banner::query()->where('image_path', $path)->orWhere('mobile_image_path', $path)->exists()) {
            $uses[] = 'a banner';
        }

        if (SuccessStory::query()->where('photo_path', $path)->exists()) {
            $uses[] = 'a success story';
        }

        if (CmsPage::query()->where('og_image_path', $path)->exists()) {
            $uses[] = 'a page’s social image';
        }

        return $uses ? implode(' and ', $uses) : null;
    }
}
