<?php namespace EmailLog\Core\UI\Setting;

use  \EmailLog\Core\UI\Page\SettingsPage;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Todos los ajustes básicos de Email Log.
 *
 * @since 2.1.0
 */
class CoreSetting extends Setting {

	protected function initialize() {
		$this->section->id          = 'email-log-core';
		$this->section->title       = __( 'Email Log Settings', 'email-log' );
		$this->section->option_name = 'email-log-core';

		$this->section->field_labels = array(
			'allowed_user_roles'    => __( 'Allowed User Roles', 'email-log' ),
			'remove_on_uninstall'   => __( 'Remove Data on Uninstall?', 'email-log' ),
			'hide_dashboard_widget' => __( 'Disable Dashboard Widget', 'email-log' ),
			'db_size_notification'  => __( 'Database Size Notification', 'email-log' ),
			'auto_delete_days'      => __( 'Auto Delete Logs', 'email-log' ),
		);

		$this->section->default_value = array(
			'allowed_user_roles'    => array(),
			'remove_on_uninstall'   => '',
			'hide_dashboard_widget' => false,
			'db_size_notification'  => array(
				'notify'                    => false,
				'admin_email'               => '',
				'logs_threshold'            => '',
				'log_threshold_met'         => false,
				'threshold_email_last_sent' => false,
			),
			'auto_delete_days'      => 0,
		);

		$this->load();
	}

	/**
	 * Sobrescribe `load` para que los ajustes básicos se muestren primero.
	 *
	 * @inheritdoc
	 */
	public function load() {
		add_filter( 'el_setting_sections', array( $this, 'register' ), 9 );

		add_action( 'add_option_' . $this->section->option_name, array( $this, 'allowed_user_roles_added' ), 10, 2 );
		add_action( 'update_option_' . $this->section->option_name, array( $this, 'allowed_user_roles_changed' ), 10, 2 );

		add_action( 'el_email_log_inserted', array( $this, 'verify_email_log_threshold' ) );
		add_action( 'el_trigger_notify_email_when_log_threshold_met', array( $this, 'trigger_threshold_met_notification_email' ) );
	}

	/**
	 * Renderiza el ajuste `Roles de usuario permitidos`.
	 *
	 * @param array $args Arguments.
	 */
	public function render_allowed_user_roles_settings( $args ) {
		$option         = $this->get_value();
		$selected_roles = $option[ $args['id'] ];

		$field_name = $this->section->option_name . '[' . $args['id'] . '][]';

		$available_roles = get_editable_roles();
		unset( $available_roles['administrator'] );
		?>

		<p>
			<input type="checkbox" checked disabled><?php esc_html_e( 'Administrator', 'email-log' ); ?>
		</p>

		<?php foreach ( $available_roles as $role_id => $role ) : ?>
			<p>
				<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $role_id ); ?>"
					<?php \EmailLog\Util\checked_array( $selected_roles, $role_id ); ?>>

				<?php echo esc_html($role['name']); ?>
			</p>
		<?php endforeach; ?>

		<p>
			<em>
				<?php \EmailLog\Core\EmailLog::wp_kses_wf(__( '<strong>Note:</strong> Users with the above User Roles can view Email Logs.', 'email-log' )); ?>
				<?php esc_html_e( 'Administrator role always has access and cannot be disabled.', 'email-log' ); ?>
			</em>
		</p>

		<?php
	}

	/**
	 * Sanitiza el ajuste de roles de usuario permitidos.
	 *
	 * @param array $roles User selected user roles.
	 *
	 * @return array Sanitized user roles.
	 */
	public function sanitize_allowed_user_roles( $roles ) {
		if ( ! is_array( $roles ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $roles );
	}

	/**
	 * Renderiza el ajuste `¿Eliminar datos al desinstalar?`.
	 *
	 * @param array $args
	 */
	public function render_remove_on_uninstall_settings( $args ) {
		$option      = $this->get_value();
		$remove_data = $option[ $args['id'] ];

		$field_name = $this->section->option_name . '[' . $args['id'] . ']';
		?>

		<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="true" <?php checked( 'true', $remove_data ); ?>>
		<?php esc_html_e( 'Check this box if you would like to completely remove all of its data when the plugin is deleted.', 'email-log' ) ?>
		<?php
	}

	/**
	 * Sanitiza el valor de eliminación al desinstalar.
	 *
	 * @param string $value User entered value.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_remove_on_uninstall( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Opción de roles de usuario permitidos añadida.
	 *
	 * @param string $option Option name.
	 * @param array  $value  Option value.
	 */
	public function allowed_user_roles_added( $option, $value ) {
		$this->allowed_user_roles_changed( array(), $value );
	}

	/**
	 * Opción de roles de usuario permitidos actualizada.
	 *
	 * Cambia las capacidades cuando los roles permitidos se modifican.
	 *
	 * @param array $old_value Old Value.
	 * @param array $new_value New Value.
	 */
	public function allowed_user_roles_changed( $old_value, $new_value ) {
		$old_roles = $this->get_user_roles( $old_value );
		$new_roles = $this->get_user_roles( $new_value );

		/**
		 * The user roles who can manage email log list is changed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $old_roles Old user roles.
		 * @param array $new_roles New user roles.
		 */
		do_action( 'el-log-list-manage-user-roles-changed', $old_roles, $new_roles );
	}

	/**
	 * Get User roles from option value.
	 *
	 * @access protected
	 *
	 * @param array $option Option value
	 *
	 * @return array User roles.
	 */
	protected function get_user_roles( $option ) {
		if ( ! array_key_exists( 'allowed_user_roles', $option ) ) {
			return array();
		}

		$user_roles = $option['allowed_user_roles'];
		if ( ! is_array( $user_roles ) ) {
			$user_roles = array( $user_roles );
		}

		return $user_roles;
	}

	/**
	 * Renders the Email Log `Remove Data on Uninstall?` settings.
	 *
	 * @param array $args
	 */
	public function render_hide_dashboard_widget_settings( $args ) {
		$option                = $this->get_value();
		$hide_dashboard_widget = $option[ $args['id'] ];

		$field_name = $this->section->option_name . '[' . $args['id'] . ']';
		?>

		<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="true" <?php checked( 'true', $hide_dashboard_widget ); ?>>
		<?php esc_html_e( 'Check this box if you would like to disable dashboard widget.', 'email-log' ) ?>

		<p>
			<em>
				<?php \EmailLog\Core\EmailLog::wp_kses_wf(__( '<strong>Note:</strong> Each users can also disable dashboard widget using screen options', 'email-log' )); ?>
			</em>
		</p>

		<?php
	}

	/**
	 * Renders the Email Log `Database Size Notification` settings.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args
	 */
	public function render_db_size_notification_settings( $args ) {
		$option                    = $this->get_value();
		$db_size_notification_data = $option[ $args['id'] ];

		$field_name = $this->section->option_name . '[' . $args['id'] . ']';
		// Since we store three different fields, give each field a unique name.
		$db_size_notification_field_name = $field_name . '[notify]';
		$admin_email_field_name          = $field_name . '[admin_email]';
		$logs_threshold_field_name       = $field_name . '[logs_threshold]';

		$email_log  = email_log();
		$logs_count = $email_log->table_manager->get_logs_count();

		$admin_email_input_field = sprintf(
			'<input type="email" name="%1$s" value="%2$s" size="35" />', esc_attr( $admin_email_field_name ), empty( $db_size_notification_data['admin_email'] ) ? get_option( 'admin_email', '' ) : esc_attr( $db_size_notification_data['admin_email'] ) );

		$logs_threshold_input_field = sprintf( '<input type="number" name="%1$s" placeholder="5000" value="%2$s" min="0" max="99999999" />',
			esc_attr( $logs_threshold_field_name ),
			empty( $db_size_notification_data['logs_threshold'] ) ? '' : esc_attr( $db_size_notification_data['logs_threshold'] )
		);
		?>

        <input type="checkbox" name="<?php echo esc_attr( $db_size_notification_field_name ); ?>" value="true" <?php
		checked( true, $db_size_notification_data['notify'] ?? false ); ?> />
		<?php
		// The values within each field are already escaped.
        /* translators: %1$s is the admin email input field html, %2$s number of logs input field */
		\EmailLog\Core\EmailLog::wp_kses_wf(sprintf( __( 'Notify %1$s if there are more than %2$s logs.', 'email-log' ),
			$admin_email_input_field,
			$logs_threshold_input_field
		));
		?>
        <p>
            <em>
				<?php
                /* translators: %1$s is the HTML bold "Note:", %2$s number of logs */
                \EmailLog\Core\EmailLog::wp_kses_wf(sprintf(__( '%1$s There are %2$s email logs currently logged in the database.', 'email-log' ),
					'<strong>Note:</strong>',
					'<strong>' . esc_attr( $logs_count ) . '</strong>'
				)); ?>
            </em>
        </p>
		<?php if ( ! empty( $db_size_notification_data['threshold_email_last_sent'] ) ) : ?>
            <p>
				<?php
                /* translators: %1$s is the last notification date, %2$s is the HTML Save in bold */
                \EmailLog\Core\EmailLog::wp_kses_wf(sprintf(__( 'Last notification email was sent on %1$s. Click %2$s button to reset sending the notification.', 'email-log' ),
					'<strong>' . get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $db_size_notification_data['threshold_email_last_sent'] ), \EmailLog\Util\get_user_defined_date_time_format() ) . '</strong>',
					'<b>Save</b>'
				)); ?>
            </p>
		<?php
		endif;
		/**
		 * After DB size notification setting in Settings page.
		 *
		 * @since 2.4.0
		 */
		do_action( 'el_after_db_size_notification_setting' );
	}

	/**
	 * Removes any additional keys set other than the ones in db_size_notification array.
	 *
	 * @since 2.3.0
	 *
	 * @param array $arr `db_size_notification` option array.
	 *
	 * @return array `db_size_notification` option array with keys removed other than the allowed.
	 */
	protected function restrict_array_to_db_size_notification_setting_keys( $arr ) {
		$allowed_keys = array_keys( $this->section->default_value['db_size_notification'] );
		$arr_keys     = array_keys( $arr );

		// Return the array when only the allowed keys exist.
		$intersecting_keys = array_intersect( $allowed_keys, $arr_keys );
		if ( count( $allowed_keys ) === count( $intersecting_keys ) ) {
			return $arr;
		}

		// Otherwise remove keys that aren't expected.
		$diff_keys = array_diff_key( $arr, $allowed_keys );
		foreach ( $diff_keys as $key ) {
			unset( $arr[ $key ] );
		}

		return $arr;
	}

	/**
	 * Sanitizes the db_size_notification option.
	 *
	 * @since 2.3.0
	 *
	 * @param array $db_size_notification_data
	 *
	 * @return array $db_size_notification_data
	 */
	public function sanitize_db_size_notification( $db_size_notification_data ) {
		$db_size_notification_data = $this->restrict_array_to_db_size_notification_setting_keys( $db_size_notification_data );

		foreach ( $db_size_notification_data as $setting => $value ) {
			if ( 'notify' === $setting ) {
				$db_size_notification_data[ $setting ] = \filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} elseif ( 'admin_email' === $setting ) {
				$db_size_notification_data[ $setting ] = \sanitize_email( $value );
			} elseif ( 'logs_threshold' === $setting ) {
				$db_size_notification_data[ $setting ] = absint( \sanitize_text_field( $value ) );
			}
		}

		// wp_parse_args won't merge nested array keys. So add the key here if it is not set.
		if ( ! array_key_exists( 'notify', $db_size_notification_data ) ) {
			$db_size_notification_data['notify'] = false;
		}
		if ( ! array_key_exists( 'log_threshold_met', $db_size_notification_data ) ) {
			$db_size_notification_data['log_threshold_met'] = false;
		}
		if ( ! array_key_exists( 'threshold_email_last_sent', $db_size_notification_data ) ) {
			$db_size_notification_data['threshold_email_last_sent'] = false;
		}

		return $db_size_notification_data;
	}

	/**
	 * Triggers the Cron to notify admin via email.
	 *
	 * Email is triggered only when the threshold setting is met.
	 *
	 * @since 2.3.0
	 */
	public function verify_email_log_threshold() {
		$cron_hook = 'el_trigger_notify_email_when_log_threshold_met';
		if ( ! wp_next_scheduled( $cron_hook ) ) {
			wp_schedule_event( time(), 'hourly', $cron_hook );
		}
	}

	/**
	 * Utility method to verify all the given keys exist in the given array.
	 *
	 * This method helps reduce the Cyclomatic Complexity in its parent method.
	 *
	 * @since 2.3.0
	 *
	 * @param array $arr  The array whose keys must be checked.
	 * @param array $keys All the required keys that the array must contain.
	 *
	 * @return bool
	 */
	protected function has_array_contains_required_keys( $arr, $keys ) {
		$has_keys = true;

		if ( ! is_array( $arr ) || ! is_array( $keys ) ) {
			return false;
		}

		foreach ( $arr as $key => $value ) {
			$has_keys = $has_keys && in_array( $key, $keys, true );
		}

		return $has_keys;
	}

	/**
	 * Send the Threshold notification email to the admin.
	 *
	 * @since 2.3.0
	 */
	public function trigger_threshold_met_notification_email() {
		$email_log  = email_log();
		$logs_count = absint( $email_log->table_manager->get_logs_count() );

		$setting_data = $this->get_value();

		// Return early.
		if ( ! array_key_exists( 'db_size_notification', $setting_data ) ) {
			return;
		}

		$db_size_notification_key  = 'db_size_notification';
		$db_size_notification_data = $setting_data[ $db_size_notification_key ];

		// Return early.
		$keys = array_keys( $this->section->default_value['db_size_notification'] );
		if ( ! $this->has_array_contains_required_keys( $db_size_notification_data, $keys ) ) {
			return;
		}

		$is_notification_enabled = $db_size_notification_data['notify'];
		$admin_email             = $db_size_notification_data['admin_email'];
		$logs_threshold          = absint( $db_size_notification_data['logs_threshold'] );
		$logs_threshold_met      = $db_size_notification_data['log_threshold_met'];

		// Ideally threshold cannot be 0. Also, skip sending email if it is already sent.
		if ( 0 === $logs_threshold || true === $logs_threshold_met ) {
			return;
		}

		if ( $logs_count < $logs_threshold ) {
			return;
		}

		$this->register_threshold_met_admin_notice();

		if ( $is_notification_enabled && is_email( $admin_email ) ) {
            /* translators: %s is the number of log entries */
			$subject = sprintf( __( 'Email Log Plugin: Your log threshold of %s has been met', 'email-log' ), $logs_threshold );
			$message = '<p>This email is generated by the Email Log plugin.</p>';
            $message .= '<p>Your log threshold has been met. You may manually delete the logs to keep your database table in size.</p>';

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			/**
			 * Filters Log threshold notification email subject.
			 *
			 * @since 2.3.0
			 *
			 * @param string $subject The email subject.
			 */
			$subject = apply_filters( 'el_log_threshold_met_notification_email_subject', $subject );

			/**
			 * Filters Log threshold notification email body.
			 *
			 * @since 2.3.0
			 *
			 * @param string $message        The email body.
			 * @param int    $logs_threshold The log threshold value set by the user.
			 */
			$message = apply_filters( 'el_log_threshold_met_notification_email_body', $message, $logs_threshold );

			wp_mail( $admin_email, $subject, $message, $headers );

			$setting_data[ $db_size_notification_key ]['log_threshold_met']         = true;
			$setting_data[ $db_size_notification_key ]['threshold_email_last_sent'] = time();
			\update_option( $this->section->option_name, $setting_data );
		}
	}

	/**
	 * Registers the Email Log threshold met admin notice.
	 *
	 * @since 2.3.0
	 */
	public function register_threshold_met_admin_notice() {
		add_action( 'admin_notices', array( $this, 'render_log_threshold_met_notice' ) );
	}

	/**
	 * Displays the Email Log threshold met admin notice.
	 *
	 * @since 2.3.0
	 */
	public function render_log_threshold_met_notice() {
		$email_log      = email_log();
		$logs_count     = absint( $email_log->table_manager->get_logs_count() );
        /* translators: %1$s is the number of log entries, %2$s admin settings link, %2$s wpemaillog.com website link */
		$notice_message = sprintf( __( 'Currently there are %1$s logged, which is more than the threshold that is set in the %2$s screen. You can delete some logs or increase the threshold.', 'email-log' ),
			$logs_count . _n( ' email log', ' email logs', $logs_count, 'email-log' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ) ) . '">settings</a> screen'
			 );
		?>
        <div class="notice notice-warning is-dismissible">
            <p><?php \EmailLog\Core\EmailLog::wp_kses_wf($notice_message); ?></p>
        </div>
		<?php
	}

    public function render_interval_settings( $args ) {
		$option = $this->get_value();
		?>
        <p><?php esc_html_e( 'Auto Delete Logs allows you to automatically delete logs that are older than specified interval (in days).', 'email-log' ); ?></p>
		<p><?php esc_html_e( 'Specify the interval beyond which the logs are to be auto deleted.', 'email-log' ); ?></p>
        <label>
            <input class="open-pro-dialog" name="<?php echo esc_attr( $this->section->option_name . '[' . $args['id'] . ']' ); ?>"
                   size="40"
                   type="text" value="365" disabled>
        </label>
        <br>
        <em> <?php esc_html_e( 'Specify the interval in days.', 'email-log' ); ?> </em>
		<?php
			/**
			 * After the Next Run setting is rendered in the Auto Delete Logs add-on.
			 *
			 * @since 1.1.1
			 */
			do_action( 'el_auto_delete_logs_after_next_run_setting' );
		?>

		<?php
	}

    public function render_monitor_emails_settings( $args ) {
		$options = get_option( 'email-log-core' );
        ?>
        <p><?php esc_html_e( 'This service checks that your WordPress site is able to send emails reliably. Once enabled, the plugin will automatically send a daily "heartbeat" email to our monitoring server. If no heartbeat is received within 24 hours, an alert will be sent to the email address you provide below. This way you\'ll know right away if your site\'s emails stop working (for example, due to SMTP misconfiguration or server issues). You can also use the "Test email delivery" button at any time to confirm that emails are being delivered correctly.', 'email-log' ); ?></p>
        <br />
		<label>
            <input data-feature="test-email" type="checkbox" class="open-upsell" name="<?php echo esc_attr( $this->section->option_name . '[' . $args['id'] . '][notify]' ); ?>" value="true" <?php
		checked( true, false ); ?> /> Notify

            <input name="<?php echo esc_attr( $this->section->option_name . '[' . $args['id'] . '][alerts_email]' ); ?>"
                   size="40"
                   type="email" class="open-upsell" data-feature="test-email" value="<?php echo esc_html(get_option('admin_email')); ?>" disabled>
        </label>
        <a class="button button-primary open-upsell" data-feature="test-email">Test email delivery now</a>
        <br>

        <p><em> <?php esc_html_e( 'Enter the email where you want to receive an alert if the monitor does not receive emails from your website.', 'email-log' ); ?> </em></p>
		<?php
	}

	public function sanitize_interval( $value ) {
		$value = absint( $value );

		return 0 !== $value ? $value : 365;
	}

    /**
	 * Render To field.
	 *
	 * @since  2.0.2
	 *
	 * @param array $args Args.
	 */
	public function render_to_settings( $args ) {
		$this->render_email_field( $args );
	}

	/**
	 * Render CC field.
	 *
	 * @since  2.0.2
	 *
	 * @param array $args Args.
	 */
	public function render_cc_settings( $args ) {
		$this->render_email_field( $args );
	}

	/**
	 * Render BCC field.
	 *
	 * @since  2.0.2
	 *
	 * @param array $args Args.
	 */
	public function render_bcc_settings( $args ) {
		$this->render_email_field( $args );
	}

	/**
	 * Render email field.
	 *
	 * @since 2.0.2 Protected method.
	 *
	 * @param array $args Args.
	 */
	protected function render_email_field( $args ) {
		$option = $this->get_value();
		?>
        <label>
            <input  class="open-pro-dialog" name="<?php echo esc_attr( $this->section->option_name . '[' . $args['id'] . ']' ); ?>" size="40"
                   type="text" value="" disabled>
        </label>
        <br>
        <em> <?php esc_html_e( 'You can enter multiple email address by separating them with comma.', 'email-log' ); ?> </em>
		<?php
	}

	public function render_auto_delete_days_settings( $args ) {
		$option = $this->get_value();
		$days   = isset( $option[ $args['id'] ] ) ? absint( $option[ $args['id'] ] ) : 0;

		$field_name = $this->section->option_name . '[' . $args['id'] . ']';
		?>
		<p><?php esc_html_e( 'Automatically delete email logs older than the specified number of days. Set to 0 to disable.', 'email-log' ); ?></p>
		<label>
			<input name="<?php echo esc_attr( $field_name ); ?>" size="10" type="number" min="0" max="9999" value="<?php echo esc_attr( $days ); ?>">
			<?php esc_html_e( 'days', 'email-log' ); ?>
		</label>
		<p><em><?php esc_html_e( 'Runs once daily via WP Cron. Set to 0 to disable.', 'email-log' ); ?></em></p>
		<?php
	}

	public function sanitize_auto_delete_days( $value ) {
		return absint( $value );
	}

	public function sanitize( $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		foreach ( $values as $key => $value ) {
            if(in_array($key, array('to', 'cc', 'bcc'))){
			    $values[ $key ] = \EmailLog\Util\sanitize_email( $value );
            }
		}

		return $values;
	}

}
