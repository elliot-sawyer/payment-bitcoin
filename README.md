SilverStripe Payment Bitcoin Module
===================================

## Maintainer Contacts
* [Elliot Sawyer](https://github.com/silverstripe-elliot)

## Requirements
* Swipestripe 2.1.0
* SilverStripe 3.0.x
* Payment module 1.0.x

## Documentation
**Unstable and not suitable for production. This module is not officially maintained by Silverstripe Ltd.**

This module provides basic Bitcoin support for the SilverStripe Payment module via Blockchain.info's Receive Payment API. A random Bitcoin address will be shown for accepting payments, and all payments sent to that address are forwarded to a "cold storage" address defined by the server admin. See notes below. At present, it's intended use is the Swipestripe platform and has not been tested without it.

As of May 18 2014, it is very experimental and insecure, and should not be used in a production environment (indeed, there's a die() in place after obtaining payment information from Blockchain.info). It does not yet use the Payment module to save any confirmation data from the blockchain; as such, the application will never actually know when a transaction is confirmed.

### Installation guide
1. Place this directory in the root of your Swipestripe installation and call it 'payment-bitcoin'.
2. Add one new constant to your _ss_environment.php or mysite/_config.php file:
    define('BITCOIN_COLDSTORAGE', 'your-bitcoin-cold-storage-address')
  In addition, SS_DEFAULT_ADMIN_EMAIL and SS_SEND_ALL_EMAILS_TO constants must be set
3. Visit yoursite.com/dev/build?flush=1 to rebuild the database.
4. Setup some products in Swipestripe
5. Enable bitcoin payment method in your payment.yaml file (as shown below)
6. Fill up your shopping cart and choose "Bitcoin" as payment method and submit.
7. You will see payment instructions along with a QR code.


### Usage Overview
Enable in your application YAML config (e.g: mysite/_config/payment.yaml):

```yaml
PaymentGateway:
  environment:
    'dev'
PaymentProcessor:
  supported_methods:
    dev:
      - 'BitcoinGateway_Express'
    live:
      - 'BitcoinGateway_Express'
```

## Notes
* The module displays a payment address, along with a BitcoinURI link and QR Code for app wallets hosted on your computer or a smartphone.
* The module needs to be running on a server with a web-accessible address if you want to receive actual transaction info back from blockchain.info. However, it will still accept a non-public callback (though it obviously cannot send data to it).
* The callback URL, if reached, will email the response to the defined server admin one time and return the expected "*ok*" to prevent repeated callbacks.