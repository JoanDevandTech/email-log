<?php namespace EmailLog\Core;

defined( 'ABSPATH' ) || exit;

class CronManager implements Loadie {

	const CRON_HOOK = 'el_auto_delete_old_logs';

	public function load() {
		add_action( self::CRON_HOOK, array( $this, 'delete_old_logs' ) );
		add_action( 'update_option_email-log-core', array( $this, 'schedule_on_save' ), 10, 2 );
	}

	public function delete_old_logs() {
		$options = get_option( 'email-log-core' );
		if ( ! is_array( $options ) || empty( $options['auto_delete_days'] ) ) {
			return;
		}

		$days = absint( $options['auto_delete_days'] );
		if ( $days < 1 ) {
			return;
		}

		$email_log = email_log();
		$email_log->table_manager->delete_logs_older_than( $days );
	}

	public function schedule_on_save( $old_value, $new_value ) {
		$hook = self::CRON_HOOK;
		$timestamp = wp_next_scheduled( $hook );

		$days = isset( $new_value['auto_delete_days'] ) ? absint( $new_value['auto_delete_days'] ) : 0;

		if ( $days > 0 && ! $timestamp ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $hook );
		} elseif ( $days < 1 && $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}
}
