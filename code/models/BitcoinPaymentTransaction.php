<?php
class BitcoinPaymentTransaction extends DataObject {
	
	static $db = array(
		'TXHash' => 'VarChar(64)',
		'InputTXHash' => 'Text',
		'ConfirmationCount' => 'Int',
		'Satoshi' => 'Int'
	);

	static $has_one = array(
		'BitcoinPayment' => 'BitcoinPayment'
	);
}