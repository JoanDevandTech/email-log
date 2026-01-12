<?php namespace EmailLog\Core;

use EmailLog\Core\UI\Page\LogListPage;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Otorga capacidades al administrador.
 * Por defecto los administradores pueden gestionar los registros de correo.
 *
 * @since 2.1.0
 */
class AdminCapabilityGiver implements Loadie {

	public function load() {
		add_filter( 'user_has_cap', array( $this, 'add_cap_to_admin_cap_list' ), 10, 4 );
	}

	/**
	 * Añade la capacidad `manage_email_logs` a la lista de capacidades del admin durante `user_has_cap`.
	 *
	 * En instalaciones nuevas esta capacidad se añade al instalar el plugin.
	 * En instalaciones antiguas puede faltarle y por eso se añade mediante el filtro.
	 *
	 * @param array    $allcaps Array con todas las capacidades del usuario.
	 * @param array    $caps    Capacidades reales para la meta capacidad.
	 * @param array    $args    Parámetros opcionales pasados a has_cap(), normalmente ID de objeto.
	 * @param \WP_User $user    Objeto de usuario.
	 *
	 * @return array Lista modificada de capacidades del usuario.
	 */
	public function add_cap_to_admin_cap_list( $allcaps, $caps, $args, $user ) {
		if ( ! in_array( 'administrator', $user->roles ) ) {
			return $allcaps;
		}

		if ( array_key_exists( LogListPage::CAPABILITY, $allcaps ) ) {
			return $allcaps;
		}

		$allcaps[ LogListPage::CAPABILITY ] = true;

		return $allcaps;
	}

	/**
	 * Añade la capacidad de gestionar registros al rol administrador.
	 * Se llamará durante la instalación.
	 */
	public function add_cap_to_admin() {
		$admin = get_role( 'administrator' );

		if ( is_null( $admin ) ) {
			return;
		}

		$admin->add_cap( LogListPage::CAPABILITY );
	}
}
