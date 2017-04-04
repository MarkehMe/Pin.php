<?php

require_once("Pin.php");

// Create an instance of the class, replace 'wxyz' with your private key
$Pin = new PinPayments("wxyz", true);

// Find test cards here: https://beta.pin.net.au/docs/api/test-cards
$customer = $Pin->createCustomer("m@rkhugh.es", [
	"number" => "4200000000000000",
	"expiry_month" => "05",
	"expiry_year" => "2018"
	"cvc" => "123",
	"name" => "Mark Hughes",
	"address_line1" => "1 George Street",
	"address_line2" => "",
	"address_city" => "Sydney",
	"address_postcode" => "2000",
	"address_state" => "NSW",
	"address_country" => "Australia"
]);

// Now we have some tokens :-)
// .. you should store this in your database
$customer_token = $customer->response->token;
$card_token = $customer->response->card->token;

$result = $Pin->createCharge([
	"email" => "m@rkhugh.es",
	"description" => "Beer",
	"amount" => "650", // Cents! My beer ain't that expensive..
	"ip_address" => "127.0.0.1",
	"customer_token" => $customer_token
]);
