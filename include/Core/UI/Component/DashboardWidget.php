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
		$email_log = email_log();
		$stats     = $email_log->table_manager->get_log_stats();
		?>

		<div class="el-dashboard-stats" style="display: flex; gap: 15px; margin-bottom: 15px;">
			<div style="flex: 1; text-align: center; padding: 10px; background: #f0f0f1; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format( $stats['total'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Total', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #f0f0f1; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format( $stats['today'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Today', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #e6ffe6; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo esc_html( number_format( $stats['success'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'OK', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #ffe6e6; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo esc_html( number_format( $stats['failed'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Failed', 'email-log' ); ?></div>
			</div>
		</div>

		<?php do_action( 'el_inside_dashboard_widget' ); ?>

		<ul class="subsubsub" style="float: none">
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=email-log' ) ); ?>"><?php esc_html_e( 'View Logs', 'email-log' ); ?></a> <span style="color: #ddd"> | </span></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=email-log-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'email-log' ); ?></a></li>
		</ul>
		<?php
	}
}
