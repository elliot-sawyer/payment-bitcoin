<?php
class BitcoinPrice extends Price {
	//digital currencies use 8 significant digits after the decimal place
	public $precision = 8;

	protected $symbol;

	public function compositeDatabaseFields() {
		return self::config()->composite_db;
	}

	public function __construct() {
		parent::__construct();
		debug::dump(self::config()->composite_db);die();
		if(defined('BITCOIN_CURRENCY_PRECISION')) {
			$this->precision = BITCOIN_CURRENCY_PRECISION;
		}
	}

	//override the traditional two decimal places
	public function getAmount() {
		return Zend_Locale_Math::round($this->amount, $this->precision);
	}

	//frontend display: override the traditional two decimal places
	public function Nice($options = array()) {
		if(!isset($options['precision'])) $options['precision'] = $this->precision;
		return parent::Nice($options);
	}

	public function setSymbol($symbol) {
		$this->symbol = $symbol;
		return $this;
	}

	public function getSymbol($currency = null, $locale = null) {
		return $this->symbol;
	}
}