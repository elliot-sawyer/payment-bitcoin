<h2>Bitcoin payment instructions</h2>
<div class="bitcoin-payment">
	<div class="demo-warning-banner">
		<strong>Warning:</strong> This is a demonstration page only. Do not send payment. Redirection is not working. This order will not be honoured.
	</div>
	<div class="qr float-left">
		<img src="$Payment.QR.AbsoluteURL()" />
	</div>
	<div class="instructions float-right">
		<p>Please send exactly $Payment.Amount.Amount to <a href="$Payment.PaymentURI">$Payment.PaymentAddress</a>.</p>
		<p>Your web browser will be redirected when the payment arrives.</p>
	</div>
</div>



