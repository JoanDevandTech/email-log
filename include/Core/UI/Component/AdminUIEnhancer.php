<?php namespace EmailLog\Core\UI\Component;

defined( 'ABSPATH' ) || exit; // Salir si se accede directamente.

/**
 * Mejora la interfaz de administración y añade enlaces sobre Email Log en:
 * - La página de listado de plugins.
 * - El pie de todas las páginas de Email Log.
 *
 * @since 2.0.0
 */
class AdminUIEnhancer {

	/**
	 * Plugin file name.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Initialize the component and store the plugin basename.
	 *
	 * @param string|null $file Plugin file.
	 */
	public function __construct( $file = null ) {
		if ( null === $file ) {
			$email_log = email_log();
			$file      = $email_log->get_plugin_file();
		}

		$this->plugin_file     = $file;
		$this->plugin_basename = plugin_basename( $file );
	}

	/**
	 * Configura los hooks.
	 *
	 *
	 */
	public function load() {
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'insert_view_logs_link' ) );

		add_action( 'el_admin_footer', array( $this, 'hook_footer_links' ) );
	}

	/**
	 * Añade enlace a 'Ver registros' en la lista de plugins.
	 *
	 * @since 2.3.0 Added Settings link.
	 *
	 * @param array $links List of links.
	 *
	 * @return array Modified list of links.
	 */
	public function insert_view_logs_link( $links ) {
		$view_logs_link = '<a href="admin.php?page=email-log">' . __( 'View Logs', 'email-log' ) . '</a>';

		array_unshift( $links, $view_logs_link );

		return $links;
	}

	/**
	 * Hook para el pie de página.
	 */
	public function hook_footer_links() {
		//add_action( 'in_admin_footer', array( $this, 'add_credit_links' ) );
	}

	/**
	 * Añade enlaces en el pie de página.
	 *
	 * @since Genesis
	 * @see   Function relied on
	 * @link  http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
	 */
	public function add_credit_links() {
		$plugin_data = get_plugin_data( $this->plugin_file );
		\EmailLog\Core\EmailLog::wp_kses_wf(sprintf(
			'%1$s ' . __( 'plugin', 'email-log' ) . ' | ' . __( 'Version', 'email-log' ) . ' %2$s | ' . __( 'by', 'email-log' ) . ' %3$s <br />',
			$plugin_data['Title'],
			$plugin_data['Version'],
			$plugin_data['Author']
        ));
	}
}
