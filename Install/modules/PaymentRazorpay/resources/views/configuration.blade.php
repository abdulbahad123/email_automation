<div class="modal fade" id="razorpayConfigurationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<form class="modal-content actionForm" action="{{ url_admin("settings/save") }}" data-call-success="Main.closeModal('razorpayConfigurationModal');">
			<div class="modal-header">
				<h1 class="modal-title fs-16">{{ __("Razorpay Configuration") }}</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-4">
         		<div class="msg-errors"></div>
 				<div class="row">
 					<div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">{{ __('Status') }}</label>
                            <div class="d-flex gap-8 flex-column flex-lg-row flex-md-column">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="razorpay_status" value="1" id="razorpay_status_1" @checked( get_option("razorpay_status", 1) == 1 ) >
                                    <label class="form-check-label mt-1" for="razorpay_status_1">
                                        {{ __('Enable') }}
                                    </label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="razorpay_status" value="0" id="razorpay_status_0" @checked( get_option("razorpay_status", 1) == 0 ) >
                                    <label class="form-check-label mt-1" for="razorpay_status_0">
                                        {{ __('Disable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
 					<div class="col-md-12">
 						<div class="mb-3">
		                  	<label for="razorpay_key_id" class="form-label">{{ __('Razorpay Key ID') }}</label>
	                     	<input class="form-control" name="razorpay_key_id" id="razorpay_key_id" type="text" value="{{ get_option("razorpay_key_id", "rzp_test_T9UaATIMf1qeO8") }}" placeholder="rzp_test_..." required>
		                </div>
 					</div>
 					<div class="col-md-12">
 						<div class="mb-3">
		                  	<label for="razorpay_key_secret" class="form-label">{{ __('Razorpay Key Secret') }}</label>
	                     	<input class="form-control" name="razorpay_key_secret" id="razorpay_key_secret" type="text" value="{{ get_option("razorpay_key_secret", "BQ9Z865NgRQrrIMCusfzmskZ") }}" placeholder="Secret Key" required>
		                </div>
 					</div>
                    <div class="col-md-12">
                        <div class="alert alert-info py-2 px-3 fs-12 mb-0" style="background:#e0f2fe;border:1px solid #bae6fd;color:#0369a1;border-radius:10px;">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            {{ __('Test API credentials are ready. Supports UPI (GPay/PhonePe), Credit/Debit Cards, NetBanking, and Wallets.') }}
                        </div>
                    </div>
 				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
				<button type="submit" class="btn btn-dark">{{ __('Save changes') }}</button>
			</div>
		</form>
	</div>
</div>
