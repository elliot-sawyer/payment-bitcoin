<?php
if(!defined('BITCOIN_CONFIRMATIONS_THRESHOLD')) {
	define("BITCOIN_CONFIRMATIONS_THRESHOLD", 3);
}
Object::useCustomClass("Price", "BitcoinPrice");