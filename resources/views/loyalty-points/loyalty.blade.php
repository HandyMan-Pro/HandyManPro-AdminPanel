<x-master-layout>
    <?php $auth_user = authSession(); ?>

    <head>
        {{-- Local JS only if you already have them in your mix, else keep temporarily --}}
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5>{{ __('messages.manage_loyalty_points') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 1: Forms --}}
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between gy-3">
                <div class="d-flex col-12 col-md-6 gap-3">
                    <button class="btn language-btn loyalty-btn" data-type="earn">{{ __('messages.earn_rule') }}</button>
                    <button class="btn language-btn loyalty-btn" data-type="redeem">{{ __('messages.redeem_rule') }}</button>
                    <button class="btn language-btn loyalty-btn" data-type="referral">{{ __('messages.referral_rule') }}</button>
                </div>

                <!-- Earn Form -->
                <div id="earn_form" class="loyalty-form">
                    @include('loyalty-points.earn_rule_form')
                </div>

                <!-- Redeem Form -->
                <div id="redeem_form" class="loyalty-form d-none">
                    @include('loyalty-points.redeem_rule_form')
                </div>

                <!-- Referral Form -->
                <div id="referral_form" class="loyalty-form d-none">
                    @include('loyalty-points.referral_rule_form')
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Index Tables --}}
    <div class="card">
        <div class="card-body">

            <!-- Earn Table -->
            <div id="earn_index" class="loyalty-index">
                @include('loyalty-points.earn_rule_index')
                @stack('earn_rule_scripts')
            </div>

            <!-- Redeem Table -->
            <div id="redeem_index" class="loyalty-index d-none">
                @include('loyalty-points.redeem_rule_index')
                @stack('redeem_rule_scripts')
            </div>

            <!-- Referral Table -->
            <div id="referral_index" class="loyalty-index d-none">
                @include('loyalty-points.referral_rule_index')
                @stack('referral_rule_scripts')
            </div>
        </div>
    </div>

    {{-- Tab Switching Script --}}
    <script>

        // tooltip
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        trigger: 'hover',
                        animation: true,
                        delay: {
                            show: 200,
                            hide: 100
                        }
                    });
                });
        });


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
                document.querySelectorAll(".loyalty-form, .loyalty-index").forEach(el => {
                    el.classList.add("d-none");
                });

                // Show selected form + table
                document.getElementById(`${type}_form`).classList.remove("d-none");
                document.getElementById(`${type}_index`).classList.remove("d-none");
            }

            // Click handlers
            buttons.forEach(btn => {
                btn.addEventListener("click", function() {
                    const type = this.getAttribute("data-type");
                    switchTab(type);
                });
            });

            // Default view → Earn
            switchTab("earn");
        });

        // Scroll to top on edit click
        $(document).on('click', '.edit-earn-rule, .edit-redeem-rule, .edit-referral-rule', function(e) {
            e.preventDefault();

            $('html, body').animate({
                scrollTop: 0
            }, 100); // adjust speed if needed
        });
    </script>
</x-master-layout>
