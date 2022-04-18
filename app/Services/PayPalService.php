<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

class PayPalService
{
	use ConsumesExternalServices;

	protected $baseUri;
	protected $clientId;
	protected $clientSecret;

	public function __construct()
	{
		$this->baseUri = config('services.paypal.base_uri');
		$this->clientId = config('services.paypal.client_id');
		$this->clientSecret = config('services.paypal.client_secret');
		
	}

	public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
	{
		$headers['Authorization'] =  $this->resolveAccessToken();
	}

	//transformando a resposta do paypal em json
	public function decodeResponse($response)
	{
		return json_decode($response);
	}

	//para vc conversar com o paypal, tem que passar o authorization a eles
	public function resolveAccessToken()
	{
		$credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");

		return "Basic {$credentials}";
	}

	public function handlePayment($request)
	{
		$order = $this->createOrder($request->value, $request->currency);

		$orderLinks = collect($order->links);

		$approve = $orderLinks->where('rel', 'approve')->first();

		session()->put('approval_id', $order->id);

		return redirect($approve->href);
	}

	public function handleApproval()
	{
		if(session()->has('approval_id')) {
			$approvalId = session()->get('approval_id');

			$payment = $this->capturePayment($approvalId);

			$name = $payment->payer->name->given_name;
			$payment = $payment->purchase_units[0]->payments->captures[0]->amount;
			$amount = $payment->value;
			$currency = $payment->currency_code;

			return redirect()->route('home')->withSuccess(['payment' => "Thanks, ${name}. We receive your {$amount}{$currency} payment."]);
		}

		return redirect()->route('home')->withErrors('we cannot capture your payment. Try again, please');
	}

	public function createOrder($value, $currency)
	{
		return $this->makeRequest(
			'POST',
			'/v2/checkout/orders',
			[],
			[
				'intent' => 'CAPTURE',
				'purchase_units' => [
					0 => [
						'amount' => [
							'currency_code' => strtoupper($currency),
							'value' => $value,
						]
					]
				],
				'application_context' => [
					'brand_name' => config('app.name'),
					'shipping_preference' => 'NO_SHIPPING',
					'user_action' => 'PAY_NOW',
					'return_url' => route('approval'),
                    'cancel_url' => route('cancelled'),
				]
			],
			[],
			$isJsonRequest = true,
		);
	}

	public function capturePayment($approvalId)
	{
		return $this->makeRequest(
			'POST',
			"/v2/checkout/orders/{$approvalId}/capture",
			[],
			[],
			[
				'Content-Type' => 'application/json',
			],
		);
	}
}