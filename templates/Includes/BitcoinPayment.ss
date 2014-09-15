<div class="BitcoinPayment" data-order-id="$Top.Order.ID"
	<% if $ConfirmationStatus = 100 %>
		data-order-complete="true"
	<% else %>
		data-order-complete="false"
	<% end_if %>
>
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
					<h3 class="order-status text-center">
					
					<% if $ConfirmationStatus = 100 %>
						Complete!
					<% else %>
						$Status
					<% end_if %>
					</h3>
				</div>

			</div>

			<div class="progress round <% if $ConfirmationStatus = 100 %>success<% end_if %>">
				<span class="meter hide" 
				<% if $ConfirmationStatus < 10 %>
				style="width:10%"
				<% else %>
				style="width:$ConfirmationStatus%"
				<% end_if %>
				>
				<div> $ConfirmationStatus%</div>
				</span>
			</div>

		</div>
	</div>
</div>