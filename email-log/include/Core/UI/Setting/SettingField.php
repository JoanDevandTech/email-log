<?php namespace EmailLog\Core\UI\Setting;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * A setting field used in Email Log Settings page.
 *
 * @see add_settings_field()
 */
class SettingField {

	public $id;
	public $title;
	public $callback;
	public $args = array();
}
