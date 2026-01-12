<?php namespace EmailLog\Core\UI;

use EmailLog\Core\Loadie;
use EmailLog\Core\UI\Page\LogListPage;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Cargador de la interfaz de administración.
 * Carga e inicializa todas las páginas y componentes del administrador.
 *
 * @since 2.0
 */
class UILoader implements Loadie {

	/**
	 * Lista de componentes de UI.
	 *
	 * @var array
	 */
	protected $components = array();

	/**
	 * Lista de páginas de administración.
	 *
	 * @var \EmailLog\Core\UI\Page\BasePage[]
	 */
	protected $pages = array();

	/**
	 * Carga todos los componentes y configura los hooks.
	 *
	 * @inheritdoc
	 */
	public function load() {
		$this->initialize_components();
		$this->initialize_pages();

		foreach ( $this->components as $component ) {
			$component->load();
		}

		foreach ( $this->pages as $page ) {
			$page->load();
		}
	}

	public function is_show_dashboard_widget() {
		$this->components['core_settings'] = new Setting\CoreSetting();
		$dashboard_status                  = false;
		$options                           = get_option( 'email-log-core' );
		if( isset( $options['hide_dashboard_widget'] ) ) {
			$dashboard_status = $options['hide_dashboard_widget'];
		}

		return $dashboard_status;
	}

	/**
	 * Inicializa los objetos de componentes de UI.
	 *
	 * This method may be overwritten in tests.
	 *
	 * @access protected
	 */
	protected function initialize_components() {
		if ( current_user_can( LogListPage::CAPABILITY ) ) {
			$this->components['admin_ui_enhancer'] = new Component\AdminUIEnhancer();
			if( ! $this->is_show_dashboard_widget() ) {
				$this->components['dashboard_widget']  = new Component\DashboardWidget();
			}
		}
	}

	/**
	 * Inicializa los objetos de páginas de administración.
	 *
	 * This method may be overwritten in tests.
	 *
	 * @access protected
	 */
	protected function initialize_pages() {
        $this->pages['log_list_page'] = new Page\LogListPage();
		$this->pages['settings_page'] = new Page\SettingsPage();
	}
}
