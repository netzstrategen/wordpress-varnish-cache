<?php

/*
  Plugin Name: Varnish Cache
  Plugin URI: https://wordpress.org/plugins/varnish-cache/
  Version: 1.0.2
  Text Domain: varnish-cache
  Network: true
  Description: Integrates the Varnish Cache with WordPress.
  Author: netzstrategen
  Author URI: https://netzstrategen.com
  License: Apache-2.0
  License URI: http://www.apache.org/licenses/LICENSE-2.0
*/

/*
  Copyright 2015-2019 netzstrategen, netzstrategen.com
  Copyright 2013-2015 Mika A. Epstein, ipstenu.org
  Copyright 2013 Leon Weidauer, lnwdr.de

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

namespace Netzstrategen\Varnish;

if (!defined('ABSPATH')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  exit;
}

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class) {
  static $ns_offset;
  if (strpos($class, __NAMESPACE__ . '\\') === 0) {
    if ($ns_offset === NULL) {
      $ns_offset = strlen(__NAMESPACE__) + 1;
    }
    include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
  }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');

register_activation_hook(__FILE__, __NAMESPACE__ . '\Schema::activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\Schema::deactivate');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Schema::uninstall');

add_action('plugins_loaded', __NAMESPACE__ . '\Plugin::loadTextdomain');
add_action('init', __NAMESPACE__ . '\Plugin::init');
add_action('admin_init', __NAMESPACE__ . '\Admin::init');

if (defined('WP_CLI') && WP_CLI) {
  \WP_CLI::add_command('varnish', __NAMESPACE__ . '\CliCommand', ['shortdesc' => 'Manages the Varnish Cache.']);
}
