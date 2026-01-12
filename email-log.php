<?php
/**
 * Plugin Name: Email Log
 * Plugin URI: https://joandev.com/email-log
 * Description: Registra cada correo enviado desde WordPress
 * Author: Joan Dev & Tech
 * Version: 1.0.1
 * Author URI: https://joandev.com
 * Text Domain: email-log-joan-dev
 * License: GPLv2 or later
 * Requires at least: 4.0
 * Tested up to: 6.8
 * Requires PHP: 7.3
 * Location: Galiza, Spain
 * Update URI: https://joandev.com/email-log
 */

/**
 * Copyright 2025 Joan Dev & Tech — Galiza, Spain
 *
 * Este programa es software libre; puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General de GNU, versión 2,
 * publicada por la Free Software Foundation.
 * Este programa se distribuye con la esperanza de que sea útil,
 * pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de
 * COMERCIABILIDAD o IDONEIDAD PARA UN PROPÓSITO PARTICULAR.
 * Consulte la Licencia Pública General de GNU para más detalles.
 * Debería haber recibido una copia de la Licencia Pública General de GNU
 * junto con este programa.
 */

defined('ABSPATH') || exit;

define('EMAIL_LOG_FILE', __FILE__);
define('EMAIL_LOG_URL', trailingslashit(plugins_url('', __FILE__)));
define('EMAIL_LOG_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('EMAIL_LOG_URI', trailingslashit(plugin_dir_url(__FILE__)));

function load_email_log($plugin_file)
{
    global $email_log;

    $plugin_dir = plugin_dir_path($plugin_file);
    if (is_admin()) {
    }
    // setup autoloader.
    require_once 'include/EmailLogAutoloader.php';
    $loader = new \EmailLog\EmailLogAutoloader();
    $loader->add_namespace('EmailLog', $plugin_dir . 'include');
    $loader->add_namespace('Sudar\\WPSystemInfo', $plugin_dir . 'vendor/sudar/wp-system-info/src/');
    if (file_exists($plugin_dir . 'tests/')) {
        $loader->add_namespace('EmailLog', $plugin_dir . 'tests/wp-tests');
    }
    $loader->add_file($plugin_dir . 'include/Util/helper.php');
    $loader->register();

    $email_log = new \EmailLog\Core\EmailLog($plugin_file, $loader, new \EmailLog\Core\DB\TableManager());
    $email_log->add_loadie(new \EmailLog\Core\EmailLogger());
    $email_log->add_loadie(new \EmailLog\Core\UI\UILoader(), true);
    $email_log->add_loadie(new \EmailLog\Core\Request\NonceChecker());
    $email_log->add_loadie(new \EmailLog\Core\Request\LogListAction());

    $capability_giver = new \EmailLog\Core\AdminCapabilityGiver();
    $email_log->add_loadie($capability_giver);

    register_activation_hook($plugin_file, array($email_log->table_manager, 'on_activate'));
    register_activation_hook($plugin_file, array($capability_giver, 'add_cap_to_admin'));
    add_action('plugins_loaded', array($email_log, 'load'), 101);
}

function email_log_plugin_version()
{
    $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');
    return $plugin_data['version'];
}

/**
 * Retorna la instancia global del plugin Email Log.
 *
 * @return \EmailLog\Core\EmailLog
 */
function email_log()
{
    global $email_log;
    return $email_log;
}

load_email_log(__FILE__);
