<?php namespace EmailLog\Core;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Interfaz Loadie de Email Log.
 * El método `load()` de esta interfaz será llamado por Email Log.
 * Aunque Loadie no es una palabra real suena más lógico que suscriptor.
 *
 * @since 2.0.0
 */
interface Loadie {

	/**
	 * Este método será llamado por Email Log tras el evento `wp-loaded`.
	 *
	 * @return void
	 */
	public function load();
}
