<?php

class BitcoinPayment extends Payment { 

	private static $satoshi = 100000000;
	
	/**
	 * @todo BITCOIN_CONFIRMATIONS_THRESHOLD as a DB column
	 * @var array
	 */
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

	/**
	 * Show balance of transactions whose confirmation count 
	 * 		exceed BITCOIN_CONFIRMATIONS_THRESHOLD
	 * @return  float
	 */
	public function ConfirmedBalance() {
		return 
			number_format( $this->Transactions()
				->exclude("ConfirmationCount:LessThan", BITCOIN_CONFIRMATIONS_THRESHOLD)
				->sum("Satoshi")
				/ (float) self::$satoshi
			, 8);
	}

	/**
	 * Show balance of transactions whose confirmation count 
	 * 		are less than BITCOIN_CONFIRMATIONS_THRESHOLD
	 */
	public function UnconfirmedBalance() {
		return 
			number_format( $this->Transactions()
				->filter("ConfirmationCount:LessThan", BITCOIN_CONFIRMATIONS_THRESHOLD)
				->sum("Satoshi")
				/ (float) self::$satoshi
			, 8);
	}

	/**
	 * Display confirmation status as a percentage on Order page
	 * @return  int		confirmation status between 0 and 100 percent
	 */
	public function ConfirmationStatus() {

		//default status
		$status = 0;
		if($total = $this->Transactions()->Count()) {
			$total *= (float) BITCOIN_CONFIRMATIONS_THRESHOLD;

			$remaining = (float)  $this->Transactions()->sum('ConfirmationCount');

			$status = (float) ($remaining / $total);
			$status *= 100;
			$status = round($status);
		}
		return $status;
	}

	/**
	 * Soft check that a Bitcoin address is properly formatted
	 *    	Does not perform checksum or determine validity
	 * @param  [string] $address Bitcoin public address
	 * @return [boolean]
	 */
	public static function check_address($address) {
		return (bool) preg_match('/^[13]{1}[1-9ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{26,34}$/', $address);
	}
}
