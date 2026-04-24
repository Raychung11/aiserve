<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Services\BillplzService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private BillplzService $billplz) {}

    public function index()
    {
        $tenant      = app('tenant');
        $subscription = $tenant->activeSubscription();
        $history     = Subscription::where('tenant_id', $tenant->id)->orderByDesc('created_at')->get();
        $plans       = $this->plans();

        return view('subscription.index', compact('tenant', 'subscription', 'history', 'plans'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan'          => 'required|in:starter,growth,enterprise',
            'billing_cycle' => 'required|in:monthly,annually',
        ]);

        $tenant = app('tenant');
        $user   = auth()->user();
        $plans  = $this->plans();
        $plan   = $request->plan;
        $cycle  = $request->billing_cycle;

        $amount = $cycle === 'annually'
            ? $plans[$plan]['annually']
            : $plans[$plan]['monthly'];

        $result = $this->billplz->createSubscriptionBill(
            tenantEmail: $user->email,
            tenantName:  $user->name,
            tenantPhone: $user->phone ?? '',
            plan:        ucfirst($plan),
            amount:      $amount,
            tenantId:    (string) $tenant->id,
        );

        if (!$result['success']) {
            return back()->with('error', 'Payment gateway error: ' . $result['error']);
        }

        $billData = $result['data'];

        Subscription::create([
            'tenant_id'             => $tenant->id,
            'plan'                  => $plan,
            'status'                => 'pending',
            'amount'                => $amount,
            'billing_cycle'         => $cycle,
            'billplz_bill_id'       => $billData['id'],
            'billplz_collection_id' => $billData['collection_id'],
            'billplz_url'           => $billData['url'],
        ]);

        ActivityLog::record('subscription.initiated', "Subscription checkout: {$plan} ({$cycle}) RM{$amount}");

        return redirect()->away($billData['url']);
    }

    public function callback(Request $request)
    {
        if (!$this->billplz->verifyWebhook($request->all())) {
            abort(403, 'Invalid signature');
        }

        $billId = $request->id;
        $paid   = $request->paid === 'true';

        $subscription = Subscription::where('billplz_bill_id', $billId)->firstOrFail();

        if ($paid) {
            $plans   = $this->plans();
            $months  = $subscription->billing_cycle === 'annually' ? 12 : 1;

            $subscription->update([
                'status'    => 'active',
                'paid_at'   => now(),
                'starts_at' => now(),
                'ends_at'   => now()->addMonths($months),
            ]);

            $tenant = $subscription->tenant;
            $tenant->update([
                'status'              => 'active',
                'plan'                => $subscription->plan,
                'max_properties'      => $plans[$subscription->plan]['properties'],
                'subscription_ends_at'=> now()->addMonths($months),
            ]);

            ActivityLog::record('subscription.paid', "Subscription activated: {$subscription->plan}");
        }

        return response()->json(['status' => 'ok']);
    }

    public function redirectCallback(Request $request)
    {
        $billId = $request->billplz['id'] ?? null;

        if (!$billId) {
            return redirect()->route('subscription.index')->with('error', 'Payment reference not found.');
        }

        $subscription = Subscription::where('billplz_bill_id', $billId)->first();

        if ($subscription && $subscription->status === 'active') {
            return redirect()->route('dashboard')->with('success', 'Subscription activated! Welcome to STRHub AI.');
        }

        return redirect()->route('subscription.index')
            ->with('info', 'Payment is being processed. Your subscription will activate shortly.');
    }

    private function plans(): array
    {
        return [
            'starter' => [
                'name'       => 'Starter',
                'monthly'    => 500,
                'annually'   => 5000,
                'properties' => 5,
                'agents'     => 2,
                'features'   => ['5 Properties', '2 Agents', 'Compliance Engine', 'ROI Calculator'],
            ],
            'growth' => [
                'name'       => 'Growth',
                'monthly'    => 1500,
                'annually'   => 15000,
                'properties' => 20,
                'agents'     => 10,
                'features'   => ['20 Properties', '10 Agents', 'All Starter features', 'Strategy Engine', 'Revenue Reports'],
            ],
            'enterprise' => [
                'name'       => 'Enterprise',
                'monthly'    => 4000,
                'annually'   => 40000,
                'properties' => 9999,
                'agents'     => 9999,
                'features'   => ['Unlimited Properties', 'Unlimited Agents', 'All features', 'Priority Support', 'Custom Branding'],
            ],
        ];
    }
}
