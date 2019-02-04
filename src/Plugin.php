<?php

/**
 * @file
 * Contains \Netzstrategen\Varnish\Plugin.
 */

namespace Netzstrategen\Varnish;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'varnish-cache';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * @var string
   */
  private static $baseUrl;

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

  /**
   * @implements init
   */
  public static function init() {
    add_action('save_post', __CLASS__ . '::purgePost', 10, 2);
    add_action('deleted_post', __CLASS__ . '::purgePost', 10, 2);
    add_action('trashed_post', __CLASS__ . '::purgePost', 10, 2);
    add_action('edit_post', __CLASS__ . '::purgePost', 10, 2);
    add_action('delete_attachment', __CLASS__ . '::purgePost', 10, 2);

    add_action('wp_update_attachment_metadata', __CLASS__ . '::purgeAttachmentMeta', 50, 2);

    add_action('switch_theme', __CLASS__ . '::purgeAll');

    add_action('shutdown', __CLASS__ . '::executePurge');
  }

  /**
   * Registers the given post, associated terms, authors, and feeds for purging.
   *
   * @param int $postId
   *   The ID of the post to purge.
   */
  public static function purgePost($postId) {
    $permalink = get_permalink($postId);
    // Skip post revisions.
    if ($permalink === FALSE) {
      return;
    }
    // Post URL.
    Varnish::$purgeUrls[$permalink] = FALSE;
    // Post pagination, feeds, and other attached URLs.
    Varnish::$purgeUrls[$permalink . '/.*'] = TRUE;

    // All associated public terms (categories, tags, custom taxonomies).
    foreach (wp_get_object_terms($postId, get_taxonomies(['public' => TRUE])) as $term) {
      $term_link = get_term_link($term);
      Varnish::$purgeUrls[$term_link] = FALSE;
      Varnish::$purgeUrls[$term_link . '/page/.*'] = TRUE;
      Varnish::$purgeUrls[$term_link . '/feed'] = FALSE;

      // Walk up parent terms in the hierarchy.
      while ($term->parent) {
        $term = get_term($term->parent, $term->taxonomy);
        $term_link = get_term_link($term);
        Varnish::$purgeUrls[$term_link] = FALSE;
        Varnish::$purgeUrls[$term_link . '/page/.*'] = TRUE;
        Varnish::$purgeUrls[$term_link . '/feed'] = FALSE;
      }
    }

    // Author URL.
    $author_id = get_post_field('post_author', $postId);
    $author_url = get_author_posts_url($author_id);
    Varnish::$purgeUrls[$author_url] = FALSE;
    Varnish::$purgeUrls[$author_url . '/page/.*'] = TRUE;
    Varnish::$purgeUrls[get_author_feed_link($author_id)] = FALSE;

    // Post type archive and feed.
    $post_type = get_post_type($postId);
    if ($post_type_archive_url = get_post_type_archive_link($post_type)) {
      Varnish::$purgeUrls[$post_type_archive_url] = FALSE;
      Varnish::$purgeUrls[$post_type_archive_url . '/page/.*'] = TRUE;
      Varnish::$purgeUrls[$post_type_archive_url . '/feed'] = FALSE;
      Varnish::$purgeUrls[get_post_type_archive_feed_link($post_type)] = FALSE;
    }

    // Site-wide feeds.
    Varnish::$purgeUrls[get_bloginfo_rss('rss2_url')] = FALSE;
    Varnish::$purgeUrls[get_bloginfo_rss('rdf_url')] = FALSE;
    Varnish::$purgeUrls[get_bloginfo_rss('rss_url')] = FALSE;
    Varnish::$purgeUrls[get_bloginfo_rss('atom_url')] = FALSE;
    Varnish::$purgeUrls[get_bloginfo_rss('comments_rss2_url')] = FALSE;
    Varnish::$purgeUrls[get_post_comments_feed_link($postId)] = FALSE;

    // Frontpage (posts page).
    Varnish::$purgeUrls[home_url('/')] = FALSE;

    if (get_option('show_on_front') === 'page') {
      Varnish::$purgeUrls[get_permalink(get_option('page_for_posts'))] = FALSE;
    }

    /**
     * Filters URLs to purge for a given post.
     *
     * @param array $urls
     *   An associative array whose keys are URLs to purge and whose values
     *   indicate whether the URL in the key is a regular expression.
     * @param int $post_id
     *   The ID of the post to purge.
     *
     * @since 4.0.0
     */
    Varnish::$purgeUrls = apply_filters('varnish/purge/post', Varnish::$purgeUrls, $postId);
  }

  /**
   * Registers the updated media file and its derivative image sizes for purging.
   *
   * @param array $attachment
   *   The data of the attachment to purge.
   * @param int $postId
   *   The ID of the post the media file is attached to.
   */
  public static function purgeAttachmentMeta($attachment, $postId) {
    if (!empty($attachment['file'])) {
      $baseurl = wp_upload_dir();
      $baseurl = $baseurl['baseurl'];
      Varnish::$purgeUrls[$baseurl . '/' . $attachment['file']] = FALSE;
      if (isset($attachment['sizes'])) {
        foreach ($attachment['sizes'] as $size) {
          Varnish::$purgeUrls[$baseurl . '/' . dirname($attachment['file']) . '/' . $size['file']] = FALSE;
        }
      }
    }
    return $attachment;
  }

  /**
   * Registers the whole cache to be purged.
   */
  public static function purgeAll() {
    Varnish::$purgeUrls[home_url('/.*')] = TRUE;
  }

  /**
   * Purges all collected URLs at end of request.
   */
  public static function executePurge() {
    if (empty(Varnish::$purgeUrls)) {
      if (isset($_GET['varnish_flush_all']) && current_user_can('manage_options') && check_admin_referer(Plugin::PREFIX)) {
        Varnish::purgeUrl(home_url('/.*'), TRUE);
      }
    }
    else {
      foreach (Varnish::$purgeUrls as $url => $is_regex) {
        Varnish::purgeUrl($url, $is_regex);
      }
    }
  }

  /**
   * The base URL path to this plugin's folder.
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(static::$baseUrl)) {
      static::$baseUrl = plugins_url('', static::getBasePath() . '/plugin.php');
    }
    return static::$baseUrl;
  }

  /**
   * The absolute filesystem base path of this plugin.
   *
   * @return string
   */
  public static function getBasePath() {
    return dirname(__DIR__);
  }

}
