<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class FirebaseSettingsController extends Controller
{
    public function index(Request $request, FirebaseNotificationService $firebaseService): View
    {
        $this->authorize('settings.view');

        $credentialsPath = $firebaseService->resolveCredentialsPath();
        $hasCredentials = $credentialsPath && file_exists($credentialsPath);
        $credentialsDetails = null;

        if ($hasCredentials) {
            try {
                $json = json_decode(file_get_contents($credentialsPath), true);
                if (is_array($json)) {
                    $credentialsDetails = [
                        'project_id' => $json['project_id'] ?? null,
                        'client_email' => $json['client_email'] ?? null,
                        'path' => $credentialsPath,
                    ];
                }
            } catch (\Throwable) {}
        }

        $users = User::query()
            ->select('id', 'name', 'profile_code', 'mobile')
            ->whereHas('fcmTokens')
            ->orWhereHas('deviceTokens')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.settings.firebase', [
            'projectId' => Setting::get('firebase_project_id', env('FIREBASE_PROJECT_ID', '')),
            'soundName' => Setting::get('firebase_sound_name', 'default'),
            'channelId' => Setting::get('firebase_android_channel_id', 'koodal_notifications'),
            'pushEnabled' => $firebaseService->isPushEnabledGlobally(),
            'hasCredentials' => $hasCredentials,
            'credentialsDetails' => $credentialsDetails,
            'usersWithTokens' => $users,
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('settings.manage');

        $request->validate([
            'firebase_project_id' => ['nullable', 'string', 'max:100'],
            'firebase_sound_name' => ['required', 'string', 'max:100'],
            'firebase_android_channel_id' => ['required', 'string', 'max:100'],
            'firebase_push_enabled' => ['required', 'in:0,1'],
            'service_account_json' => ['nullable', 'file', 'max:2048'], // 2MB max
        ]);

        // 1. Process uploaded Service Account JSON
        if ($request->hasFile('service_account_json')) {
            $file = $request->file('service_account_json');
            $content = file_get_contents($file->getRealPath());
            $decoded = json_decode($content, true);

            if (! is_array($decoded) || empty($decoded['project_id']) || empty($decoded['private_key'])) {
                return back()->withErrors([
                    'service_account_json' => 'The uploaded file is not a valid Firebase Service Account JSON. Missing project_id or private_key.',
                ]);
            }

            // Secure private storage: storage/app/private/firebase/service-account.json
            $dir = storage_path('app/private/firebase');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0750, true, true);
            }

            $destPath = $dir.'/service-account.json';
            File::put($destPath, $content);

            // Also update project ID if not set
            if (! $request->filled('firebase_project_id') && ! empty($decoded['project_id'])) {
                Setting::updateOrCreate(
                    ['key' => 'firebase_project_id'],
                    ['value' => $decoded['project_id'], 'type' => 'string', 'group' => 'firebase', 'is_public' => false]
                );
            }

            Setting::updateOrCreate(
                ['key' => 'firebase_credentials_path'],
                ['value' => 'storage/app/private/firebase/service-account.json', 'type' => 'string', 'group' => 'firebase', 'is_public' => false]
            );
        }

        // 2. Save settings rows
        $settingsToSave = [
            'firebase_project_id' => [
                'value' => $request->input('firebase_project_id', ''),
                'type' => 'string',
                'is_public' => false,
            ],
            'firebase_sound_name' => [
                'value' => $request->input('firebase_sound_name', 'default'),
                'type' => 'string',
                'is_public' => true,
            ],
            'firebase_android_channel_id' => [
                'value' => $request->input('firebase_android_channel_id', 'koodal_notifications'),
                'type' => 'string',
                'is_public' => true,
            ],
            'firebase_push_enabled' => [
                'value' => $request->input('firebase_push_enabled') === '1' ? 'true' : 'false',
                'type' => 'bool',
                'is_public' => true,
            ],
        ];

        foreach ($settingsToSave as $key => $config) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'group' => 'firebase',
                    'is_public' => $config['is_public'],
                ]
            );
        }

        // Clear settings cache
        \Illuminate\Support\Facades\Cache::forget(Setting::CACHE_KEY);

        $audit->log('settings.firebase_updated', null, description: 'Firebase push notification settings updated');

        return back()->with('status', 'Firebase settings updated successfully!');
    }

    public function sendTest(Request $request, FirebaseNotificationService $firebaseService): RedirectResponse
    {
        $this->authorize('settings.manage');

        $request->validate([
            'test_target_type' => ['required', 'in:token,user'],
            'fcm_token' => ['nullable', 'string', 'max:500', 'required_if:test_target_type,token'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:test_target_type,user'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:250'],
        ]);

        $title = $request->input('title');
        $body = $request->input('body');
        $data = [
            'type' => 'test',
            'reference_id' => 'test-'.time(),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        if ($request->input('test_target_type') === 'token') {
            $token = trim((string) $request->input('fcm_token'));
            $res = $firebaseService->sendDirectToToken($token, $title, $body, $data, 'test');

            if ($res['success']) {
                return back()->with('status', 'Test push notification sent successfully to token!');
            }

            return back()->withErrors(['test_push' => 'Failed to send test push: '.($res['error'] ?? 'Unknown error')]);
        }

        $userId = (int) $request->input('user_id');
        $success = $firebaseService->sendDirectToUser($userId, $title, $body, $data, 'test');

        if ($success) {
            return back()->with('status', 'Test push notification sent successfully to user!');
        }

        return back()->withErrors(['test_push' => 'Failed to send test push to user. Check if user has active tokens or if push is enabled.']);
    }
}
