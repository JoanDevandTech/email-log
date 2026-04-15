<?php namespace EmailLog\Core\UI\Page;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Página de ajustes.
 * Esta página se muestra solo si algún complemento tiene ajustes habilitados.
 *
 * @since 2.0.0
 */
class SettingsPage extends BasePage {

	/**
	 * Slug de la página.
	 */
	const PAGE_SLUG = 'email-log-settings';

	/**
	 * Especifica hooks adicionales.
	 *
	 * @inheritdoc
	 */
	public function load() {
		parent::load();

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registra ajustes y añade secciones y campos de ajustes.
	 */
	public function register_settings() {
		$sections = $this->get_setting_sections();

		foreach ( $sections as $section ) {
			register_setting(
				self::PAGE_SLUG,
				$section->option_name,
				array( 'sanitize_callback' => $section->sanitize_callback )
			);

			add_settings_section(
				$section->id,
				$section->title,
				$section->callback,
				self::PAGE_SLUG
			);

			foreach ( $section->fields as $field ) {
				add_settings_field(
					$section->id . '[' . $field->id . ']',
					$field->title,
					$field->callback,
					self::PAGE_SLUG,
					$section->id,
					$field->args
				);
			}
		}
	}

	/**
	 * Obtiene la lista de secciones de ajustes definidas.
	 * Un complemento puede definir su propia sección de ajustes.
	 *
	 * @return \EmailLog\Core\UI\Setting\SettingSection[] List of defined setting sections.
	 */
	protected function get_setting_sections() {
		/**
		 * Specify the list of setting sections in the settings page.
		 * An add-on can add its own setting section by adding an instance of
		 * SectionSection to the array.
		 *
		 * @since 2.0.0
		 *
		 * @param \EmailLog\Core\UI\Setting\SettingSection[] List of SettingSections.
		 */
		return apply_filters( 'el_setting_sections', array() );
	}

	/**
	 * Registra la página.
	 */
	public function register_page() {

		$sections = $this->get_setting_sections();

		if ( empty( $sections ) ) {
			return;
		}

		$this->page = add_submenu_page(
			LogListPage::PAGE_SLUG,
			__( 'Settings', 'email-log' ),
			__( 'Settings', 'email-log' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renderiza la página.
	 * // TODO: Convertir estas secciones en pestañas.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><img class="el-logo" src="<?php echo esc_url(EMAIL_LOG_URL . 'assets/img/logo-64x64.png'); ?>" /><?php esc_html_e( 'Email Log', 'email-log' ); ?></h1>
            <div class="email-log-body-wrapper">
                <form method="post" action="options.php">
                    <?php
                    settings_errors();
                    settings_fields( self::PAGE_SLUG );
                    do_settings_sections( self::PAGE_SLUG );

                    submit_button( __( 'Save', 'email-log' ) );
                    ?>
                </form>
            </div>

		</div>
		<?php

		$this->render_page_footer();
	}
}
