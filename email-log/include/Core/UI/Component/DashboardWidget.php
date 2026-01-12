<?php namespace EmailLog\Core\UI\Component;

use EmailLog\Core\Loadie;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/*
 * Widget que muestra información del registro de correos en el escritorio.
 *
 * @since 2.2.0
 */
class DashboardWidget implements Loadie {

	/**
	 * Configura los hooks.
	 *
	 * @inheritdoc
	 */
	public function load() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	/**
	 * Añade el widget del escritorio para mostrar la actividad de Email Log.
	 */
	public function register() {
		wp_add_dashboard_widget(
			'email_log_dashboard_widget',
			__( 'Email Log Summary', 'email-log' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Imprime el contenido en el widget del escritorio.
	 */
	public function render() {
		$email_log  = email_log();
		$logs_count = $email_log->table_manager->get_logs_count();
		?>

		<p>
			<?php esc_html_e( 'Total number of emails logged' , 'email-log' ); ?>: <strong><?php echo number_format( absint( $logs_count ), 0, ',', ',' ); ?></strong>
		</p>

		<?php
			/**
			 * Triggered just after printing the content of the dashboard widget.
			 * Use this hook to add custom messages to the dashboard widget.
			 *
			 * @since 2.4.0
			 */
			do_action( 'el_inside_dashboard_widget' );
		?>

		<ul class="subsubsub" style="float: none">
			<li><?php
            /* translators: %s email logs page link */
            \EmailLog\Core\EmailLog::wp_kses_wf(sprintf(__( '<a href="%s">View Logs</a>', 'email-log' ), 'admin.php?page=email-log' )); ?> <span style="color: #ddd"> | </span></li>
			<li><?php
            /* translators: %s settings page link */
            \EmailLog\Core\EmailLog::wp_kses_wf(sprintf(__( '<a href="%s">Settings</a>', 'email-log' ), 'admin.php?page=email-log-settings' )); ?> <span style="color: #ddd"> | </span></li>
		</ul>

		<?php
	}
}
