/**
 * Foxentry Integration Plugin - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Hlavní objekt pro Foxentry funkcionalita
    var FoxentryValidator = {
        
        // Cache pro uložení výsledků
        cache: {},
        
        // Timeout pro debounce
        timeouts: {},
        
        // Inicializace
        init: function() {
            this.bindEvents();
        },
        
        // Navázání event listenerů
        bindEvents: function() {
            var self = this;
            
            // Event listener pro všechny Foxentry validátory
            $(document).on('input keyup paste', '.foxentry-validator', function() {
                var $input = $(this);
                var inputId = $input.attr('id');
                var value = $input.val().trim();
                var type = $input.data('type');
                
                // Vyčištění předchozího timeoutu
                if (self.timeouts[inputId]) {
                    clearTimeout(self.timeouts[inputId]);
                }
                
                // Reset stylů pokud je pole prázdné
                if (value === '') {
                    self.resetValidation($input);
                    return;
                }
                
                // Debounce - validace až po 500ms od posledního zadání
                self.timeouts[inputId] = setTimeout(function() {
                    self.validateField($input, value, type);
                }, 500);
            });
            
            // Validace při focus out
            $(document).on('blur', '.foxentry-validator', function() {
                var $input = $(this);
                var value = $input.val().trim();
                var type = $input.data('type');
                
                if (value !== '') {
                    // Vyčistí timeout a validuje okamžitě
                    var inputId = $input.attr('id');
                    if (self.timeouts[inputId]) {
                        clearTimeout(self.timeouts[inputId]);
                    }
                    self.validateField($input, value, type);
                }
            });
        },
        
        // Validace pole
        validateField: function($input, value, type) {
            var self = this;
            var cacheKey = type + '_' + value;
            var $result = $('#' + $input.attr('id') + '_result');
            
            // Kontrola cache
            if (self.cache[cacheKey]) {
                self.showResult($input, $result, self.cache[cacheKey]);
                return;
            }
            
            // Základní kontrola formátu před API voláním
            if (!self.isBasicFormatValid(value, type)) {
                var errorMessage = self.getErrorMessage(type);
                self.showResult($input, $result, {
                    isValid: false,
                    message: errorMessage
                });
                return;
            }
            
            // Zobrazení loading stavu
            self.showLoading($input, $result);
            
            // AJAX volání
            $.ajax({
                url: foxentry_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'foxentry_validate',
                    value: value,
                    type: type,
                    nonce: foxentry_ajax.nonce
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        var result = self.parseApiResponse(response.data, type);
                        self.cache[cacheKey] = result;
                        self.showResult($input, $result, result);
                    } else {
                        self.showResult($input, $result, {
                            isValid: false,
                            message: foxentry_ajax.messages.validation_error + ' ' + response.data
                        });
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = foxentry_ajax.messages.connection_error;
                    if (status === 'timeout') {
                        errorMsg = foxentry_ajax.messages.timeout_error;
                    }
                    self.showResult($input, $result, {
                        isValid: false,
                        message: errorMsg
                    });
                }
            });
        },
        
        // Základní validace formátu
        isBasicFormatValid: function(value, type) {
            switch (type) {
                case 'email':
                    // Základní regex pro email
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                case 'phone':
                    // Obsahuje alespoň nějaká čísla
                    return /\d/.test(value) && value.length >= 6;
                case 'address':
                    // Délka alespoň 5 znaků
                    return value.length >= 5;
                default:
                    return true;
            }
        },
        
        // Zobrazení loading stavu
        showLoading: function($input, $result) {
            $input.removeClass('valid invalid').addClass('validating');
            $result.removeClass('valid invalid').addClass('loading').text(foxentry_ajax.messages.validating);
        },
        
        // Reset validace
        resetValidation: function($input) {
            var $result = $('#' + $input.attr('id') + '_result');
            $input.removeClass('valid invalid validating');
            $result.removeClass('valid invalid loading').text('');
        },
        
        // Zobrazení výsledku
        showResult: function($input, $result, result) {
            $input.removeClass('validating');
            
            if (result.isValid) {
                $input.removeClass('invalid').addClass('valid');
                $result.removeClass('invalid loading').addClass('valid').text(result.message);
            } else {
                $input.removeClass('valid').addClass('invalid');
                $result.removeClass('valid loading').addClass('invalid').text(result.message);
            }
        },
        
        // Parsování odpovědi z API
        parseApiResponse: function(apiData, type) {
            // Toto bude záviset na struktuře odpovědi z Foxentry API
            // Zde je základní implementace, kterou můžete upravit podle skutečné API dokumentace
            
            switch (type) {
                case 'email':
                    if (apiData.valid === true) {
                        return {
                            isValid: true,
                            message: foxentry_ajax.messages.email_valid
                        };
                    } else {
                        return {
                            isValid: false,
                            message: apiData.message || foxentry_ajax.messages.email_invalid
                        };
                    }
                    
                case 'phone':
                    if (apiData.valid === true) {
                        var formattedPhone = apiData.formattedNumber || apiData.formatted || '';
                        return {
                            isValid: true,
                            message: foxentry_ajax.messages.phone_valid + (formattedPhone ? ': ' + formattedPhone : '')
                        };
                    } else {
                        return {
                            isValid: false,
                            message: apiData.message || foxentry_ajax.messages.phone_invalid
                        };
                    }
                    
                case 'address':
                    if (apiData.valid === true) {
                        var formattedAddress = apiData.formattedAddress || apiData.formatted || '';
                        return {
                            isValid: true,
                            message: foxentry_ajax.messages.address_valid + (formattedAddress ? ': ' + formattedAddress : '')
                        };
                    } else {
                        return {
                            isValid: false,
                            message: apiData.message || foxentry_ajax.messages.address_invalid
                        };
                    }
                    
                default:
                    return {
                        isValid: false,
                        message: foxentry_ajax.messages.unknown_type
                    };
            }
        },
        
        // Chybové zprávy pro základní validaci
        getErrorMessage: function(type) {
            switch (type) {
                case 'email':
                    return foxentry_ajax.messages.email_format_error;
                case 'phone':
                    return foxentry_ajax.messages.phone_format_error;
                case 'address':
                    return foxentry_ajax.messages.address_format_error;
                default:
                    return foxentry_ajax.messages.invalid_format;
            }
        }
    };
    
    // Inicializace při načtení DOM
    $(document).ready(function() {
        FoxentryValidator.init();
    });
    
    // Export pro globální použití
    window.FoxentryValidator = FoxentryValidator;
    
})(jQuery);
