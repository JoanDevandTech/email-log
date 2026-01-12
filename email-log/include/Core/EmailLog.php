<?php

namespace EmailLog\Core;

use EmailLog\Core\DB\TableManager;
use EmailLog\EmailLogAutoloader;

/**
 * Clase principal del plugin.
 *
 * @since Génesis
 */
class EmailLog
{
    /**
     * Bandera para saber si el plugin está cargado.
     *
     * @since 2.0
     * @access private
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Bandera para sobreescribir la API de plugins.
     *
     * @since 2.4.5
     * @access private
     *
     * @var bool
     */
    private $plugins_api_overridden = false;

    /**
     * Ruta del archivo del plugin.
     *
     * @since 2.0
     * @access private
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Ruta del directorio donde se almacenan las traducciones.
     *
     * @since 2.0
     *
     * @var string
     */
    public $translations_path;

    /**
     * Autocargador.
     *
     * @var \EmailLog\EmailLogAutoloader
     */
    public $loader;

    /**
     * Gestor de tablas de base de datos.
     *
     * @since 2.0
     *
     * @var \EmailLog\Core\DB\TableManager
     */
    public $table_manager;

    /**
     * Lista de componentes cargables.
     *
     * @var Loadie[]
     */
    private $loadies = array();
    private $loadies_init = array();
    /**
     * Inicializa el plugin.
     *
     * @param string             $file          Archivo del plugin.
     * @param EmailLogAutoloader $loader        Autocargador de EmailLog.
     * @param TableManager       $table_manager Gestor de tablas.
     */
    public function __construct($file, $loader, $table_manager)
    {
        $this->plugin_file   = $file;
        $this->loader        = $loader;
        $this->table_manager = $table_manager;

        $this->add_loadie($table_manager);

        $this->translations_path = dirname(plugin_basename($this->plugin_file)) . '/languages/';
    }

    /**
     * Añade un componente (Loadie) de Email Log.
     * El método `load()` del componente será llamado cuando Email Log se cargue.
     *
     * @param \EmailLog\Core\Loadie $loadie Componente a cargar.
     *
     * @return bool Falso si Email Log ya está cargado o si $loadie no es del tipo `Loadie`. Verdadero en caso contrario.
     */
    public function add_loadie($loadie, $loadie_init = false)
    {
        if ($this->loaded) {
            return false;
        }

        if (! $loadie instanceof Loadie) {
            return false;
        }

        if ($loadie_init === true) {
            $this->loadies_init[] = $loadie;
        } else {
            $this->loadies[] = $loadie;
        }

        return true;
    }

    /**
     * Carga el plugin.
     */
    public function load()
    {
        if ($this->loaded) {
            return;
        }

        load_plugin_textdomain('email-log', false, $this->translations_path);

        $this->table_manager->load();

        foreach ($this->loadies as $loadie) {
            $loadie->load();
        }

        foreach ($this->loadies_init as $loadie_init) {
            add_action('init', array($loadie_init, 'load'));
        }

        $this->loaded = true;

        $options = get_option('email-log-core');

        /**
         * Email Log plugin loaded.
         *
         * @since 2.0
         */
        do_action('el_loaded');

        
    }

    function admin_enqueue_scripts()
    {
        $js_localize = array(
            'undocumented_error' => __('An undocumented error has occurred. Please refresh the page and try again.', 'email-log'),
            'documented_error' => __('An error has occurred.', 'email-log'),
            'plugin_name' => 'Email Log PRO',
            'url' => EMAIL_LOG_URL,
            'icon_url' => EMAIL_LOG_URI . 'assets/img/loader-icon.png',
            'settings_url' => admin_url('admin.php?page=email-log'),
            'is_plugin_page' => $this->is_plugin_page(),
            'home_url' => get_home_url(),
            'loading_icon_url' => EMAIL_LOG_URI . 'assets/img/loading-icon.png',
            'email_test_url' => EMAIL_LOG_URI . 'assets/img/emaillog-test.gif',
            'el_nonce' => wp_create_nonce('el-email-test'),
            'wp301_install_url' => add_query_arg(array('action' => 'emaillog_install_wp301', '_wpnonce' => wp_create_nonce('install_wp301'), 'rnd' => wp_rand()), admin_url('admin.php')),
        );

        if ($this->is_plugin_page()) {
            wp_enqueue_style('wp-jquery-ui-dialog');

            wp_enqueue_script('jquery-ui-position');
            wp_enqueue_script("jquery-effects-core");
            wp_enqueue_script("jquery-effects-blind");
            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script('jquery-ui-dialog');

            wp_enqueue_script('email-log', EMAIL_LOG_URL . 'assets/js/email-log-admin.js', array('jquery'), $this->get_version(), true);
            wp_enqueue_style('email-log', EMAIL_LOG_URL . 'assets/css/email-log-admin.css', array(), $this->get_version());
            wp_localize_script('email-log', 'wp_email_log', $js_localize);
        }
    }

    // auto download / install / activate WP 301 Redirects plugin
    public function install_wp301()
    {
        check_ajax_referer('install_wp301');

        if (false === current_user_can('manage_options')) {
            wp_die('Sorry, you have to be an admin to run this action.');
        }

        $plugin_slug = 'eps-301-redirects/eps-301-redirects.php';
        $plugin_zip = 'https://downloads.wordpress.org/plugin/eps-301-redirects.latest-stable.zip';

        @include_once ABSPATH . 'wp-admin/includes/plugin.php';
        @include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        @include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        @include_once ABSPATH . 'wp-admin/includes/file.php';
        @include_once ABSPATH . 'wp-admin/includes/misc.php';
        echo '<style>
		body{
			font-family: sans-serif;
			font-size: 14px;
			line-height: 1.5;
			color: #444;
		}
		</style>';

        echo '<div style="margin: 20px; color:#444;">';
        echo 'If things are not done in a minute <a target="_parent" href="' . esc_url(admin_url('plugin-install.php?s=301%20redirects%20webfactory&tab=search&type=term')) . '">install the plugin manually via Plugins page</a><br><br>';
        echo 'Starting ...<br><br>';

        wp_cache_flush();
        $upgrader = new \Plugin_Upgrader();
        echo 'Check if WP 301 Redirects is already installed ... <br />';
        if ($this->is_plugin_installed($plugin_slug)) {
            echo 'WP 301 Redirects is already installed! <br /><br />Making sure it\'s the latest version.<br />';
            $upgrader->upgrade($plugin_slug);
            $installed = true;
        } else {
            echo 'Installing WP 301 Redirects.<br />';
            $installed = $upgrader->install($plugin_zip);
        }
        wp_cache_flush();

        if (!is_wp_error($installed) && $installed) {
            echo 'Activating WP 301 Redirects.<br />';
            $activate = activate_plugin($plugin_slug);

            if (is_null($activate)) {
                echo 'WP 301 Redirects Activated.<br />';

                echo '<script>setTimeout(function() { top.location = "' . esc_url(admin_url('options-general.php?page=eps_redirects')) . '"; }, 1000);</script>';
                echo '<br>If you are not redirected in a few seconds - <a href="' . esc_url(admin_url('options-general.php?page=eps_redirects')) . '" target="_parent">click here</a>.';
            }
        } else {
            echo 'Could not install WP 301 Redirects. You\'ll have to <a target="_parent" href="' . esc_url(admin_url('plugin-install.php?s=301%20redirects%20webfactory&tab=search&type=term')) . '">download and install manually</a>.';
        }

        echo '</div>';
    } // install_wp301

    /**
     * Check if given plugin is installed
     *
     * @param [string] $slug Plugin slug
     * @return boolean
     */
    static function is_plugin_installed($slug)
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();

        if (!empty($all_plugins[$slug])) {
            return true;
        } else {
            return false;
        }
    } // is_plugin_installed

    public function is_plugin_page()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $current_screen = get_current_screen();
        if (!empty($current_screen) && in_array($current_screen->id, array('toplevel_page_email-log', 'toplevel_page_email-log-license', 'email-log_page_email-log-settings', 'email-log_page_email-log-license', 'email-log_page_email-log-monitor'))) {
            return true;
        } else {
            return false;
        }
    } // is_plugin_page

    /**
     * Plugin API has been overridden.
     *
     * @since 2.4.5
     */
    public function plugin_api_overridden()
    {
        $this->plugins_api_overridden = true;
    }

    /**
     * Has the plugin API have been overridden?
     *
     * @since 2.4.5
     *
     * @return bool True if overridden, False otherwise.
     */
    public function is_plugin_api_overridden()
    {
        return $this->plugins_api_overridden;
    }

    /**
     * Return Email Log version.
     *
     * @return string Email Log Version.
     */
    public function get_version()
    {
        return email_log_plugin_version();
    }

    /**
     * Return the Email Log plugin directory path.
     *
     * @return string Plugin directory path.
     */
    public function get_plugin_path()
    {
        return plugin_dir_path($this->plugin_file);
    }

    /**
     * Return the Email Log plugin file.
     *
     * @since 2.0.0
     *
     * @return string Plugin directory path.
     */
    public function get_plugin_file()
    {
        return $this->plugin_file;
    }

    public static function wp_kses_wf($html)
    {
        add_filter('safe_style_css', function ($styles) {
            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width',
                'display',
            );

            foreach ($styles_wf as $style_wf) {
                $styles[] = $style_wf;
            }
            return $styles;
        });

        $allowed_tags = wp_kses_allowed_html('post');
        $allowed_tags['input'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'size' => true,
            'disabled' => true
        );

        $allowed_tags['textarea'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'cols' => true,
            'rows' => true,
            'disabled' => true,
            'autocomplete' => true
        );

        $allowed_tags['select'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'multiple' => true,
            'disabled' => true
        );

        $allowed_tags['option'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true
        );
        $allowed_tags['optgroup'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true,
            'label' => true
        );

        $allowed_tags['a'] = array(
            'href' => true,
            'data-*' => true,
            'class' => true,
            'style' => true,
            'id' => true,
            'target' => true,
            'data-*' => true,
            'role' => true,
            'aria-controls' => true,
            'aria-selected' => true,
            'disabled' => true
        );

        $allowed_tags['div'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['li'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['span'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'aria-hidden' => true
        );

        $allowed_tags['style'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'style' => true
        );

        $allowed_tags['fieldset'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'style' => true
        );

        $allowed_tags['link'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'rel' => true,
            'href' => true,
            'media' => true,
            'style' => true
        );

        $allowed_tags['form'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true,
            'style' => true
        );

        $allowed_tags['script'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'src' => true,
            'style' => true
        );

        $allowed_tags['table'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'cellpadding' => true,
            'cellspacing' => true,
            'border' => true,
            'style' => true
        );

        $allowed_tags['canvas'] = array(
            'class' => true,
            'id' => true,
            'style' => true
        );

        if(empty($html)){
            echo '';
        } else {
            echo wp_kses($html, $allowed_tags);
        }

        add_filter('safe_style_css', function ($styles) {
            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width'
            );

            foreach ($styles_wf as $style_wf) {
                if (($key = array_search($style_wf, $styles)) !== false) {
                    unset($styles[$key]);
                }
            }
            return $styles;
        });
    }
}
