<?php namespace EmailLog\Core\Request;

use EmailLog\Core\Loadie;
use EmailLog\Core\UI\Page\LogListPage;

defined( 'ABSPATH' ) || exit;

class ExportAction implements Loadie {

	public function load() {
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	public function handle_export() {
		if ( ! isset( $_GET['el_export_csv'] ) || $_GET['el_export_csv'] !== '1' ) {
			return;
		}

		if ( ! current_user_can( LogListPage::CAPABILITY ) ) {
			wp_die( __( 'You do not have permission to export logs.', 'email-log' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'el-export-csv' ) ) {
			wp_die( __( 'Security check failed.', 'email-log' ), 403 );
		}

		$email_log     = email_log();
		$table_manager = $email_log->table_manager;

		$request = $_GET;
		list( $items, $total ) = $table_manager->fetch_log_items( $request, 0, 0 );

		$filename = 'email-log-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv( $output, array( 'ID', 'Date', 'To', 'Subject', 'Status', 'IP Address' ) );

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$status = is_null( $item->result ) ? '' : ( $item->result ? 'OK' : 'FAILED' );
				fputcsv( $output, array(
					$item->id,
					$item->sent_date,
					$item->to_email,
					$item->subject,
					$status,
					$item->ip_address,
				) );
			}
		}

		fclose( $output );
		exit;
	}
}
