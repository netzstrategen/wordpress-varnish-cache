<?php

/**
 * @file
 * Contains \Netzstrategen\Varnish\Admin.
 */

namespace Netzstrategen\Varnish;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * @implements admin_init
   */
  public static function init() {
    // Confirm manual purge.
    if (isset($_GET['varnish_flush_all']) && check_admin_referer(Plugin::PREFIX)) {
      add_action('admin_notices', __CLASS__ . '::admin_notices_purged');
    }

    // Require custom permalinks to be set up.
    if (!get_option('permalink_structure') && current_user_can('manage_options')) {
      add_action('admin_notices', __CLASS__ . '::admin_notices_permalinks');
    }

    // Output manual purge button in admin bar.
    if (static::hasPurgeAccess()) {
      add_action('admin_bar_menu', __CLASS__ . '::admin_bar_menu', 100);
    }
    // Output manual purge button on admin dashboard.
    add_action('activity_box_end', __CLASS__ . '::activity_box_end', 100);
  }

  /**
   * Returns whether current user can manually purge Varnish.
   */
  public static function hasPurgeAccess() {
    global $blog_id;

    // SingleSite admin can always purge.
    if (!is_multisite()) {
      return current_user_can('activate_plugins');
    }
    // Multisite network admin can always purge.
    if (current_user_can('manage_network')) {
      return TRUE;
    }
    // Multisite site admins can purge, unless this is a subfolder install and
    // this is the first site.
    if (SUBDOMAIN_INSTALL || (!SUBDOMAIN_INSTALL && (BLOG_ID_CURRENT_SITE != $blog_id))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @implements admin_notices
   */
  public static function admin_notices_purged() {
    echo '<div class="updated fade"><p><strong>' . __('Varnish cache purged.', Plugin::L10N) . '</strong></p></div>';
  }

  /**
   * @implements admin_notices
   */
  public static function admin_notices_permalinks() {
    echo '<div class="error"><p>' . __('Varnish requires custom permalinks. Please go to the <a href="options-permalink.php">Permalinks Options Page</a> to configure them.', Plugin::L10N) . '</p></div>';
  }

  /**
   * @implements admin_bar_menu
   */
  public static function admin_bar_menu($admin_bar) {
    $admin_bar->add_menu([
      'id' => 'purge-varnish-cache-all',
      'title' => __('Purge Varnish', Plugin::L10N),
      'href' => wp_nonce_url(add_query_arg('varnish_flush_all', 1), Plugin::PREFIX),
      'meta' => [
        'title' => __('Purge Varnish', Plugin::L10N),
      ],
    ]);
  }

  /**
   * @implements activity_box_end
   */
  public static function activity_box_end() {
    global $blog_id;

    $output = '<span style="flex-grow: 1; line-height: 2.3;">';
    $output .= __('Content edits are automatically reflected in Varnish cache.', Plugin::L10N);
    $output .= '</span>';

    if (static::hasPurgeAccess()) {
      $url = wp_nonce_url(admin_url('?varnish_flush_all'), Plugin::PREFIX);
      $output .= '<a class="button" href="' . $url . '">';
      $output .= __('Manually flush cache', Plugin::L10N);
      $output .= '</a>';
    }
    echo '<div class="varnish-rightnow" style="display: flex;">' . $output . '</div>';
  }

}
