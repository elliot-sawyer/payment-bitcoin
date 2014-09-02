<div class="BitcoinPayment">
	<div class="row">
		<div class="small-2 large-2 columns">
			<img src="$QR.AbsoluteURL()" />
		</div>
		<div class="small-10 large-10 columns">
			<h4>Bitcoin Payment Instructions</h4>

			<div class="row">

				<div class="small-6 large-6 columns">
					<div>Please send exactly $Top.BaseCurrencySymbol $Amount.Amount to</div>
					<div class="public-address">
						<a href="$PaymentURI">$PaymentAddress</a>
					</div>
					<div class="browser-refresh">Refresh the page to check your confirmed balance.</div>
				</div>

				<div class="small-6 large-6 columns">
					<h3 class="order-status text-center">$Status</h3>
				</div>

			</div>

			<div class="progress success round">
				<% if $ConfirmationStatus < 10 %>
				<span class="meter" style="width:10%">
				<% else %>
				<span class="meter" style="width:$ConfirmationStatus%">
				<% end_if %>
				<div> $ConfirmationStatus%</div>
				</span>
			</div>

		</div>
	</div>
</div>