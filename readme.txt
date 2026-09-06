=== Simple Revisions Delete ===
Contributors: briKou
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7Z6YVM63739Y8
Tags: revisions, cleanup, delete, purge, gutenberg
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Delete the revisions of a post one by one or all at once, from the block editor, the classic editor or a bulk action.

== Description ==

**Delete the revisions of your posts individually or all at once, either from the editor or through a bulk action. Works with both the block editor and the classic editor.**

= What does it do? =

Simple Revisions Delete adds a discreet purge control next to the revisions counter of the post you are editing. One click and the revisions of that post are gone.

It helps you keep a clean database by removing unnecessary post revisions. Unlike most similar plugins, it lets you delete the revisions of a specific post rather than wiping every revision of your site at once.

The plugin blends into the WordPress admin, relies exclusively on core functions to delete revisions safely, and adds no settings page: there is nothing to configure.

= How does it work? =

* In the **block editor**, a "Purge" button sits in the post status panel of the sidebar, right below the publishing options. It tells you how many revisions the post has and deletes them all in one request.
* In the **classic editor**, the same control appears in the Publish metabox, and each revision listed in the Revisions metabox gets its own "Delete" button.
* In the **posts and pages list**, a "Purge revisions" bulk action lets you clean up several posts at once.

Everything happens through the WordPress REST API, so no page reload is needed. Without JavaScript, the classic editor control falls back to a regular form submission and the bulk action keeps working.

= What's new in 2.0? =

The block editor integration has been rewritten from scratch. The previous version relied on jQuery and on the internal markup of the editor, which stopped working when Gutenberg 7.1 reorganised its DOM. The purge control is now a genuine editor plugin (`PluginPostStatusInfo`) reading and writing through a dedicated REST API, so it no longer depends on class names that can change at any release.

The rest of the plugin has been rewritten too: one class per responsibility, no more inline `<style>` and `<script>` blocks, destructive actions moved off `GET` requests, native bulk action filters instead of injected jQuery, and an interface that works with a keyboard and a screen reader.

[See the plugin page](http://b-website.com/simple-revisions-delete-free-wordpress-plugin "Plugin page")

NOTE: this plugin has no settings page, and needs none.

= Post type support =

The supported post types are **post** and **page** by default. Use the `wpsrd_post_types_list` filter to add your own or to remove the defaults:

`
function bweb_wpsrd_add_post_types( $post_types ) {
	$post_types[] = 'additional-cpt';
	$post_types[] = 'another-cpt';

	return $post_types;
}
add_filter( 'wpsrd_post_types_list', 'bweb_wpsrd_add_post_types' );
`

Use [get_post_types()](https://developer.wordpress.org/reference/functions/get_post_types/) if you want to support every custom post type at once.

= Custom user capability =

The capability required to delete revisions is `delete_post`. Override it with the `wpsrd_capability` filter:

`
function bweb_wpsrd_capability() {
	return 'edit_post';
}
add_filter( 'wpsrd_capability', 'bweb_wpsrd_capability' );
`

= REST API =

The plugin registers three routes under the `wpsrd/v1` namespace. All of them check that the post type is supported and that the current user holds the capability above for the requested post:

* `GET /wpsrd/v1/posts/<id>/revisions` — returns the number of revisions
* `DELETE /wpsrd/v1/posts/<id>/revisions` — deletes every revision of the post
* `DELETE /wpsrd/v1/posts/<id>/revisions/<revision_id>` — deletes a single revision

Each response returns the number of deleted revisions and the number of revisions left.

= Languages =

The plugin only holds a handful of sentences, easy to translate through its `.po` and `.mo` files. Available languages:

* English
* French
* German — thanks to [mallard66](https://profiles.wordpress.org/mallard66 "mallard66")
* Dutch — thanks to [jondor](https://profiles.wordpress.org/jondor "jondor")

Want to add yours? [Get in touch](http://b-website.com/contact "Contact")

[CHECK OUT MY OTHER PLUGINS](http://b-website.com/category/plugins-en "More plugins by b*web")

**Please ask for help or report bugs if anything goes wrong. It is the best way to make the whole community benefit from it!**


== Installation ==

1. Upload and activate the plugin (or install it from the WordPress plugin directory)
2. That's it, it is ready to use


== Frequently Asked Questions ==

= Who can purge the revisions of a post? =

Only users allowed to delete that post, unless you change the required capability with the `wpsrd_capability` filter.

= Does it work on multisite? =

Yes.

= Does it work without JavaScript? =

In the classic editor, yes: the purge control is a plain form and falls back to a full page submission. The bulk action works without JavaScript too. The block editor itself requires JavaScript, so its purge button does as well.

= Can I delete a single revision? =

Yes, from the Revisions metabox of the classic editor: every listed revision has its own "Delete" button. In the block editor, only the full purge is available.

= Does deleting revisions affect the published post? =

No. Only revisions are deleted, through the core `wp_delete_post_revision()` function. The post itself and its current content are never touched.

= Is there a settings page? =

No, and none is needed. The two filters documented above are the only configuration points.


== Screenshots ==

1. The purge control in the classic editor
2. Purge in progress
3. Purge done
4. The bulk action
5. Deleting a single revision
6. The purge control in the block editor

== Changelog ==

= 2.0.1 - 2026/09/06 =
* Fix: the 2.0.0 tag was published incorrectly and did not contain the code described below; this release republishes the 2.0.0 changes correctly

= 2.0.0 - 2026/09/02 =
* Block editor: integration rewritten as a real editor plugin (`PluginPostStatusInfo` + REST API). The purge control had been broken since Gutenberg 7.1, when the DOM the old jQuery workaround relied on was reorganised
* Block editor: a single revisions row now carries the count and the purge button, replacing the native one instead of adding a second row
* Block editor: the revision count is read from the REST API and refreshed after every save
* New: `wpsrd/v1` REST namespace replacing the admin-ajax endpoints
* Security: destructive actions no longer travel through `GET` requests
* Security: dismissing the "revisions are disabled" notice is now nonce protected
* Security: capability and post type are checked again on every request, whatever the entry point
* Accessibility: real buttons instead of placeholder links, live regions, focus moved to the closest remaining control after a deletion, screen-reader announcements, no feedback conveyed by colour alone
* Bulk action: now built on the native `bulk_actions` and `handle_bulk_actions` filters instead of a jQuery-injected option
* Refactoring: one class per responsibility, PHPDoc throughout, no more inline `<style>` and `<script>` blocks
* Classic editor: the purge control sits next to the core revisions counter again, and disappears once the last revision is gone
* Classic editor: deleting revisions one by one decrements the counter live
* Notices now travel through query arguments instead of transients
* Translations updated for French, German and Dutch, and a `.pot` template added
* Requires WordPress 6.4 and PHP 7.4

= 1.5.5 – 2025/11/30 =
* Tested on WP 6.9 with success!
* Update readme

= 1.5.4 – 2024/03/07 =
* Security fix – Cross Site Request Forgery (CSRF)
* Update readme

= 1.5.3 – 2024/03/07 =
* Tested on WP 6.4.3 with success!
* Update readme

= 1.5.2 – 2022/10/24 =
* Tested on WP 6.0.3 with success!
* Update readme


= 1.5.1 – 2020/09/16 =
* Tested on WP 5.5.1 with success!
* Remove W3 Total Cache fix from 1.3
* Coding standards improvements

= 1.5 – 2019/11/14 =
* BETA FEATURE : Add Gutenberg editor compatibility
* Tested on WP 5.3 with success!
* Replace depreciated jQuery "live" API by "on"
* readme.txt update

= 1.4.7 – 2016/11/29 =
* Bug fix : fix an issue with WooCommerce duplicate product

= 1.4.6 – 2016/11/03 =
* Change text-domain to take advantage of language packs translate.wordpress.org

= 1.4.5 =
* Better respect WordPress Coding standards
* readme.txt update

= 1.4.4 =
* Tested on WP 4.3 with success!

= 1.4.3 =
* Dutch translation by [jondor](https://profiles.wordpress.org/jondor "jondor")

= 1.4.2 =
* Fix a bug when clicking on the revision link in the revision's metabox
* Change button on single revision delete by a more discreet link
* Tested on WP 4.2 with success!
* readme.txt update

= 1.4.1 =
* Fix a bug when W3 Total Cache is activated and plugins updates are available
* Fix a bug where delete button appears in the admin bottom
* Minor JS improvement
* Loader added during single revision deletion
* Change the default primary button (blue) to normal button (grey) for UX purpose

= 1.4 =
* Adding conditionnal extra notice on bulk delete
* Deutsch translation by [mallard66](https://profiles.wordpress.org/mallard66 "mallard66")

= 1.3.3 =
* Fixe Transients filtering issue

= 1.3.2 =
* PHP notices fix

= 1.3.1 =
* New screenshot added
* Readme.txt update

= 1.3 =
* Minor PHP fixes
* JS improvements
* Works with W3 Total Cache object caching
* New feature: revisions can be deleted individually
* User capability support with the new **wpsrd_capability** hook
* Readme.txt update

= 1.2.1 =
* URL parameter added on bulk action 
* Readme.txt update for W3 Total Cache issue

= 1.2 =
* NEW FEATURE: Bulk revisions delete 
* Plugin file refactoring
* Custom post type's support with the new **wpsrd_post_types_list** hook
* Readme.txt update

= 1.1.1 =
* Hide revisions metabox on revisions purge success.

= 1.1 =
* Better security.
* Check if revisions are activated on plugin activation
* No JS is now supported
* Remove inline CSS
* Readme.txt update
* Special thanks to [Julio Potier](https://profiles.wordpress.org/juliobox "Julio Potier") for his help in improving the plugin :)

= 1.0 =
* First release.


== Upgrade Notice ==

= 2.0.1 =
Corrects a botched 2.0.0 release that did not contain the intended code. Everyone should update to this version.

= 2.0.0 =
Major release. The block editor purge control works again, and the plugin now requires WordPress 6.4 and PHP 7.4. If you built anything on the old admin-ajax endpoints, switch to the `wpsrd/v1` REST routes documented in the description.

= 1.0 =
First release.