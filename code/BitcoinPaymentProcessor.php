<?php

class BitcoinPaymentProcessor extends PaymentProcessor {

	private static $allowed_actions = array(
		'capture',
		'confirm',
		'complete'
	);

	private $blockchainPaymentData = [];

	//TODO all the info required to complete payment is here. Need to play nicely with Swipestripe ordering system
	public function capture($data) {

		parent::capture($data);

		//Redirect to a form that the customer can submit
		$confirmURL = Director::absoluteURL(Controller::join_links(
			$this->link(),
			'confirm',
			$this->methodName,
			$this->payment->ID,
			'?ref=' . $data['Reference']
		));
		
		Controller::curr()->redirect($confirmURL);
		return;
	}


	public function confirm($request) {

		Requirements::css("payment-bitcoin/templates/css/bitcoin-tx.css");

		// Reconstruct the payment object
		$this->payment = BitcoinPayment::get()->byID($request->param('OtherID'));

		// Reconstruct the gateway object
		$methodName = $request->param('ID');
		$this->gateway = PaymentFactory::get_gateway($methodName);
		
		$config = Config::inst()->get('BitcoinPaymentGateway', PaymentGateway::get_environment());
		
		$returnURL = Director::absoluteURL(Controller::join_links(
			$this->link(),
			'complete',
			$methodName,
			$this->payment->ID
		));
		
		$cancelURL = Director::absoluteURL(Controller::join_links(
			$this->link(),
			'cancel',
			$methodName,
			$this->payment->ID
		));
		
		$ref = $request->getVar('ref');


		$payload = $this->getPaymentData();
		$this->payment->update($payload);

		$content = $this->customise(array(
			'Payment' => $this->payment
		))->renderWith('BitcoinConfirmation');
		
		return $this->customise(array(
			'Content' => $content,
		))->renderWith('Page');
	}
	


	public function complete($request) {
		
		SS_Log::log(new Exception(print_r($request, true)), SS_Log::NOTICE);
		
		// Reconstruct the payment object
		$this->payment = Payment::get()->byID($request->param('OtherID'));

		// Reconstruct the gateway object
		$methodName = $request->param('ID');
		$this->gateway = PaymentFactory::get_gateway($methodName);

		// Query the gateway for the payment result
		$result = $this->gateway->getResponse($request);
		$this->payment->updateStatus($result);

		// Do redirection
		$this->doRedirect();
	}
	
	public function cancel($request) {
		// Reconstruct the payment object
		$this->payment = Payment::get()->byID($request->param('OtherID'));

		// Reconstruct the gateway object
		$methodName = $request->param('ID');
		$this->gateway = PaymentFactory::get_gateway($methodName);

		// Query the gateway for the payment result
		// $result = $this->gateway->getResponse($request);
		$this->payment->updateStatus(new PaymentGateway_Failure(null, 'Payment was cancelled.'));

		// Do redirection
		$this->doRedirect();
	}

	private function getPaymentData() {
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
			$qr = $this->QRCode($response->input_address, $this->payment->Amount->Amount);
			return [
				'PaymentAddress' => $response->input_address,
				'DestinationAddress' => $response->destination,
				'FeePercent' => $response->fee_percent,
				'PaymentURI' => $this->BitcoinURI($response->input_address, $this->payment->Amount->Amount),
				'BlockchainURL' => $this->BlockchainURL($response->input_address),
				'QRID' => $qr->ID,
				'CallbackForBlockchain' => $callback_url,
				'Token' => $tokenSecret
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