<?php
/**
 * Plugin Name: Foxentry Integration
 * Plugin URI: https://foxentry.com/?aff=or8eaq
 * Description: Moderní a bezpečný WordPress plugin pro integraci s Foxentry API pro validaci emailů, telefonů a adres.
 * Version: 1.0.0
 * Author: Webklient.cz
 * Author URI: https://www.webklient.cz
 * License: GPL v2 or later
 * Text Domain: foxentry-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 * Tags: foxentry, validation, address validation, validace adres, email validation, validace emailu, telefon validation, validace telefonu, name validation, validace jmena, company validation, validace firmy
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definice konstant
define('FOXENTRY_PLUGIN_VERSION', '1.0.0');
define('FOXENTRY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FOXENTRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FOXENTRY_AFFILIATE_CODE', 'or8eaq');

/**
 * Hlavní třída pluginu
 */
class FoxentryIntegration {
    
    private $api_key;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Načtení překladů
        load_plugin_textdomain('foxentry-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Načtení nastavení
        $this->api_key = get_option('foxentry_api_key', '');
        
        // Přidání admin menu
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Registrace shortcodů
        add_shortcode('foxentry_validator', array($this, 'validator_shortcode'));
        
        // AJAX akce
        add_action('wp_ajax_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_nopriv_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_foxentry_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_foxentry_scan_forms', array($this, 'ajax_scan_forms'));
        add_action('wp_ajax_foxentry_save_form_validation', array($this, 'ajax_save_form_validation'));
        add_action('wp_ajax_foxentry_remove_form_validation', array($this, 'ajax_remove_form_validation'));
        
        // Enqueue scripts a styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function activate() {
        // Vytvoření výchozích nastavení
        add_option('foxentry_api_key', '');
        add_option('foxentry_affiliate_code', FOXENTRY_AFFILIATE_CODE);
        add_option('foxentry_cache_duration', 3600);
    }
    
    public function deactivate() {
        // Vyčištění cache
        wp_cache_flush();
    }
    
    public function admin_menu() {
        add_options_page(
            __('Foxentry Nastavení', 'foxentry-integration'),
            __('Foxentry', 'foxentry-integration'),
            'manage_options',
            'foxentry-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('foxentry_settings', 'foxentry_api_key');
        register_setting('foxentry_settings', 'foxentry_cache_duration');
    }
    
    public function admin_page() {
        ?>
        <style>
        /* Foxentry Admin Styles */
        .foxentry-api-guide {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .guide-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .guide-header h2 {
            color: white;
            font-size: 28px;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .guide-steps {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .guide-step {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .guide-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .step-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .step-content {
            flex: 1;
            color: #333;
        }
        
        .step-content h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .step-image {
            margin: 15px 0;
            text-align: center;
            width: 33%;
            flex-shrink: 0;
        }
        
        .step-image img {
            width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 3px solid rgba(255, 255, 255, 0.4);
            transition: all 0.4s ease;
            object-fit: cover;
        }
        
        .critical-step {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: 2px solid #ff4757;
        }
        
        .critical-step .step-content {
            color: white;
        }
        
        .final-step {
            background: linear-gradient(135deg, #2ed573, #1e90ff);
            color: white;
        }
        
        .final-step .step-content {
            color: white;
        }
        
        .guide-controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .guide-toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .guide-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        .foxentry-form-scanner {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .scanner-controls {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .scanner-controls .button-primary {
            background: linear-gradient(135deg, #007cba, #005a87);
            border-color: #007cba;
        }
        
        .form-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        
        .form-item:hover {
            border-color: #007cba;
            box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
        }
        
        .form-item.validation-applied {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .form-item.validation-applied h4 {
            color: #28a745;
        }
        
            .form-item .apply-validation:disabled {
                background: #6c757d;
                border-color: #6c757d;
                cursor: not-allowed;
            }
            
            .form-actions {
                margin-top: 15px;
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .remove-validation {
                background: #dc3545 !important;
                border-color: #dc3545 !important;
                color: white !important;
            }
            
            .remove-validation:hover {
                background: #c82333 !important;
                border-color: #bd2130 !important;
            }
        </style>
        
        <div class="wrap">
            <h1><?php _e('Foxentry Nastavení', 'foxentry-integration'); ?></h1>
            
            <!-- Foxentry promo banner -->
            <div style="text-align: center; margin: 20px 0;">
                <a href="https://foxentry.com/?aff=<?php echo FOXENTRY_AFFILIATE_CODE; ?>" target="_blank">
                    <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/banner.jpg" width="336" height="280" alt="Foxentry - Validace dat" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                </a>
            </div>
            
            <?php if (empty($this->api_key)): ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Registrace na Foxentry:', 'foxentry-integration'); ?></strong> 
                    <a href="https://app.foxentry.com/registration?aff=<?php echo FOXENTRY_AFFILIATE_CODE; ?>" target="_blank">
                        <?php _e('Zaregistrujte se zde pro získání API klíče', 'foxentry-integration'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Vizuální průvodce pro získání API klíče -->
            <div class="foxentry-api-guide" id="foxentry-guide">
                <div class="guide-header">
                    <h2><?php _e('Jak získat API klíč - Vizuální průvodce', 'foxentry-integration'); ?></h2>
                    <p><?php _e('Následujte tyto kroky pro získání vašeho Foxentry API klíče:', 'foxentry-integration'); ?></p>
                    <div class="guide-controls">
                        <button type="button" id="toggle-guide" class="guide-toggle-btn">
                            <span class="toggle-text"><?php _e('Skrýt průvodce', 'foxentry-integration'); ?></span>
                            <span class="toggle-icon">▼</span>
                        </button>
                    </div>
                </div>
                
                <div class="guide-steps">
                    <!-- Krok 1: Registrace -->
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3><?php _e('Registrace na Foxentry', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Nejdříve se zaregistrujte na Foxentry, pokud ještě nemáte účet.', 'foxentry-integration'); ?></p>
                            <a href="https://app.foxentry.com/registration?aff=<?php echo FOXENTRY_AFFILIATE_CODE; ?>" target="_blank" class="guide-button">
                                <?php _e('Zaregistrovat se', 'foxentry-integration'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Krok 2: Vytvoření projektu -->
                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3><?php _e('Vytvoření nového projektu', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Po přihlášení vytvořte nový projekt a pokračujte do třetího kroku.', 'foxentry-integration'); ?></p>
                            <a href="https://app.foxentry.com/projects/creator" target="_blank" class="guide-button">
                                <?php _e('Vytvořit projekt', 'foxentry-integration'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Krok 3: Výběr typu projektu -->
                    <div class="guide-step critical-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3><?php _e('DŮLEŽITÉ: Výběr typu projektu', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Ve třetím kroku při vytváření projektu MUSÍTE vybrat "Aplikace" místo "Webová stránka"!', 'foxentry-integration'); ?></p>
                            <div class="step-image">
                                <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/api01.png" alt="<?php _e('Výběr typu projektu - Aplikace', 'foxentry-integration'); ?>" />
                                <div class="image-caption">
                                    <?php _e('Vyberte "Aplikace" pro získání přístupu k API klíčům', 'foxentry-integration'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Krok 4: Dokončení projektu -->
                    <div class="guide-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3><?php _e('Dokončení projektu', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Dokončete vytváření projektu a pokračujte do administrace.', 'foxentry-integration'); ?></p>
                            <div class="step-image">
                                <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/api02.png" alt="<?php _e('Dokončení projektu', 'foxentry-integration'); ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Krok 5: Správa API klíčů -->
                    <div class="guide-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h3><?php _e('Přejít do správy API klíčů', 'foxentry-integration'); ?></h3>
                            <p><?php _e('V administraci přejděte do sekce "Nastavení projektu" > "API klíče".', 'foxentry-integration'); ?></p>
                            <div class="step-image">
                                <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/api03.png" alt="<?php _e('Správa API klíčů', 'foxentry-integration'); ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Krok 6: Vytvoření API klíče -->
                    <div class="guide-step">
                        <div class="step-number">6</div>
                        <div class="step-content">
                            <h3><?php _e('Vytvoření nového API klíče', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Klikněte na "Vytvořit API klíč" a zadejte název (např. "WordPress").', 'foxentry-integration'); ?></p>
                            <div class="step-image">
                                <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/api04.png" alt="<?php _e('Vytvoření API klíče', 'foxentry-integration'); ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Krok 7: Zkopírování API klíče -->
                    <div class="guide-step final-step">
                        <div class="step-number">7</div>
                        <div class="step-content">
                            <h3><?php _e('Zkopírování API klíče', 'foxentry-integration'); ?></h3>
                            <p><?php _e('Zkopírujte vygenerovaný API klíč a vložte ho do pole níže.', 'foxentry-integration'); ?></p>
                            <div class="step-image">
                                <img src="<?php echo FOXENTRY_PLUGIN_URL; ?>assets/api05.png" alt="<?php _e('Zkopírování API klíče', 'foxentry-integration'); ?>" />
                            </div>
                            <div class="api-key-example">
                                <strong><?php _e('Příklad API klíče:', 'foxentry-integration'); ?></strong>
                                <code>76R2HarteqgqnY6p6wRI</code>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="guide-footer">
                    <div class="success-tip">
                        <h4><?php _e('Úspěch!', 'foxentry-integration'); ?></h4>
                        <p><?php _e('Pokud jste postupovali podle tohoto průvodce, máte nyní platný API klíč, který můžete použít v tomto pluginu.', 'foxentry-integration'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="notice notice-success" style="border-left-color: #7c3aed;">
                <p>
                    <strong><?php _e('Plugin vytvořen studiem Webklient', 'foxentry-integration'); ?></strong> - 
                    <a href="https://www.webklient.cz" target="_blank">www.webklient.cz</a> | 
                    Mediatoring.com s.r.o.
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('foxentry_settings');
                do_settings_sections('foxentry_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('API Klíč', 'foxentry-integration'); ?></th>
                        <td>
                            <input type="password" name="foxentry_api_key" value="<?php echo esc_attr(get_option('foxentry_api_key')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Vložte váš Foxentry API klíč ze sekce Nastavení > API klíče', 'foxentry-integration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cache doba (sekundy)', 'foxentry-integration'); ?></th>
                        <td>
                            <input type="number" name="foxentry_cache_duration" value="<?php echo esc_attr(get_option('foxentry_cache_duration', 3600)); ?>" class="small-text" />
                            <p class="description"><?php _e('Jak dlouho cachovat výsledky validace', 'foxentry-integration'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- Automatické skenování formulářů -->
            <div class="foxentry-form-scanner">
                <h2><?php _e('Automatické skenování formulářů', 'foxentry-integration'); ?></h2>
                <p><?php _e('Plugin automaticky najde všechny formuláře na vašem webu a umožní vám vybrat, která pole chcete ověřovat.', 'foxentry-integration'); ?></p>
                
                <div class="scanner-controls">
                    <button type="button" id="scan-forms" class="button button-primary">
                        <?php _e('Skenovat formuláře na webu', 'foxentry-integration'); ?>
                    </button>
                    <button type="button" id="clear-scan" class="button" style="display: none;">
                        <?php _e('Vymazat výsledky', 'foxentry-integration'); ?>
                    </button>
                </div>
                
                <div id="scan-results" style="display: none;">
                    <h3><?php _e('Nalezené formuláře:', 'foxentry-integration'); ?></h3>
                    <div id="forms-list"></div>
                </div>
            </div>
            
            <h2><?php _e('Použití', 'foxentry-integration'); ?></h2>
            <h3><?php _e('Shortcodes:', 'foxentry-integration'); ?></h3>
            <p><code>[foxentry_validator type="email"]</code> - <?php _e('Validátor emailu', 'foxentry-integration'); ?></p>
            <p><code>[foxentry_validator type="phone"]</code> - <?php _e('Validátor telefonu', 'foxentry-integration'); ?></p>
            <p><code>[foxentry_validator type="address"]</code> - <?php _e('Validátor adresy', 'foxentry-integration'); ?></p>
            
            <h3><?php _e('Test API klíče:', 'foxentry-integration'); ?></h3>
            <button type="button" id="test-api" class="button"><?php _e('Otestovat API klíč', 'foxentry-integration'); ?></button>
            <div id="api-test-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test API klíče
            $('#test-api').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Testování...', 'foxentry-integration'); ?>');
                
                $.post(ajaxurl, {
                    action: 'foxentry_test_api',
                    nonce: '<?php echo wp_create_nonce('foxentry_test'); ?>'
                }, function(response) {
                    $('#api-test-result').html(response.success ? 
                        '<div class="notice notice-success"><p><?php _e('API klíč je platný!', 'foxentry-integration'); ?></p></div>' : 
                        '<div class="notice notice-error"><p><?php _e('Chyba:', 'foxentry-integration'); ?> ' + response.data + '</p></div>'
                    );
                    button.prop('disabled', false).text('<?php _e('Otestovat API klíč', 'foxentry-integration'); ?>');
                });
            });
            
            // Interaktivní prvky průvodce
            var guideSteps = $('#foxentry-guide .guide-steps');
            var toggleBtn = $('#toggle-guide');
            var isVisible = localStorage.getItem('foxentry_guide_visible') !== 'false';
            
            // Načtení uloženého stavu
            if (!isVisible) {
                guideSteps.hide();
                toggleBtn.find('.toggle-text').text('<?php _e('Zobrazit průvodce', 'foxentry-integration'); ?>');
                toggleBtn.find('.toggle-icon').text('▶');
            }
            
            // Toggle průvodce
            toggleBtn.click(function() {
                if (isVisible) {
                    guideSteps.slideUp(300);
                    toggleBtn.find('.toggle-text').text('<?php _e('Zobrazit průvodce', 'foxentry-integration'); ?>');
                    toggleBtn.find('.toggle-icon').text('▶');
                    isVisible = false;
                    localStorage.setItem('foxentry_guide_visible', 'false');
                } else {
                    guideSteps.slideDown(300);
                    toggleBtn.find('.toggle-text').text('<?php _e('Skrýt průvodce', 'foxentry-integration'); ?>');
                    toggleBtn.find('.toggle-icon').text('▼');
                    isVisible = true;
                    localStorage.setItem('foxentry_guide_visible', 'true');
                }
            });
            
            // Smooth scroll pro kroky
            $('.guide-step').click(function() {
                var $this = $(this);
                $('html, body').animate({
                    scrollTop: $this.offset().top - 100
                }, 500);
            });
            
            // Hover efekty
            $('.guide-step').hover(
                function() {
                    $(this).addClass('hover-effect');
                },
                function() {
                    $(this).removeClass('hover-effect');
                }
            );
            
            // Skenování formulářů
            $('#scan-forms').click(function() {
                var button = $(this);
                var originalText = button.text();
                button.prop('disabled', true).text('<?php _e('Skenuji...', 'foxentry-integration'); ?>');
                
                // AJAX volání pro skenování
                $.post(ajaxurl, {
                    action: 'foxentry_scan_forms',
                    nonce: '<?php echo wp_create_nonce('foxentry_scan'); ?>'
                }, function(response) {
                    if (response.success) {
                        displayScanResults(response.data);
                        $('#scan-results').show();
                        $('#clear-scan').show();
                    } else {
                        alert('<?php _e('Chyba při skenování:', 'foxentry-integration'); ?> ' + response.data);
                    }
                    button.prop('disabled', false).text(originalText);
                });
            });
            
            // Vymazání výsledků
            $('#clear-scan').click(function() {
                $('#scan-results').hide();
                $('#forms-list').empty();
                $(this).hide();
            });
            
            // Aplikování validace na vybraná pole
            $(document).on('click', '.apply-validation', function() {
                var formIndex = $(this).data('form');
                var selectedFields = [];
                
                // Získání vybraných polí
                $('#forms-list .form-item').eq(formIndex).find('input[type="checkbox"]:checked').each(function() {
                    selectedFields.push({
                        name: $(this).val(),
                        type: $(this).data('type')
                    });
                });
                
                if (selectedFields.length === 0) {
                    alert('<?php _e('Vyberte alespoň jedno pole pro validaci', 'foxentry-integration'); ?>');
                    return;
                }
                
                // AJAX volání pro uložení nastavení
                $.post(ajaxurl, {
                    action: 'foxentry_save_form_validation',
                    nonce: '<?php echo wp_create_nonce('foxentry_save_validation'); ?>',
                    form_index: formIndex,
                    fields: selectedFields
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Validace byla úspěšně aplikována!', 'foxentry-integration'); ?>');
                        // Označit formulář jako zpracovaný
                        var $formItem = $('#forms-list .form-item').eq(formIndex);
                        $formItem.addClass('validation-applied');
                        $formItem.find('.apply-validation').hide();
                        $formItem.find('.remove-validation').show();
                    } else {
                        alert('<?php _e('Chyba při aplikování validace:', 'foxentry-integration'); ?> ' + response.data);
                    }
                });
            });
            
            // Zrušení validace
            $(document).on('click', '.remove-validation', function() {
                var formIndex = $(this).data('form');
                
                if (!confirm('<?php _e('Opravdu chcete zrušit validaci pro tento formulář?', 'foxentry-integration'); ?>')) {
                    return;
                }
                
                // AJAX volání pro zrušení validace
                $.post(ajaxurl, {
                    action: 'foxentry_remove_form_validation',
                    nonce: '<?php echo wp_create_nonce('foxentry_remove_validation'); ?>',
                    form_index: formIndex
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Validace byla zrušena!', 'foxentry-integration'); ?>');
                        // Obnovit formulář do původního stavu
                        var $formItem = $('#forms-list .form-item').eq(formIndex);
                        $formItem.removeClass('validation-applied');
                        $formItem.find('.apply-validation').show().prop('disabled', false).text('<?php _e('Aplikovat validaci', 'foxentry-integration'); ?>');
                        $formItem.find('.remove-validation').hide();
                        $formItem.find('input[type="checkbox"]').prop('checked', false);
                    } else {
                        alert('<?php _e('Chyba při rušení validace:', 'foxentry-integration'); ?> ' + response.data);
                    }
                });
            });
            
            function displayScanResults(forms) {
                var html = '';
                if (forms.length === 0) {
                    html = '<p><?php _e('Nebyly nalezeny žádné formuláře.', 'foxentry-integration'); ?></p>';
                } else {
                    forms.forEach(function(form, index) {
                        html += '<div class="form-item">';
                        html += '<h4>Formulář ' + (index + 1) + '</h4>';
                        html += '<p><strong>Stránka:</strong> ' + form.page + '</p>';
                        html += '<p><strong>Nalezená pole:</strong></p>';
                        html += '<ul>';
                        form.fields.forEach(function(field) {
                            html += '<li>';
                            html += '<label><input type="checkbox" value="' + field.name + '" data-type="' + field.validation_type + '" data-form="' + index + '"> ';
                            if (field.label && field.label !== field.name) {
                                html += field.label + ' (' + field.name + ') - ' + field.type + ' - <strong>' + field.validation_type + '</strong>';
                            } else {
                                html += field.name + ' (' + field.type + ') - <strong>' + field.validation_type + '</strong>';
                            }
                            html += '</label>';
                            html += '</li>';
                        });
                        html += '</ul>';
                        html += '<div class="form-actions">';
                        html += '<button type="button" class="button apply-validation" data-form="' + index + '">';
                        html += '<?php _e('Aplikovat validaci', 'foxentry-integration'); ?>';
                        html += '</button>';
                        html += '<button type="button" class="button remove-validation" data-form="' + index + '" style="display: none; margin-left: 10px; background: #dc3545; border-color: #dc3545;">';
                        html += '<?php _e('Zrušit validaci', 'foxentry-integration'); ?>';
                        html += '</button>';
                        html += '</div>';
                        html += '</div>';
                    });
                }
                $('#forms-list').html(html);
            }
            
            // Načtení uložených formulářů při načtení stránky
            var savedForms = <?php echo json_encode(get_option('foxentry_scanned_forms', array())); ?>;
            if (savedForms.length > 0) {
                displayScanResults(savedForms);
                $('#scan-results').show();
                $('#clear-scan').show();
            }
            
            // Načtení uložených validací
            var savedValidations = <?php echo json_encode(get_option('foxentry_form_validation', array())); ?>;
            if (Object.keys(savedValidations).length > 0) {
                // Označit formuláře s aplikovanou validací
                setTimeout(function() {
                    Object.keys(savedValidations).forEach(function(formIndex) {
                        var $formItem = $('#forms-list .form-item').eq(parseInt(formIndex));
                        $formItem.addClass('validation-applied');
                        $formItem.find('.apply-validation').hide();
                        $formItem.find('.remove-validation').show();
                    });
                }, 100);
            }
        });
        </script>
        <?php
    }
    
    public function validator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'placeholder' => '',
            'class' => 'foxentry-validator',
            'form_action' => '',
            'submit_text' => '',
            'required' => 'true'
        ), $atts);
        
        $placeholder = $atts['placeholder'] ?: $this->get_default_placeholder($atts['type']);
        $unique_id = 'foxentry_' . uniqid();
        $form_action = $atts['form_action'] ?: '#';
        $submit_text = $atts['submit_text'] ?: __('Odeslat', 'foxentry-integration');
        $required = $atts['required'] === 'true' ? 'required' : '';
        
        ob_start();
        ?>
        <div class="foxentry-wrapper">
            <form class="foxentry-form" id="<?php echo $unique_id; ?>_form" action="<?php echo esc_url($form_action); ?>" method="post">
                <div class="foxentry-field-wrapper">
                    <input type="text" 
                           id="<?php echo $unique_id; ?>" 
                           name="foxentry_<?php echo esc_attr($atts['type']); ?>"
                           class="<?php echo esc_attr($atts['class']); ?>" 
                           data-type="<?php echo esc_attr($atts['type']); ?>"
                           placeholder="<?php echo esc_attr($placeholder); ?>"
                           <?php echo $required; ?> />
                    <div class="foxentry-result" id="<?php echo $unique_id; ?>_result"></div>
                </div>
                <script>
                console.log('Foxentry: Shortcode vytvořen s ID:', '<?php echo $unique_id; ?>', 'Typ:', '<?php echo esc_attr($atts['type']); ?>', 'Třída:', '<?php echo esc_attr($atts['class']); ?>');
                </script>
                <div class="foxentry-form-actions">
                    <button type="submit" class="foxentry-submit-btn" disabled>
                        <?php echo esc_html($submit_text); ?>
                    </button>
                    <div class="foxentry-form-status" id="<?php echo $unique_id; ?>_status"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    
    public function ajax_validate() {
        // Debug log
        error_log('Foxentry AJAX: Validace volána s hodnotou: ' . $_POST['value'] . ', typ: ' . $_POST['type']);
        
        // Bezpečnostní kontrola
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_validate')) {
            error_log('Foxentry AJAX: Chyba nonce');
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        $value = sanitize_text_field($_POST['value']);
        $type = sanitize_text_field($_POST['type']);
        
        if (empty($this->api_key)) {
            error_log('Foxentry AJAX: API klíč není nastaven');
            wp_send_json_error(__('API klíč není nastaven', 'foxentry-integration'));
        }
        
        // Kontrola cache
        $cache_key = 'foxentry_' . md5($type . $value);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            wp_send_json_success($cached_result);
        }
        
        // Foxentry validace pomocí REST API
        error_log('Foxentry AJAX: Volám API pro typ: ' . $type . ', hodnota: ' . $value);
        $result = $this->validate_with_foxentry_api($type, $value);
        
        if ($result) {
            error_log('Foxentry AJAX: API vrátilo výsledek: ' . print_r($result, true));
            // Uložení do cache
            $cache_duration = get_option('foxentry_cache_duration', 3600);
            set_transient($cache_key, $result, $cache_duration);
            wp_send_json_success($result);
        } else {
            error_log('Foxentry AJAX: API vrátilo false');
            wp_send_json_error(__('Chyba při validaci', 'foxentry-integration'));
        }
    }
    
    private function validate_with_foxentry_api($type, $value) {
        error_log('Foxentry API: Začínám validaci pro typ: ' . $type . ', hodnota: ' . $value);
        
        $endpoints = array(
            'email' => 'https://api.foxentry.com/email/validate',
            'phone' => 'https://api.foxentry.com/phone/validate',
            'address' => 'https://api.foxentry.com/location/validate'
        );
        
        if (!isset($endpoints[$type])) {
            error_log('Foxentry API: Neznámý typ: ' . $type);
            return false;
        }
        
        error_log('Foxentry API: Používám endpoint: ' . $endpoints[$type]);
        
        $request_data = array(
            'request' => array(
                'query' => array(
                    $type => $value
                ),
                'options' => array(
                    'validationType' => 'extended'
                )
            )
        );
        
        $response = wp_remote_post($endpoints[$type], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Api-Version' => '2.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log('Foxentry API: WP Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        error_log('Foxentry API: Status code: ' . $status_code);
        
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            error_log('Foxentry API: Error response: ' . $body);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        error_log('Foxentry API: Response body: ' . $body);
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['response']['result'])) {
            error_log('Foxentry API: Neplatná response struktura');
            return false;
        }
        
        error_log('Foxentry API: Parsing response...');
        $result = $this->parse_foxentry_response($data['response'], $type);
        error_log('Foxentry API: Parsed result: ' . print_r($result, true));
        
        return $result;
    }
    
    private function parse_foxentry_response($response, $type) {
        $result = $response['result'];
        $is_valid = $result['isValid'];
        $proposal = $result['proposal'];
        
        // Zpracování opravených dat
        $corrected_data = null;
        if (isset($response['resultCorrected']) && $response['resultCorrected']['isValid']) {
            $corrected_data = $response['resultCorrected']['data'];
        }
        
        // Zpracování návrhů
        $suggestions = array();
        if (isset($response['suggestions']) && !empty($response['suggestions'])) {
            foreach ($response['suggestions'] as $suggestion) {
                $suggestions[] = $suggestion['data'];
            }
        }
        
        // Vytvoření zprávy
        $message = $this->create_validation_message($is_valid, $proposal, $corrected_data, $suggestions, $type);
        
        return array(
            'isValid' => $is_valid,
            'message' => $message,
            'correctedData' => $corrected_data,
            'suggestions' => $suggestions,
            'proposal' => $proposal
        );
    }
    
    private function create_validation_message($is_valid, $proposal, $corrected_data, $suggestions, $type) {
        $type_names = array(
            'email' => 'Email',
            'phone' => 'Telefon',
            'address' => 'Adresa'
        );
        
        $type_name = isset($type_names[$type]) ? $type_names[$type] : ucfirst($type);
        
        switch ($proposal) {
            case 'valid':
                return sprintf(__('%s je platný ✓', 'foxentry-integration'), $type_name);
                
            case 'invalid':
                $format_hints = $this->get_format_hints($type);
                return sprintf(__('%s není platný. %s', 'foxentry-integration'), $type_name, $format_hints);
                
            case 'validWithSuggestion':
                $message = sprintf(__('%s je platný ✓', 'foxentry-integration'), $type_name);
                if (!empty($suggestions)) {
                    $message .= ' ' . __('Doporučujeme:', 'foxentry-integration') . ' ' . implode(', ', array_column($suggestions, $type));
                }
                return $message;
                
            case 'invalidWithCorrection':
                if ($corrected_data) {
                    return sprintf(__('%s opraveno na: %s ✓', 'foxentry-integration'), $type_name, $corrected_data[$type]);
                }
                $format_hints = $this->get_format_hints($type);
                return sprintf(__('%s není platný. %s', 'foxentry-integration'), $type_name, $format_hints);
                
            default:
                return $is_valid ? 
                    sprintf(__('%s je platný ✓', 'foxentry-integration'), $type_name) : 
                    sprintf(__('%s není platný. %s', 'foxentry-integration'), $type_name, $this->get_format_hints($type));
        }
    }
    
    private function get_format_hints($type) {
        switch ($type) {
            case 'email':
                return __('Očekávaný formát: jmeno@domena.cz', 'foxentry-integration');
            case 'phone':
                return __('Očekávaný formát: +420123456789 nebo 123456789', 'foxentry-integration');
            case 'address':
                return __('Očekávaný formát: Ulice číslo, Město, PSČ', 'foxentry-integration');
            default:
                return __('Zkontrolujte formát', 'foxentry-integration');
        }
    }
    
    private function get_default_placeholder($type) {
        $placeholders = array(
            'email' => __('Zadejte email adresu', 'foxentry-integration'),
            'phone' => __('Zadejte telefonní číslo', 'foxentry-integration'),
            'address' => __('Zadejte adresu', 'foxentry-integration')
        );
        
        return isset($placeholders[$type]) ? $placeholders[$type] : __('Zadejte hodnotu', 'foxentry-integration');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'foxentry-frontend',
            FOXENTRY_PLUGIN_URL . 'assets/frontend.js',
            array('jquery'),
            FOXENTRY_PLUGIN_VERSION,
            true
        );
        
        // Debug: Přidání informací o načítání
        wp_add_inline_script('foxentry-frontend', 'console.log("Foxentry: Frontend script načten");', 'before');
        
        // Načtení vygenerovaného validation scriptu
        $validation_script = get_option('foxentry_validation_script', '');
        if (!empty($validation_script)) {
            wp_add_inline_script('foxentry-frontend', $validation_script);
            wp_add_inline_script('foxentry-frontend', 'console.log("Foxentry: Validation script načten");', 'before');
        } else {
            wp_add_inline_script('foxentry-frontend', 'console.log("Foxentry: Žádný validation script");', 'before');
        }
        
        wp_localize_script('foxentry-frontend', 'foxentry_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('foxentry_validate'),
            'messages' => array(
                'validating' => __('Ověřuji...', 'foxentry-integration'),
                'email_valid' => __('Email je platný', 'foxentry-integration'),
                'email_invalid' => __('Email není platný', 'foxentry-integration'),
                'phone_valid' => __('Telefon je platný', 'foxentry-integration'),
                'phone_invalid' => __('Telefonní číslo není platné', 'foxentry-integration'),
                'address_valid' => __('Adresa je platná', 'foxentry-integration'),
                'address_invalid' => __('Adresa není platná', 'foxentry-integration'),
                'email_format_error' => __('Neplatný formát emailu', 'foxentry-integration'),
                'phone_format_error' => __('Neplatný formát telefonu', 'foxentry-integration'),
                'address_format_error' => __('Adresa je příliš krátká', 'foxentry-integration'),
                'validation_error' => __('Chyba při validaci:', 'foxentry-integration'),
                'connection_error' => __('Chyba připojení', 'foxentry-integration'),
                'timeout_error' => __('Vypršel časový limit', 'foxentry-integration'),
                'unknown_type' => __('Neznámý typ validace', 'foxentry-integration'),
                'invalid_format' => __('Neplatný formát', 'foxentry-integration'),
                'submitting' => __('Odesílám...', 'foxentry-integration'),
                'processing' => __('Zpracovávám...', 'foxentry-integration'),
                'submitted' => __('Odesláno', 'foxentry-integration'),
                'submit' => __('Odeslat', 'foxentry-integration'),
                'invalid_data' => __('Neplatná data', 'foxentry-integration')
            )
        ));
        
        wp_enqueue_style(
            'foxentry-frontend',
            FOXENTRY_PLUGIN_URL . 'assets/frontend.css',
            array(),
            FOXENTRY_PLUGIN_VERSION
        );
        
        // Debug: Přidání informace o načítání CSS
        wp_add_inline_script('foxentry-frontend', 'console.log("Foxentry: CSS styly načteny");', 'before');
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_foxentry-settings') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Načtení CSS pro admin stránku
        wp_enqueue_style(
            'foxentry-admin',
            FOXENTRY_PLUGIN_URL . 'assets/frontend.css',
            array(),
            FOXENTRY_PLUGIN_VERSION
        );
        
        // Test API AJAX
        add_action('wp_ajax_foxentry_test_api', array($this, 'ajax_test_api'));
    }
    
    public function ajax_test_api() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_test')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        if (empty($this->api_key)) {
            wp_send_json_error(__('API klíč není nastaven', 'foxentry-integration'));
        }
        
        // Test API s jednoduchým emailem
        $test_result = $this->validate_with_foxentry_api('email', 'test@example.com');
        
        if ($test_result) {
            wp_send_json_success(__('API klíč je platný!', 'foxentry-integration'));
        } else {
            wp_send_json_error(__('API klíč není platný - zkontrolujte vložený klíč', 'foxentry-integration'));
        }
    }
    
    public function ajax_scan_forms() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_scan')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        $forms = $this->scan_website_forms();
        
        // Uložení naskenovaných formulářů do databáze
        update_option('foxentry_scanned_forms', $forms);
        
        wp_send_json_success($forms);
    }
    
    public function ajax_save_form_validation() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_save_validation')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        $form_index = intval($_POST['form_index']);
        $fields = $_POST['fields'];
        
        // Uložení nastavení do WordPress options
        $validation_settings = get_option('foxentry_form_validation', array());
        $validation_settings[$form_index] = $fields;
        update_option('foxentry_form_validation', $validation_settings);
        
        // Generování JavaScript kódu pro aplikování validace
        $this->generate_validation_script($fields);
        
        wp_send_json_success(__('Nastavení validace bylo uloženo', 'foxentry-integration'));
    }
    
    public function ajax_remove_form_validation() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_remove_validation')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        $form_index = intval($_POST['form_index']);
        
        // Odstranění nastavení z WordPress options
        $validation_settings = get_option('foxentry_form_validation', array());
        if (isset($validation_settings[$form_index])) {
            unset($validation_settings[$form_index]);
            update_option('foxentry_form_validation', $validation_settings);
        }
        
        // Regenerování validation scriptu bez tohoto formuláře
        $this->regenerate_validation_script();
        
        wp_send_json_success(__('Validace byla zrušena', 'foxentry-integration'));
    }
    
    private function regenerate_validation_script() {
        $validation_settings = get_option('foxentry_form_validation', array());
        $all_fields = array();
        
        // Sběr všech polí ze všech formulářů
        foreach ($validation_settings as $form_fields) {
            $all_fields = array_merge($all_fields, $form_fields);
        }
        
        if (!empty($all_fields)) {
            $this->generate_validation_script($all_fields);
        } else {
            // Pokud nejsou žádná pole, vymažeme script
            update_option('foxentry_validation_script', '');
        }
    }
    
    private function generate_validation_script($fields) {
        $script = "jQuery(document).ready(function($) {";
        $script .= "console.log('Foxentry: Načítám univerzální validation script pro', " . count($fields) . ", polí');";
        $script .= "console.log('Foxentry: Pole pro validaci:', " . json_encode($fields) . ");";
        
        foreach ($fields as $field) {
            $field_name = sanitize_text_field($field['name']);
            $field_type = sanitize_text_field($field['type']);
            $validation_type = sanitize_text_field($field['validation_type']);
            
            $script .= "
                // Univerzální hledání pole
                var selectors = [
                    'input[name=\"{$field_name}\"]',
                    'textarea[name=\"{$field_name}\"]',
                    'select[name=\"{$field_name}\"]',
                    'input[name*=\"{$field_name}\"]',
                    'textarea[name*=\"{$field_name}\"]',
                    'select[name*=\"{$field_name}\"]',
                    'input[data-field-name=\"{$field_name}\"]',
                    'input[data-name=\"{$field_name}\"]',
                    'input[data-field=\"{$field_name}\"]'
                ];
                
                // Pro různé form pluginy přidáme specifické selektory
                if ('{$field_name}'.includes('wpforms[fields]')) {
                    var fieldId = '{$field_name}'.match(/\\[(\\d+)\\]/);
                    if (fieldId) {
                        selectors.push('input[id*=\"wpforms-\"][id*=\"field_' + fieldId[1] + '\"]');
                        selectors.push('textarea[id*=\"wpforms-\"][id*=\"field_' + fieldId[1] + '\"]');
                    }
                }
                
                if ('{$field_name}'.includes('contact-form-7')) {
                    selectors.push('input[name*=\"contact-form-7\"]');
                    selectors.push('textarea[name*=\"contact-form-7\"]');
                }
                
                if ('{$field_name}'.includes('ninja_forms_field_')) {
                    var fieldId = '{$field_name}'.match(/ninja_forms_field_(\\d+)/);
                    if (fieldId) {
                        selectors.push('input[id*=\"ninja_forms_field_' + fieldId[1] + '\"]');
                        selectors.push('textarea[id*=\"ninja_forms_field_' + fieldId[1] + '\"]');
                    }
                }
                
                // Inteligentní hledání podle typu pole
                if ('{$field_type}' === 'email' || '{$validation_type}' === 'email') {
                    selectors.push('input[type=\"email\"]');
                    selectors.push('input[placeholder*=\"email\"]');
                    selectors.push('input[placeholder*=\"Email\"]');
                    selectors.push('input[aria-label*=\"email\"]');
                    selectors.push('input[aria-label*=\"Email\"]');
                }
                
                if ('{$field_type}' === 'tel' || '{$validation_type}' === 'phone') {
                    selectors.push('input[type=\"tel\"]');
                    selectors.push('input[placeholder*=\"phone\"]');
                    selectors.push('input[placeholder*=\"telefon\"]');
                    selectors.push('input[placeholder*=\"Telefon\"]');
                    selectors.push('input[aria-label*=\"phone\"]');
                    selectors.push('input[aria-label*=\"telefon\"]');
                    selectors.push('input[aria-label*=\"Telefon\"]');
                }
                
                var found = false;
                console.log('Foxentry: Hledám pole \"{$field_name}\" s selektory:', selectors);
                selectors.forEach(function(selector) {
                    if (!found) {
                        var \$elements = $(selector);
                        console.log('Foxentry: Selektor \"' + selector + '\" našel', \$elements.length, 'elementů');
                        \$elements.each(function() {
                            var \$input = $(this);
                            
                            // Kontrola, zda pole už nemá validaci
                            if (\$input.hasClass('foxentry-validator')) {
                                console.log('Foxentry: Pole už má validaci, přeskočuji');
                                return;
                            }
                            
                            console.log('Foxentry: Aplikuji validaci na pole:', \$input.attr('name') || \$input.attr('id'), 'Typ:', '{$validation_type}');
                            
                            \$input.addClass('foxentry-validator');
                            \$input.attr('data-type', '{$validation_type}');
                            
                            // Přidání wrapperu pokud neexistuje
                            if (!\$input.closest('.foxentry-field-wrapper').length) {
                                \$input.wrap('<div class=\"foxentry-field-wrapper\"></div>');
                                \$input.after('<div class=\"foxentry-result\"></div>');
                                console.log('Foxentry: Přidán wrapper a result div');
                            }
                            
                            found = true;
                        });
                    }
                });
                
                if (!found) {
                    console.log('Foxentry: Pole \"{$field_name}\" nebylo nalezeno');
                }
            ";
        }
        
        $script .= "});";
        
        // Uložení scriptu do WordPress
        update_option('foxentry_validation_script', $script);
    }
    
    private function scan_website_forms() {
        $forms = array();
        
        // 1. Univerzální skenování všech stránek a příspěvků
        $this->scan_all_pages($forms);
        
        // 2. Skenování specifických form pluginů
        $this->scan_form_plugins($forms);
        
        // 3. Skenování widgetů a sidebarů
        $this->scan_widgets($forms);
        
        // 4. Skenování Elementor stránek
        $this->scan_elementor_pages($forms);
        
        // 5. Skenování Bricks stránek
        $this->scan_bricks_pages($forms);
        
        return $forms;
    }
    
    private function scan_all_pages(&$forms) {
        // Skenování všech typů stránek
        $post_types = get_post_types(array('public' => true), 'names');
        $pages = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'numberposts' => 200, // Více stránek pro lepší pokrytí
        ));
        
        foreach ($pages as $page) {
            $content = $page->post_content;
            $page_url = get_permalink($page->ID);
            $page_title = $page->post_title;
            
            // Skenování obsahu stránky
            $page_forms = $this->extract_forms_from_content($content, $page_url, $page_title);
            $forms = array_merge($forms, $page_forms);
            
            // Skenování meta polí (pro page buildery)
            $meta_forms = $this->extract_forms_from_meta($page->ID, $page_url, $page_title);
            $forms = array_merge($forms, $meta_forms);
        }
    }
    
    private function scan_form_plugins(&$forms) {
        // Contact Form 7
        $this->scan_contact_form_7($forms);
        
        // Gravity Forms
        $this->scan_gravity_forms($forms);
        
        // WPForms
        $this->scan_wpforms($forms);
        
        // Ninja Forms
        $this->scan_ninja_forms($forms);
        
        // Formidable Forms
        $this->scan_formidable_forms($forms);
        
        // Caldera Forms
        $this->scan_caldera_forms($forms);
    }
    
    private function scan_elementor_pages(&$forms) {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        // Skenování Elementor stránek
        $elementor_pages = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_elementor_edit_mode',
                    'value' => 'builder',
                    'compare' => '='
                )
            ),
            'numberposts' => 100
        ));
        
        foreach ($elementor_pages as $page) {
            $elementor_data = get_post_meta($page->ID, '_elementor_data', true);
            if ($elementor_data) {
                $forms = array_merge($forms, $this->extract_forms_from_elementor($elementor_data, get_permalink($page->ID), $page->post_title));
            }
        }
    }
    
    private function scan_bricks_pages(&$forms) {
        if (!class_exists('Bricks\Core')) {
            return;
        }
        
        // Skenování Bricks stránek
        $bricks_pages = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_bricks_editor_mode',
                    'value' => 'true',
                    'compare' => '='
                )
            ),
            'numberposts' => 100
        ));
        
        foreach ($bricks_pages as $page) {
            $bricks_data = get_post_meta($page->ID, '_bricks_page_content', true);
            if ($bricks_data) {
                $forms = array_merge($forms, $this->extract_forms_from_bricks($bricks_data, get_permalink($page->ID), $page->post_title));
            }
        }
    }
    
    private function extract_forms_from_content($content, $page_url, $page_title = '') {
        $forms = array();
        
        // Regex pro nalezení formulářů
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $content, $form_matches);
        
        foreach ($form_matches[0] as $index => $form_html) {
            $form_data = array(
                'page' => $page_url,
                'fields' => array()
            );
            
            // Nalezení input polí
            preg_match_all('/<input[^>]*>/i', $form_html, $input_matches);
            foreach ($input_matches[0] as $input) {
                $field = $this->parse_input_field($input);
                if ($field) {
                    $form_data['fields'][] = $field;
                }
            }
            
            // Nalezení textarea polí
            preg_match_all('/<textarea[^>]*>(.*?)<\/textarea>/is', $form_html, $textarea_matches);
            foreach ($textarea_matches[0] as $textarea) {
                $field = $this->parse_textarea_field($textarea);
                if ($field) {
                    $form_data['fields'][] = $field;
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
        
        return $forms;
    }
    
    private function extract_forms_from_meta($page_id, $page_url, $page_title) {
        $forms = array();
        
        // Skenování všech meta polí stránky
        $meta_keys = array(
            '_elementor_data',
            '_bricks_page_content', 
            '_vc_post_settings',
            '_wpb_shortcodes',
            '_fusion_page_options',
            '_beaver_builder_data',
            '_divi_page_settings'
        );
        
        foreach ($meta_keys as $meta_key) {
            $meta_data = get_post_meta($page_id, $meta_key, true);
            if ($meta_data) {
                $forms = array_merge($forms, $this->extract_forms_from_meta_data($meta_data, $page_url, $page_title, $meta_key));
            }
        }
        
        return $forms;
    }
    
    private function extract_forms_from_meta_data($meta_data, $page_url, $page_title, $meta_key) {
        $forms = array();
        
        // Konverze na string pokud je to JSON
        if (is_array($meta_data) || is_object($meta_data)) {
            $meta_data = json_encode($meta_data);
        }
        
        // Hledání formulářů v meta datech
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $meta_data, $form_matches);
        
        foreach ($form_matches[0] as $index => $form_html) {
            $form_data = array(
                'page' => $page_title . ' (' . $meta_key . ')',
                'fields' => array()
            );
            
            // Inteligentní hledání všech typů polí
            $fields = $this->find_all_form_fields($form_html);
            $form_data['fields'] = $fields;
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
        
        return $forms;
    }
    
    private function find_all_form_fields($form_html) {
        $fields = array();
        
        // Hledání všech input polí
        preg_match_all('/<input[^>]*>/i', $form_html, $input_matches);
        foreach ($input_matches[0] as $input) {
            $field = $this->parse_field_intelligently($input, 'input');
            if ($field) {
                $fields[] = $field;
            }
        }
        
        // Hledání všech textarea polí
        preg_match_all('/<textarea[^>]*>(.*?)<\/textarea>/is', $form_html, $textarea_matches);
        foreach ($textarea_matches[0] as $textarea) {
            $field = $this->parse_field_intelligently($textarea, 'textarea');
            if ($field) {
                $fields[] = $field;
            }
        }
        
        // Hledání select polí
        preg_match_all('/<select[^>]*>(.*?)<\/select>/is', $form_html, $select_matches);
        foreach ($select_matches[0] as $select) {
            $field = $this->parse_field_intelligently($select, 'select');
            if ($field) {
                $fields[] = $field;
            }
        }
        
        return $fields;
    }
    
    private function parse_field_intelligently($field_html, $field_type) {
        // Extrakce všech možných atributů
        $attributes = array();
        preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $field_html, $attr_matches);
        for ($i = 0; $i < count($attr_matches[1]); $i++) {
            $attributes[$attr_matches[1][$i]] = $attr_matches[2][$i];
        }
        
        $name = $attributes['name'] ?? $attributes['id'] ?? '';
        $type = $attributes['type'] ?? 'text';
        $placeholder = $attributes['placeholder'] ?? '';
        $aria_label = $attributes['aria-label'] ?? '';
        $label = $attributes['data-label'] ?? '';
        
        if (empty($name)) return null;
        
        // Inteligentní detekce typu validace na základě všech dostupných informací
        $validation_type = $this->detect_field_type_intelligently($name, $type, $placeholder, $aria_label, $label);
        
        return array(
            'name' => $name,
            'type' => $type,
            'validation_type' => $validation_type,
            'placeholder' => $placeholder,
            'aria_label' => $aria_label,
            'label' => $label,
            'html' => $field_html
        );
    }
    
    private function detect_field_type_intelligently($name, $type, $placeholder, $aria_label, $label) {
        $text = strtolower($name . ' ' . $placeholder . ' ' . $aria_label . ' ' . $label);
        
        // Detekce emailu
        if ($type === 'email' || 
            strpos($text, 'email') !== false || 
            strpos($text, 'e-mail') !== false ||
            strpos($text, '@') !== false) {
            return 'email';
        }
        
        // Detekce telefonu
        if ($type === 'tel' || 
            strpos($text, 'phone') !== false || 
            strpos($text, 'telefon') !== false ||
            strpos($text, 'mobil') !== false ||
            strpos($text, 'mobile') !== false ||
            strpos($text, 'cislo') !== false ||
            strpos($text, 'číslo') !== false) {
            return 'phone';
        }
        
        // Detekce adresy
        if (strpos($text, 'address') !== false || 
            strpos($text, 'adresa') !== false ||
            strpos($text, 'street') !== false || 
            strpos($text, 'ulice') !== false ||
            strpos($text, 'city') !== false || 
            strpos($text, 'mesto') !== false ||
            strpos($text, 'město') !== false ||
            strpos($text, 'psc') !== false ||
            strpos($text, 'zip') !== false ||
            strpos($text, 'postal') !== false) {
            return 'address';
        }
        
        return 'text';
    }
    
    private function parse_input_field($input_html) {
        preg_match('/type=["\']([^"\']*)["\']/', $input_html, $type_matches);
        preg_match('/name=["\']([^"\']*)["\']/', $input_html, $name_matches);
        preg_match('/id=["\']([^"\']*)["\']/', $input_html, $id_matches);
        
        $type = isset($type_matches[1]) ? $type_matches[1] : 'text';
        $name = isset($name_matches[1]) ? $name_matches[1] : (isset($id_matches[1]) ? $id_matches[1] : '');
        
        if (empty($name)) return null;
        
        // Detekce typu pole pro validaci
        $validation_type = $this->detect_field_type($name, $type);
        
        return array(
            'name' => $name,
            'type' => $type,
            'validation_type' => $validation_type
        );
    }
    
    private function parse_textarea_field($textarea_html) {
        preg_match('/name=["\']([^"\']*)["\']/', $textarea_html, $name_matches);
        preg_match('/id=["\']([^"\']*)["\']/', $textarea_html, $id_matches);
        
        $name = isset($name_matches[1]) ? $name_matches[1] : (isset($id_matches[1]) ? $id_matches[1] : '');
        
        if (empty($name)) return null;
        
        return array(
            'name' => $name,
            'type' => 'textarea',
            'validation_type' => $this->detect_field_type($name, 'textarea')
        );
    }
    
    private function detect_field_type($name, $html_type) {
        $name_lower = strtolower($name);
        
        // Detekce podle HTML typu
        if ($html_type === 'email') {
            return 'email';
        }
        if ($html_type === 'tel') {
            return 'phone';
        }
        
        // Detekce emailu podle názvu
        if (strpos($name_lower, 'email') !== false || 
            strpos($name_lower, 'mail') !== false ||
            strpos($name_lower, 'e-mail') !== false ||
            strpos($name_lower, 'e_mail') !== false) {
            return 'email';
        }
        
        // Detekce telefonu podle názvu
        if (strpos($name_lower, 'phone') !== false || 
            strpos($name_lower, 'tel') !== false || 
            strpos($name_lower, 'telefon') !== false || 
            strpos($name_lower, 'mobil') !== false ||
            strpos($name_lower, 'mobile') !== false ||
            strpos($name_lower, 'telefonni') !== false ||
            strpos($name_lower, 'cislo') !== false) {
            return 'phone';
        }
        
        // Detekce adresy podle názvu
        if (strpos($name_lower, 'address') !== false || 
            strpos($name_lower, 'adresa') !== false ||
            strpos($name_lower, 'street') !== false || 
            strpos($name_lower, 'ulice') !== false ||
            strpos($name_lower, 'city') !== false || 
            strpos($name_lower, 'mesto') !== false ||
            strpos($name_lower, 'město') !== false ||
            strpos($name_lower, 'psc') !== false ||
            strpos($name_lower, 'zip') !== false ||
            strpos($name_lower, 'postal') !== false) {
            return 'address';
        }
        
        // Detekce jména
        if (strpos($name_lower, 'name') !== false || 
            strpos($name_lower, 'jmeno') !== false ||
            strpos($name_lower, 'jméno') !== false ||
            strpos($name_lower, 'firstname') !== false ||
            strpos($name_lower, 'lastname') !== false ||
            strpos($name_lower, 'krestni') !== false ||
            strpos($name_lower, 'prijmeni') !== false) {
            return 'text';
        }
        
        return 'text';
    }
    
    private function scan_contact_form_7(&$forms) {
        if (!class_exists('WPCF7_ContactForm')) {
            return;
        }
        
        $cf7_forms = get_posts(array(
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($cf7_forms as $form) {
            $form_data = array(
                'page' => 'Contact Form 7: ' . $form->post_title,
                'fields' => array()
            );
            
            // Parsování CF7 shortcode
            $content = $form->post_content;
            preg_match_all('/\[([^\]]+)\]/', $content, $matches);
            
            foreach ($matches[1] as $field) {
                $field_parts = explode(' ', $field);
                $field_type = $field_parts[0];
                $field_name = '';
                
                // Extrakce názvu pole
                foreach ($field_parts as $part) {
                    if (strpos($part, 'name=') === 0) {
                        $field_name = str_replace(array('name=', '"', "'"), '', $part);
                        break;
                    }
                }
                
                if ($field_name) {
                    $validation_type = $this->detect_field_type($field_name, $field_type);
                    $form_data['fields'][] = array(
                        'name' => $field_name,
                        'type' => $field_type,
                        'validation_type' => $validation_type
                    );
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function scan_gravity_forms(&$forms) {
        if (!class_exists('GFAPI')) {
            return;
        }
        
        $gf_forms = GFAPI::get_forms();
        
        foreach ($gf_forms as $form) {
            $form_data = array(
                'page' => 'Gravity Forms: ' . $form['title'],
                'fields' => array()
            );
            
            foreach ($form['fields'] as $field) {
                $field_name = $field->label ?: $field->adminLabel;
                $field_type = $field->type;
                $validation_type = $this->detect_field_type($field_name, $field_type);
                
                $form_data['fields'][] = array(
                    'name' => $field_name,
                    'type' => $field_type,
                    'validation_type' => $validation_type
                );
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function scan_wpforms(&$forms) {
        if (!class_exists('WPForms')) {
            return;
        }
        
        $wpforms = get_posts(array(
            'post_type' => 'wpforms',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($wpforms as $form) {
            $form_data = array(
                'page' => 'WPForms: ' . $form->post_title,
                'fields' => array()
            );
            
            $form_content = json_decode($form->post_content, true);
            if (isset($form_content['fields'])) {
                foreach ($form_content['fields'] as $field) {
                    $field_label = $field['label'] ?? 'Field ' . $field['id'];
                    $field_type = $field['type'] ?? 'text';
                    $field_id = $field['id'] ?? '';
                    
                    // Pro WPForms používáme skutečný název pole
                    $field_name = 'wpforms[fields][' . $field_id . ']';
                    $validation_type = $this->detect_field_type($field_label, $field_type);
                    
                    $form_data['fields'][] = array(
                        'name' => $field_name,
                        'type' => $field_type,
                        'validation_type' => $validation_type,
                        'label' => $field_label,
                        'id' => $field_id
                    );
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function scan_theme_forms(&$forms) {
        // Skenování témat s vestavěnými formuláři
        $theme_forms = array(
            'contact' => 'Kontaktní formulář',
            'newsletter' => 'Newsletter formulář',
            'search' => 'Vyhledávací formulář',
            'login' => 'Přihlašovací formulář',
            'register' => 'Registrační formulář'
        );
        
        foreach ($theme_forms as $form_type => $form_name) {
            // Zkontrolovat, zda téma má tento typ formuláře
            if (function_exists($form_type . '_form') || 
                get_theme_mod($form_type . '_form_enabled', false)) {
                
                $form_data = array(
                    'page' => 'Téma: ' . $form_name,
                    'fields' => array()
                );
                
                // Přidat běžná pole pro tento typ formuláře
                switch ($form_type) {
                    case 'contact':
                        $form_data['fields'][] = array('name' => 'name', 'type' => 'text', 'validation_type' => 'text');
                        $form_data['fields'][] = array('name' => 'email', 'type' => 'email', 'validation_type' => 'email');
                        $form_data['fields'][] = array('name' => 'phone', 'type' => 'tel', 'validation_type' => 'phone');
                        $form_data['fields'][] = array('name' => 'message', 'type' => 'textarea', 'validation_type' => 'text');
                        break;
                    case 'newsletter':
                        $form_data['fields'][] = array('name' => 'email', 'type' => 'email', 'validation_type' => 'email');
                        break;
                    case 'search':
                        $form_data['fields'][] = array('name' => 's', 'type' => 'search', 'validation_type' => 'text');
                        break;
                }
                
                if (!empty($form_data['fields'])) {
                    $forms[] = $form_data;
                }
            }
        }
    }
    
    private function scan_widgets(&$forms) {
        // Skenování widgetů
        $widget_areas = array('sidebar-1', 'footer-1', 'footer-2', 'footer-3', 'sidebar', 'footer');
        
        foreach ($widget_areas as $area) {
            if (is_active_sidebar($area)) {
                ob_start();
                dynamic_sidebar($area);
                $widget_content = ob_get_clean();
                
                $widget_forms = $this->extract_forms_from_content($widget_content, 'Widget: ' . $area);
                $forms = array_merge($forms, $widget_forms);
            }
        }
    }
    
    private function scan_ninja_forms(&$forms) {
        if (!class_exists('Ninja_Forms')) {
            return;
        }
        
        $ninja_forms = get_posts(array(
            'post_type' => 'nf_sub',
            'post_status' => 'publish',
            'numberposts' => 50
        ));
        
        foreach ($ninja_forms as $form) {
            $form_data = array(
                'page' => 'Ninja Forms: ' . $form->post_title,
                'fields' => array()
            );
            
            // Skenování Ninja Forms polí
            $form_fields = get_post_meta($form->ID, '_form_fields', true);
            if ($form_fields) {
                foreach ($form_fields as $field) {
                    $field_name = 'ninja_forms_field_' . $field['id'];
                    $field_type = $field['type'] ?? 'text';
                    $validation_type = $this->detect_field_type_intelligently(
                        $field_name, 
                        $field_type, 
                        $field['placeholder'] ?? '', 
                        $field['aria-label'] ?? '', 
                        $field['label'] ?? ''
                    );
                    
                    $form_data['fields'][] = array(
                        'name' => $field_name,
                        'type' => $field_type,
                        'validation_type' => $validation_type,
                        'label' => $field['label'] ?? '',
                        'placeholder' => $field['placeholder'] ?? ''
                    );
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function scan_formidable_forms(&$forms) {
        if (!class_exists('FrmForm')) {
            return;
        }
        
        $formidable_forms = get_posts(array(
            'post_type' => 'frm_form',
            'post_status' => 'publish',
            'numberposts' => 50
        ));
        
        foreach ($formidable_forms as $form) {
            $form_data = array(
                'page' => 'Formidable Forms: ' . $form->post_title,
                'fields' => array()
            );
            
            // Skenování Formidable Forms polí
            $form_fields = get_post_meta($form->ID, 'frm_fields', true);
            if ($form_fields) {
                foreach ($form_fields as $field) {
                    $field_name = 'item_meta[' . $field['id'] . ']';
                    $field_type = $field['type'] ?? 'text';
                    $validation_type = $this->detect_field_type_intelligently(
                        $field_name, 
                        $field_type, 
                        $field['placeholder'] ?? '', 
                        $field['aria_label'] ?? '', 
                        $field['name'] ?? ''
                    );
                    
                    $form_data['fields'][] = array(
                        'name' => $field_name,
                        'type' => $field_type,
                        'validation_type' => $validation_type,
                        'label' => $field['name'] ?? '',
                        'placeholder' => $field['placeholder'] ?? ''
                    );
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function scan_caldera_forms(&$forms) {
        if (!class_exists('Caldera_Forms')) {
            return;
        }
        
        $caldera_forms = get_posts(array(
            'post_type' => 'caldera_forms',
            'post_status' => 'publish',
            'numberposts' => 50
        ));
        
        foreach ($caldera_forms as $form) {
            $form_data = array(
                'page' => 'Caldera Forms: ' . $form->post_title,
                'fields' => array()
            );
            
            // Skenování Caldera Forms polí
            $form_config = get_post_meta($form->ID, '_caldera_form_config', true);
            if ($form_config && isset($form_config['fields'])) {
                foreach ($form_config['fields'] as $field_id => $field) {
                    $field_name = $field_id;
                    $field_type = $field['type'] ?? 'text';
                    $validation_type = $this->detect_field_type_intelligently(
                        $field_name, 
                        $field_type, 
                        $field['placeholder'] ?? '', 
                        $field['aria_label'] ?? '', 
                        $field['label'] ?? ''
                    );
                    
                    $form_data['fields'][] = array(
                        'name' => $field_name,
                        'type' => $field_type,
                        'validation_type' => $validation_type,
                        'label' => $field['label'] ?? '',
                        'placeholder' => $field['placeholder'] ?? ''
                    );
                }
            }
            
            if (!empty($form_data['fields'])) {
                $forms[] = $form_data;
            }
        }
    }
    
    private function extract_forms_from_elementor($elementor_data, $page_url, $page_title) {
        $forms = array();
        
        // Rekurzivní prohledávání Elementor dat
        $this->scan_elementor_recursive($elementor_data, $forms, $page_url, $page_title);
        
        return $forms;
    }
    
    private function scan_elementor_recursive($data, &$forms, $page_url, $page_title) {
        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['widgetType']) && $item['widgetType'] === 'form') {
                    // Elementor form widget nalezen
                    $form_data = array(
                        'page' => $page_title . ' (Elementor)',
                        'fields' => array()
                    );
                    
                    // Extrakce polí z Elementor formuláře
                    if (isset($item['settings']['form_fields'])) {
                        foreach ($item['settings']['form_fields'] as $field) {
                            $field_name = $field['custom_id'] ?? $field['field_label'];
                            $field_type = $field['field_type'] ?? 'text';
                            $validation_type = $this->detect_field_type_intelligently(
                                $field_name, 
                                $field_type, 
                                $field['placeholder'] ?? '', 
                                $field['aria_label'] ?? '', 
                                $field['field_label'] ?? ''
                            );
                            
                            $form_data['fields'][] = array(
                                'name' => $field_name,
                                'type' => $field_type,
                                'validation_type' => $validation_type,
                                'label' => $field['field_label'] ?? '',
                                'placeholder' => $field['placeholder'] ?? ''
                            );
                        }
                    }
                    
                    if (!empty($form_data['fields'])) {
                        $forms[] = $form_data;
                    }
                }
                
                // Rekurzivní volání pro vnořené elementy
                if (isset($item['elements'])) {
                    $this->scan_elementor_recursive($item['elements'], $forms, $page_url, $page_title);
                }
            }
        }
    }
    
    private function extract_forms_from_bricks($bricks_data, $page_url, $page_title) {
        $forms = array();
        
        // Skenování Bricks dat pro formuláře
        if (is_string($bricks_data)) {
            $bricks_data = json_decode($bricks_data, true);
        }
        
        if (is_array($bricks_data)) {
            $this->scan_bricks_recursive($bricks_data, $forms, $page_url, $page_title);
        }
        
        return $forms;
    }
    
    private function scan_bricks_recursive($data, &$forms, $page_url, $page_title) {
        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['name']) && $item['name'] === 'form') {
                    // Bricks form element nalezen
                    $form_data = array(
                        'page' => $page_title . ' (Bricks)',
                        'fields' => array()
                    );
                    
                    // Extrakce polí z Bricks formuláře
                    if (isset($item['settings']['fields'])) {
                        foreach ($item['settings']['fields'] as $field) {
                            $field_name = $field['name'] ?? $field['label'];
                            $field_type = $field['type'] ?? 'text';
                            $validation_type = $this->detect_field_type_intelligently(
                                $field_name, 
                                $field_type, 
                                $field['placeholder'] ?? '', 
                                $field['aria_label'] ?? '', 
                                $field['label'] ?? ''
                            );
                            
                            $form_data['fields'][] = array(
                                'name' => $field_name,
                                'type' => $field_type,
                                'validation_type' => $validation_type,
                                'label' => $field['label'] ?? '',
                                'placeholder' => $field['placeholder'] ?? ''
                            );
                        }
                    }
                    
                    if (!empty($form_data['fields'])) {
                        $forms[] = $form_data;
                    }
                }
                
                // Rekurzivní volání pro vnořené elementy
                if (isset($item['children'])) {
                    $this->scan_bricks_recursive($item['children'], $forms, $page_url, $page_title);
                }
            }
        }
    }
    
}

// Spuštění pluginu
new FoxentryIntegration();

// Přidání nastavení odkazu do seznamu pluginů
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=foxentry-settings') . '">' . __('Nastavení', 'foxentry-integration') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
