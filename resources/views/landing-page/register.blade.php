@extends('landing-page.layouts.headerremove')


@section('content')

    <div>
        <div class="container-fluid px-lg-0 py-lg-0 pb-5">
            <div class="row min-vh-100 g-lg-0">
                <div class="col-xl-8 col-lg-7 mh-100">
                    <div class="py-5 h-100 d-flex flex-column justify-content-center">
                        <div class="row justify-content-center">
                            <div class="col-xl-8 col-lg-10">
                                <div class="text-center">

                                    @if ($sectionData && isset($sectionData['login_register']) && $sectionData['login_register'] == 1)
                                        <div class="mb-5">
                                            <h3 class="text-capitalize mb-3">
                                                {{ $sectionData['title'] }}
                                            </h3>
                                            <p class="m-0">
                                                {{ $sectionData['description'] }}

                                            </p>
                                        </div>
                                        @php
                                            $loginregisterimage = Spatie\MediaLibrary\MediaCollections\Models\Media::where(
                                                'collection_name',
                                                'login_register_image',
                                            )->first();
                                        @endphp
                                        @if ($loginregisterimage)
                                            <img src="{{ url('storage/' . $loginregisterimage->id . '/' . $loginregisterimage->file_name) }}"
                                                alt="video-popup" class="img-fluid w-100 rounded">
                                        @else
                                            <img src="{{ asset('landing-images/general/login.webp ') }}" class="img-fluid"
                                                alt="log-in" />
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 mh-100">
                    <div class="py-5 px-3 bg-light h-100 d-flex flex-column justify-content-center">
                        <div class="row justify-content-center">
                            <div class="col-xl-8 col-lg-10">
                                <div class="authontication-forms">
                                    <div class="text-center mb-5 pb-lg-5">
                                        <h4 class="text-capitalize">{{ __('auth.signup') }}</h4>
                                    </div>
                                    <div class="iq-login-form">
                                        <div class="alert alert-danger d-none" role="alert" id="error">
                                        </div>
                                        <form id="registerForm" method="POST" data-toggle="validator">
                                            {{ csrf_field() }}
                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('auth.first_name') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="first_name" name="first_name" class="form-control"
                                                    placeholder="{{ __('placeholder.first_name') }}" aria-label="firstname"
                                                    aria-describedby="basic-addon1" required>
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>


                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('auth.last_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" id="last_name" name="last_name" class="form-control"
                                                    placeholder="{{ __('placeholder.last_name') }}" aria-label="lastname"
                                                    aria-describedby="basic-addon2" required>
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>


                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('landingpage.user_name') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="username" name="username" class="form-control"
                                                    placeholder="{{ __('placeholder.user_name') }}" aria-label="Username"
                                                    aria-describedby="basic-addon3" required>
                                                <small id="username-error" class="help-block with-errors text-danger"
                                                    style="display: none;"></small>
                                            </div>

                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('landingpage.email') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="email" id="email" name="email" class="form-control"
                                                    placeholder="{{ __('placeholder.email') }}" aria-label="Email Address"
                                                    aria-describedby="basic-addon4" required>
                                                <small id="email-error" class="help-block with-errors text-danger"
                                                    style="display: none;"></small>
                                            </div>


                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('landingpage.your') }} {{ __('auth.login_password') }} <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" id="password" name="password"
                                                        class="form-control"
                                                        placeholder="{{ __('placeholder.login_password') }}"
                                                        aria-label="Password" aria-describedby="togglePasswordIcon"
                                                        minlength="8" maxlength="12" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"
                                                            onclick="togglePassword('password', 'togglePasswordIcon')">
                                                            <i class="fa fa-eye-slash" id="togglePasswordIcon"
                                                                aria-hidden="true"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <small id="password-error" class="help-block with-errors text-danger"
                                                    style="display: none;"></small>
                                            </div>

                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('auth.confirm_password') }}<span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" id="password_confirmation"
                                                        name="password_confirmation" class="form-control"
                                                        placeholder="{{ __('placeholder.login_password') }}"
                                                        aria-label="Password" aria-describedby="toggleConfirmPasswordIcon"
                                                        minlength="8" maxlength="12" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"
                                                            onclick="togglePassword('password_confirmation', 'toggleConfirmPasswordIcon')">
                                                            <i class="fa fa-eye-slash" id="toggleConfirmPasswordIcon"
                                                                aria-hidden="true"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <small class="help-block text-danger d-none"></small>
                                            </div>

                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>{{ __('auth.contact_number') }}<span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" id="contact_number" name="contact_number"
                                                        class="form-control"
                                                        placeholder="{{ __('placeholder.contact_number') }}"
                                                        aria-label="cnumber" aria-describedby="basic-addon6" required>
                                                </div>
                                                <small id="contact_number-error"
                                                    class="help-block with-errors text-danger"
                                                    style="display:none;"></small>
                                            </div>

                                            <input type="hidden" name="register" value="user_register">

                                            <div class="form-group icon-right mb-5 custom-form-field">
                                                <label>Do you have a referral code? <span
                                                        class="text-muted">(Optional)</span></label>
                                                <input type="text" id="referral_code" name="referral_code"
                                                    class="form-control" placeholder="Referral Code"
                                                    aria-label="Referral Code">
                                                <small id="referral-message" class="help-block mt-2"></small>
                                            </div>


                                            <div class="login-submit position-relative">
                                                <button id="submitButton" class="btn btn-primary w-100 text-capitalize"
                                                    type="submit">{{ __('messages.register') }}</button>
                                                <button type="submit" id="loader"
                                                    class="btn btn-primary d-none w-100 text-capitalize">
                                                    <span class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span> {{ __('messages.loading') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="text-center mt-4 text-signup">
                                        <label class="m-0 text-capitalize">{{ __('auth.already_have_account') }}</label>
                                        <a href="{{ route('user.login') }}"
                                            class="btn-link align-baseline ms-1">{{ __('auth.sign_in') }}</a>
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

@endsection

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://www.gstatic.com/firebasejs/6.0.2/firebase.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script src="https://unpkg.com/libphonenumber-js@1.10.25/bundle/libphonenumber-js.min.js"></script>
<script src="{{ asset('js/phone-input-handler.js') }}"></script>

<script>
    $(document).ready(function() {
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        // ✅ Auto-populate referral code from URL
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');
        if (refCode) {
            $('#referral_code').val(refCode).trigger('input');
        }

        // ✅ Referral code validation with debounce
        let referralTimer = null;
        $('#referral_code').on('input', function() {
            clearTimeout(referralTimer);
            const value = $(this).val().trim();
            console.log(value);
            const messageBox = $('#referral-message');

            if (value === '') {
                messageBox.text('').removeClass('text-success text-danger');
                return;
            }

            referralTimer = setTimeout(function() {
                $.ajax({
                    method: 'POST',
                    url: baseUrl + '/api/check-referral', // <-- your API endpoint
                    data: {
                        _token: csrfToken,
                        referral_code: value
                    },
                    success: function(response) {
                        if (response.status === true) {
                            messageBox
                                .text(response.message)
                                .removeClass('text-danger')
                                .addClass('text-success');
                        } else {
                            messageBox
                                .text(response.message)
                                .removeClass('text-success')
                                .addClass('text-danger');
                        }
                    },
                    error: function() {
                        messageBox
                            .text(
                                'Error validating referral code. Please try again.')
                            .removeClass('text-success')
                            .addClass('text-danger');
                    }
                });
            }, 400); // debounce delay
        });
    });

    $(document).ready(function() {
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        let isUsernameValid = true;
        let isEmailValid = true;
        let isContactNumberValid = true;

        function validateInput(inputSelector, fieldName, errorSelector) {
            let debounceTimer = null;

            $(inputSelector).on('input', function() {
                let value = $(this).val().trim();
                clearTimeout(debounceTimer);

                if (fieldName === 'contact_number' && value !== '') {
                    const selectedCountry = iti.getSelectedCountryData();
                    const dialCode = selectedCountry?.dialCode || '';
                    value = `+${dialCode}${value}`;
                }

                debounceTimer = setTimeout(function() {
                    if (value !== '') {
                        $.ajax({
                            method: 'POST',
                            url: baseUrl + '/api/check-field', // ✅ use general endpoint
                            data: {
                                _token: csrfToken,
                                field: fieldName,
                                value: value
                            },
                            success: function(response) {
                                const hasError = response.status === 'error';
                                if (hasError) {
                                    $(errorSelector).text(
                                        `${fieldName.replace('_', ' ')} already exists.`
                                    ).show();
                                } else {
                                    $(errorSelector).text('').hide();
                                }

                                // Update validation state
                                if (fieldName === 'username') isUsernameValid = !
                                    hasError;
                                if (fieldName === 'email') isEmailValid = !hasError;
                                if (fieldName === 'contact_number')
                                    isContactNumberValid = !hasError;
                            },
                            error: function() {
                                $(errorSelector).text(
                                    `Error checking ${fieldName.replace('_', ' ')}.`
                                ).show();

                                if (fieldName === 'username') isUsernameValid =
                                    false;
                                if (fieldName === 'email') isEmailValid = false;
                                if (fieldName === 'contact_number')
                                    isContactNumberValid = false;
                            }
                        });
                    } else {
                        $(errorSelector).text('').hide();

                        // Reset to true if empty
                        if (fieldName === 'username') isUsernameValid = true;
                        if (fieldName === 'email') isEmailValid = true;
                        if (fieldName === 'contact_number') isContactNumberValid = true;
                    }
                }, 300);
            });
        }

        validateInput('#username', 'username', '#username-error');
        validateInput('#email', 'email', '#email-error');
        validateInput('#contact_number', 'contact_number', '#contact_number-error');

        // Initialize phone input handler
        if (typeof PhoneInputHandler !== 'undefined' && typeof window.intlTelInput !== 'undefined') {
            PhoneInputHandler.init({
                inputSelector: '#contact_number',
                errorSelector: '#contact_number-error',
                formSelector: '#registerForm',
                initialCountry: 'in'
            });
        }

        $('#registerForm').submit(function(e) {

            e.preventDefault();
            var password = $('#password').val();
            var confirmPassword = $('#password_confirmation').val();
            const successMessage = "Register successful!";

            // Reset error messages
            $('#error').addClass('d-none').text('');
            $('#password-error').hide().text('');

            var passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/;
            if (!password || password.length < 8 || password.length > 12) {
                $('#password-error').text($('#password-error').data('msg-length') || 'Password must be 8 to 12 characters long.').show();
                return;
            }
            if (!passwordPattern.test(password)) {
                $('#password-error').text($('#password-error').data('msg-pattern') || 'Password must have at least 1 uppercase, 1 lowercase and 1 special character.').show();
                return;
            }

            if (password !== confirmPassword) {
                // Display error if passwords do not match
                $('#error').text('Password & Confirm Password Do not Match.').removeClass(
                    'd-none'); // Show the error message
                return; // Stop further execution
            }

            if (!isUsernameValid || !isEmailValid || !isContactNumberValid) {

                return;
            }
            // Disable the submit button and show the loader
            $('#submitButton').addClass('d-none');
            $('#loader').removeClass('d-none');
            var formData = $(this).serialize();

            $.ajax({
                method: 'post',
                url: baseUrl + '/api/register',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.data) {
                   
                        
                        window.location.href = baseUrl + '/login-page';
                    }
                },
                error: function(error) {

                    $('#error').removeClass('d-none')

                    $('#error').text(error.responseJSON.message)
                    $('#loader').addClass('d-none');
                    $('#submitButton').removeClass('d-none');

                },
                complete: function() {
                    // Make sure the loader is hidden and button is shown after request completes
                    $('#loader').addClass('d-none');
                    $('#submitButton').removeClass('d-none');
                }
            });
        });

        $('#email').on('input', function() {
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(function() {
                const email = $('#email').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (email === '') {
                    $('#email-error').hide();
                } else if (!emailRegex.test(email)) {
                    $('#email-error').text('Invalid Email format').show();
                } else {
                    $('#email-error').hide();
                }
            }, 300);
        });


    });

    function togglePassword(passwordInputId, iconId) {
        const passwordInput = document.getElementById(passwordInputId);
        const icon = document.getElementById(iconId);
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        icon.className = type === 'password' ? 'fa fa-eye-slash' : 'fa fa-eye';

    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const errorMsg = document.getElementById('password-error');
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/;

        function validatePassword(password) {
            if (!password || password.length < 8 || password.length > 12) {
                return '{{ __("messages.password_length_8_12") }}';
            }
            if (!passwordPattern.test(password)) {
                return '{{ __("messages.password_must_contain") }}';
            }
            return '';
        }

        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value.trim();
            const msg = validatePassword(password);
            errorMsg.textContent = msg;
            errorMsg.style.display = msg ? 'block' : 'none';
        });
    });
</script>
