# Email Log v2.0 - Security Fixes, Cleanup & New Features

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all security vulnerabilities, remove dead code, and implement Export CSV, Auto-Delete, Resend Email, Filter by Status, Date Range Search, and improved Dashboard Widget.

**Architecture:** WordPress plugin using Loadie pattern (component registration). DB table `{prefix}_email_log`. All new features plug into existing Settings API and LogListAction AJAX pattern. No external dependencies added.

**Tech Stack:** PHP 7.3+, WordPress 4.0+, jQuery UI (already loaded), WordPress Settings API, WP Cron API.

---

## Phase 1: Security & Bug Fixes

### Task 1: Fix CSRF on AJAX view message endpoint

**Files:**
- Modify: `include/Core/Request/LogListAction.php:34-40`
- Modify: `include/Core/UI/ListTable/LogListTable.php:163-171`

- [ ] **Step 1: Add nonce verification to AJAX handler**

In `include/Core/Request/LogListAction.php`, replace lines 34-40:

```php
	public function view_log_message() {
		if ( ! current_user_can( LogListPage::CAPABILITY ) ) {
			wp_die();
		}

			// nonce no necesario; puede llamarse directamente
		$id = absint( $_GET['log_id'] ); //phpcs:ignore
```

With:

```php
	public function view_log_message() {
		if ( ! current_user_can( LogListPage::CAPABILITY ) ) {
			wp_die();
		}

		check_ajax_referer( 'el-view-log-message', 'nonce' );

		$id = absint( $_GET['log_id'] );
```

- [ ] **Step 2: Add nonce to the AJAX URL in LogListTable**

In `include/Core/UI/ListTable/LogListTable.php`, replace lines 163-171:

```php
		$content_ajax_url = add_query_arg(
			array(
				'action' => 'el-log-list-view-message',
				'log_id' => $item->id,
				'width'  => '800',
				'height' => '550',
			),
			'admin-ajax.php'
		);
```

With:

```php
		$content_ajax_url = add_query_arg(
			array(
				'action' => 'el-log-list-view-message',
				'log_id' => $item->id,
				'nonce'  => wp_create_nonce( 'el-view-log-message' ),
				'width'  => '800',
				'height' => '550',
			),
			'admin-ajax.php'
		);
```

- [ ] **Step 3: Commit**

```bash
git add include/Core/Request/LogListAction.php include/Core/UI/ListTable/LogListTable.php
git commit -m "fix(security): add CSRF nonce to AJAX view message endpoint"
```

---

### Task 2: Fix SQL queries - use $wpdb->prepare()

**Files:**
- Modify: `include/Core/DB/TableManager.php:117-126, 209-333`

- [ ] **Step 1: Fix delete_logs to use prepare with FIND_IN_SET**

In `include/Core/DB/TableManager.php`, replace the `delete_logs` method (lines 117-126):

```php
	public function delete_logs( $ids ) {
		global $wpdb;

		$table_name = $this->get_log_table_name();

		// Can't use wpdb->prepare for the below query. If used it results in this bug // https://github.com/sudar/email-log/issues/13.
		$ids = esc_sql( $ids );

		return $wpdb->query( "DELETE FROM {$table_name} where id IN ( {$ids} )" ); //phpcs:ignore
	}
```

With:

```php
	public function delete_logs( $ids ) {
		global $wpdb;

		$table_name = $this->get_log_table_name();

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids_array = array_map( 'absint', explode( ',', $ids ) );
		$ids_array = array_filter( $ids_array );

		if ( empty( $ids_array ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids_array ), '%d' ) );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id IN ( {$placeholders} )", $ids_array ) ); //phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
	}
```

- [ ] **Step 2: Fix fetch_log_items search to use prepare**

In `include/Core/DB/TableManager.php`, replace the search section of `fetch_log_items` (lines 217-285) with prepared queries. Replace the entire `if ( isset( $request['s'] ) ...` block:

```php
		if ( isset( $request['s'] ) && is_string( $request['s'] ) && $request['s'] !== '' ) {
			$search_term = trim( sanitize_text_field( wp_unslash( $request['s'] ) ) );

			if ( Util\is_advanced_search_term( $search_term ) ) {
				$predicates = Util\get_advanced_search_term_predicates( $search_term );

				foreach ( $predicates as $column => $term_value ) {
					$like_value = '%' . $wpdb->esc_like( $term_value ) . '%';

					switch ( $column ) {
						case 'id':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare( 'id = %d', absint( $term_value ) );
							break;
						case 'to':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare( 'to_email LIKE %s', $like_value );
							break;
						case 'email':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare(
								'( to_email LIKE %s OR subject LIKE %s OR ( headers <> %s AND ( headers LIKE %s ) ) )',
								$like_value, $like_value, '', $like_value
							);
							break;
						case 'cc':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare(
								'( headers <> %s AND headers LIKE %s )',
								'', '%CC:%' . $wpdb->esc_like( $term_value ) . '%'
							);
							break;
						case 'bcc':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare(
								'( headers <> %s AND headers LIKE %s )',
								'', '%BCC:%' . $wpdb->esc_like( $term_value ) . '%'
							);
							break;
						case 'reply-to':
							$query_cond .= empty( $query_cond ) ? ' WHERE ' : ' AND ';
							$query_cond .= $wpdb->prepare(
								'( headers <> %s AND headers LIKE %s )',
								'', '%Reply-to:%' . $wpdb->esc_like( $term_value ) . '%'
							);
							break;
					}
				}
			} else {
				$like_value = '%' . $wpdb->esc_like( $search_term ) . '%';
				$query_cond .= $wpdb->prepare( ' WHERE ( to_email LIKE %s OR subject LIKE %s ) ', $like_value, $like_value );
			}
		}

		if ( isset( $request['d'] ) && $request['d'] !== '' ) {
			$search_date = sanitize_text_field( trim( $request['d'] ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $search_date ) ) {
				if ( '' === $query_cond ) {
					$query_cond .= $wpdb->prepare( " WHERE sent_date BETWEEN %s AND %s ", $search_date . ' 00:00:00', $search_date . ' 23:59:59' );
				} else {
					$query_cond .= $wpdb->prepare( " AND sent_date BETWEEN %s AND %s ", $search_date . ' 00:00:00', $search_date . ' 23:59:59' );
				}
			}
		}
```

- [ ] **Step 3: Commit**

```bash
git add include/Core/DB/TableManager.php
git commit -m "fix(security): migrate SQL queries to wpdb->prepare()"
```

---

### Task 3: Fix uninstall.php condition and NonceChecker

**Files:**
- Modify: `uninstall.php:6`
- Modify: `include/Core/Request/NonceChecker.php`

- [ ] **Step 1: Fix uninstall.php OR condition**

In `uninstall.php`, replace line 6:

```php
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
```

With:

```php
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
```

- [ ] **Step 2: Make NonceChecker die on invalid nonce instead of silently returning**

In `include/Core/Request/NonceChecker.php`, replace lines 62-64:

```php
			if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST[ $action . '_nonce' ] ?? '')), $action ) ) {
				return;
			}
```

With:

```php
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $action . '_nonce' ] ?? '' ) ), $action ) ) {
				wp_die( __( 'Security check failed.', 'email-log' ), 403 );
			}
```

Do the same for lines 88-89 (the REQUEST nonce check):

```php
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash($_REQUEST[ LogListPage::LOG_LIST_ACTION_NONCE_FIELD ] ?? '')), LogListPage::LOG_LIST_ACTION_NONCE ) ) {
				return;
			}
```

Replace with:

```php
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ LogListPage::LOG_LIST_ACTION_NONCE_FIELD ] ?? '' ) ), LogListPage::LOG_LIST_ACTION_NONCE ) ) {
				wp_die( __( 'Security check failed.', 'email-log' ), 403 );
			}
```

And lines 97-99 (cron nonce check):

```php
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash($_REQUEST[ $action . '-nonce-field' ] ?? '' )), $action . '-nonce' ) ) {
					return;
				}
```

Replace with:

```php
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $action . '-nonce-field' ] ?? '' ) ), $action . '-nonce' ) ) {
					wp_die( __( 'Security check failed.', 'email-log' ), 403 );
				}
```

- [ ] **Step 3: Commit**

```bash
git add uninstall.php include/Core/Request/NonceChecker.php
git commit -m "fix(security): fix uninstall condition, die on invalid nonce"
```

---

### Task 4: Fix IP spoofing in EmailLogger

**Files:**
- Modify: `include/Core/EmailLogger.php:92-99`

- [ ] **Step 1: Only use REMOTE_ADDR, remove X-Forwarded-For trust**

In `include/Core/EmailLogger.php`, replace lines 92-99:

```php
		// IP Address detection.
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip    = trim( $parts[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$log['ip_address'] = sanitize_text_field( $ip );
```

With:

```php
		// IP Address detection - only trust REMOTE_ADDR to prevent spoofing.
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$log['ip_address'] = sanitize_text_field( $ip );
```

- [ ] **Step 2: Commit**

```bash
git add include/Core/EmailLogger.php
git commit -m "fix(security): remove X-Forwarded-For trust to prevent IP spoofing"
```

---

## Phase 2: Dead Code Removal & Cleanup

### Task 5: Remove legacy files and wf-flyout

**Files:**
- Delete: `include/install.php`
- Delete: `include/class-email-header-parser.php`
- Delete: `include/class-email-log-list-table.php`
- Delete: `wf-flyout/` (entire directory)
- Delete: `assets/js/auto-delete-logs.js`
- Delete: `assets/js/email-log-export-logs.js`
- Delete: `assets/js/insQ.min.js`
- Delete: `assets/js/jquery.numeric.min.js`

- [ ] **Step 1: Delete all dead files**

```bash
rm include/install.php
rm include/class-email-header-parser.php
rm include/class-email-log-list-table.php
rm -rf wf-flyout/
rm assets/js/auto-delete-logs.js
rm assets/js/email-log-export-logs.js
rm assets/js/insQ.min.js
rm assets/js/jquery.numeric.min.js
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "chore: remove legacy dead code, wf-flyout, and unused JS"
```

---

### Task 6: Clean up JS - remove insertionQ, upsell PRO dialog, trim jQuery UI

**Files:**
- Modify: `assets/js/view-logs.js`
- Modify: `assets/js/email-log-admin.js`
- Modify: `include/Core/UI/Page/LogListPage.php:206-223`

- [ ] **Step 1: Rewrite view-logs.js without insertionQ**

Replace entire `assets/js/view-logs.js` with:

```javascript
( function( $ ) {
	$( document ).ready( function() {
		$( '#search_id-search-date-input' ).datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: 'yy-mm-dd'
		});

		$( document ).on( 'click', '#thickbox-footer-close', function( event ) {
			event.preventDefault();
			tb_remove();
		});

		$( '.el-help' ).tooltip({
			content: function() { return $( this ).prop( 'title' ); },
			position: { my: 'center top', at: 'center bottom+10', collision: 'flipfit' },
			hide: { duration: 100 },
			show: { duration: 100 }
		});

		// Initialize tabs inside thickbox when they appear.
		$( document ).on( 'tb_init', function() {
			var checkTabs = setInterval( function() {
				var $tabs = $( '#tabs' );
				if ( $tabs.length && ! $tabs.hasClass( 'ui-tabs' ) ) {
					var activeTabIndex = parseInt( $tabs.find( 'ul' ).data( 'active-tab' ) );
					activeTabIndex = isNaN( activeTabIndex ) ? 1 : activeTabIndex;
					$tabs.tabs({ active: activeTabIndex });
					clearInterval( checkTabs );
				}
			}, 100 );
			// Stop checking after 5 seconds.
			setTimeout( function() { clearInterval( checkTabs ); }, 5000 );
		});
	});
})( jQuery );
```

- [ ] **Step 2: Trim jQuery UI dependencies in LogListPage**

In `include/Core/UI/Page/LogListPage.php`, replace lines 206-223 (the `load_view_logs_assets` method):

```php
	public function load_view_logs_assets( $hook ) {
		if ( 'toplevel_page_email-log' !== $hook && 'email-log_page_email-log-settings' !== $hook ) {
			return;
		}

		$email_log = email_log();
		$version   = $email_log->get_version();

		wp_enqueue_script( 'el-view-logs', EMAIL_LOG_URL . 'assets/js/view-logs.js', array( 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-tooltip', 'jquery-ui-tabs' ), $version, true );
		wp_enqueue_style( 'el-jquery-ui-css', EMAIL_LOG_URL . 'assets/css/email-log-jquery-ui.min.css', array() );
	}
```

- [ ] **Step 3: Commit**

```bash
git add assets/js/view-logs.js include/Core/UI/Page/LogListPage.php
git commit -m "chore: remove insertionQ, trim jQuery UI deps, clean JS"
```

---

### Task 7: Remove dead methods and fix strict comparisons

**Files:**
- Modify: `include/Core/UI/Page/BasePage.php:69-71`
- Modify: `include/Core/UI/ListTable/LogListTable.php:44,481-483`
- Modify: `include/Core/UI/Setting/CoreSetting.php:62-82`
- Modify: `include/Core/UI/Component/AdminUIEnhancer.php:51,74-76`
- Modify: `include/Core/UI/Page/LogListPage.php:191`
- Modify: `include/Core/UI/ListTable/LogListTable.php:61`
- Modify: `include/Util/EmailHeaderParser.php:80,120`

- [ ] **Step 1: Remove BasePage::sidebar()**

In `include/Core/UI/Page/BasePage.php`, remove lines 69-71:

```php
    function sidebar(){
        return '';
    }
```

Then in `include/Core/UI/Page/LogListPage.php`, remove line 115 (the sidebar call):

```php
            <div class="email-log-sidebar-wrapper">
                <?php \EmailLog\Core\EmailLog::wp_kses_wf($this->sidebar()); ?>
            </div>
```

Replace with just an empty string (remove the sidebar wrapper):

```php
```

And in `include/Core/UI/Page/SettingsPage.php`, remove lines 121-123:

```php
            <div class="email-log-sidebar-wrapper">
                <?php \EmailLog\Core\EmailLog::wp_kses_wf($this->sidebar()); ?>
            </div>
```

- [ ] **Step 2: Remove LogListTable::add_body_class() and its hook**

In `include/Core/UI/ListTable/LogListTable.php`, remove line 44:

```php
		add_action( 'admin_body_class', array( $this, 'add_body_class' ) );
```

And remove the method at lines 481-483:

```php
	public function add_body_class( $classes ) {
		return $classes;
	}
```

- [ ] **Step 3: Remove empty render methods in CoreSetting**

In `include/Core/UI/Setting/CoreSetting.php`, remove lines 57-82 (the three empty render methods):

```php
    public function render_delete_log_settings() {
        echo '';
    }

    public function render_forward_email_settings() {
        echo '';
    }

    public function render_email_monitor_title_settings() {
        echo '';
    }
```

- [ ] **Step 4: Remove dead hook_footer_links and add_credit_links**

In `include/Core/UI/Component/AdminUIEnhancer.php`, remove the `el_admin_footer` hook at line 51:

```php
		add_action( 'el_admin_footer', array( $this, 'hook_footer_links' ) );
```

And remove the two methods (lines 74-93):

```php
	public function hook_footer_links() {
		//add_action( 'in_admin_footer', array( $this, 'add_credit_links' ) );
	}

	public function add_credit_links() { ... }
```

- [ ] **Step 5: Fix strict comparisons**

In `include/Core/UI/ListTable/LogListTable.php` line 61, change:

```php
		if ( 'top' == $which ) {
```
To:
```php
		if ( 'top' === $which ) {
```

In `include/Core/UI/Page/LogListPage.php` line 191, change:

```php
		if ( 'per_page' == $option ) {
```
To:
```php
		if ( 'per_page' === $option ) {
```

In `include/Util/EmailHeaderParser.php` line 80, change:

```php
		if ( trim( $value ) != '' ) {
```
To:
```php
		if ( trim( $value ) !== '' ) {
```

In `include/Util/EmailHeaderParser.php` line 120, change:

```php
		if ( 2 == count( $header ) ) {
```
To:
```php
		if ( count( $header ) === 2 ) {
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "chore: remove dead methods, fix strict comparisons"
```

---

### Task 8: Add database index for performance

**Files:**
- Modify: `include/Core/DB/TableManager.php:494-513`

- [ ] **Step 1: Add index to the CREATE TABLE query**

In `include/Core/DB/TableManager.php`, in `get_create_table_query()`, replace lines 499-513:

```php
		$sql = 'CREATE TABLE ' . $table_name . ' (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				to_email VARCHAR(500) NOT NULL,
				subject VARCHAR(500) NOT NULL,
				message TEXT NOT NULL,
				headers TEXT NOT NULL,
				attachments TEXT NOT NULL,
				sent_date timestamp NOT NULL,
				attachment_name VARCHAR(1000),
				ip_address VARCHAR(15),
				result TINYINT(1),
				error_message VARCHAR(1000),
				PRIMARY KEY  (id)
			) ' . $charset_collate . ';';
```

With:

```php
		$sql = 'CREATE TABLE ' . $table_name . ' (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				to_email VARCHAR(500) NOT NULL,
				subject VARCHAR(500) NOT NULL,
				message TEXT NOT NULL,
				headers TEXT NOT NULL,
				attachments TEXT NOT NULL,
				sent_date timestamp NOT NULL,
				attachment_name VARCHAR(1000),
				ip_address VARCHAR(15),
				result TINYINT(1),
				error_message VARCHAR(1000),
				PRIMARY KEY  (id),
				KEY idx_sent_date (sent_date),
				KEY idx_result (result)
			) ' . $charset_collate . ';';
```

- [ ] **Step 2: Bump DB version to trigger migration**

In the same file, change line 28:

```php
	const DB_VERSION = '0.3';
```
To:
```php
	const DB_VERSION = '0.4';
```

- [ ] **Step 3: Commit**

```bash
git add include/Core/DB/TableManager.php
git commit -m "perf: add indexes on sent_date and result columns"
```

---

## Phase 3: New Features

### Task 9: Filter by status (success/failed)

**Files:**
- Modify: `include/Core/DB/TableManager.php` (fetch_log_items method)
- Modify: `include/Core/UI/ListTable/LogListTable.php` (extra_tablenav method)

- [ ] **Step 1: Add status filter dropdown in LogListTable**

In `include/Core/UI/ListTable/LogListTable.php`, replace the `extra_tablenav` method:

```php
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$current_status = isset( $_GET['result'] ) ? sanitize_text_field( $_GET['result'] ) : '';
			?>
			<div class="alignleft actions">
				<select name="result" id="filter-by-result">
					<option value=""><?php esc_html_e( 'All statuses', 'email-log' ); ?></option>
					<option value="1" <?php selected( $current_status, '1' ); ?>><?php esc_html_e( 'Successful', 'email-log' ); ?></option>
					<option value="0" <?php selected( $current_status, '0' ); ?>><?php esc_html_e( 'Failed', 'email-log' ); ?></option>
				</select>
				<?php submit_button( __( 'Filter', 'email-log' ), '', 'filter_action', false ); ?>
			</div>
			<?php

			do_action( 'el_before_logs_list_table', $this->get_pagination_arg( 'total_items' ) );
		}
	}
```

- [ ] **Step 2: Handle status filter in TableManager::fetch_log_items**

In `include/Core/DB/TableManager.php`, inside `fetch_log_items()`, after the date filter block (after the `$request['d']` block), add:

```php
		if ( isset( $request['result'] ) && $request['result'] !== '' ) {
			$result_value = absint( $request['result'] );
			if ( '' === $query_cond ) {
				$query_cond .= $wpdb->prepare( ' WHERE result = %d', $result_value );
			} else {
				$query_cond .= $wpdb->prepare( ' AND result = %d', $result_value );
			}
		}
```

- [ ] **Step 3: Commit**

```bash
git add include/Core/DB/TableManager.php include/Core/UI/ListTable/LogListTable.php
git commit -m "feat: add filter by email status (success/failed)"
```

---

### Task 10: Date range search (from - to)

**Files:**
- Modify: `include/Core/UI/ListTable/LogListTable.php` (search_box method)
- Modify: `include/Core/DB/TableManager.php` (fetch_log_items method)
- Modify: `assets/js/view-logs.js`

- [ ] **Step 1: Add date-to field in search_box**

In `include/Core/UI/ListTable/LogListTable.php`, replace the `search_box` method with:

```php
	public function search_box( $text, $input_id ) {
		$input_text_id  = $input_id . '-search-input';
		$input_date_from_id = $input_id . '-search-date-from';
		$input_date_to_id   = $input_id . '-search-date-to';
		$input_date_from_val = ( ! empty( $_REQUEST['d'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['d'] ) ) : '';
		$input_date_to_val   = ( ! empty( $_REQUEST['d_to'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['d_to'] ) ) : '';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_text_field( $_REQUEST['orderby'] ) ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( $_REQUEST['order'] ) ) . '" />';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_date_from_id ); ?>" name="d" value="<?php echo esc_attr( $input_date_from_val ); ?>" placeholder="<?php esc_html_e( 'Date from', 'email-log' ); ?>" class="el-datepicker" />
			<input type="search" id="<?php echo esc_attr( $input_date_to_id ); ?>" name="d_to" value="<?php echo esc_attr( $input_date_to_val ); ?>" placeholder="<?php esc_html_e( 'Date to', 'email-log' ); ?>" class="el-datepicker" />
			<input type="search" id="<?php echo esc_attr( $input_text_id ); ?>" name="s" value="<?php echo esc_attr( isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '' ); ?>" placeholder="<?php esc_html_e( 'Search by term', 'email-log' ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}
```

- [ ] **Step 2: Update date filter in TableManager to support range**

In `include/Core/DB/TableManager.php`, replace the date filter block (the `$request['d']` section from Task 2) with:

```php
		$date_from = isset( $request['d'] ) && $request['d'] !== '' ? sanitize_text_field( trim( $request['d'] ) ) : '';
		$date_to   = isset( $request['d_to'] ) && $request['d_to'] !== '' ? sanitize_text_field( trim( $request['d_to'] ) ) : '';

		if ( $date_from !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$date_end = $date_to !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ? $date_to : $date_from;
			if ( '' === $query_cond ) {
				$query_cond .= $wpdb->prepare( " WHERE sent_date BETWEEN %s AND %s ", $date_from . ' 00:00:00', $date_end . ' 23:59:59' );
			} else {
				$query_cond .= $wpdb->prepare( " AND sent_date BETWEEN %s AND %s ", $date_from . ' 00:00:00', $date_end . ' 23:59:59' );
			}
		}
```

- [ ] **Step 3: Update view-logs.js to init all datepickers**

In `assets/js/view-logs.js`, replace the datepicker init line:

```javascript
		$( '#search_id-search-date-input' ).datepicker({
```

With:

```javascript
		$( '.el-datepicker' ).datepicker({
```

- [ ] **Step 4: Commit**

```bash
git add include/Core/UI/ListTable/LogListTable.php include/Core/DB/TableManager.php assets/js/view-logs.js
git commit -m "feat: add date range search (from-to)"
```

---

### Task 11: Export CSV

**Files:**
- Create: `include/Core/Request/ExportAction.php`
- Modify: `email-log.php` (register new loadie)
- Modify: `include/Core/UI/Page/LogListPage.php` (add export button)

- [ ] **Step 1: Create ExportAction class**

Create `include/Core/Request/ExportAction.php`:

```php
<?php namespace EmailLog\Core\Request;

use EmailLog\Core\Loadie;
use EmailLog\Core\UI\Page\LogListPage;

defined( 'ABSPATH' ) || exit;

class ExportAction implements Loadie {

	public function load() {
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	public function handle_export() {
		if ( ! isset( $_GET['el_export_csv'] ) || $_GET['el_export_csv'] !== '1' ) {
			return;
		}

		if ( ! current_user_can( LogListPage::CAPABILITY ) ) {
			wp_die( __( 'You do not have permission to export logs.', 'email-log' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'el-export-csv' ) ) {
			wp_die( __( 'Security check failed.', 'email-log' ), 403 );
		}

		$email_log     = email_log();
		$table_manager = $email_log->table_manager;

		// Use same filters as current view.
		$request = $_GET;
		list( $items, $total ) = $table_manager->fetch_log_items( $request, 0, 0 );

		$filename = 'email-log-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv( $output, array( 'ID', 'Date', 'To', 'Subject', 'Status', 'IP Address' ) );

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$status = is_null( $item->result ) ? '' : ( $item->result ? 'OK' : 'FAILED' );
				fputcsv( $output, array(
					$item->id,
					$item->sent_date,
					$item->to_email,
					$item->subject,
					$status,
					$item->ip_address,
				) );
			}
		}

		fclose( $output );
		exit;
	}
}
```

- [ ] **Step 2: Fix fetch_log_items to allow unlimited results**

In `include/Core/DB/TableManager.php`, update the pagination section (around line 324):

```php
		if ( ! empty( $current_page_no ) && ! empty( $per_page ) ) {
```

This already works -- when `$per_page=0` and `$current_page_no=0`, no LIMIT is added. Good.

- [ ] **Step 3: Register the new loadie in email-log.php**

In `email-log.php`, after line 61 (`$email_log->add_loadie(new \EmailLog\Core\Request\LogListAction());`), add:

```php
	$email_log->add_loadie( new \EmailLog\Core\Request\ExportAction() );
```

- [ ] **Step 4: Add export button to LogListPage**

In `include/Core/UI/Page/LogListPage.php`, inside `render_page()`, after line 101 (`<?php settings_errors(); ?>`), add:

```php
			<?php
			$export_url = add_query_arg( array(
				'page'          => self::PAGE_SLUG,
				'el_export_csv' => '1',
				'_wpnonce'      => wp_create_nonce( 'el-export-csv' ),
				's'             => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '',
				'd'             => isset( $_GET['d'] ) ? sanitize_text_field( $_GET['d'] ) : '',
				'd_to'          => isset( $_GET['d_to'] ) ? sanitize_text_field( $_GET['d_to'] ) : '',
				'result'        => isset( $_GET['result'] ) ? sanitize_text_field( $_GET['result'] ) : '',
			), admin_url( 'admin.php' ) );
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-bottom: 10px;">
				<?php esc_html_e( 'Export CSV', 'email-log' ); ?>
			</a>
```

- [ ] **Step 5: Commit**

```bash
git add include/Core/Request/ExportAction.php email-log.php include/Core/UI/Page/LogListPage.php
git commit -m "feat: add CSV export with current filters"
```

---

### Task 12: Auto-delete old logs via WP Cron

**Files:**
- Modify: `include/Core/UI/Setting/CoreSetting.php`
- Create: `include/Core/CronManager.php`
- Modify: `email-log.php`

- [ ] **Step 1: Create CronManager class**

Create `include/Core/CronManager.php`:

```php
<?php namespace EmailLog\Core;

defined( 'ABSPATH' ) || exit;

class CronManager implements Loadie {

	const CRON_HOOK = 'el_auto_delete_old_logs';

	public function load() {
		add_action( self::CRON_HOOK, array( $this, 'delete_old_logs' ) );
		add_action( 'update_option_email-log-core', array( $this, 'schedule_on_save' ), 10, 2 );
	}

	public function delete_old_logs() {
		$options = get_option( 'email-log-core' );
		if ( ! is_array( $options ) || empty( $options['auto_delete_days'] ) ) {
			return;
		}

		$days = absint( $options['auto_delete_days'] );
		if ( $days < 1 ) {
			return;
		}

		$email_log = email_log();
		$email_log->table_manager->delete_logs_older_than( $days );
	}

	public function schedule_on_save( $old_value, $new_value ) {
		$hook = self::CRON_HOOK;
		$timestamp = wp_next_scheduled( $hook );

		$days = isset( $new_value['auto_delete_days'] ) ? absint( $new_value['auto_delete_days'] ) : 0;

		if ( $days > 0 && ! $timestamp ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $hook );
		} elseif ( $days < 1 && $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}
}
```

- [ ] **Step 2: Add auto_delete_days setting to CoreSetting**

In `include/Core/UI/Setting/CoreSetting.php`, in the `initialize()` method, add to `field_labels` array:

```php
			'auto_delete_days'      => __( 'Auto Delete Logs', 'email-log' ),
```

And add to `default_value`:

```php
			'auto_delete_days'      => 0,
```

- [ ] **Step 3: Replace the render_interval_settings with a working auto_delete_days renderer**

In `include/Core/UI/Setting/CoreSetting.php`, replace the `render_interval_settings` method (lines 521-543) with:

```php
	public function render_auto_delete_days_settings( $args ) {
		$option = $this->get_value();
		$days   = isset( $option[ $args['id'] ] ) ? absint( $option[ $args['id'] ] ) : 0;

		$field_name = $this->section->option_name . '[' . $args['id'] . ']';
		?>
		<p><?php esc_html_e( 'Automatically delete email logs older than the specified number of days. Set to 0 to disable.', 'email-log' ); ?></p>
		<label>
			<input name="<?php echo esc_attr( $field_name ); ?>" size="10" type="number" min="0" max="9999" value="<?php echo esc_attr( $days ); ?>">
			<?php esc_html_e( 'days', 'email-log' ); ?>
		</label>
		<p><em><?php esc_html_e( 'Runs once daily via WP Cron. Set to 0 to disable.', 'email-log' ); ?></em></p>
		<?php
	}

	public function sanitize_auto_delete_days( $value ) {
		return absint( $value );
	}
```

- [ ] **Step 4: Register CronManager in email-log.php**

In `email-log.php`, after the ExportAction line, add:

```php
	$email_log->add_loadie( new \EmailLog\Core\CronManager() );
```

- [ ] **Step 5: Commit**

```bash
git add include/Core/CronManager.php include/Core/UI/Setting/CoreSetting.php email-log.php
git commit -m "feat: add auto-delete logs older than N days via WP Cron"
```

---

### Task 13: Resend email from log

**Files:**
- Modify: `include/Core/Request/LogListAction.php`
- Modify: `include/Core/UI/ListTable/LogListTable.php`

- [ ] **Step 1: Add resend AJAX handler to LogListAction**

In `include/Core/Request/LogListAction.php`, in the `load()` method, add after the view-message action:

```php
		add_action( 'wp_ajax_el-log-list-resend-email', array( $this, 'resend_email' ) );
```

Then add the method after `view_log_message()`:

```php
	public function resend_email() {
		if ( ! current_user_can( LogListPage::CAPABILITY ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'email-log' ) );
		}

		check_ajax_referer( 'el-resend-email', 'nonce' );

		$id = absint( $_GET['log_id'] );
		if ( $id <= 0 ) {
			wp_send_json_error( __( 'Invalid log ID.', 'email-log' ) );
		}

		$log_items = $this->get_table_manager()->fetch_log_items_by_id( array( $id ) );
		if ( empty( $log_items ) ) {
			wp_send_json_error( __( 'Log not found.', 'email-log' ) );
		}

		$log_item = $log_items[0];

		$headers = array();
		if ( ! empty( $log_item['headers'] ) ) {
			$parser       = new \EmailLog\Util\EmailHeaderParser();
			$parsed       = $parser->parse_headers( $log_item['headers'] );
			$headers_text = $parser->join_headers( $parsed );
			if ( ! empty( $headers_text ) ) {
				$headers = explode( "\r\n", trim( $headers_text ) );
				$headers = array_filter( $headers );
			}
		}

		$sent = wp_mail( $log_item['to_email'], $log_item['subject'], $log_item['message'], $headers );

		if ( $sent ) {
			wp_send_json_success( __( 'Email resent successfully.', 'email-log' ) );
		} else {
			wp_send_json_error( __( 'Failed to resend email.', 'email-log' ) );
		}
	}
```

- [ ] **Step 2: Add resend action link in LogListTable**

In `include/Core/UI/ListTable/LogListTable.php`, inside `column_sent_date()`, after the delete action (after line 191), add:

```php
		$resend_url = add_query_arg(
			array(
				'action' => 'el-log-list-resend-email',
				'log_id' => $item->id,
				'nonce'  => wp_create_nonce( 'el-resend-email' ),
			),
			'admin-ajax.php'
		);

		$actions['resend'] = sprintf(
			'<a href="%s" class="el-resend-email" data-id="%d">%s</a>',
			esc_url( $resend_url ),
			$item->id,
			__( 'Resend', 'email-log' )
		);
```

- [ ] **Step 3: Add JS handler for resend**

In `assets/js/view-logs.js`, inside the `$( document ).ready` block, add:

```javascript
		$( document ).on( 'click', '.el-resend-email', function( event ) {
			event.preventDefault();
			var $link = $( this );
			if ( ! confirm( 'Resend this email?' ) ) {
				return;
			}
			$.get( $link.attr( 'href' ), function( response ) {
				if ( response.success ) {
					alert( response.data );
				} else {
					alert( 'Error: ' + response.data );
				}
			}).fail( function() {
				alert( 'Request failed.' );
			});
		});
```

- [ ] **Step 4: Commit**

```bash
git add include/Core/Request/LogListAction.php include/Core/UI/ListTable/LogListTable.php assets/js/view-logs.js
git commit -m "feat: add resend email from log entry"
```

---

### Task 14: Enhanced Dashboard Widget with stats

**Files:**
- Modify: `include/Core/UI/Component/DashboardWidget.php`
- Modify: `include/Core/DB/TableManager.php`

- [ ] **Step 1: Add stats method to TableManager**

In `include/Core/DB/TableManager.php`, add this new method after `get_logs_count()`:

```php
	/**
	 * Get email log statistics.
	 *
	 * @return array Stats array with total, success, failed, today counts.
	 */
	public function get_log_stats() {
		global $wpdb;
		$table = $this->get_log_table_name();

		$total   = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
		$success = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE result = %d", 1 ) ) );
		$failed  = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE result = %d", 0 ) ) );
		$today   = absint( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE sent_date >= %s",
			current_time( 'Y-m-d' ) . ' 00:00:00'
		) ) );

		return array(
			'total'   => $total,
			'success' => $success,
			'failed'  => $failed,
			'today'   => $today,
		);
	}
```

- [ ] **Step 2: Rewrite DashboardWidget::render()**

Replace the entire `render()` method in `include/Core/UI/Component/DashboardWidget.php`:

```php
	public function render() {
		$email_log = email_log();
		$stats     = $email_log->table_manager->get_log_stats();
		?>

		<div class="el-dashboard-stats" style="display: flex; gap: 15px; margin-bottom: 15px;">
			<div style="flex: 1; text-align: center; padding: 10px; background: #f0f0f1; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format( $stats['total'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Total', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #f0f0f1; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format( $stats['today'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Today', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #e6ffe6; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo esc_html( number_format( $stats['success'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'OK', 'email-log' ); ?></div>
			</div>
			<div style="flex: 1; text-align: center; padding: 10px; background: #ffe6e6; border-radius: 4px;">
				<div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo esc_html( number_format( $stats['failed'] ) ); ?></div>
				<div style="color: #646970;"><?php esc_html_e( 'Failed', 'email-log' ); ?></div>
			</div>
		</div>

		<?php do_action( 'el_inside_dashboard_widget' ); ?>

		<ul class="subsubsub" style="float: none">
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=email-log' ) ); ?>"><?php esc_html_e( 'View Logs', 'email-log' ); ?></a> <span style="color: #ddd"> | </span></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=email-log-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'email-log' ); ?></a></li>
		</ul>
		<?php
	}
```

- [ ] **Step 3: Commit**

```bash
git add include/Core/DB/TableManager.php include/Core/UI/Component/DashboardWidget.php
git commit -m "feat: enhanced dashboard widget with success/failed/today stats"
```

---

## Phase 4: Version Bump & Final Cleanup

### Task 15: Bump version and clean up upsell references

**Files:**
- Modify: `email-log.php:7`
- Modify: `assets/js/email-log-admin.js`
- Modify: `include/Core/UI/Setting/CoreSetting.php` (remove upsell render methods)

- [ ] **Step 1: Bump plugin version**

In `email-log.php`, change line 7:

```php
 * Version: 1.0.1
```
To:
```php
 * Version: 2.0.0
```

- [ ] **Step 2: Strip email-log-admin.js to minimal**

Replace entire `assets/js/email-log-admin.js` with:

```javascript
( function( $ ) {
	'use strict';
	// Email Log admin - minimal JS.
})( jQuery );
```

- [ ] **Step 3: Remove render_monitor_emails_settings and render_to/cc/bcc upsell methods from CoreSetting**

In `include/Core/UI/Setting/CoreSetting.php`, remove these methods entirely:

- `render_monitor_emails_settings()` (lines 545-563)
- `render_to_settings()` (lines 578-580)
- `render_cc_settings()` (lines 588-590)
- `render_bcc_settings()` (lines 598-600)
- `render_email_field()` (lines 611-621)
- `render_interval_settings()` (the old disabled one, if still present)
- `sanitize_interval()` (lines 565-569)

Also clean up the `sanitize()` method to remove to/cc/bcc handling since those fields are not real settings:

```php
	public function sanitize( $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		return $values;
	}
```

This works because the parent `Setting::sanitize()` already handles calling `sanitize_{field_id}` for each registered field.

Actually, we should use the parent's sanitize instead. Remove the `sanitize()` override entirely from CoreSetting (lines 623-635) -- the parent class `Setting::sanitize()` already does the right thing by iterating field_labels and calling `sanitize_{field_id}`.

- [ ] **Step 4: Commit**

```bash
git add email-log.php assets/js/email-log-admin.js include/Core/UI/Setting/CoreSetting.php
git commit -m "chore: bump to v2.0.0, remove upsell/PRO references"
```

---

### Task 16: Update CLAUDE.md with new architecture

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update CLAUDE.md to reflect v2.0.0 changes**

Update the CLAUDE.md to mention:
- Version 2.0.0
- New files: `CronManager.php`, `ExportAction.php`
- New features: CSV export, auto-delete, resend, filter by status, date range
- DB version 0.4 with indexes
- Removed: legacy files, wf-flyout, insertionQ

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md for v2.0.0"
```
