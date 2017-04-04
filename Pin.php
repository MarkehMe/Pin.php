<?php

define("PIN_PHP_VERSION", "0.0.1");

class PinPayments {

	/*--------------------------------------------------*
	 *  Construct
	 *--------------------------------------------------*/

	function __construct($private_key, $test) {
		$this->private_key = $private_key;

		if ($test) {
			$this->hostname = "https://test-api.pin.net.au/1/";
		}
	}

	/*--------------------------------------------------*
	 *  Variables
	 *--------------------------------------------------*/

	private $hostname = "https://api.pin.net.au/1/";
	private $private_key;

	/*--------------------------------------------------*
	 *  API Functions
	 *--------------------------------------------------*/

	/**
	 * Create a charge to a card or token and returns the results
	 *
	 * Uses Pin Payments charges endpoint to create a charge to either a card,
	 * card token, or a customer token. You can select this by setting a key
	 * with the appropriate information in the charge array.
	 *
	 * @param array $charge The charge information.
	 *
	 * @return array
	 */
	function createCharge($fields = []) {
		return $this->sendPOSTRequest("charges", $fields);
	}

	/**
	 * Captures a previously authorised charge and returns its details.
	 *
	 * Uses Pin Payments charges endpoint to create a capture a previously
	 * authorised charge and returns its details. Currently, you can only
	 * capture the full amount that was originally authorised.
	 *
	 * @param string $token The charge token.
	 *
	 * @return array
	 */
	function capture($token) {
		return $this->sendPUTRequest("charges/{$token}/capture");
	}

	/**
	 * Returns a paginated list of all charges.
	 *
	 * @param integer $page The page, defaults to 1.
	 *
	 * @return array
	 */
	function getCharges($page = 1) {
		return $this->sendGETRequest("charges", [ "page" => $page ]);
	}

	/**
	 * Returns a paginated list of charges matching the search criteria.
	 *
	 * @param array $page The page, defaults to 1.
	 *
	 * @return array
	 */
	function searchCharges($fields) {
		return $this->sendGETRequest("charges", $fields);
	}

	function getChargeDetails($charge_token) {
		return $this->sendGETRequest("charges/{$charge_token}");
	}

	function refund($charge_token, $amount = null) {
		if ($amount != null) {
			return $this->sendPOSTRequest("charges/{$charge_token}/refunds", ["amount" => $amount]);
		} else {
			return $this->sendPOSTRequest("charges/{$charge_token}/refunds");
		}
	}

	function createCustomer($email, $card) {
		$fields = [
			"email" => $email,
			"card[number]" => $card["number"],
			"card[expiry_month]" => $card["expiry_month"],
			"card[expiry_year]" => $card["expiry_year"],
			"card[cvc]" => $card["cvc"],
			"card[name]" => $card["name"],
			"card[address_line1]" => $card["address_line1"],
			"card[address_line2]" => $card["address_line2"],
			"card[address_city]" => $card["address_city"],
			"card[address_postcode]" => $card["address_postcode"],
			"card[address_state]" => $card["address_state"],
			"card[address_country]" => $card["address_country"]

		];

		return $this->sendPOSTRequest("customers", $fields);
	}

	function captureCustomer($customer_token, $fields) {
		$fields["customer_token"] = $customer_token;

		return $this->sendPOSTRequest("charges", $fields);
	}

	function getAllCustomers($page = 1) {
		return $this->sendGETRequest("customers", [ "page" => $page ]);
	}

	function getCustomer($customer_token) {
		return $this->sendGETRequest("customers/{$customer_token}");
	}

	function updateCustomerCard($customer_token, $card, $email = null) {
		$fields = [
			"card" => $card
		];

		return $this->sendPUTRequest("customers/{$customer_token}");
	}

	function deleteCustomer($customer_token) {
		return $this->sendDELETERequest("customers/{$customer_token}");
	}

	function getCustomerCharges($customer_token) {
		return $this->sendGETRequest("customers/{$customer_token}/charges");
	}

	function getCustomerCards($customer_token, $page = 1) {
		return $this->sendGETRequest("customers/{$customer_token}/charges", [ "page" => $page ]);
	}

	function addCustomerCard($customer_token, $card_or_token) {
		if (is_array($card_or_token)) {
			return $this->sendPOSTRequest("customers/{$customer_token}/cards", $card_or_token);
		} else {
			return $this->sendPOSTRequest("customers/{$customer_token}/cards", [ "card_token" => $card_or_token ]);
		}
	}

	function removeCustomerCard($customer_token, $card_token) {
		return $this->sendDELETERequest("customers/{$customer_token}/cards/{$card_token}");
	}

	/*--------------------------------------------------*
	 *  Util Functions
	 *--------------------------------------------------*/

	function sendGETRequest($endpoint, $fields = []) {
		$url = "{$this->hostname}{$endpoint}";

		if ( ! empty($fields)) {
			$fields_string = "";
			foreach ($fields as $key => $value) {
				$fields_string .= "{$key}={$value}&";
			}

			rtrim($fields_string, '&');

			$url = "{$url}?{$fields_string}";
		}

		$c = $this->fetchCURL($url);
		$response = json_decode(curl_exec($c));
		$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$response->status_code = $http_code;
		curl_close($c);

		return $response;
	}

	function sendPOSTRequest($endpoint, $fields) {
		$url = "{$this->hostname}{$endpoint}";

		$fields_string = "";
		foreach ($fields as $key => $value) {
			$fields_string .= "{$key}={$value}&";
		}

		rtrim($fields_string, '&');

		$c = $this->fetchCURL($url);
		curl_setopt($c, CURLOPT_POST, count($fields));
		curl_setopt($c, CURLOPT_POSTFIELDS, $fields_string);
		$response = json_decode(curl_exec($c));
		$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$response->status_code = $http_code;
		curl_close($c);

		return $response;
	}

	function sendPUTRequest($endpoint, $fields = []) {
		$url = "{$this->hostname}{$endpoint}";

		$c = $this->fetchCURL($url);

		curl_setopt($c, CURLOPT_PUT, true);

		if ( ! empty($fields)) {
			curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($fields));
		}

		$response = json_decode(curl_exec($c));
		$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$response->status_code = $http_code;
		curl_close($c);

		return $response;
	}

	function sendDELETERequest($endpoint, $fields = []) {
		$url = "{$this->hostname}{$endpoint}";

		$c = $this->fetchCURL($url);

		curl_setopt($c, CURLOPT_CUSTOMREQUEST, "DELETE");

		if ( ! empty($fields)) {
			curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($fields));
		}

		$response = json_decode(curl_exec($c));
		$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$response->status_code = $http_code;
		curl_close($c);

		return $response;
	}

	function fetchCURL($url) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_USERPWD, "{$this->private_key}:");
		curl_setopt($c, CURLOPT_USERAGENT, "Pin.php (github.com/MarkehMe/Pin.php)/" . PIN_PHP_VERSION);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		return $c;
	}

}
