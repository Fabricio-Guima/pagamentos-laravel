<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentInfoRequest;
use App\Services\PayPalService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(PaymentInfoRequest $request)
    {
        // dd($request->all());
        $paymentPlatform = resolve(PayPalService::class);

        return $paymentPlatform->handlePayment($request);


    }

    public function approval()
    {
        $paymentPlatform = resolve(PayPalService::class);

        return $paymentPlatform->handleApproval();

    }

    public function cancelled()
    {
        
    }
}
