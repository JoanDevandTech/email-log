<?php namespace EmailLog\Core\UI\Page;

use EmailLog\Core\Loadie;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Clase base para todas las páginas de administración de Email Log.
 *
 * @since 2.0.0
 */
abstract class BasePage implements Loadie {

	/**
	 * Página actual.
	 *
	 * @var string
	 */
	protected $page;

	/**
	 * Pantalla actual.
	 *
	 * @var \WP_Screen
	 */
	protected $screen;

	/**
	 * Registra la página.
	 *
	 * @return void
	 */
	abstract public function register_page();

	/**
	 * Configura los hooks relacionados con las páginas.
	 *
	 * @inheritdoc
	 */
	public function load() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Renderiza el pie de la página de administración.
	 */
	protected function render_page_footer() {
		/**
		 * Acción para añadir contenido adicional al pie de administración de Email Log.
		 *
		 * @since 1.8
		 */
		do_action( 'el_admin_footer' );
	}

	/**
	 * Devuelve el objeto WP_Screen para el handle de la página actual.
	 *
	 * @return \WP_Screen Screen object.
	 */
	public function get_screen() {
		if ( ! isset( $this->screen ) ) {
			$this->screen = \WP_Screen::get( $this->page );
		}

		return $this->screen;
	}

    function sidebar(){
        return '';
    }
}
