<?php

class BitcoinPaymentProcessor extends PaymentProcessor {

	private static $allowed_actions = array(
		'capture',
		'confirm',
		'callback',
		'complete',
		'status'
	);

	private $blockchainPaymentData = [];

	/**
	 * capture() method invoked by Payment module
	 * 			 redirects to a confirmation URL
	 * @param  associative array passed into PaymentProcessor::process()
	 * @return null
	 */
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

	/**
	 * overload confirm() method on PaymentProcessor
	 * @param  SS_Request $request 	request object passed into function
	 * 					redirects to order page
	 * @return null
	 */
	public function confirm($request) {
		// Reconstruct the payment object
		$payload = array();
		$this->payment = BitcoinPayment::get()->byID($request->param('OtherID'));
		// Reconstruct the gateway object

		if(!$this->payment->PaymentAddress) {
			$payload = $this->getPaymentData();
		}
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


		
		$this->payment->update($payload)->write();
		$this->payment->updateStatus(new PaymentGateway_Incomplete());

		//Example URL: http://1.2.3.4/BitcoinPaymentProcessor/confirm/BitcoinPaymentProcessor/8?ref=38

		// Do redirection
		$this->doRedirect();
	}
	
	/**
	 * Marks an order complete
	 * @param  SS_Request $request
	 * @return null
	 */
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
	
	/**
	 * Cancel method - cancels a payment in progress
	 * @param  SS_Request $request
	 * @return  null
	 */
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

	/**
	 * Callback function used by Blockchain.info to report transaction data as it occurs
	 * Note: the website needs to be publically accessible to the Internet for this to work
	 * Uncomment the "Email::create" statements in the else blocks to aid with debugging
	 * @return 	string 	"*ok*" if all conditions are met. Exact string tells Blockchain
	 * 					to "hang up" and stop trying the call back.
	 * 			null 	no output if there is an error, but messages will be emailed
	 * 					to the site admin
	 */
	public function callback() {
		//recreate the payment object
		$payment = BitcoinPayment::get()->filter([
			'Reference' => $this->request->getVar('OrderID'),
			'SecretToken' => $this->request->getVar('TokenSecret')
		])->First();

		//blockchain has sent us back some information about our payment object
		if($payment->ID) {

			//we've found our payment object, check that this request knows our cold storage address
			if($this->request->getVar('destination_address') === BITCOIN_COLDSTORAGE) {

				//is there a transaction hash?
				if($this->request->getVar('transaction_hash')) {

					$txHash = $this->request->getVar('transaction_hash');
					$inputTXHash = $this->request->getVar('input_transaction_hash');

					//is there a value and some confirmations?
					if($this->request->getVar('confirmations') >= 0 && $this->request->getVar('value') >= 0) {

						//only continue if we need more confirmations
						if($this->request->getVar('confirmations') <= BITCOIN_CONFIRMATIONS_THRESHOLD) {

							//awesome, we have all we need. See if tx has been recorded already
							$tx = $payment->Transactions()->find('TXHash', $this->request->getVar('transaction_hash')) ?: BitcoinPaymentTransaction::create();

							$tx->ConfirmationCount = $this->request->getVar('confirmations');
							$tx->Satoshi = $this->request->getVar('value');
							if($tx && $tx->ID) {
								//sweet, we've already received this one. Update the database
								$tx->write();

							} else {
								//this txhash is new, let's save it in the database
								$tx->TXHash = $txHash;
								$tx->InputTXHash = $inputTXHash;
								$payment->Transactions()->add($tx);
							}
						//..otherwise, we're done!
						} else {

							$payment->updateStatus(new PaymentGateway_Success());
							// $subject = 'bitcoin transaction confirmed';
							// $message =  'confirmations: '.$this->request->getVar('confirmations') 
							// 	.'<br/>value: '.$this->request->getVar('value')
							// 	.'<br/>txhash: '.$this->request->getVar('transaction_hash')
							// 	.'<br/>delivered to: '.BITCOIN_COLDSTORAGE;
							// Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, $subject, $message)->send();

							//we must return exactly this, otherwise Blockchain will keep sending data
							return "*ok*";
						}

					} else {
						// $subject = 'missing confirmation or value';
						// $message =  $this->request->getVar('confirmations') . " confirmations ||| value: " . $this->request->getVar('value');
						// Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, $subject, $message)->send();
					}
				} else {
					// $subject = 'missing tx hash';
					// $message = $this->request->getVar('transaction_hash');
					// Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, $subject, $message)->send();
				}
			} else {
				// $subject = 'destination address mismatch';
				// $message = $this->request->getVar('destination_address') . " does not equal " . BITCOIN_COLDSTORAGE;
				// Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, $subject, $message)->send();
			}
		} else {
			// $subject = 'payment object not found';
			// $message = json_encode(array('Reference' => $this->request->getVar('OrderID'),'SecretToken' => $this->request->getVar('TokenSecret')));
			// Email::create(SS_DEFAULT_ADMIN_EMAIL, SS_SEND_ALL_EMAILS_TO, $subject, $message)->send();
		}
	}

	public function status() {
		$this->response->addHeader("Content-Type", "application/json");
		$json = [];

		if(Director::is_ajax()) {
			$payment = BitcoinPayment::get()->filter([
				'Reference' => $this->request->getVar('OrderID')
			])->First();

			if($payment) {
				$json = [
					'ConfirmationStatus' => $payment->ConfirmationStatus(),
					'ConfirmedBalance' => $payment->ConfirmedBalance(),
					'UnconfirmedBalance' => $payment->UnconfirmedBalance()
				];
			}
		}
		return json_encode($json);

	}

	/**
	 * Obtain payment information from Blockchain.info
	 * @return Array  	BitcoinPayment data from Blockchain.info
	 * 					empty array if no response from blockchain
	 */
	private function getPaymentData() {

		//need a really random token, so we can recreate our payment object from Blockchain
		$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
		$tokenSecret = sha1(mcrypt_create_iv($size, MCRYPT_DEV_RANDOM));

		$callback_url = sprintf("%s/%s/callback?OrderID=%s&TokenSecret=%s",
			Director::protocolAndHost(),
			__CLASS__,
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
		$validResponse = (
			$response && 
			BitcoinPayment::check_address($response->input_address) &&
			BitcoinPayment::check_address($response->destination) &&
			is_numeric($this->payment->Amount->Amount)
		);
		if($validResponse) {
			$qr = $this->QRCode($response->input_address, $this->payment->Amount->Amount);
			return [
				'PaymentAddress' => $response->input_address,
				'DestinationAddress' => $response->destination,
				'FeePercent' => $response->fee_percent,
				'PaymentURI' => $this->BitcoinURI($response->input_address, $this->payment->Amount->Amount),
				'BlockchainURL' => $this->BlockchainURL($response->input_address),
				'QRID' => $qr->ID,
				'CallbackForBlockchain' => $callback_url,
				'SecretToken' => $tokenSecret
			];			
		}
		return [];

	}

	/**
	 * BlockchainURL get HTTP address to Bitcoin public address
	 * @param string $address	Bitcoin public address
	 * @return  string 	Web address or empty string if invalid
	 */
	private function BlockchainURL($address) {
		return BitcoinPayment::check_address($address)
			? sprintf("https://blockchain.info/address/%s", $address)
			: '';
	}

	/**
	 * BitcoinURI URI handler to open local program for payment
	 * @param string $address 	Bitcoin public address
	 * @param float $amount
	 * @return  string Bitcoin URI handler or empty string if invalid
	 */
	private function BitcoinURI($address, $amount) {
		return BitcoinPayment::check_address($address) && is_numeric($amount)
			? sprintf("bitcoin:%s?amount=%s", $address, $amount)
			: '';
	}

	/**
	 * QRCode obtains a QR code from Google Chart API
	 * @param [string] $address 	Bitcoin public address
	 * @param [float] $amount floating point amount
	 * @return  Image 	downloaded PNG from Google Charts API
	 * @todo Google Charts API is deprecated, we cannot rely on this 
	 * 		generate this locally without using an online API
	 */
	private function QRCode($address, $amount) {
		if(BitcoinPayment::check_address($address) && is_numeric($amount)) {
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
		return false;
	}
}