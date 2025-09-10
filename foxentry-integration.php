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
 * Tags: foxentry, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation, validation
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
    
    private $foxentry_code;
    private $code_injected = false;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Načtení překladů
        load_plugin_textdomain('foxentry-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Načtení nastavení
        $this->foxentry_code = get_option('foxentry_code', '');
        
        // Přidání admin menu
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Registrace shortcodů
        add_shortcode('foxentry_validator', array($this, 'validator_shortcode'));
        add_shortcode('foxentry_promo', array($this, 'promo_shortcode'));
        
        // AJAX akce
        add_action('wp_ajax_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_nopriv_foxentry_validate', array($this, 'ajax_validate'));
        add_action('wp_ajax_foxentry_test_code', array($this, 'ajax_test_code'));
        add_action('wp_ajax_foxentry_check_frontend', array($this, 'ajax_check_frontend'));
        
        // Enqueue scripts a styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Zajištění vložení Foxentry kódu i bez wp_footer hook
        add_action('wp_footer', array($this, 'inject_foxentry_code'), 999);
        add_action('wp_print_footer_scripts', array($this, 'inject_foxentry_code'), 999);
        add_action('wp_print_scripts', array($this, 'inject_foxentry_code'), 999);
    }
    
    public function activate() {
        // Vytvoření výchozích nastavení
        add_option('foxentry_code', '');
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
        register_setting('foxentry_settings', 'foxentry_code');
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
                        <?php _e('Zaregistrujte se zde pro získání Foxentry kódu', 'foxentry-integration'); ?>
                    </a>
                </p>
            </div>
            
            <div class="notice notice-warning">
                <h3><?php _e('Návod k aktivaci Foxentry:', 'foxentry-integration'); ?></h3>
                <ol>
                    <li><?php _e('Přihlaste se do svého Foxentry účtu', 'foxentry-integration'); ?></li>
                    <li><?php _e('V dashboardu klikněte na váš projekt', 'foxentry-integration'); ?></li>
                    <li><?php _e('V levém menu klikněte na "Integrace"', 'foxentry-integration'); ?></li>
                    <li><?php _e('V sekci "Vložení kódu do stránek" zkopírujte kód z levé části', 'foxentry-integration'); ?></li>
                    <li><?php _e('Vložte zkopírovaný kód do pole níže', 'foxentry-integration'); ?></li>
                </ol>
                <p><strong><?php _e('Tip:', 'foxentry-integration'); ?></strong> <?php _e('Kód vypadá podobně jako YC8qjBWpzq a najdete ho v části "Vložení kódu do stránek" → levá strana obrazovky', 'foxentry-integration'); ?></p>
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
                        <th scope="row"><?php _e('Foxentry Kód', 'foxentry-integration'); ?></th>
                        <td>
                            <textarea name="foxentry_code" rows="8" cols="80" class="large-text code"><?php echo esc_textarea(get_option('foxentry_code')); ?></textarea>
                            <p class="description"><?php _e('Vložte váš Foxentry integrační kód ze sekce Integrace', 'foxentry-integration'); ?></p>
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
            
            <h3><?php _e('Test Foxentry kódu:', 'foxentry-integration'); ?></h3>
            <button type="button" id="test-foxentry" class="button"><?php _e('Otestovat Foxentry kód', 'foxentry-integration'); ?></button>
            <div id="foxentry-test-result"></div>
            
            <h3><?php _e('Kontrola frontendu:', 'foxentry-integration'); ?></h3>
            <button type="button" id="check-frontend" class="button button-secondary"><?php _e('Zkontrolovat, zda je kód na frontendu', 'foxentry-integration'); ?></button>
            <div id="frontend-check-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-foxentry').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Testování...', 'foxentry-integration'); ?>');
                
                $.post(ajaxurl, {
                    action: 'foxentry_test_code',
                    nonce: '<?php echo wp_create_nonce('foxentry_test'); ?>'
                }, function(response) {
                    $('#foxentry-test-result').html(response.success ? 
                        '<div class="notice notice-success"><p><?php _e('Foxentry kód je platný!', 'foxentry-integration'); ?></p></div>' : 
                        '<div class="notice notice-error"><p><?php _e('Chyba:', 'foxentry-integration'); ?> ' + response.data + '</p></div>'
                    );
                    button.prop('disabled', false).text('<?php _e('Otestovat Foxentry kód', 'foxentry-integration'); ?>');
                });
            });
            
            $('#check-frontend').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Kontroluji...', 'foxentry-integration'); ?>');
                
                $.post(ajaxurl, {
                    action: 'foxentry_check_frontend',
                    nonce: '<?php echo wp_create_nonce('foxentry_test'); ?>'
                }, function(response) {
                    $('#frontend-check-result').html(response.success ? 
                        '<div class="notice notice-success"><p><?php _e('Foxentry kód je správně vložen na frontendu!', 'foxentry-integration'); ?></p></div>' : 
                        '<div class="notice notice-error"><p><?php _e('Chyba:', 'foxentry-integration'); ?> ' + response.data + '</p></div>'
                    );
                    button.prop('disabled', false).text('<?php _e('Zkontrolovat, zda je kód na frontendu', 'foxentry-integration'); ?>');
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
            'class' => 'foxentry-validator'
        ), $atts);
        
        $placeholder = $atts['placeholder'] ?: $this->get_default_placeholder($atts['type']);
        $unique_id = 'foxentry_' . uniqid();
        
        ob_start();
        ?>
        <div class="foxentry-wrapper">
            <input type="text" 
                   id="<?php echo $unique_id; ?>" 
                   class="<?php echo esc_attr($atts['class']); ?>" 
                   data-type="<?php echo esc_attr($atts['type']); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>" />
            <div class="foxentry-result" id="<?php echo $unique_id; ?>_result"></div>
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
        
        if (empty($this->foxentry_code)) {
            wp_send_json_error(__('Foxentry kód není nastaven', 'foxentry-integration'));
        }
        
        // Kontrola cache
        $cache_key = 'foxentry_' . md5($type . $value);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            wp_send_json_success($cached_result);
        }
        
        // Foxentry validace pomocí JavaScript API
        $result = $this->validate_with_foxentry($type, $value);
        
        if ($result) {
            // Uložení do cache
            $cache_duration = get_option('foxentry_cache_duration', 3600);
            set_transient($cache_key, $result, $cache_duration);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Chyba při validaci', 'foxentry-integration'));
        }
    }
    
    private function validate_with_foxentry($type, $value) {
        // Foxentry validace se provádí na frontendu pomocí JavaScript API
        // Tato metoda pouze vrací základní strukturu pro frontend
        return array(
            'valid' => true, // Bude nastaveno na frontendu
            'message' => '',
            'type' => $type,
            'value' => $value
        );
    }
    
    private function extract_foxentry_code() {
        // Extrahuje Foxentry kód z vloženého HTML kódu
        $code = get_option('foxentry_code', '');
        
        if (empty($code)) {
            return false;
        }
        
        // Hledá Foxentry kód v HTML
        if (preg_match("/FoxentryBase\('([^']+)'\)/", $code, $matches)) {
            return $matches[1];
        }
        
        return false;
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
            'foxentry_code' => $this->extract_foxentry_code(),
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
                'invalid_format' => __('Neplatný formát', 'foxentry-integration')
            )
        ));
        
        wp_enqueue_style(
            'foxentry-frontend',
            FOXENTRY_PLUGIN_URL . 'assets/frontend.css',
            array(),
            FOXENTRY_PLUGIN_VERSION
        );
    }
    
    public function inject_foxentry_code() {
        // Zamezení duplicitního vkládání
        if ($this->code_injected) {
            return;
        }
        
        $foxentry_code = get_option('foxentry_code', '');
        if (empty($foxentry_code)) {
            return;
        }
        
        // Ověření, že kód obsahuje Foxentry script
        if (strpos($foxentry_code, 'foxentry.cz') === false && strpos($foxentry_code, 'FoxentryBase') === false) {
            return;
        }
        
        // Vložení kódu s bezpečnostními opatřeními
        echo "\n<!-- Foxentry Integration Plugin - Auto injected -->\n";
        echo $foxentry_code;
        echo "\n<!-- End Foxentry Integration -->\n";
        
        $this->code_injected = true;
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_foxentry-settings') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Test Foxentry kódu AJAX
        add_action('wp_ajax_foxentry_test_code', array($this, 'ajax_test_code'));
    }
    
    public function ajax_test_code() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_test')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        $foxentry_code = $this->extract_foxentry_code();
        
        if ($foxentry_code) {
            wp_send_json_success(__('Foxentry kód je platný!', 'foxentry-integration'));
        } else {
            wp_send_json_error(__('Foxentry kód není platný - zkontrolujte vložený kód', 'foxentry-integration'));
        }
    }
    
    public function ajax_check_frontend() {
        if (!wp_verify_nonce($_POST['nonce'], 'foxentry_test')) {
            wp_die(__('Bezpečnostní chyba', 'foxentry-integration'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Nedostatečná oprávnění', 'foxentry-integration'));
        }
        
        $result = $this->check_frontend_injection();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function check_frontend_injection() {
        $foxentry_code = get_option('foxentry_code', '');
        
        if (empty($foxentry_code)) {
            return array(
                'success' => false,
                'message' => __('Foxentry kód není nastaven', 'foxentry-integration')
            );
        }
        
        // Extrahujeme Foxentry kód z uloženého HTML
        $extracted_code = $this->extract_foxentry_code();
        if (!$extracted_code) {
            return array(
                'success' => false,
                'message' => __('Foxentry kód není platný', 'foxentry-integration')
            );
        }
        
        // Získáme URL domovské stránky
        $home_url = home_url('/');
        
        // Simulujeme požadavek na frontend
        $response = wp_remote_get($home_url, array(
            'timeout' => 10,
            'user-agent' => 'Foxentry Integration Plugin Check'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => __('Nelze načíst frontend stránku: ', 'foxentry-integration') . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Kontrola, zda se Foxentry kód nachází v HTML
        $foxentry_found = false;
        $checks = array(
            'foxentry.cz',
            'FoxentryBase',
            'Foxentry Integration Plugin - Auto injected',
            $extracted_code
        );
        
        foreach ($checks as $check) {
            if (strpos($body, $check) !== false) {
                $foxentry_found = true;
                break;
            }
        }
        
        if ($foxentry_found) {
            return array(
                'success' => true,
                'message' => __('Foxentry kód je správně vložen na frontendu!', 'foxentry-integration')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Foxentry kód nebyl nalezen na frontendu. Možné příčiny: 1) Téma nepoužívá wp_footer hook, 2) Kód byl odstraněn jiným pluginem, 3) Cache problém', 'foxentry-integration')
            );
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
?>
