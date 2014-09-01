<?php

class BitcoinPayment extends Payment { 

	private static $satoshi = 100000000;
	
	static $db = array(
		'SecretToken' => 'Varchar',
		'PaymentAddress' => 'VarChar(34)',
		'DestinationAddress' => 'VarChar(34)',
		'FeePercent' => 'Double',
		'PaymentURI' => 'VarChar(80)',
		'BlockchainURL' => 'Text',
		'CallbackForBlockchain' => 'Text'
	);

	static $has_one = array(
		'QR' => 'Image'
	);

	static $has_many = array(
		'Transactions' => 'BitcoinPaymentTransaction'
	);

	public function ConfirmedBalance() {
		return $this->Transactions()
			->exclude("ConfirmationCount:LessThan", BITCOIN_CONFIRMATIONS_THRESHOLD)
			->sum("Satoshi")
			/ self::$satoshi;
	}

	public function UnconfirmedBalance() {
		return $this->Transactions()
			->filter("ConfirmationCount:LessThan", BITCOIN_CONFIRMATIONS_THRESHOLD)
			->sum("Satoshi")
			/ self::$satoshi;
	}
}
