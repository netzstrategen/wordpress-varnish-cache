<?php

/**
 * @file
 * Contains \Netzstrategen\Varnish\Elementor.
 */

namespace Netzstrategen\Varnish;

/**
 * Elementor integration.
 */
class Elementor {

  /**
   * @implements init
   */
  public static function init() {
    add_action('elementor/editor/after_save', Elementor::class . '::editor_after_save');
  }

  /**
   * @implements elementor/editor/after_save
   */
  public static function editor_after_save($post_id) {
    if (get_post_status($post_id) !== 'publish') {
      return;
    }
    if ($url = get_permalink($post_id)) {
      Varnish::$purgeUrls[$url] = FALSE;
    }
    $baseurl = wp_upload_dir();
    $baseurl = $baseurl['baseurl'];
    Varnish::$purgeUrls["$baseurl/elementor/css/post-{$post_id}.css"] = FALSE;
  }

}
