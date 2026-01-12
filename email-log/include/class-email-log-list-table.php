<?php
/**
 * Tabla para mostrar los correos registrados.
 *
 * Basada en el ejemplo de Custom List Table de Matt Van Andel.
 *
 * @autor  Joan Dev & Tech — Galiza, Spain
 * @paquete Email Log
 */
class Email_Log_List_Table extends WP_List_Table {

	/**
	 * Configura el constructor haciendo referencia al constructor padre.
	 *
	 * Usamos la referencia al padre para establecer configuraciones por defecto.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular'  => 'email-log',     // nombre en singular de los registros listados
			'plural'    => 'email-logs',    // nombre en plural de los registros listados
			'ajax'      => false,           // esta tabla no soporta ajax
		) );
	}

	/**
	 * Añade marcado extra en las barras de herramientas antes o después de la lista.
	 *
	 * @access protected
	 *
	 * @param string $which Add the markup after (bottom) or before (top) the list.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
		}

		if ( 'bottom' == $which ) {
		}
	}

	/**
	 * Devuelve la lista de columnas y títulos visibles.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'.
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'sent_date' => __( 'Sent at', 'email-log' ),
			'to'        => __( 'To', 'email-log' ),
			'subject'   => __( 'Subject', 'email-log' ),
		);

		return apply_filters( EmailLog::HOOK_LOG_COLUMNS, $columns );
	}

	/**
	 * Devuelve la lista de columnas ordenables.
	 *
	 * @access protected
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool).
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'sent_date'   => array( 'sent_date', true ), //true means it's already sorted
			'to'          => array( 'to_email', false ),
			'subject'     => array( 'subject', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Devuelve el valor para columnas por defecto.
	 *
	 * @access protected
	 *
	 * @param object $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {
		do_action( EmailLog::HOOK_LOG_DISPLAY_COLUMNS, $column_name, $item );
	}

	/**
	 * Muestra la columna de fecha de envío.
	 *
	 * @access protected
	 *
	 * @param  object $item Current item object.
	 * @return string       Markup to be displayed for the column.
	 */
	protected function column_sent_date( $item ) {
		$email_date = mysql2date(
            /* traductores: %1$s es la fecha en la que se envió el correo, %2$s es la hora */
			sprintf( __( '%1$s @ %2$s', 'email-log' ), get_option( 'date_format', 'F j, Y' ), get_option( 'time_format', 'g:i A' ) ),
			$item->sent_date
		);

		$actions = array();

		$content_ajax_url = add_query_arg(
			array(
				'action'    => 'display_content',
				'email_id'  => $item->id,
				'TB_iframe' => 'true',
				'width'     => '600',
				'height'    => '550',
			),
			'admin-ajax.php'
		);

		$actions['view-content'] = sprintf( '<a href="%1$s" class="thickbox" title="%2$s">%3$s</a>',
			esc_url( $content_ajax_url ),
			__( 'Email Content', 'email-log' ),
			__( 'View Content', 'email-log' )
		);

        //phpcs:ignore nonce not needed as it can be called directly
		$delete_url = add_query_arg(
			array(
				'page'                           => sanitize_text_field(wp_unslash($_REQUEST['page'] ?? '')), //phpcs:ignore
				'action'                         => 'delete',
				$this->_args['singular']         => $item->id,
				EmailLog::DELETE_LOG_NONCE_FIELD => wp_create_nonce( EmailLog::DELETE_LOG_ACTION ),
			)
		);

		$actions['delete'] = sprintf( '<a href="%s">%s</a>',
			esc_url( $delete_url ),
			__( 'Delete', 'email-log' )
		);

		/**
	 * Este filtro puede usarse para modificar la lista de acciones por fila que se muestran.
		 *
		 * @since 1.8
		 *
		 * @param array $actions List of actions.
		 * @param object $item The current log item.
		 */
		$actions = apply_filters( 'el_row_actions', $actions, $item );

		return sprintf( '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ $email_date,
			/*$2%s*/ $item->id,
			/*$3%s*/ $this->row_actions( $actions )
		);
	}

	/**
	 * Campo Para.
	 *
	 * @access protected
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_to( $item ) {
		return esc_html( $item->to_email );
	}

	/**
	 * Campo Asunto.
	 *
	 * @access protected
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_subject( $item ) {
		return esc_html( $item->subject );
	}

	/**
	 * Marcado para la columna de acciones.
	 *
	 * @access protected
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->id
		);
	}

	/**
	 * Especifica la lista de acciones en lote.
	 *
	 * @access protected
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'.
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete'     => __( 'Delete', 'email-log' ),
			'delete-all' => __( 'Delete All Logs', 'email-log' ),
		);
		return $actions;
	}

	/**
	 * Maneja las acciones en lote.
	 *
	 * @see $this->prepare_items()
	 */
	public function process_bulk_action() {
		global $wpdb;
		global $EmailLog; 

		if ( 'delete' === $this->current_action() ) {
			// Delete a list of logs by id.

			if ( wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST[ EmailLog::DELETE_LOG_NONCE_FIELD ] ?? '')), EmailLog::DELETE_LOG_ACTION ) ) {

				$ids = sanitize_text_field(wp_unslash($_GET[ $this->_args['singular'] ] ?? ''));

				if ( is_array( $ids ) ) {
					$selected_ids = implode( ',', $ids );
				} else {
					$selected_ids = $ids;
				}

				// Can't use wpdb->prepare for the below query. If used it results in this bug
				// https://github.com/sudar/email-log/issues/13

				$selected_ids = esc_sql( $selected_ids );

				$table_name = $wpdb->prefix . EmailLog::TABLE_NAME;
				$EmailLog->logs_deleted = $wpdb->query( "DELETE FROM $table_name where id IN ( $selected_ids )" ); //@codingStandardsIgnoreLine
			} else {
				wp_die( 'Cheating, Huh? ' );
			}
		} elseif ( 'delete-all' === $this->current_action() ) {
			// Delete all logs.
			if ( wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST[ EmailLog::DELETE_LOG_NONCE_FIELD ] ?? '')), EmailLog::DELETE_LOG_ACTION ) ) {
				$table_name = $wpdb->prefix . EmailLog::TABLE_NAME;
				$EmailLog->logs_deleted = $wpdb->query( "DELETE FROM $table_name" ); //@codingStandardsIgnoreLine
			} else {
				wp_die( 'Cheating, Huh? ' );
			}
		}
	}

	/**
	 * Prepara datos para mostrar.
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . EmailLog::TABLE_NAME;
		$this->_column_headers = $this->get_column_info();

		// Handle bulk actions.
		$this->process_bulk_action();

		// Get current page number.
		$current_page = $this->get_pagenum();

		$query = 'SELECT * FROM ' . $table_name;
		$count_query = 'SELECT count(*) FROM ' . $table_name;
		$query_cond = '';

        //nonce not needed as can be linked directly
		if ( isset( $_GET['s'] ) ) { //phpcs:ignore
			$search_term = trim( esc_sql( wp_unslash($_GET['s']) ) ); //phpcs:ignore
			$query_cond .= " WHERE to_email LIKE '%$search_term%' OR subject LIKE '%$search_term%' ";
		}

		// Ordering parameters.
		$orderby = ! empty( $_GET['orderby'] ) ? esc_sql( $_GET['orderby'] ) : 'sent_date'; //phpcs:ignore 
		$order   = ! empty( $_GET['order'] ) ? esc_sql( $_GET['order'] ) : 'DESC'; //phpcs:ignore

		if ( ! empty( $orderby ) & ! empty( $order ) ) {
			$query_cond .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		// Find total number of items.
		$count_query = $count_query . $query_cond;
		$total_items = $wpdb->get_var( $count_query ); //phpcs:ignore

		// Adjust the query to take pagination into account.
		$per_page = EmailLog::get_per_page();
		if ( ! empty( $current_page ) && ! empty( $per_page ) ) {
			$offset = ( $current_page - 1 ) * $per_page;
			$query_cond .= ' LIMIT ' . (int) $offset . ',' . (int) $per_page;
		}

		// Fetch the items.
		$query = $query . $query_cond;
		$this->items = $wpdb->get_results( $query ); //phpcs:ignore

		// Register pagination options & calculations.
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Displays default message when no items are found.
	 */
	public function no_items() {
		esc_attr_e( 'Your email log is empty', 'email-log' );
	}
}
?>
