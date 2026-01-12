<?php namespace EmailLog\Core\Request;

use EmailLog\Core\Loadie;
use EmailLog\Core\UI\Page\LogListPage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Verifica el nonce para todas las peticiones de Email Log.
 *
 * @since 2.0.0
 */
class NonceChecker implements Loadie {

	/**
	 * Configura los hooks.
	 *
	 * @inheritdoc
	 */
	public function load() {
		add_action( 'admin_init', array( $this, 'check_nonce' ) );
	}

	/**
	 * Verifica el nonce para las peticiones.
	 * Todas las peticiones de Email Log tienen el prefijo `el_`
	 * y el nonce estará disponible como `el_{nombre_accion}_nonce`.
	 *
	 * Claves para acciones en lote:
	 * action => acciones del desplegable superior.
	 * action2 => acciones del desplegable inferior.
	 */
	public function check_nonce() {
		if ( ! isset( $_POST['el-action'] ) && ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['action2'] ) ) {
			return;
		}

		if ( isset( $_POST['el-action'] ) ) {
			$action = sanitize_text_field( wp_unslash($_POST['el-action']) );

			$allowed_actions = [
				'el-download-system-info',
				'el_license_activate',
				'el_license_deactivate',
				'el_bundle_license_activate',
				'el_bundle_license_deactivate',
				'el-log-list-export',
				'el-log-list-export-all',
				'el-export-logs-with-columns'
			];

            

			if ( ! in_array( $action, $allowed_actions ) ) {
				return;
			}

			if ( ! isset( $_POST[ $action . '_nonce' ] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST[ $action . '_nonce' ] ?? '')), $action ) ) {
				return;
			}
		}

		if ( isset( $_REQUEST['action'] ) || isset( $_REQUEST['action2'] ) ) {
			$action = sanitize_text_field( wp_unslash($_REQUEST['action']) );

			if ( '-1' === $action ) {
				if ( ! isset( $_REQUEST['action2'] ) ) {
					return;
				}

				$action = sanitize_text_field( wp_unslash($_REQUEST['action2']) );
			}

			if ( strpos( $action, 'el-log-list-' ) !== 0 && strpos( $action, 'el-cron-' ) !== 0 ) {
				return;
			}

            
			if ( strpos( $action, 'el-log-list-' ) === 0 ) {
				if ( ! isset( $_REQUEST[ LogListPage::LOG_LIST_ACTION_NONCE_FIELD ] ) ) {
					return;
				}

				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash($_REQUEST[ LogListPage::LOG_LIST_ACTION_NONCE_FIELD ] ?? '')), LogListPage::LOG_LIST_ACTION_NONCE ) ) {
					return;
				}
			}

			if ( strpos( $action, 'el-cron-' ) === 0 ) {
				if ( ! isset( $_REQUEST[ $action . '-nonce-field' ] ) ) {
					return;
				}

				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash($_REQUEST[ $action . '-nonce-field' ] ?? '' )), $action . '-nonce' ) ) {
					return;
				}
			}
		}

		/**
		 * Ejecuta la acción `el`.
		 * La verificación de nonce ya se ha realizado.
		 *
		 * @since 2.0.0
		 *
		 * @param string $action   Nombre de la acción.
		 * @param array  $_REQUEST Datos de la petición.
		 */
		do_action( 'el_action', $action, $_REQUEST );

		/**
		 * Ejecuta la acción `el`.
		 * La verificación de nonce ya se ha realizado.
		 *
		 * @since 2.0.0
		 *
		 * @param array $_REQUEST Datos de la petición.
		 */

		do_action( $action, $_REQUEST );
	}
}
