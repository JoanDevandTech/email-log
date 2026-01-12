<?php
/**
 * Maneja la instalación y la creación de tablas de la BD.
 */

defined('ABSPATH') || exit;

/**
 * Clase de ayuda para crear y mantener tablas.
 */
class Email_Log_Init
{

	/**
	 * Realiza la activación según sea multisite o no.
	 *
	 * @global object $wpdb
	 */
	public static function on_activate($network_wide)
	{
		global $wpdb;

		if (is_multisite() && $network_wide) {
			// almacena el ID del blog actual
			$current_blog = $wpdb->blogid;

			// obtiene todos los blogs de la red y activa el plugin en cada uno
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs"); //phpcs:ignore
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				self::create_emaillog_table();
				restore_current_blog();
			}
		} else {
			self::create_emaillog_table();
		}
	}

	/**
	 * Crea la tabla de registros cuando se crea un nuevo blog.
	 */
	public static function on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta)
	{
		if (is_plugin_active_for_network('email-log/email-log.php')) {
			switch_to_blog($blog_id);
			self::create_emaillog_table();
			restore_current_blog();
		}
	}

	/**
	 * Elimina la tabla de registros cuando se borra un blog.
	 *
	 * @global object $wpdb
	 * @param  array  $tables List of tables to be deleted
	 * @return array  $tables Modified list of tables to be deleted
	 */
	public static function on_delete_blog($tables)
	{
		global $wpdb;
		$tables[] = $wpdb->prefix . EmailLog::TABLE_NAME;
		return $tables;
	}

	/**
	 * Crea la tabla de registros de correo.
	 *
	 * @global object $wpdb
	 */
	private static function create_emaillog_table()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . EmailLog::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		if ($wpdb->get_var("show tables like '{$table_name}'") != $table_name) { //phpcs:ignore

			$sql = 'CREATE TABLE ' . $table_name . ' ( 
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				to_email VARCHAR(100) NOT NULL,
				subject VARCHAR(250) NOT NULL,
				message TEXT NOT NULL,
				headers TEXT NOT NULL,
				attachments TEXT NOT NULL,
				sent_date timestamp NOT NULL,
				PRIMARY KEY  (id)
			) ' . $charset_collate . ' ;';

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			add_option(EmailLog::DB_OPTION_NAME, EmailLog::DB_VERSION);
		}
	}
}

register_activation_hook(EMAIL_LOG_PLUGIN_FILE, array('Email_Log_Init', 'on_activate'));
add_action('wpmu_new_blog', array('Email_Log_Init', 'on_create_blog'), 10, 6);
add_filter('wpmu_drop_tables', array('Email_Log_Init', 'on_delete_blog'));
