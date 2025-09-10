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
        add_shortcode('foxentry_promo', array($this, 'promo_shortcode'));
        
        // AJAX akce
        add_action('wp_ajax_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_nopriv_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_foxentry_test_api', array($this, 'ajax_test_api'));
        
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
        <div class="wrap">
            <h1><?php _e('Foxentry Nastavení', 'foxentry-integration'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Registrace na Foxentry:', 'foxentry-integration'); ?></strong> 
                    <a href="https://app.foxentry.com/registration?aff=<?php echo FOXENTRY_AFFILIATE_CODE; ?>" target="_blank">
                        <?php _e('Zaregistrujte se zde pro získání API klíče', 'foxentry-integration'); ?>
                    </a>
                </p>
            </div>
            
            <div class="notice notice-warning">
                <h3><?php _e('Návod k získání API klíče:', 'foxentry-integration'); ?></h3>
                <ol>
                    <li><?php _e('Přihlaste se do svého Foxentry účtu', 'foxentry-integration'); ?></li>
                    <li><?php _e('V dashboardu klikněte na váš projekt', 'foxentry-integration'); ?></li>
                    <li><?php _e('Přejděte do sekce "Nastavení"', 'foxentry-integration'); ?></li>
                    <li><?php _e('Klikněte na "API klíče"', 'foxentry-integration'); ?></li>
                    <li><?php _e('Klikněte na "Vytvořit API klíč"', 'foxentry-integration'); ?></li>
                    <li><?php _e('Zkopírujte vygenerovaný API klíč', 'foxentry-integration'); ?></li>
                    <li><?php _e('Vložte API klíč do pole níže', 'foxentry-integration'); ?></li>
                </ol>
                <p><strong><?php _e('Tip:', 'foxentry-integration'); ?></strong> <?php _e('API klíč vypadá podobně jako "fox_1234567890abcdef" a najdete ho v sekci API klíče', 'foxentry-integration'); ?></p>
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
            
            <h2><?php _e('Použití', 'foxentry-integration'); ?></h2>
            <h3><?php _e('Shortcodes:', 'foxentry-integration'); ?></h3>
            <p><code>[foxentry_validator type="email"]</code> - <?php _e('Validátor emailu', 'foxentry-integration'); ?></p>
            <p><code>[foxentry_validator type="phone"]</code> - <?php _e('Validátor telefonu', 'foxentry-integration'); ?></p>
            <p><code>[foxentry_validator type="address"]</code> - <?php _e('Validátor adresy', 'foxentry-integration'); ?></p>
            <p><code>[foxentry_promo]</code> - <?php _e('Propagační banner', 'foxentry-integration'); ?></p>
            
            <h3><?php _e('Test API klíče:', 'foxentry-integration'); ?></h3>
            <button type="button" id="test-api" class="button"><?php _e('Otestovat API klíč', 'foxentry-integration'); ?></button>
            <div id="api-test-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
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
    
    public function promo_shortcode($atts) {
        $atts = shortcode_atts(array(
            'width' => '336',
            'height' => '280'
        ), $atts);
        
        return sprintf(
            '<div class="foxentry-promo">
                <a href="https://foxentry.com/?aff=%s" target="_blank">
                    <img src="https://cdn.foxentry.cz/promo/purple_%sx%s.jpg" 
                         width="%s" height="%s" 
                         alt="Foxentry - Validace dat" 
                         style="max-width: 100%%; height: auto;" />
                </a>
            </div>',
            FOXENTRY_AFFILIATE_CODE,
            $atts['width'], $atts['height'],
            esc_attr($atts['width']), esc_attr($atts['height'])
        );
    }
    
    public function ajax_validate() {
        // Bezpečnostní kontrola
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_validate')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        $value = sanitize_text_field($_POST['value']);
        $type = sanitize_text_field($_POST['type']);
        
        if (empty($this->api_key)) {
            wp_send_json_error(__('API klíč není nastaven', 'foxentry-integration'));
        }
        
        // Kontrola cache
        $cache_key = 'foxentry_' . md5($type . $value);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            wp_send_json_success($cached_result);
        }
        
        // Foxentry validace pomocí REST API
        $result = $this->validate_with_foxentry_api($type, $value);
        
        if ($result) {
            // Uložení do cache
            $cache_duration = get_option('foxentry_cache_duration', 3600);
            set_transient($cache_key, $result, $cache_duration);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Chyba při validaci', 'foxentry-integration'));
        }
    }
    
    private function validate_with_foxentry_api($type, $value) {
        $endpoints = array(
            'email' => 'https://api.foxentry.com/email/validate',
            'phone' => 'https://api.foxentry.com/phone/validate',
            'address' => 'https://api.foxentry.com/location/validate'
        );
        
        if (!isset($endpoints[$type])) {
            return false;
        }
        
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
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['response']['result'])) {
            return false;
        }
        
        return $this->parse_foxentry_response($data['response'], $type);
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
        switch ($proposal) {
            case 'valid':
                return sprintf(__('%s je platný', 'foxentry-integration'), ucfirst($type));
                
            case 'invalid':
                return sprintf(__('%s není platný', 'foxentry-integration'), ucfirst($type));
                
            case 'validWithSuggestion':
                $message = sprintf(__('%s je platný', 'foxentry-integration'), ucfirst($type));
                if (!empty($suggestions)) {
                    $message .= ' ' . __('Doporučujeme:', 'foxentry-integration') . ' ' . implode(', ', array_column($suggestions, $type));
                }
                return $message;
                
            case 'invalidWithCorrection':
                if ($corrected_data) {
                    return sprintf(__('%s opraveno na: %s', 'foxentry-integration'), ucfirst($type), $corrected_data[$type]);
                }
                return sprintf(__('%s není platný', 'foxentry-integration'), ucfirst($type));
                
            default:
                return $is_valid ? 
                    sprintf(__('%s je platný', 'foxentry-integration'), ucfirst($type)) : 
                    sprintf(__('%s není platný', 'foxentry-integration'), ucfirst($type));
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
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_foxentry-settings') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
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
    
}

// Spuštění pluginu
new FoxentryIntegration();

// Přidání nastavení odkazu do seznamu pluginů
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=foxentry-settings') . '">' . __('Nastavení', 'foxentry-integration') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
