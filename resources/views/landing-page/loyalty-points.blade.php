@extends('landing-page.layouts.default')

@section('content')
    <div class="container mt-3">
        <div class="row">
            <div class="col-12 mt">
                <div class="header">
                    <div class="d-flex justify-content-center align-items-center mt-3">
                        <h5>{{ __('landingpage.loyalty_points') }}</h5>
                    </div>
                </div>
                <div class="body">
                    <div class="row">
                        <div class="col-12">
                            <div class="row justify-content-between gy-3">
                                <div class="d-flex col-12 gap-2 mb-3">
                                    <button class="btn btn-primary loyalty-btn rounded-pill py-1 me-2" data-type="loyalty"
                                        style="font-size: .92rem;">{{ __('landingpage.loyalty') }}</button>
                                    <button
                                        class="btn btn-primary-subtle loyalty-btn rounded-pill py-1 @if ($referrer_points == null && $referred_user_points == null) d-none @endif"
                                        data-type="referral"
                                        style="font-size: .92rem;">{{ __('landingpage.referral') }}</button>
                                </div>
                                <!-- Loyalty Section -->
                                <div id="loyalty_section" class="loyalty-section">
                                    <!-- Loyalty Points Banner/Card -->
                                    <div class="col-12 mb-3">
                                        <div
                                            class="d-flex align-items-center rounded-3 bg-primary position-relative overflow-hidden py-4">
                                            <div class="px-4 py-3">
                                                <h5 class="mb-3 text-white">{{ __('landingpage.available_loyalty_points') }}
                                                </h5>
                                                <div class="mt-1 d-flex align-items-center">
                                                    <img src="{{ asset('images/loylity_coin.png') }}" alt="Loyalty Coin"
                                                        width="50" height="50" />
                                                    <h3 class="text-white">
                                                        {{ $user->loyalty_points ?? 0 }}</h3>
                                                </div>
                                            </div>
                                            <img src="{{ asset('images/loylity_big_coin.png') }}" alt="coin"
                                                class="position-absolute end-0 top-0 object-fit-contain z-1"
                                                style="right:-10px; bottom:-16px; width: 300px; height: 300px;" />
                                        </div>
                                    </div>
                                    <loyalty-history
                                        link="{{ route('loyalty.history', ['user_id' => $user->id]) }}"></loyalty-history>
                                </div>

                                <!-- Referral Section -->
                                <div id="referral_section" class="loyalty-section d-none">
                                    <!-- Share Your Promo Code Section -->
                                    <div class="col-12 mb-5">
                                        <div class="bg-primary-subtle rounded-3 p-4">
                                            <div class="row">
                                                <div class="col-md-7 mb-3 mb-md-0">
                                                    <h5 class="mb-3">
                                                        {{ __('landingpage.share_promo_code_message', ['referrer_points' => $referrer_points, 'referred_points' => $referred_user_points]) }}
                                                    </h5>
                                                    <p class="text-muted mb-3">
                                                        {{ __('landingpage.copy_your_code_share') }}
                                                    </p>
                                                </div>
                                                <div class="col-md-5 d-flex align-items-center flex-wrap gap-3">
                                                    <span
                                                        class="text-muted me-2">{{ __('landingpage.your_referral_code') }}</span>
                                                    <div class="input-group flex-nowrap form-control p-0"
                                                        style="max-width: 250px;">
                                                        <input type="text" class="form-control border-0"
                                                            id="referralCode"
                                                            value="{{ optional($user)->referral_code ?? 'XXXXXXXX' }}"
                                                            readonly>
                                                        <button class="btn border-0" type="button" id="copyCodeBtn"
                                                            title="Copy">
                                                            <i class="far fa-copy text-primary"></i>
                                                        </button>
                                                    </div>
                                                    <button class="btn btn-primary" id="shareBtn">
                                                        {{ __('landingpage.share') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- How It Works Section -->
                                    <div class="col-12 pb-3 mb-5">
                                        <h5 class="mb-4">{{ __('landingpage.how_it_works') }}</h5>
                                        <div class="row g-4 mb-3">
                                            <!-- Step 1 -->
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="rounded-circle border border-3 border-warning d-flex align-items-center justify-content-center"
                                                            style="width: 50px; height: 50px; background-color: #fff;">
                                                            <span class="fw-bold text-warning">1</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-2">{{ __('landingpage.share_your_code') }}</h6>
                                                        <p class="text-muted small mb-0">
                                                            {{ __('landingpage.share_your_code_desc') }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Step 2 -->
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="rounded-circle border border-3 border-success d-flex align-items-center justify-content-center"
                                                            style="width: 50px; height: 50px; background-color: #fff;">
                                                            <span class="fw-bold text-success">2</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-2">{{ __('landingpage.earn_points') }}</h6>
                                                        <p class="text-muted small mb-0">
                                                            {{ __('landingpage.earn_points_desc') }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Step 3 -->
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="rounded-circle border border-3 border-primary d-flex align-items-center justify-content-center"
                                                            style="width: 50px; height: 50px; background-color: #fff;">
                                                            <span class="fw-bold text-primary">3</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-2">{{ __('landingpage.redeem_rewards') }}</h6>
                                                        <p class="text-muted small mb-0">
                                                            {{ __('landingpage.redeem_rewards_desc', ['points' => $referrer_points]) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const buttons = document.querySelectorAll(".loyalty-btn");

                function switchTab(type) {
                    // Reset buttons
                    buttons.forEach(btn => {
                        btn.classList.remove("btn-primary");
                        btn.classList.add("btn-primary-subtle");
                    });

                    // Highlight active
                    const activeButton = document.querySelector(`[data-type="${type}"]`);
                    if (activeButton) {
                        activeButton.classList.remove("btn-primary-subtle");
                        activeButton.classList.add("btn-primary");
                    }

                    // Hide all sections
                    document.querySelectorAll(".loyalty-section").forEach(el => {
                        el.classList.add("d-none");
                    });

                    // Show selected section
                    document.getElementById(`${type}_section`).classList.remove("d-none");
                }

                // Click handlers
                buttons.forEach(btn => {
                    btn.addEventListener("click", function() {
                        const type = this.getAttribute("data-type");
                        switchTab(type);
                    });
                });

                // Default view → Earn
                switchTab("loyalty");

                // Copy referral code functionality
                const copyCodeBtn = document.getElementById("copyCodeBtn");
                const referralCodeInput = document.getElementById("referralCode");

                if (copyCodeBtn && referralCodeInput) {
                    copyCodeBtn.addEventListener("click", function() {
                        referralCodeInput.select();
                        referralCodeInput.setSelectionRange(0, 99999); // For mobile devices

                        try {
                            navigator.clipboard.writeText(referralCodeInput.value).then(function() {
                                // Show feedback
                                const originalHTML = copyCodeBtn.innerHTML;
                                copyCodeBtn.innerHTML = '<i class="fas fa-check text-primary"></i>';

                                setTimeout(function() {
                                    copyCodeBtn.innerHTML = originalHTML;
                                    copyCodeBtn.classList.remove("btn-success");
                                }, 2000);
                            });
                        } catch (err) {
                            // Fallback for older browsers
                            document.execCommand("copy");
                            // alert("Code copied to clipboard!");
                        }
                    });
                }

                // Share button functionality
                const shareBtn = document.getElementById("shareBtn");
                if (shareBtn && referralCodeInput) {
                    shareBtn.addEventListener("click", function() {
                        const referralCode = referralCodeInput.value;
                        const siteUrl = "{{ env('APP_URL') }}";
                        const referralUrl = `${siteUrl}/register-page?ref=${encodeURIComponent(referralCode)}`;
                        const shareText =
                            `Use my referral code ${referralCode} and earn {{ $referred_user_points }} Loyalty Points! Join here: ${referralUrl}`;

                        navigator.share({
                            title: "Join and earn loyalty points",
                            text: shareText,
                            url: referralUrl
                        }).catch(err => console.log("Error sharing", err));
                    });
                }
            });
        </script>
    @endsection
