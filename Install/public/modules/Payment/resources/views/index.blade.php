@extends('layouts.app')

@section('content')
    @php
        $calculatePayment = Payment::calculatePayment($plan->price, $plan->id, true);
    @endphp 

    <div class="max-w-600 mx-auto p-5">
        <div class="text-center">
            <div class="mb-2">
                <span class="badge badge-outline badge-sm badge-pill badge-info">
                    @switch($plan->type)

                        @case(2)
                            {{ __("Yearly") }}
                            @break

                        @case(3)
                            {{ __("Lifetime") }}
                            @break

                        @default
                            {{ __("Monthly") }}

                    @endswitch
                </span>
            </div>
            <div class="fw-9 fs-30 mb-2">{{ $plan->name }}</div>
            <div class="text-gray-700 mb-5">{{ $plan->desc }}</div>
        </div>

        @include('admincoupons::block_apply_coupon', [])

        <div class="card border-gray-300 b-r-6 fs-14 mb-5">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-12 mb-2">
                    <div>{{ __("Subtotal") }}</div>
                    <div class="text-end">{{ $calculatePayment['subtotal'] }}</div>
                </div>
                <div class="d-flex justify-content-between gap-12 mb-2">
                    <div>{{ __("Promotion") }}</div>
                    <div class="text-end text-danger">{{ $calculatePayment['discount'] }}</div>
                </div>
                

                <div class="d-flex justify-content-between gap-12 border-top pt-3 mt-3 fw-6">
                    <div>{{ __("Total") }}</div>
                    <div class="text-end">{{ $calculatePayment['total'] }}</div>
                </div>
            </div>
        </div>

        @php
        $paymentTypes = [
            1 => __("One-time Payment"),
            2 => __("Recurring Payment"),
        ];
        $allPayments = Payment::getPaymentsByType(null);
        $havePaymentMethod = false;
        @endphp

        <div class="mb-4">
            <span class="fw-6 text-gray-900 fs-20">{{ __("Payment methods") }}</span>
        </div>

        @foreach($paymentTypes as $type => $label)
            @if(!empty($allPayments[$type]))
                @php
                $havePaymentMethod = true;
                @endphp
                <div class="d-flex align-items-center gap-2 mt-3 mb-1">
                    <span class="fw-6 text-gray-700 fs-16">{{ $label }}</span>
                </div>
                @if($type == 1)
                    <div class="fs-13 text-muted mb-3">{{ __("Pay one time, no auto-renewal") }}</div>
                @elseif($type == 2)
                    <div class="fs-13 text-muted mb-3">{{ __("Subscription will auto-renew until you cancel") }}</div>
                @endif
                <div class="row mb-4">
                    @foreach($allPayments[$type] as $value)
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-4">
                            <a 
                                href="{{ route("payment.checkout", [ "gateway" => $value['uri'], "plan" => $plan->id_secure ]) }}" 
                                class="card shadow-none border border-gray-300 b-r-6 bg-hover-primary-100 border-hover-primary payment-option"
                                data-payment-type="{{ $type }}"
                                data-gateway="{{ $value['uri'] }}"
                            >
                                <div class="card-body d-flex justify-content-between align-items-center px-3 gap-16">
                                    <div class="d-flex align-items-center gap-8 fs-13 fw-5 text-truncate">
                                        <div class="size-30 d-flex align-items-center justify-content-between fs-20">
                                            <img src="{{ $value['logo'] }}" class="w-100" alt="{{ $value['name'] }}">
                                        </div>
                                        <div>
                                            {{ $value['name'] }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach

        @if( get_option("payment_manual_status", 0) )

        @php
        $havePaymentMethod = true;
        @endphp
        <div class="d-flex align-items-center gap-2 mt-3 mb-1">
            <span class="fw-6 text-gray-700 fs-16">{{ __("Manual Payment") }}</span>
        </div>
        <div class="fs-13 text-muted mb-3">{{ __("Bank transfer, cash, or manual confirmation.") }}</div>

        <div class="card p-4 border border-gray-300 rounded-3 mb-4">
            @include("components.main-message")
            
            <ul class="mb-3 fs-14 text-muted">
                <li class="mb-3">
                    {!! get_option('payment_manual_info', 'Bank Info') !!}
                </li>
                <li>
                    <span class="mb-1 fw-6">{{ __("Transfer content") }}</span>
                    <span class="badge badge-light b-r-6 fs-15 px-3 py-3 w-100">{{ $transactionCode }}</span>
                </li>
            </ul>
            {{-- UPI QR Code & Instant Deposit Block --}}
            @php
                $waUpiPrice = preg_replace('/[^0-9.]/', '', $calculatePayment['total'] ?? $plan->price ?? 0);
                $waUpiUri = 'upi://pay?pa=abdulbahad833@okicici&pn=WhatsAppBulk&am=' . $waUpiPrice . '&cu=INR';
                $waQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($waUpiUri);
            @endphp
            <div class="p-3 my-3 text-center bg-white rounded-3 shadow-sm border border-gray-300">
                <span class="badge badge-outline badge-success mb-2 font-weight-bold" style="font-size: 11px;">SCAN TO PAY VIA ANY UPI APP</span>
                <div class="my-2">
                    <img id="payment_wa_qr_img" src="{{ $waQrUrl }}" alt="UPI QR Code" style="width: 170px; height: 170px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 4px;" />
                </div>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-1" style="border-radius: 20px; font-size: 11px;" onclick="var img=document.getElementById('payment_wa_qr_img'); if(img && img.src){ fetch(img.src).then(r=>r.blob()).then(b=>{ var u=URL.createObjectURL(b); var a=document.createElement('a'); a.href=u; a.download='whatsappbulk-upi-qr.png'; a.click(); URL.revokeObjectURL(u); }).catch(()=>window.open(img.src,'_blank')); }">
                        <i class="fas fa-download me-1"></i> {{ __('Download QR Code') }}
                    </button>
                </div>
                <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mt-2">
                    <span class="fw-bold" style="font-size: 13px;">UPI ID:</span>
                    <code class="px-2 py-1 bg-light rounded border text-primary fw-bold" style="font-size: 13px;">abdulbahad833@okicici</code>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 fw-bold" style="font-size: 11px; border-radius: 6px;" onclick="if(navigator.clipboard){navigator.clipboard.writeText('abdulbahad833@okicici');} var b=this; b.innerHTML='<i class=\'fas fa-check text-success\'></i> Copied!'; setTimeout(function(){ b.innerHTML='<i class=\'fas fa-copy\'></i> Copy'; }, 2000);">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <small class="text-muted d-block mt-2" style="font-size: 11px;">Supports GPay, PhonePe, Paytm, BHIM & NetBanking UPI</small>
            </div>

            <form action="{{ route("payment.manual_payment") }}" method="POST">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id_secure }}">
                <input type="hidden" name="transaction_code" value="{{ $transactionCode }}">
                <div class="mb-3">
                    <label class="form-label fw-6">{{ __("UTR / Transaction Reference No. (Optional)") }}</label>
                    <input type="text" name="utr_number" class="form-control" placeholder="e.g. 427819028341">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-6">{{ __("Your transfer information") }}</label>
                    <textarea name="payment_info" class="form-control" rows="2" placeholder="{{ __("E.g. I transferred via PhonePe/GPay, account name John Doe") }}" required></textarea>
                </div>
                <div class="mb-3">
                    {!! Captcha::render(); !!}
                </div>
                <button type="submit" class="btn btn-dark w-100 ">
                    {{ __("I have transferred") }}
                </button>
            </form>
        </div>
        @endif

        @if(!$havePaymentMethod)
        <div class="mt-5 d-flex flex-column align-items-center justify-content-center py-5">
            <div class="empty"></div>
            <div class="fw-bold fs-5 mt-3 text-dark">{{ __("No payment methods available") }}</div>
            <div class="text-muted mt-2">
                {{ __("Please contact support or try again later.") }}
            </div>
        </div>
        @endif
    </div>
@endsection


