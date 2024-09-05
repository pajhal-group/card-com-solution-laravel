<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Cart\Http\Middleware\CheckCartItemsStock;
use Modules\Cart\Http\Middleware\RedirectIfCartIsEmpty;

class CheckoutController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware([
            RedirectIfCartIsEmpty::class,
        ]);

        $this->middleware([
            CheckCartItemsStock::class,
        ])->only('store');
    }



    public function handleCardcomWebhook(Request $request)
    {
        // Extract relevant information from the webhook payload
        $orderId = $request->input('orderId');
        $status = $request->input('status');
        // Add more fields as per your requirement

        // Find the order by ID
        $order = Order::find($orderId);

        if ($order) {
            // Update the order status or perform other actions based on the webhook data
            if ($status === 'success') {
                $order->update(['status' => 'completed']);
            } else {
                $order->update(['status' => 'failed']);
            }
        }

        // Return a response to acknowledge receipt of the webhook
        return response()->json(['message' => 'Webhook received and processed']);
    }
}
