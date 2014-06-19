<h2>Bitcoin payment instructions</h2>
<div class="bitcoin-payment">
	<div class="demo-warning-banner clearfix">
		<strong>Warning:</strong> Redirection is not working. Refresh the page to check your confirmed balance.
	</div>

	<strong>Unconfirmed balance</strong>: $Payment.UnconfirmedBalance
	<br/>
	<strong>Confirmed balance</strong>: $Payment.ConfirmedBalance
	<hr />
	<div class="qr float-left">
		<img src="$Payment.QR.AbsoluteURL()" />
	</div>
	<div class="instructions float-right">
		<p>Please send exactly $Payment.Amount.Amount to <a href="$Payment.PaymentURI">$Payment.PaymentAddress</a>.</p>
		<p>Your web browser will be redirected when the payment arrives.</p>
	</div>
</div>



