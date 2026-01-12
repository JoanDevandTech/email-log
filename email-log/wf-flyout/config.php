<?php
$config = array();

$config['plugin_screen'] = array('toplevel_page_email-log', 'email-log_page_email-log-settings');
$config['icon_border'] = 'none';
$config['icon_right'] = '35px';
$config['icon_bottom'] = '35px';
$config['icon_image'] = 'wpemaillog.png';
$config['icon_padding'] = '7px';
$config['icon_size'] = '65px';
$config['menu_accent_color'] = '#135a6a';
$config['custom_css'] = '#wf-flyout .wff-menu-item .dashicons.dashicons-universal-access { font-size: 30px; padding: 0px 10px 0px 0; } #wf-flyout .ucp-icon .wff-icon img { max-width: 66%; } #wf-flyout .ucp-icon .wff-icon { line-height: 57px; } #wf-flyout .wpr-icon .wff-icon { line-height: 62px; } #wf-flyout .wp301-icon .wff-icon img { max-width: 66%; } #wf-flyout .wp301-icon .wff-icon { line-height: 57px; } #wf-flyout .wpfssl-icon .wff-icon img { max-width: 66%; } #wf-flyout .wpfssl-icon .wff-icon { line-height: 57px; } #wf-flyout .emaillog-icon img { filter: grayscale(100%);}';

$config['menu_items'] = array(
  array('href' => '#', 'data' => 'data-pro-feature="flyout"', 'label' => 'Get Email Log PRO with a special discount', 'icon' => 'wpemaillog.png', 'class' => 'emaillog-icon open-pro-dialog'),
  array('href' => 'https://wpforcessl.com/?ref=wff-emaillog', 'label' => 'Fix all SSL problems &amp; monitor site in real-time', 'icon' => 'wp-ssl.png', 'class' => 'wpfssl-icon'),
  array('href' => 'https://wp301redirects.com/?ref=wff-emaillog&coupon=50off', 'label' => 'Fix 2 most common SEO issues on WordPress that most people ignore', 'icon' => '301-logo.png', 'class' => 'wp301-icon'),
  array('href' => 'https://wpreset.com/?ref=wff-emaillog&coupon=50off', 'target' => '_blank', 'label' => 'Get WP Reset PRO with 50% off', 'icon' => 'wp-reset.png', 'class' => 'wpr-icon'),
  array('href' => 'https://underconstructionpage.com/?ref=wff-emaillog&coupon=welcome', 'target' => '_blank', 'label' => 'Create the perfect Under Construction Page', 'icon' => 'ucp.png', 'class' => 'ucp-icon'),
  array('href' => 'https://wpsticky.com/?ref=wff-emaillog', 'target' => '_blank', 'label' => 'Make any element (header, widget, menu) sticky with WP Sticky', 'icon' => 'dashicons-admin-post'),
  array('href' => 'https://wordpress.org/support/plugin/email-log/reviews/#new-post', 'target' => '_blank', 'label' => 'Rate the Plugin', 'icon' => 'dashicons-thumbs-up'),
  array('href' => 'https://wordpress.org/support/plugin/email-log/#new-post', 'target' => '_blank', 'label' => 'Get Support', 'icon' => 'dashicons-sos'),
);
