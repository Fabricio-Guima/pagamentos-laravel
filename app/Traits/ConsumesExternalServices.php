<?php

namespace App\Traits;
use GuzzleHttp\Client;


trait ConsumesExternalServices
{
	public function makeRequest($methods, $requestUrl, $queryParams = [], $formParams = [],$headers = [], $isJsonRequest = false)
	{
		$client = new Client([
			'base_uri' =>$this->baseUri,
			'verify' => false
		]);

		if(method_exists($this, 'decodeResponse')) {
			$this->resolveAuthorization($queryParams, $formParams, $headers);

		}

		$response = $client->request($methods, $requestUrl, [
			$isJsonRequest ? 'json' : 'form_params' => $formParams,
			'headers' =>$headers,
			'query' =>$queryParams,
		]);

		$response = $response->getBody()->getContents();

		$response = $this->decodeResponse($response);

		return $response;
	}
}