<?php

class BitcoinPaymentGateway extends PaymentGateway {
	
	protected $supportedCurrencies = array(
		'NZD' => 'New Zealand Dollar',
		'USD' => 'United States Dollar',
		'BTC' => 'Bitcoin',
		'XBT' => 'Bitcoin'
	);

	public function confirm($data) {
		return new PaymentGateway_Success($data, 'success');
	}
}