<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationLogWebController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('notifications.view');

        $request->validate([
            'type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:sent,failed'],
            'user' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = NotificationLog::query()
            ->with(['user:id,name,profile_code,mobile'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('user'), function ($q) use ($request) {
                $term = trim($request->string('user')->toString());
                if (is_numeric($term)) {
                    $q->where('user_id', (int) $term);
                } else {
                    $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")
                        ->orWhere('profile_code', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%"));
                }
            })
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id');

        $types = NotificationLog::query()->distinct()->pluck('type')->filter()->values();

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.notifications.logs', [
            'logs' => $logs,
            'types' => $types,
            'selectedType' => $request->input('type'),
            'selectedStatus' => $request->input('status'),
            'selectedUser' => $request->input('user'),
            'fromDate' => $request->input('from'),
            'toDate' => $request->input('to'),
        ]);
    }
}
