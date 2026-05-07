/**
 * Unified Phone Input Handler
 * Handles international phone number input validation and formatting
 * across all forms in the application using intl-tel-input library
 * 
 * Usage:
 * PhoneInputHandler.init({
 *     inputSelector: '#contact_number',
 *     countryCodeSelector: '#country_code',
 *     errorSelector: '#contact_number-error',
 *     formSelector: '#provider',
 *     initialCountry: 'in',
 *     allowUniquenessCheck: true,
 *     uniquenessCheckUrl: '/api/check-phone',
 *     uniquenessDebounceMs: 500
 * });
 */

const PhoneInputHandler = {
    /**
     * Initialize phone input handler for a form
     * @param {Object} options - Configuration options
     */
    init: function(options) {
        // Default options
        const defaults = {
            inputSelector: '#contact_number',
            countryCodeSelector: '#country_code',
            errorSelector: '#contact_number-error',
            formSelector: 'form',
            initialCountry: 'in',
            allowUniquenessCheck: false,
            uniquenessCheckUrl: null,
            uniquenessDebounceMs: 500,
            maxDigits: 15
        };

        const config = { ...defaults, ...options };
        const phoneInput = document.querySelector(config.inputSelector);
        
        if (!phoneInput) {
            console.warn(`Phone input not found: ${config.inputSelector}`);
            return false;
        }

        // Check if intl-tel-input library is available
        if (typeof window.intlTelInput !== 'function') {
            console.error('intl-tel-input library is not loaded. Please ensure the intl-tel-input scripts are included.');
            return false;
        }

        const countryCodeInput = document.querySelector(config.countryCodeSelector);
        const errorElement = document.querySelector(config.errorSelector);
        const formElement = document.querySelector(config.formSelector);

        // Initialize intl-tel-input
        let iti;
        try {
            iti = window.intlTelInput(phoneInput, {
                initialCountry: config.initialCountry,
                separateDialCode: true,
                utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js',
                customContainer: 'w-100',
                formatOnDisplay: false
            });
        } catch (e) {
            console.error('Failed to initialize intl-tel-input:', e);
            return false;
        }

        // Prefill with existing number (for edit mode)
        if (phoneInput.value && phoneInput.value.trim()) {
            try {
                iti.setNumber(phoneInput.value);
            } catch (e) {
                console.warn('Could not parse phone number:', e);
            }
        }

        // Store initial country code
        this.updateCountryCode(iti, countryCodeInput);

        // ===== EVENT HANDLERS =====

        // Country change event
        phoneInput.addEventListener('countrychange', function() {
            PhoneInputHandler.updateCountryCode(iti, countryCodeInput);
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }
        });

        // Keypress: only allow digits
        phoneInput.addEventListener('keypress', function(e) {
            const charCode = e.which || e.keyCode;
            // Allow digits (48-57), + (43), and space (32)
            if (charCode === 43 || charCode === 32 || (charCode >= 48 && charCode <= 57)) {
                return true;
            }
            e.preventDefault();
            return false;
        });

        // Paste: strip non-numeric characters
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pastedText.replace(/\D/g, '');
            
            const cursorPos = this.selectionStart;
            const textBefore = this.value.substring(0, cursorPos);
            const textAfter = this.value.substring(this.selectionEnd);
            
            this.value = textBefore + numbersOnly + textAfter;
            this.selectionStart = this.selectionEnd = cursorPos + numbersOnly.length;
            
            this.dispatchEvent(new Event('input'));
        });

        // Input: enforce digit limit and keep only numbers
        phoneInput.addEventListener('input', function(e) {
            const numbersOnly = this.value.replace(/\D/g, '');
            
            if (numbersOnly.length > config.maxDigits) {
                const trimmed = numbersOnly.substring(0, config.maxDigits);
                this.value = trimmed;
                if (errorElement) {
                    errorElement.textContent = `Phone number cannot exceed ${config.maxDigits} digits`;
                    errorElement.style.display = 'block';
                }
            } else {
                if (errorElement && errorElement.textContent.includes('cannot exceed')) {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
                this.value = numbersOnly;
            }
        });

        // Blur: validate phone number (but don't format)
        phoneInput.addEventListener('blur', function() {
            if (this.value.trim()) {
                if (!iti.isValidNumber()) {
                    if (errorElement) {
                        errorElement.textContent = 'Invalid phone number for selected country';
                        errorElement.style.display = 'block';
                    }
                } else {
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                    
                    // Optionally check uniqueness
                    if (config.allowUniquenessCheck && config.uniquenessCheckUrl) {
                        PhoneInputHandler.debounceUniquenessCheck(
                            phoneInput,
                            iti,
                            config.uniquenessCheckUrl,
                            config.uniquenessDebounceMs,
                            errorElement
                        );
                    }
                }
            }
        });

        // Form submit: final validation and format to E164
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                if (!iti.isValidNumber()) {
                    e.preventDefault();
                    if (errorElement) {
                        errorElement.textContent = 'Please enter a valid phone number';
                        errorElement.style.display = 'block';
                    }
                    return false;
                }

                // Format to E164 and extract components
                const fullNumber = iti.getNumber(intlTelInputUtils.numberFormat.E164);
                const dialCode = iti.getSelectedCountryData().dialCode;
                const nationalNumber = fullNumber.replace('+' + dialCode, '');
                
                // Store formatted number in input (E164: +CC NUMBER)
                phoneInput.value = fullNumber;
                
                // Store country code separately if field exists
                if (countryCodeInput) {
                    countryCodeInput.value = dialCode;
                }

                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
                
                return true;
            });
        }
        
        return true; // Successfully initialized
    },

    /**
     * Update country code in hidden field
     */
    updateCountryCode: function(iti, countryCodeInput) {
        if (countryCodeInput) {
            const countryData = iti.getSelectedCountryData();
            if (countryData) {
                countryCodeInput.value = countryData.dialCode;
            }
        }
    },

    /**
     * Debounced uniqueness check for phone numbers
     */
    debounceUniquenessCheck: function(phoneInput, iti, url, delayMs, errorElement) {
        if (!PhoneInputHandler.uniquenessTimers) {
            PhoneInputHandler.uniquenessTimers = {};
        }

        const fieldId = phoneInput.id || 'contact_number';
        clearTimeout(PhoneInputHandler.uniquenessTimers[fieldId]);

        PhoneInputHandler.uniquenessTimers[fieldId] = setTimeout(() => {
            const fullNumber = iti.getNumber(intlTelInputUtils.numberFormat.E164);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ phone_number: fullNumber })
            })
            .then(response => response.json())
            .then(data => {
                if (data.available === false) {
                    if (errorElement) {
                        errorElement.textContent = data.message || 'This phone number is already in use';
                        errorElement.style.display = 'block';
                    }
                } else {
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                }
            })
            .catch(err => {
                console.warn('Error checking phone uniqueness:', err);
            });
        }, delayMs);
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhoneInputHandler;
}
