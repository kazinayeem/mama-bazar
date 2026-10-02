<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CheckoutNotice;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\UserAddress;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public const BD_PHONE_RULE = 'regex:/^(?:\+?880|0)1[3-9]\d{8}$/';

    public function index(Request $request)
    {
        // Campaign attribution: persist UTM + landing page across the session (no cross-site tracking).
        if (! $request->session()->has('landing_page')) {
            $request->session()->put('landing_page', $request->fullUrl());
        }
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $k) {
            if ($request->filled($k)) {
                $request->session()->put('attribution.'.$k, $request->input($k));
            }
        }

        PaymentMethod::ensureDefaults();

        $shippingMethods = ShippingMethod::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->orderBy('id')
            ->get();

        $paymentMethods = PaymentMethod::activeCheckout()->get();

        $notices = CheckoutNotice::where('status', 'active')
            ->orderBy('priority', 'desc')
            ->orderBy('id')
            ->get();

        $checkoutSettings = $this->checkoutSettings();

        $taxRate = 0;
        $taxRow = SiteSetting::where('key', 'tax_settings')->first();
        if ($taxRow) {
            $decoded = json_decode($taxRow->value, true);
            $taxRate = min(max((float) ($decoded['taxRate'] ?? 0), 0), 25);
        }

        $savedAddresses = collect();
        if (auth()->check()) {
            $savedAddresses = UserAddress::where('user_id', auth()->id())
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        return view('web.checkout', [
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'checkoutNotices' => $notices,
            // Back-compat alias for existing blade sections.
            'checkoutNotice' => $notices->first(),
            'checkoutSettings' => $checkoutSettings,
            'taxRate' => $taxRate,
            'savedAddresses' => $savedAddresses,
        ]);
    }

    /**
     * Converts Bangla digits and strips spaces, dashes, dots and brackets
     * so "০১৭১২-৩৪৫ ৬৭৮" and "01712345678" validate and store identically.
     */
    public static function normalizePhoneInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtr($value, array_combine(
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']
        ));

        return preg_replace('/[\s\-().]+/u', '', $value);
    }

    public function process(Request $request)
    {
        PaymentMethod::ensureDefaults();

        foreach (['phone', 'alternative_phone', 'sender_number'] as $phoneField) {
            if (is_string($request->input($phoneField))) {
                $request->merge([$phoneField => self::normalizePhoneInput($request->input($phoneField))]);
            }
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:20', self::BD_PHONE_RULE],
            'alternative_phone' => ['nullable', 'string', 'max:20', self::BD_PHONE_RULE],
            'email' => 'nullable|email|max:255',
            'district' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'delivery_instructions' => 'nullable|string|max:1000',
            'order_note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'payment_method' => 'required|string|max:50',
            'shipping_method_id' => 'required|integer',
            'coupon_code' => 'nullable|string|max:50',
            'order_key' => 'nullable|string|max:64',
            'sender_number' => 'nullable|string|max:30',
            'transaction_id' => 'nullable|string|max:100',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:150',
            'utm_content' => 'nullable|string|max:150',
            'utm_term' => 'nullable|string|max:150',
            'marketing_consent' => 'nullable|boolean',
        ], [
            'phone.regex' => 'Please enter a valid Bangladesh mobile number (e.g. 01712345678).',
            'alternative_phone.regex' => 'Alternative phone must be a valid Bangladesh mobile number.',
        ]);

        // Duplicate-submission guard: same order_key resubmitted (double-click / back button).
        $orderKey = $validated['order_key'] ?? null;
        if ($orderKey && session()->has('checkout_order_'.$orderKey)) {
            $existingId = session()->get('checkout_order_'.$orderKey);

            return redirect()->route('order.success', ['orderId' => $existingId])
                ->with('success', 'Order already placed.');
        }

        $paymentMethodCode = strtolower($validated['payment_method']);
        $isCod = $paymentMethodCode === 'cod';

        $activeMethod = PaymentMethod::activeCheckout()->where('code', $paymentMethodCode)->first();
        if (! $activeMethod) {
            return back()->withInput()->with('error', 'Selected payment method is unavailable. Please choose another.');
        }

        $shippingMethod = ShippingMethod::where('id', $validated['shipping_method_id'])
            ->where('status', 'active')
            ->first();
        if (! $shippingMethod) {
            return back()->withInput()->with('error', 'Selected delivery method is unavailable. Please choose another.');
        }
        if (! $shippingMethod->isApplicableTo($validated['district'])) {
            return back()->withInput()->with('error', "\"{$shippingMethod->name}\" is not available for {$validated['district']}. Please choose another delivery method.");
        }
        if ($isCod && ! $shippingMethod->cod_available) {
            return back()->withInput()->with('error', 'Cash on Delivery is not available for the selected delivery method.');
        }

        if (! $isCod) {
            $isMobileBanking = $activeMethod->type === 'mobile_banking';

            $request->validate([
                'sender_number' => ['required', 'string', 'max:30', $isMobileBanking ? self::BD_PHONE_RULE : 'regex:/^[A-Za-z0-9+]{4,30}$/'],
                'transaction_id' => 'required|string|max:100',
            ], [
                'sender_number.required' => $isMobileBanking
                    ? 'Sender number is required for this payment method.'
                    : 'Sender account number is required for this payment method.',
                'sender_number.regex' => $isMobileBanking
                    ? "Sender number must be the {$activeMethod->name} mobile number you paid from (e.g. 01712345678)."
                    : 'Sender account number may only contain letters and digits.',
                'transaction_id.required' => 'Transaction ID is required for this payment method.',
            ]);
        }

        // Pre-validate coupon for a friendly error before order creation.
        if (! empty($validated['coupon_code'])) {
            $couponError = $this->couponError(trim($validated['coupon_code']), null);
            if ($couponError) {
                return back()->withInput()->with('error', $couponError);
            }
        }

        try {
            $payload = $request->all();
            $payload['payment_method'] = $paymentMethodCode;
            $payload['transaction_id'] = $request->input('transaction_id');
            $payload['sender_number'] = $request->input('sender_number');
            $payload['transactionId'] = $request->input('transaction_id');
            $payload['senderNumber'] = $request->input('sender_number');
            $payload['couponCode'] = $request->input('coupon_code');
            // Guest checkout: authenticated user stays linked, guests stay nullable.
            $payload['userId'] = auth()->check() ? auth()->id() : null;
            // Idempotency: order_key doubles as server-side idempotency key.
            $payload['idempotency_key'] = $orderKey;
            $payload['order_note'] = $request->input('order_note') ?: $request->input('delivery_instructions');
            // Privacy-conscious analytics metadata (server-side only).
            $payload['_client_ip'] = $request->ip();
            $payload['_user_agent'] = mb_substr((string) $request->userAgent(), 0, 1000);
            $payload['_referrer'] = $request->headers->get('referer');
            $payload['_landing_page'] = session()->get('landing_page', $request->headers->get('referer'));
            $payload['order_source'] = 'web';
            $payload['marketing_consent'] = $request->boolean('marketing_consent');

            $order = OrderService::createOrder($payload);
            $orderData = is_array($order) ? ($order['order'] ?? []) : [];
            $orderId = $orderData['orderId'] ?? '';
            $accessToken = $orderData['accessToken'] ?? null;

            if ($orderKey && $orderId) {
                session()->put('checkout_order_'.$orderKey, $orderId);
            }
            // Purchase-event dedup flag for the success page (refresh-safe).
            if ($orderId) {
                session()->put('purchase_tracked_'.$orderId, false);
            }

            $successParams = ['orderId' => $orderId];
            if ($accessToken) {
                $successParams['token'] = $accessToken;
            }

            return redirect()->route('order.success', $successParams)
                ->with('success', 'Order placed successfully!');
        } catch (Exception $e) {
            Log::warning('Checkout failed', ['error' => $e->getMessage(), 'phone' => $request->input('phone')]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /** AJAX coupon validation for live order-summary updates. */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $code = trim($request->input('code'));
        $error = $this->couponError($code, $request->input('subtotal'));
        if ($error) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $coupon = $this->findCoupon($code);
        $subtotal = (float) ($request->input('subtotal') ?? 0);
        $discount = $coupon->discount_type === 'percentage'
            ? ($subtotal * (float) $coupon->discount_value) / 100
            : (float) $coupon->discount_value;
        $discount = round(min($discount, $subtotal), 2);

        return response()->json([
            'success' => true,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'discount' => $discount,
            'message' => "Coupon {$coupon->code} applied.",
        ]);
    }

    public function success(Request $request)
    {
        $orderId = $request->query('orderId');
        $token = $request->query('token');
        $order = null;
        if ($orderId) {
            $order = Order::where('order_id', $orderId)->first();
            // Verify token matches when an order carries one (prevents ID guessing).
            if ($order && $order->access_token && $token && ! hash_equals((string) $order->access_token, (string) $token)) {
                $order = null;
            }
        }

        return view('web.success', compact('orderId', 'order', 'token'));
    }

    protected function findCoupon(string $code): ?Coupon
    {
        return Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->where('status', 'active')
            ->first();
    }

    protected function couponError(string $code, $subtotal): ?string
    {
        $coupon = $this->findCoupon($code);
        if (! $coupon) {
            return 'Invalid or expired coupon code.';
        }
        if ($coupon->expiry_date && $coupon->expiry_date->isPast()) {
            return 'This coupon has expired.';
        }
        if ($subtotal !== null && $coupon->min_order_amount && (float) $subtotal < (float) $coupon->min_order_amount) {
            return 'Minimum order ৳'.number_format((float) $coupon->min_order_amount, 0).' required for this coupon.';
        }

        return null;
    }

    protected function checkoutSettings(): array
    {
        $row = SiteSetting::where('key', 'checkout_settings')->first();
        if ($row) {
            $decoded = json_decode($row->value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['default_district' => 'Dhaka', 'min_order_amount' => 0];
    }
}
