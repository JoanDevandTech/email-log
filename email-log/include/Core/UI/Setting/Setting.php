<?php namespace EmailLog\Core\UI\Setting;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Ajuste de Email Log.
 * Contiene una sección de ajustes y varios campos.
 *
 * @since 2.0.0
 */
abstract class Setting {

	/**
	 * @var \EmailLog\Core\UI\Setting\SettingSection
	 */
	protected $section;

	/**
	 * Establece valores por defecto para SettingSection.
	 * Más personalización puede hacerse en el método `initialize` del complemento.
	 */
	public function __construct() {
		$this->section = new SettingSection();

		$this->initialize();

		$this->section->fields            = $this->get_fields();
		$this->section->callback          = array( $this, 'render' );
		$this->section->sanitize_callback = array( $this, 'sanitize' );
	}

	/**
	 * Configura hooks y filtros.
	 */
	public function load() {
		add_filter( 'el_setting_sections', array( $this, 'register' ) );
	}

	/**
	 * Registra el ajuste usando el filtro.
	 *
	 * @param SettingSection[] $sections List of existing SettingSections.
	 *
	 * @return SettingSection[] Modified list of SettingSections.
	 */
	public function register( $sections ) {
		$sections[] = $this->section;

		return $sections;
	}

	/**
	 * Obtiene el valor almacenado en la opción.
	 * Si no se encuentra valor se devuelven los valores por defecto.
	 *
	 * @return array Stored value.
	 */
	public function get_value() {
		$value = get_option( $this->section->option_name );

		return wp_parse_args( $value, $this->section->default_value );
	}

	/**
	 * Personaliza la sección de ajustes.
	 *
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Obtiene la lista de campos de ajustes.
	 *
	 * @return SettingField[] List of fields for the Setting.
	 */
	protected function get_fields() {
		return $this->build_fields();
	}

	/**
	 * Renderiza la sección de ajustes.
	 *
	 * Por defecto no hace nada.
	 */
	public function render() {
		return;
	}

	/**
	 * Sanitiza los valores de la opción.
	 *
	 * @param mixed $values User entered values.
	 *
	 * @return mixed Sanitized values.
	 */
	public function sanitize( $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$values           = wp_parse_args( $values, $this->section->default_value );
		$sanitized_values = array();

		foreach ( $this->section->field_labels as $field_id => $label ) {
			$callback = array( $this, 'sanitize_' . $field_id );

			if ( is_callable( $callback ) ) {
				$sanitized_values[ $field_id ] = call_user_func( $callback, $values[ $field_id ] );
			} else {
				$sanitized_values[ $field_id ] = $values[ $field_id ];
			}
		}

		return $sanitized_values;
	}

	/**
	 * Construye objetos SettingField a partir de IDs y etiquetas.
	 *
	 * @since 2.1.0
	 *
	 * @return \EmailLog\Core\UI\Setting\SettingField[] Built SettingFields.
	 */
	protected function build_fields() {
		$fields = array();

		foreach ( $this->section->field_labels as $field_id => $label ) {
			$field           = new SettingField();
			$field->id       = $field_id;
			$field->title    = $label;
			$field->args     = array( 'id' => $field_id );
			$field->callback = array( $this, 'render_' . $field_id . '_settings' );

			$fields[] = $field;
		}

		return $fields;
	}
}
