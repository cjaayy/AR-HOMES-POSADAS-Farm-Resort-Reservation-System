<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PayMongoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PayMongoService $paymongo;

    public function __construct(PayMongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    public function createPaymentIntent(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to proceed with payment'
            ], 401);
        }

        $reservationId = $request->input('reservation_id');
        $paymentType = $request->input('payment_type', 'downpayment');

        if (!$reservationId) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation ID is required'
            ], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            if ($paymentType === 'full_payment') {
                if ($reservation->status !== 'confirmed' || !$reservation->downpayment_verified) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Downpayment must be verified before paying remaining balance'
                    ], 400);
                }
                if ($reservation->full_payment_paid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Full payment has already been made'
                    ], 400);
                }

                $amount = $reservation->total_amount - $reservation->downpayment_amount;
                $description = "AR Homes Resort - Reservation #{$reservationId} - Remaining Balance";
            } else {
                if ($reservation->status !== 'pending_payment') {
                    return response()->json([
                        'success' => false,
                        'message' => 'This reservation has already been processed'
                    ], 400);
                }

                $amount = $reservation->downpayment_amount;
                $description = "AR Homes Resort - Reservation #{$reservationId} - Downpayment";
            }

            $amountInCentavos = (int)($amount * 100);
            $remarks = "Reservation ID: {$reservationId} - Payment Type: {$paymentType}";

            $result = $this->paymongo->createPaymentLink($amountInCentavos, $description, $remarks);

            if (!$result['success']) {
                $err = $result['curl_error'] ?? 'Failed to create payment link';
                return response()->json([
                    'success' => false,
                    'message' => $err
                ], 500);
            }

            $linkData = $result['data']['data'] ?? [];
            $checkoutUrl = $linkData['attributes']['checkout_url'] ?? null;
            $linkId = $linkData['id'] ?? null;

            // Store source/link id for webhook reconciliation
            $reservation->update([
                'paymongo_source_id' => $linkId,
                'paymongo_payment_type' => $paymentType
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'link_id' => $linkId,
                'amount' => $amount,
                'description' => $description
            ]);

        } catch (Exception $e) {
            Log::error('Create Payment Intent Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadPaymentProof(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login first'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $paymentType = $request->input('payment_type', 'downpayment');
        $paymentMethod = $request->input('payment_method');
        $referenceNumber = $request->input('reference_number');

        if (!$reservationId || !$paymentMethod || !$referenceNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation ID, payment method, and reference number are required'
            ], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Invalid reservation'], 404);
            }

            if (!$request->hasFile('payment_proof')) {
                return response()->json(['success' => false, 'message' => 'Please upload payment proof (screenshot)'], 400);
            }

            $file = $request->file('payment_proof');

            if (!$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'Uploaded file is invalid'], 400);
            }

            $extension = $file->getClientOriginalExtension();
            $filename = 'payment_' . $reservationId . '_' . $paymentType . '_' . time() . '.' . $extension;

            // Store in public/uploads/payment_proofs/
            $destinationPath = public_path('uploads/payment_proofs');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            if ($paymentType === 'downpayment') {
                $reservation->update([
                    'downpayment_proof' => $filename,
                    'downpayment_reference' => $referenceNumber,
                    'payment_method' => $paymentMethod,
                    'downpayment_paid' => 1,
                    'downpayment_paid_at' => now(),
                    'status' => 'pending_confirmation'
                ]);
            } else {
                $reservation->update([
                    'full_payment_proof' => $filename,
                    'full_payment_reference' => $referenceNumber,
                    'full_payment_paid' => 1,
                    'full_payment_paid_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => ucfirst($paymentType) . ' proof uploaded successfully! Waiting for admin verification.',
                'filename' => $filename,
                'status' => $paymentType === 'downpayment' ? 'pending_confirmation' : $reservation->status
            ]);

        } catch (Exception $e) {
            Log::error('Upload Payment Proof Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $reservationId = $request->query('reservation_id');
        return response()->json([
            'success' => true,
            'message' => 'Payment received successfully!',
            'reservation_id' => $reservationId
        ]);
    }

    public function paymentFailed(Request $request)
    {
        $reservationId = $request->query('reservation_id');
        return response()->json([
            'success' => false,
            'message' => 'Payment was unsuccessful or cancelled.',
            'reservation_id' => $reservationId
        ]);
    }
}
