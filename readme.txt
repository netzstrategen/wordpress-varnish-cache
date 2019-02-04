=== Varnish ===
Contributors: netzstrategen, tha_sun, techpriester, Ipstenu, DH-Shredder
Tags: varnish, cache, proxy, reverse-proxy, purge, ban, performance, speed, PageSpeed, caching
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.0.0

Integrates the Varnish Cache with WordPress.

== Description ==

This plugin enables persistent caching of all content in Varnish with a very
long lifetime (TTL) while still publishing new and changed content sooner.

When creating or editing a post, WordPress notifies Varnish by issuing PURGE
(BAN) requests to remove all of the post's associated URLs from the cache, so
that they will be refetched from the (WordPress) backend when they are requested
again:

- The post's own page URL, pagination, and comment feed
- Listing pages of all public terms (categories including their parents, tags,
  etc) associated with the post
- Listing pages of the post's author
- Site-wide feeds
- The site's frontpage

<a href="https://www.varnish-cache.org/">Varnish</a> is a web application
accelerator also known as a caching HTTP reverse proxy. You install it in front
of your website and configure it to cache its contents. This plugin does not
install Varnish for you, nor does it configure Varnish for WordPress.

Site administrators are able to manually purge all content in Varnish using a
button in the admin bar. On a multisite network, only network/super admins are
able to purge manually.

This plugin is actively used by major publishing websites with millions of
published posts and thus considered mature enough to be used in production.
However, patches and improvements are always welcome,
<a href="https://github.com/netzstrategen/wordpress-varnish">development happens
on GitHub</a>.


== Installation ==

1. Install and activate the plugin as usual.

2. Configure the IP address and optionally port of your Varnish server in the
   `VARNISH_HOST` constant in your `wp-config.php`:
   ```
   const VARNISH_HOST = 'http://127.0.0.1:8080';
   ```


= Requirements =

* PHP 7.0 or later.
* Varnish 3.x or later.
* Pretty Permalinks.


== Frequently Asked Questions ==

= What versions of Varnish are supported? =

Every version above Varnish 3.


= Why doesn't every page refresh when I make a new post? =

Only relevant URLs are selectively invalidated in the cache in order to serve
all other existing pages as quickly as possible and avoid slow response times
and a high load on the webserver whenever content changes.

The only way to improve this in the future would be to implement full support
for Varnish Cache Tags. However, this would have to be supported from the entire
application (WordPress Core) to work flawlessly. (See Drupal 8+ or the ambitious
<a href="https://wordpress.org/plugins/pantheon-advanced-page-cache/">Pantheon Advanced Page Cache</a>
for an example of such efforts.)

However, a simplified "flush all upon any change" option would be possible and
could be added if there is sufficient interest.


= Why are my theme changes not visible? =

Only content that is updated through the WordPress application causes relevant
URLs to be purged in the Varnish cache.

After changing code or doing anything else outside of WordPress API functions
you need to manually purge all affected content from the cache. You may use the
links to flush all content in the administration to do so.

Alternatively, use the following command to purge only files in your theme:

```
curl -X PURGE -H 'Host: example.com' -H 'X-Purge-Method: regex' 'http://127.0.0.1/wp-content/themes/mytheme/.*'
```


= How do I manually purge the whole cache? =

As a user with administrator privileges, you can click the button "Purge Varnish
Cache" button in the admin toolbar or on the administrative dashboard.

If you do not see these buttons, you do not have sufficient privileges.

In a multisite installation, only network/super administrators are able to
manually purge the full cache, as that affects all sites in the cache.


= Can I use this with a CDN or proxy service like CloudFlare? =

Yes, but when you use CloudFlare or similar services, you have a proxy server in
front of the Varnish cache, which is a (reverse) proxy on its own. Ensure to
set the proper IP address of your Varnish cache (and not the one of the CDN) in
your wp-config.php.


= How do I find my Varnish IP address? =

The IP addresses from which Varnish can be purged are configured in your VCL,
check the configuration for e.g. `acl purge`.

If your Varnish server listens to multiple IPs, pick the private IP address that
can be accessed from the network of your webserver.

Also make sure to include the port (unless Varnish listens on 80), for example:
```
const VARNISH_HOST = 'http://127.0.0.1:8080';
```


= How do I configure my Varnish VCL? =

Support on configuring Varnish is not provided by this plugin. Please contact
your hosting provider instead.

Your Varnish configuration must support PURGE/BAN requests. Helpful examples may
be contributed and found in the
<a href="https://github.com/netzstrategen/wordpress-varnish/wiki">Varnish Plugin Wiki</a>.


= How does this plugin differ from Proxy Cache Purge / varnish-http-purge? =

_Proxy Cache Purge/Varnish HTTP Purge_ was authored for the masses and attempts
to support users on-screen with basic setup questions, and thus carries a lot of
unnecessary weight for a performance plugin that is supposed to make your site
faster (but unnecessary features make it slower). In addition, the plugin's code
and architecture was never overhauled and modernized for standards and
performance.

The Varnish plugin follows a different approach and focuses on performance only.

Ultimately, the goal is to hide the whole infrastructure scaling problem away
from the users. The button(s) to manually purge the cache are unwanted and can
hopefully be removed soon.


== Changelog ==

= 1.0.0 =
Refactored the plugin code inherited from varnish-http-purge to establish a
modern and fast plugin architecture.

API changes:

* Constant `VHP_VARNISH_IP` in wp-config.php has been renamed to `VARNISH_HOST`
  and includes the protocol/schema and optionally port now:
```diff
-const VHP_VARNISH_IP = '127.0.0.1';
+const VARNISH_HOST = 'http://127.0.0.1:8080';
```

* Filter 'varnish_http_purge_schema' has been removed.

* Filter 'vhp_purge_urls' has been renamed to 'varnish/purge/post':
```diff
-add_filter('vhp_purge_urls', ...)
+add_filter('varnish/purge/post', ...)
```

* Class VarnishPurger no longer exists; use the appropriate filters to purge
  further URLs instead (or create PRs to suggest new integration points).

* Action 'after_purge_url' has been renamed:
```diff
-add_action('after_purge_url', ...);
+add_action('varnish/purge-url/after', ...);
```


The varnish-cache plugin was originally forked from
<a href="https://wordpress.org/plugins/varnish-http-purge/">varnish-http-purge</a>
v3.7.3 in 2015. Credits go to the original authors for the initial integration
ideas.
