<?php

namespace Netzstrategen\Varnish;

class CliCommand extends \WP_CLI_Command {

  /**
   * Flushes all content in the cache.
   *
   * @synopsis [--theme]
   */
  public function flush(array $args, array $options) {
    if (empty($options)) {
      Varnish::purgeUrl(home_url('/.*'), TRUE);
      \WP_CLI::success("Varnish cache flushed.");
    }
    elseif (!empty($options['theme'])) {
      $urls = [get_stylesheet_directory_uri()];
      if ($urls[0] !== $parent_theme_url = get_template_directory_uri()) {
        $urls[] = $parent_theme_url;
      }
      foreach ($urls as $url) {
        Varnish::purgeUrl($url . '/.*', TRUE);
        \WP_CLI::success("Varnish cache flushed for " . $url . '/*');
      }
    }
  }

}
