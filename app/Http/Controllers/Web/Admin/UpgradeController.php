<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatrimonyLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function index(): View
    {
        $limit = MatrimonyLimit::current();
        $whatsappUrls = [];

        foreach (array_keys(MatrimonyLimit::PLANS) as $key) {
            $whatsappUrls[$key] = self::getWhatsAppUrl($key, $limit);
        }

        return view('admin.upgrade.index', [
            'limit' => $limit,
            'plans' => MatrimonyLimit::PLANS,
            'whatsappUrls' => $whatsappUrls,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan_type' => ['required', 'string', 'in:basic,premium,advanced,unlimited'],
        ]);

        $limit = MatrimonyLimit::requestUpgrade($validated['plan_type']);
        $whatsappUrl = self::getWhatsAppUrl($validated['plan_type'], $limit);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiting for admin update',
                'whatsapp_url' => $whatsappUrl,
                'requested_plan' => $validated['plan_type'],
            ]);
        }

        return redirect()->route('admin.upgrade.index')
            ->with('status', 'Waiting for admin update')
            ->with('whatsapp_redirect', $whatsappUrl);
    }

    /**
     * Generate WhatsApp direct link with formatted plan and platform details.
     */
    public static function getWhatsAppUrl(string $planKey, ?MatrimonyLimit $limit = null): string
    {
        $plan = MatrimonyLimit::PLANS[$planKey] ?? null;
        if (! $plan) {
            return '';
        }

        $phone = '918072515050';
        $siteName = config('app.name', 'Matrimony');
        $siteUrl = url('/');
        $user = auth()->user();
        $adminName = $user ? $user->name : 'Admin';
        $adminContact = $user ? ($user->email ?: $user->mobile ?: '') : '';
        $currentPlanName = $limit ? (MatrimonyLimit::PLANS[$limit->plan_type]['name'] ?? ucfirst($limit->plan_type)) : 'Basic';

        $featuresText = '';
        if (! empty($plan['features'])) {
            foreach ($plan['features'] as $f) {
                $featuresText .= "  • {$f}\n";
            }
        }

        $message = "Hello Admin,\n\n"
            ."I would like to upgrade my Matrimony platform plan.\n\n"
            ."📌 *Plan Upgrade Request:*\n"
            ."• *Plan Name:* {$plan['name']}\n"
            ."• *Price:* {$plan['price']} (".($plan['period'] ?? 'Yearly plan').")\n"
            ."• *User Capacity:* ".($plan['users_label'] ?? ($plan['limit'].' Users'))."\n"
            .($featuresText ? "• *Features:*\n{$featuresText}" : '')
            ."\n🌐 *Platform Details:*\n"
            ."• *Website:* {$siteName}\n"
            ."• *Site URL:* {$siteUrl}\n"
            ."• *Current Plan:* {$currentPlanName}\n"
            ."• *Requested By:* {$adminName}".($adminContact ? " ({$adminContact})" : '')."\n\n"
            ."Waiting for admin update.";

        return 'https://api.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message);
    }
}
