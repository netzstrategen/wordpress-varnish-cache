<?php

/**
 * @file
 * Contains \Netzstrategen\Varnish\Varnish.
 */

namespace Netzstrategen\Varnish;

/**
 * Varnish client functionality.
 */
class Varnish {

  /**
   * Associative array whose keys are URLs to purge and whose values are Booleans indicating regex.
   *
   * @var array
   */
  public static $purgeUrls = [];

  /**
   * Purges a given URL from Varnish.
   *
   * @param string $url
   *   The absolute URL to purge.
   * @param bool $is_regex
   *   Whether $url is a regular expression.
   */
  public static function purgeUrl($url, $is_regex = FALSE) {
    $headers = [];
    if ($is_regex) {
      $headers['X-Purge-Method'] = 'regex';
    }

    $url_parts = parse_url($url);
    $path = $url_parts['path'] ?? '';

    if (defined('VARNISH_HOST')) {
      $varnish_purge_url = rtrim(VARNISH_HOST, '/') . $path;
    }
    else {
      $varnish_purge_url = 'http://' . $url_parts['host'] . $path;
    }

    $request_options = [
      'method' => 'PURGE',
      'headers' => [
        'Host' => $url_parts['host'],
      ] + $headers,
    ];
     $response = wp_remote_request($varnish_purge_url, $request_options);
     // curl -v -X PURGE -H 'Host: example.com' -H 'X-Purge-Method: regex' 'http://127.0.0.1/.*'

     if (defined('VARNISH_DEBUG_LOG') && VARNISH_DEBUG_LOG) {
       static $first = TRUE;

       $log = '';
       if ($first) {
         $log .= "\n--- [" . date('Y-m-d H:i:s') . "] ---\n";
         $first = FALSE;
       }
       $log .= $request_options['method'] . ' ' . $varnish_purge_url . "\n";
       foreach ($request_options['headers'] as $key => $value) {
         $log .= "$key: $value\n";
       }
       //$log .= var_export($response, TRUE);
       file_put_contents(wp_upload_dir()['basedir'] . '/varnish.log', $log, FILE_APPEND);
     }

     do_action('varnish/purge-url/after', $url, $varnish_purge_url, $is_regex);
   }

}
