<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PayMongoService;
use App\Services\ReservationStateManager;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PayMongoService $paymongo;

    public function __construct(PayMongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature', '');

        Log::info('PayMongo Webhook Received: ' . $payload);

        $webhookData = json_decode($payload, true);
        if (!$webhookData || !isset($webhookData['data'])) {
            return response()->json(['success' => false, 'message' => 'Invalid webhook data'], 400);
        }

        try {
            $event = $webhookData['data'];
            $eventType = $event['attributes']['type'] ?? '';

            if ($eventType === 'link.payment.paid' || $eventType === 'payment.paid') {
                $payment = $event['attributes']['data']['attributes'] ?? [];
                $remarks = $payment['remarks'] ?? ($event['attributes']['data']['attributes']['remarks'] ?? '');

                $reservationId = null;
                if (preg_match('/Reservation ID:\s*([A-Z0-9-]+)/i', $remarks, $matches)) {
                    $reservationId = trim($matches[1]);
                }

                if ($reservationId) {
                    $reservation = Reservation::find($reservationId);
                    if ($reservation) {
                        $paymentType = str_contains(strtolower($remarks), 'remaining') ? 'full_payment' : 'downpayment';
                        $paymentId = $event['attributes']['data']['id'] ?? 'pay_webhook';

                        if ($paymentType === 'downpayment') {
                            $stateManager = new ReservationStateManager('system', 'system');
                            $stateManager->transitionTo($reservationId, 'confirmed', [
                                'downpayment_proof' => 'PAYMONGO_ONLINE',
                                'downpayment_reference' => $paymentId,
                                'payment_method' => 'gcash',
                                'admin_notes' => 'Auto-confirmed via PayMongo Webhook'
                            ]);
                        } else {
                            $reservation->update([
                                'full_payment_paid' => 1,
                                'full_payment_paid_at' => now(),
                                'full_payment_verified' => 1,
                                'full_payment_verified_at' => now(),
                                'full_payment_reference' => $paymentId,
                                'remaining_balance' => 0
                            ]);
                        }
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('PayMongo Webhook Processing Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
