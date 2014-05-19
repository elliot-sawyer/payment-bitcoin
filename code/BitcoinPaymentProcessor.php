<?php

class BitcoinPaymentProcessor extends PaymentProcessor {

	private static $allowed_actions = array(
		'capture',
		'complete'
	);

	//TODO all the info required to complete payment is here. Need to play nicely with Swipestripe ordering system
	public function capture($data) {

		parent::capture($data);

		$paymentData = $this->getPaymentData($data);
		echo sprintf("Payment address: %s
			<br/>
			<a href=\"%s\">Pay</a>
			<br/>
			<div><img src=\"%s\" /></div>", 
				$paymentData['payment_address'],
				$paymentData['payment_uri'],
				$paymentData['qr']->getAbsoluteURL()
			);

		if($paymentData) {
			debug::dump([
				'<h2>debugging data</h2>',
				$paymentData
			]);die();
			// $this->payment->PaymentAddress = $paymentData['payment_address'];
			// $this->payment->DestinationAddress = $paymentData['destination_address'];
			// $this->payment->SecretToken = $paymentData['tokenSecret'];
			// $this->payment->FeePercent = $paymentData['payment_feepercent'];
			// $this->payment->PaymentURI = $paymentData['payment_uri'];
			// $this->payment->QRID = $paymentData['qr']->ID;
			// $this->payment->BlockchainURL = $paymentData['verification_address'];
			// $this->payment->CallbackForBlockchain = urldecode($paymentData['callback']);
		} else {
//			$this->payment->Status = 'Failed';
		}
		
	}

	public function complete($request) {
		debug::dump([__METHOD__,$request]);die();
	}

	private function getPaymentData($FormData) {
		$tokenSecret = md5(json_encode([$this->payment, microtime()]));
		$callback_url = sprintf("%s/BitcoinPaymentProcessor_Controller/callback?OrderID=%s&TokenSecret=%s",
			Director::protocolAndHost(),
			$this->payment->OrderID,
			$tokenSecret
		);
		$svc = new RestfulService('https://blockchain.info/api');
		$svc->setQueryString(array(
			'method' => 'create',
			'address' => BITCOIN_COLDSTORAGE,
			'callback' => $callback_url
		));

		$blockchain = $svc->request('/receive');
		$response = json_decode($blockchain->getBody());
		if($response) {
			$qr = $this->QRCode($response->input_address, $FormData['Amount']);
			return [
				'payment_address' => $response->input_address,
				'destination_address' => $response->destination,
				'payment_feepercent' => $response->fee_percent,
				'payment_uri' => $this->BitcoinURI($response->input_address, $FormData['Amount']),
				'verification_address' => $this->BlockchainURL($response->input_address),
				'qr' => $qr,
				'callback' => $callback_url,
				'tokenSecret' => $tokenSecret
			];			
		}
		return [];

	}
	private function BlockchainURL($address) {
		return sprintf("https://blockchain.info/address/%s", $address);
	}

	private function BitcoinURI($address, $amount) {
		return sprintf("bitcoin:%s?amount=%s", $address, $amount);
	}

	private function QRCode($address, $amount) {
		$svc = new RestfulService('https://chart.googleapis.com/');
		$svc->setQueryString(array(
			'chs' => '150x150',
			'cht' => 'qr',
			'chl' => 'bitcoin:'.$address.'?amount='.$amount,
			'choe' => 'UTF-8'
		));
		$qr = $svc->request('chart');

		$folder = Folder::find_or_make('qrcode');
		$imgData = array(
			'Name' => $address.'.png',
		);
		$image = Image::get()->filter($imgData)->First() ?: new Image($imgData);
		$image->setParentID($folder->ID);
		$image->write();
		file_put_contents($image->getFullPath(), $qr->getBody());

		return $image;
	}

}

class BitcoinPaymentProcessor_Controller extends Page_Controller {
	static $allowed_actions = array(
		'callback'
	);

	public function callback() {
		// value - The value of the payment received in satoshi. Divide by 100000000 to get the value in BTC.
		// input_address - The bitcoin address that received the transaction.
		// confirmations - The number of confirmations of this transaction.
		// order id
		//tokensecret 
		// transaction_hash - The transaction hash.
		// input_transaction_hash - The original paying in hash before forwarding.
		// destination_address - The destination bitcoin address. Check this matches your address.
		Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, 'blockchain callback data',
			json_encode([
				(array)$this->request
			])
		)->send();
		return '*ok*';
	}
}
