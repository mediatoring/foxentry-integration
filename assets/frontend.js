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
                console.log('Foxentry: Detekován input na validátoru');
                var $input = $(this);
                var inputId = $input.attr('id');
                var value = $input.val().trim();
                var type = $input.data('type');
                var $form = $input.closest('.foxentry-form');
                var $submitBtn = $form.find('.foxentry-submit-btn');
                
                console.log('Foxentry: Input hodnota:', value, 'Typ:', type);
                
                // Vyčištění předchozího timeoutu
                if (self.timeouts[inputId]) {
                    clearTimeout(self.timeouts[inputId]);
                }
                
                // Reset stylů pokud je pole prázdné
                if (value === '') {
                    self.resetValidation($input);
                    self.updateSubmitButton($submitBtn, false);
                    return;
                }
                
                // Kontrola, zda je hodnota dostatečně dlouhá pro validaci
                if (!self.isValueComplete(value, type)) {
                    self.updateSubmitButton($submitBtn, false);
                    return;
                }
                
                // Debounce - validace až po 800ms od posledního zadání (delší pro lepší UX)
                self.timeouts[inputId] = setTimeout(function() {
                    self.validateField($input, value, type);
                }, 800);
            });
            
            // Validace při focus out
            $(document).on('blur', '.foxentry-validator', function() {
                var $input = $(this);
                var value = $input.val().trim();
                var type = $input.data('type');
                var $form = $input.closest('.foxentry-form');
                var $submitBtn = $form.find('.foxentry-submit-btn');
                
                if (value !== '' && self.isValueComplete(value, type)) {
                    // Vyčistí timeout a validuje okamžitě
                    var inputId = $input.attr('id');
                    if (self.timeouts[inputId]) {
                        clearTimeout(self.timeouts[inputId]);
                    }
                    self.validateField($input, value, type);
                } else {
                    self.updateSubmitButton($submitBtn, false);
                }
            });
            
            // Submit formuláře
            $(document).on('submit', '.foxentry-form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $input = $form.find('.foxentry-validator');
                var $submitBtn = $form.find('.foxentry-submit-btn');
                var $status = $form.find('.foxentry-form-status');
                
                if ($submitBtn.prop('disabled')) {
                    return false;
                }
                
                self.handleFormSubmit($form, $input, $submitBtn, $status);
            });
        },
        
        // Validace pole
        validateField: function($input, value, type) {
            console.log('Foxentry: Začínám validaci pole:', value, 'Typ:', type);
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
            console.log('Foxentry: Odesílám AJAX požadavek pro validaci:', {value: value, type: type});
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
                    console.log('Foxentry: AJAX odpověď:', response);
                    if (response.success) {
                        console.log('Foxentry: API data:', response.data);
                        var result = self.parseApiResponse(response.data, type);
                        console.log('Foxentry: Parsed result:', result);
                        self.cache[cacheKey] = result;
                        self.showResult($input, $result, result);
                    } else {
                        console.log('Foxentry: API chyba:', response.data);
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
            var $form = $input.closest('.foxentry-form');
            var $submitBtn = $form.find('.foxentry-submit-btn');
            
            $input.removeClass('validating');
            
            if (result.isValid) {
                $input.removeClass('invalid').addClass('valid');
                $result.removeClass('invalid loading').addClass('valid').text(result.message);
                this.updateSubmitButton($submitBtn, true);
            } else {
                $input.removeClass('valid').addClass('invalid');
                $result.removeClass('valid loading').addClass('invalid').text(result.message);
                this.updateSubmitButton($submitBtn, false);
            }
        },
        
        // Kontrola, zda je hodnota dostatečně kompletní pro validaci
        isValueComplete: function(value, type) {
            console.log('Foxentry: Kontrola hodnoty:', value, 'Typ:', type);
            switch (type) {
                case 'email':
                    // Email musí obsahovat @ a alespoň 5 znaků
                    var result = value.length >= 5 && value.includes('@');
                    console.log('Foxentry: Email validace:', result);
                    return result;
                case 'phone':
                    // Telefon musí obsahovat alespoň 8 znaků a nějaká čísla
                    var result = value.length >= 8 && /\d/.test(value);
                    console.log('Foxentry: Telefon validace:', result, 'Délka:', value.length, 'Obsahuje čísla:', /\d/.test(value));
                    return result;
                case 'address':
                    // Adresa musí být alespoň 10 znaků dlouhá
                    var result = value.length >= 10;
                    console.log('Foxentry: Adresa validace:', result);
                    return result;
                default:
                    var result = value.length >= 3;
                    console.log('Foxentry: Výchozí validace:', result);
                    return result;
            }
        },
        
        // Aktualizace stavu submit tlačítka
        updateSubmitButton: function($submitBtn, isValid) {
            if (isValid) {
                $submitBtn.prop('disabled', false).removeClass('disabled');
            } else {
                $submitBtn.prop('disabled', true).addClass('disabled');
            }
        },
        
        // Zpracování odeslání formuláře
        handleFormSubmit: function($form, $input, $submitBtn, $status) {
            var self = this;
            var value = $input.val().trim();
            var type = $input.data('type');
            
            // Zobrazení loading stavu
            $submitBtn.prop('disabled', true).text(foxentry_ajax.messages.submitting || 'Odesílám...');
            $status.removeClass('success error').addClass('loading').text(foxentry_ajax.messages.processing || 'Zpracovávám...');
            
            // Simulace odeslání (můžete nahradit skutečným AJAX voláním)
            setTimeout(function() {
                if (value && self.isValueComplete(value, type)) {
                    $status.removeClass('loading').addClass('success').text(foxentry_ajax.messages.submitted || 'Formulář byl úspěšně odeslán!');
                    $submitBtn.text(foxentry_ajax.messages.submitted || 'Odesláno');
                } else {
                    $status.removeClass('loading').addClass('error').text(foxentry_ajax.messages.invalid_data || 'Neplatná data');
                    $submitBtn.prop('disabled', false).text(foxentry_ajax.messages.submit || 'Odeslat');
                }
            }, 1500);
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
        console.log('Foxentry: Inicializace validátoru...');
        FoxentryValidator.init();
        console.log('Foxentry: Validátor inicializován');
        
        // Debug: Zkontroluj počet validátorů
        setTimeout(function() {
            var validators = $('.foxentry-validator').length;
            console.log('Foxentry: Nalezeno ' + validators + ' validátorů');
            
            // Debug: Zobraz všechny validátory
            $('.foxentry-validator').each(function(index) {
                console.log('Foxentry: Validátor ' + (index + 1) + ':', {
                    name: $(this).attr('name'),
                    id: $(this).attr('id'),
                    type: $(this).attr('data-type'),
                    class: $(this).attr('class')
                });
            });
        }, 1000);
        
        // Debug: Zkontroluj po 3 sekundách (pro později načtené elementy)
        setTimeout(function() {
            var validators = $('.foxentry-validator').length;
            console.log('Foxentry: Po 3 sekundách nalezeno ' + validators + ' validátorů');
        }, 3000);
    });
    
    // Export pro globální použití
    window.FoxentryValidator = FoxentryValidator;
    
})(jQuery);
