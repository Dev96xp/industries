<?php

namespace App\Http\Controllers;

use App\Models\QuotePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook');

        // Verify the webhook signature if secret is configured
        if ($secret) {
            try {
                $event = Webhook::constructEvent($payload, $signature, $secret);
            } catch (SignatureVerificationException) {
                return response('Invalid signature.', 400);
            }
        } else {
            $event = json_decode($payload);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // Primary lookup: match by the Payment Link ID stored at creation time
            $payment = null;
            $paymentLinkId = $session->payment_link ?? null;

            if ($paymentLinkId) {
                $payment = QuotePayment::where('stripe_session_id', $paymentLinkId)
                    ->where('status', 'pending')
                    ->where('method', 'card')
                    ->first();
            }

            // Fallback: legacy lookup via session metadata quote_id
            if (! $payment) {
                $quoteId = $session->metadata->quote_id ?? null;

                if ($quoteId) {
                    $payment = QuotePayment::where('quote_id', $quoteId)
                        ->where('status', 'pending')
                        ->where('method', 'card')
                        ->orderBy('created_at')
                        ->first();
                }
            }

            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'stripe_session_id' => $session->id,
                    'notes' => 'Paid via Stripe',
                ]);
            }
        }

        return response('OK', 200);
    }
}
