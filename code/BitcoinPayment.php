<?php

class BitcoinPayment extends Payment { 
	
	static $db = array(
		'SecretToken' => 'Varchar',
		'PaymentAddress' => 'VarChar(34)',
		'DestinationAddress' => 'VarChar(34)',
		'FeePercent' => 'Double',
		'PaymentURI' => 'VarChar(50)',
		'BlockchainURL' => 'Text',
		'CallbackForBlockchain' => 'Text'
	);
	static $has_one = array(
		'QR' => 'Image'
	);

	public function populateDefaults() {
		DB::requireField("Payment", "AmountAmount", "Decimal(19,8)");
	}

	//Security check: avoid MITM attacks. Check cold storage address against return data from Blockchain (in case it's compromised)
	// public function onBeforeWrite() {
	// 	parent::onBeforeWrite();
	// 	return ($this->DestinationAddress === BITCOIN_COLDSTORAGE);
	// }
}
