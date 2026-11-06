<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Enquiry\HandleEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HandleEnquiryRequest;
use App\Http\Resources\Admin\AdminEnquiryResource;
use App\Models\ContactEnquiry;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The other end of the public contact form.
 *
 * `CmsController::contact` has been writing `contact_enquiries` rows since the
 * CMS phase and nothing read them — the `status`, `handled_by` and `response`
 * columns were in the schema with no code touching them. A contact form that
 * writes to a table nobody opens is worse than no contact form: it tells people
 * they have been heard.
 *
 * Gated on its own `enquiries.*` permissions rather than on `cms.*`. The
 * enquiry arrives through the CMS controller, but answering support mail is
 * support's job and the `support` role holds no CMS permission — riding on
 * `cms.manage` would have let a content editor answer enquiries while support
 * could not.
 */
class ContactEnquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ContactEnquiry::class);

        $request->validate([
            'status' => ['nullable', 'in:open,in_progress,responded,closed'],
            'handled_by' => ['nullable', 'integer'],
            'unassigned' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $enquiries = ContactEnquiry::query()
            ->with('handler:id,name')

            ->status($request->string('status')->toString() ?: null)
            ->when($request->integer('handled_by'), fn ($q, $id) => $q->where('handled_by', $id))
            ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('handled_by'))

            // Name, email and subject only — not the message body. A LIKE over
            // a TEXT column is a full scan of the table, and support searches
            // this to find a person they are already talking to, not to grep
            // the corpus.
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';

                $q->where(fn ($w) => $w
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('subject', 'like', $term));
            })

            // Oldest first, and for the same reason the report queue is: this is
            // a service-level queue, and the enquiry that has waited longest is
            // the person most likely to give up on us. The `(status,
            // created_at)` index the migration declares serves exactly this.
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 25));

        $this->hydrateMembers($enquiries->getCollection());

        return ApiResponse::paginated($enquiries, AdminEnquiryResource::class, 'Enquiries retrieved successfully');
    }

    public function show(ContactEnquiry $enquiry): JsonResponse
    {
        $this->authorize('view', $enquiry);

        $enquiry->load('handler:id,name');

        $this->hydrateMembers(collect([$enquiry]));

        return ApiResponse::success(new AdminEnquiryResource($enquiry), 'Enquiry retrieved successfully');
    }

    /**
     * claim | respond | close — one endpoint, matching
     * `PATCH /admin/users/{user}/{action}` and the verification queue rather
     * than inventing a third shape for the same idea.
     *
     * The Policy is authorised for all three, including `claim`: the ability it
     * expresses is "this enquiry is yours to work", and claiming one somebody
     * else already holds is exactly what it refuses.
     */
    public function action(
        HandleEnquiryRequest $request,
        ContactEnquiry $enquiry,
        string $action,
        HandleEnquiry $handle,
    ): JsonResponse {
        $this->authorize('respond', $enquiry);

        $enquiry = $handle($enquiry, $action, $request->user(), $request->input('response'));

        return ApiResponse::success(
            new AdminEnquiryResource($enquiry),
            match ($action) {
                'claim' => 'Enquiry claimed. It is yours to answer.',
                'respond' => 'Reply recorded against the enquiry.',
                default => 'Enquiry closed without a reply.',
            },
        );
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Match each enquiry's email against the member base.
     *
     * The single most useful thing on this screen. An enquiry is an email
     * address and a paragraph; whether that address belongs to a paying member
     * changes both the priority and the answer, and support otherwise has to
     * copy the address into the user search to find out.
     *
     * One `whereIn` per page rather than a lookup per row — the pattern
     * `ReportManagementController::hydrateHistory` set. Matching on email is a
     * heuristic and stays one: someone can write in from a different address
     * than they registered with, so an absent `member` means "not found by
     * email", never "not a member".
     *
     * @param  Collection<int, ContactEnquiry>  $enquiries
     */
    protected function hydrateMembers($enquiries): void
    {
        $emails = $enquiries->pluck('email')->filter()->unique();

        if ($emails->isEmpty()) {
            return;
        }

        $members = User::query()
            ->members()
            ->whereIn('email', $emails)
            ->get(['id', 'email', 'profile_code', 'name', 'status'])
            ->keyBy('email');

        foreach ($enquiries as $enquiry) {
            $member = $members->get($enquiry->email);

            $enquiry->member = $member ? [
                'id' => $member->id,
                'profile_code' => $member->profile_code,
                'name' => $member->name,
                'status' => $member->status,
            ] : null;
        }
    }
}
