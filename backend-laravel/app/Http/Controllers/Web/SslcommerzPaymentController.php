<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SslcommerzTransaction;
use App\Services\SeoService;
use App\Services\SslcommerzService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * SSLCOMMERZ return / IPN endpoints. Callback routes run without session or CSRF
 * middleware (they are cross-site POSTs from the gateway); the order state is
 * only changed after server-side validation inside SslcommerzService.
 */
class SslcommerzPaymentController extends Controller
{
    public function __construct(private SslcommerzService $sslcommerz) {}

    public function success(Request $request): RedirectResponse
    {
        $result = $this->sslcommerz->handleValidCallback($request->post(), 'success_redirect');
        $transaction = $result['transaction'];

        if (! $transaction) {
            return redirect()->route('home');
        }

        $order = $transaction->order;
        $paid = in_array($result['outcome'], [SslcommerzService::OUTCOME_PAID, SslcommerzService::OUTCOME_ALREADY_PROCESSED], true)
            && in_array($order->payment_status, ['verified', 'success'], true);

        return redirect()->route($paid ? 'order.success' : 'payment.sslcommerz.status', $this->orderParams($order));
    }

    public function fail(Request $request): RedirectResponse
    {
        return $this->unsuccessfulReturn($request, SslcommerzTransaction::STATUS_FAILED);
    }

    public function cancel(Request $request): RedirectResponse
    {
        return $this->unsuccessfulReturn($request, SslcommerzTransaction::STATUS_CANCELLED);
    }

    public function ipn(Request $request): Response
    {
        $payload = $request->post();
        $status = strtoupper((string) ($payload['status'] ?? ''));

        if (in_array($status, ['VALID', 'VALIDATED'], true)) {
            $result = $this->sslcommerz->handleValidCallback($payload, 'ipn');

            return match ($result['outcome']) {
                SslcommerzService::OUTCOME_UNKNOWN => response('Unknown transaction', 404),
                SslcommerzService::OUTCOME_INVALID => response('Validation failed', 422),
                SslcommerzService::OUTCOME_GATEWAY_ERROR => response('Validation unavailable', 503),
                default => response('IPN processed', 200),
            };
        }

        if (! $this->sslcommerz->verifySignature($payload)) {
            return response('Invalid signature', 403);
        }

        $unsuccessfulStatus = $status === 'CANCELLED' ? SslcommerzTransaction::STATUS_CANCELLED : SslcommerzTransaction::STATUS_FAILED;
        $transaction = $this->sslcommerz->markUnsuccessful($payload, $unsuccessfulStatus, 'ipn');

        return $transaction ? response('IPN processed', 200) : response('Unknown transaction', 404);
    }

    public function status(Request $request): View|RedirectResponse
    {
        $order = $this->authorizedOrder($request);

        if (in_array($order->payment_status, ['verified', 'success'], true)) {
            return redirect()->route('order.success', $this->orderParams($order));
        }

        $latestTransaction = SslcommerzTransaction::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        $state = match (true) {
            $order->payment_status === 'payment_verification' => 'review',
            in_array($order->status, ['cancelled', 'returned'], true) => 'closed',
            $latestTransaction?->status === SslcommerzTransaction::STATUS_INITIATED && $latestTransaction->created_at->gt(now()->subMinutes(30)) => 'awaiting',
            default => 'unpaid',
        };

        return view('web.payment-status', [
            'order' => $order,
            'token' => (string) $order->access_token,
            'state' => $state,
            'seo' => SeoService::getForPrivate('Payment Status'),
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        $order = $this->authorizedOrder($request);
        $initiation = $this->sslcommerz->initiatePayment($order);

        if ($initiation['success']) {
            return redirect()->away($initiation['redirectUrl']);
        }

        return redirect()->route('payment.sslcommerz.status', $this->orderParams($order))
            ->with('error', $initiation['message']);
    }

    private function unsuccessfulReturn(Request $request, string $status): RedirectResponse
    {
        $payload = $request->post();
        if (isset($payload['verify_sign']) && ! $this->sslcommerz->verifySignature($payload)) {
            $payload = [];
        }

        $transaction = $this->sslcommerz->markUnsuccessful($payload, $status, $status.'_redirect');
        if (! $transaction) {
            return redirect()->route('home');
        }

        return redirect()->route('payment.sslcommerz.status', $this->orderParams($transaction->order));
    }

    private function authorizedOrder(Request $request): Order
    {
        $order = Order::query()->where('order_id', (string) $request->input('orderId'))->first();

        abort_unless(
            $order
            && $order->payment_method === 'sslcommerz'
            && $order->access_token
            && hash_equals((string) $order->access_token, (string) $request->input('token')),
            404
        );

        return $order;
    }

    /**
     * @return array{orderId: string, token: string}
     */
    private function orderParams(Order $order): array
    {
        return ['orderId' => (string) $order->order_id, 'token' => (string) $order->access_token];
    }
}
