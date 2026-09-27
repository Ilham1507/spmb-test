<?php

namespace App\Http\Controllers;

use App\Models\PaymentCheckout;
use App\Services\MidtransClient;
use App\Services\PaymentCheckoutService;
use Illuminate\Http\Request;
use Throwable;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransClient $client, PaymentCheckoutService $service)
    {
        abort_unless($client->ready(), 503);
        abort_unless($client->validSignature($request->all()), 403);
        $checkout = PaymentCheckout::where('order_id', $request->input('order_id'))->firstOrFail();
        try {
            // Callback status is NOT trusted: the signature does not cover every field.
            $confirmed = $service->refresh($checkout);
            if (! $confirmed->provider_status) {
                return response()->json(['message' => 'Menunggu status penyedia.'], 503);
            }
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Status belum dapat dikonfirmasi.'], 503);
        }

        return response()->json(['received' => true]);
    }
}
