/**
 * Foxentry Integration Plugin - Frontend JavaScript (Univerzální verze)
 * 
 * Tato verze je navržena tak, aby fungovala s jakýmkoli formulářem
 * (Contact Form 7, WPForms, Gravity Forms, Elementor Forms, atd.)
 * bez zasahování do jejich odesílací logiky.
 */

(function($) {
    'use strict';
    
    var FoxentryValidator = {
        
        cache: {},
        timeouts: {},
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            console.log('Foxentry: bindEvents - registruji event listenery');
            
            // Sledujeme změny v inputu s třídou .foxentry-validator
            $(document).on('input keyup paste', '.foxentry-validator', function() {
                console.log('Foxentry: Detekován input na validátoru');
                var $input = $(this);
                console.log('Foxentry: Input hodnota:', $input.val());
                self.handleFieldValidation($input);
            });
            
            // Také validujeme, když uživatel opustí pole (blur)
            $(document).on('blur', '.foxentry-validator', function() {
                var $input = $(this);
                // Vyčistí timeout a validuje okamžitě, pokud je co validovat
                var inputId = $input.attr('id') || $input.attr('name');
                if (self.timeouts[inputId]) {
                    clearTimeout(self.timeouts[inputId]);
                }
                self.handleFieldValidation($input, true); // true = validovat okamžitě
            });
        },

        handleFieldValidation: function($input, validateImmediately = false) {
            var self = this;
            var inputId = $input.attr('id') || $input.attr('name'); // Použijeme ID nebo NAME jako unikátní identifikátor
            var value = $input.val().trim();
            var type = $input.data('type');
            
            console.log('Foxentry: handleFieldValidation - inputId:', inputId, 'value:', value, 'type:', type);
            
            // Pokud je to našeptávání, spustíme autocomplete
            if (type && type.indexOf('_search') !== -1) {
                self.handleAutocomplete($input, value, type);
                return;
            }

            if (self.timeouts[inputId]) {
                clearTimeout(self.timeouts[inputId]);
            }
            
            if (value === '') {
                console.log('Foxentry: Pole je prázdné, resetuji validaci');
                self.resetValidation($input);
                return;
            }
            
            // Spouštíme validaci pouze přes Foxentry API - žádné vlastní kontroly
            console.log('Foxentry: Spouštím validaci přes Foxentry API');

            var delay = validateImmediately ? 0 : 800; // Okamžitá validace při 'blur', jinak s prodlevou

            self.timeouts[inputId] = setTimeout(function() {
                console.log('Foxentry: Spouštím validateField po timeoutu');
                self.validateField($input, value, type);
            }, delay);
        },
        
        validateField: function($input, value, type) {
            var self = this;
            var cacheKey = type + '_' + value;
            var $result = $input.siblings('.foxentry-result');
            
            console.log('Foxentry: validateField - cacheKey:', cacheKey, 'result div:', $result.length);
            console.log('Foxentry: validateField - input HTML:', $input[0].outerHTML);
            console.log('Foxentry: validateField - result div HTML:', $result[0] ? $result[0].outerHTML : 'NENALEZEN');
            
            // Pokud se nenajde result div, zkusíme ho najít jinak
            if ($result.length === 0) {
                console.log('Foxentry: Result div nenalezen jako sibling, hledám jinak');
                $result = $input.parent().find('.foxentry-result');
                console.log('Foxentry: Result div v parent:', $result.length);
                
                // Pokud se stále nenajde, vytvoříme ho
                if ($result.length === 0) {
                    console.log('Foxentry: Vytvářím result div');
                    $result = $('<div class="foxentry-result"></div>');
                    $input.after($result);
                }
            }
            
            if (self.cache[cacheKey]) {
                console.log('Foxentry: Používám cached výsledek');
                self.showResult($input, $result, self.cache[cacheKey]);
                return;
            }
            
            console.log('Foxentry: Zobrazuji loading stav');
            self.showLoading($input, $result);
            
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
                    console.log('Foxentry: AJAX success response:', response);
                    console.log('Foxentry: Response.success:', response.success);
                    console.log('Foxentry: Response.data:', response.data);
                    
                    if (response.success && response.data) {
                        console.log('Foxentry: Ukládám do cache a zobrazuji výsledek');
                        console.log('Foxentry: Data pro showResult:', response.data);
                        self.cache[cacheKey] = response.data;
                        self.showResult($input, $result, response.data);
                    } else {
                        console.log('Foxentry: Chyba v odpovědi, zobrazuji chybovou zprávu');
                        console.log('Foxentry: Error response data:', response.data);
                        self.showResult($input, $result, {
                            isValid: false,
                            message: foxentry_ajax.messages.validation_error + ' ' + (response.data || 'Unknown error')
                        });
                    }
                },
                error: function(xhr, status) {
                    console.log('Foxentry: AJAX error:', status, xhr);
                    var errorMsg = status === 'timeout' ? foxentry_ajax.messages.timeout_error : foxentry_ajax.messages.connection_error;
                    self.showResult($input, $result, {
                        isValid: false,
                        message: errorMsg
                    });
                }
            });
        },
        
        showLoading: function($input, $result) {
            $input.removeClass('valid invalid').addClass('validating');
            if ($result.length) {
                $result.removeClass('valid invalid').addClass('loading').text(foxentry_ajax.messages.validating);
            }
        },
        
        resetValidation: function($input) {
            var $result = $input.siblings('.foxentry-result');
            $input.removeClass('valid invalid validating');
            if ($result.length) {
                $result.removeClass('valid invalid loading').text('');
            }
        },
        
        showResult: function($input, $result, result) {
            console.log('Foxentry: showResult - input:', $input.attr('id'), 'result:', result);
            console.log('Foxentry: showResult - result div length:', $result.length);
            console.log('Foxentry: showResult - result div HTML před:', $result.html());
            
            $input.removeClass('validating');
            
            if (result.isValid) {
                console.log('Foxentry: showResult - platný výsledek');
                $input.removeClass('invalid').addClass('valid');
                if ($result.length) {
                    $result.removeClass('invalid loading').addClass('valid').text(result.message);
                    console.log('Foxentry: showResult - zobrazena zpráva:', result.message);
                    console.log('Foxentry: showResult - result div HTML po:', $result.html());
                } else {
                    console.log('Foxentry: showResult - CHYBA: result div nenalezen!');
                }
            } else {
                console.log('Foxentry: showResult - neplatný výsledek');
                $input.removeClass('valid').addClass('invalid');
                if ($result.length) {
                    $result.removeClass('valid loading').addClass('invalid').text(result.message);
                    console.log('Foxentry: showResult - zobrazena chybová zpráva:', result.message);
                    console.log('Foxentry: showResult - result div HTML po:', $result.html());
                } else {
                    console.log('Foxentry: showResult - CHYBA: result div nenalezen pro chybu!');
                }
            }
        },
        
    };
    
    $(document).ready(function() {
        console.log('Foxentry: Frontend script se spouští');
        console.log('Foxentry: jQuery verze:', $.fn.jquery);
        console.log('Foxentry: foxentry_ajax objekt:', typeof foxentry_ajax !== 'undefined' ? foxentry_ajax : 'NEDEFINOVÁNO');
        
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
                    class: $(this).attr('class'),
                    value: $(this).val()
                });
            });
            
            // Debug: Zkontroluj result divy
            var resultDivs = $('.foxentry-result');
            console.log('Foxentry: Nalezeno ' + resultDivs.length + ' result divů');
            resultDivs.each(function(index) {
                var div = $(this);
                console.log('Foxentry: Result div ' + (index + 1) + ':', {
                    html: div[0].outerHTML,
                    text: div.text(),
                    parent: div.parent()[0].outerHTML
                });
            });
        }, 1000);
    });
    
    // Funkce pro našeptávání
    FoxentryValidator.handleAutocomplete = function($input, value, type) {
        var self = this;
        var inputId = $input.attr('id') || $input.attr('name');
        var limit = $input.data('limit') || 10;
        
        // Pokud je hodnota příliš krátká, skryjeme návrhy
        if (value.length < 2) {
            self.hideSuggestions($input);
            return;
        }
        
        // Zrušíme předchozí timeout
        if (self.timeouts[inputId + '_autocomplete']) {
            clearTimeout(self.timeouts[inputId + '_autocomplete']);
        }
        
        // Spustíme našeptávání s debounce
        self.timeouts[inputId + '_autocomplete'] = setTimeout(function() {
            self.fetchSuggestions($input, value, type, limit);
        }, 300);
    };
    
    // Funkce pro načtení návrhů
    FoxentryValidator.fetchSuggestions = function($input, value, type, limit) {
        var self = this;
        var inputId = $input.attr('id') || $input.attr('name');
        
        $.post(foxentry_ajax.ajax_url, {
            action: 'foxentry_validate',
            value: value,
            type: type,
            limit: limit,
            nonce: foxentry_ajax.nonce
        }, function(response) {
            if (response.success && response.data.suggestions) {
                self.showSuggestions($input, response.data.suggestions);
            } else {
                self.hideSuggestions($input);
            }
        }).fail(function(xhr, status, error) {
            self.hideSuggestions($input);
        });
    };
    
    // Funkce pro zobrazení návrhů
    FoxentryValidator.showSuggestions = function($input, suggestions) {
        var $suggestions = $input.siblings('.foxentry-suggestions');
        if ($suggestions.length === 0) {
            $suggestions = $('<div class="foxentry-suggestions"></div>').insertAfter($input);
        }
        
        var html = '<ul class="foxentry-suggestions-list">';
        suggestions.forEach(function(suggestion) {
            html += '<li class="foxentry-suggestion-item" data-value="' + suggestion.value + '">';
            html += '<span class="suggestion-text">' + suggestion.text + '</span>';
            if (suggestion.description) {
                html += '<span class="suggestion-description">' + suggestion.description + '</span>';
            }
            html += '</li>';
        });
        html += '</ul>';
        
        $suggestions.html(html).show();
        
        // Přidáme event listenery pro kliknutí na návrhy
        $suggestions.find('.foxentry-suggestion-item').on('click', function() {
            var value = $(this).data('value');
            $input.val(value);
            $suggestions.hide();
            $input.trigger('change');
        });
    };
    
    // Funkce pro skrytí návrhů
    FoxentryValidator.hideSuggestions = function($input) {
        var $suggestions = $input.siblings('.foxentry-suggestions');
        $suggestions.hide();
    };
    
    // Export pro globální použití
    window.FoxentryValidator = FoxentryValidator;
    
})(jQuery);