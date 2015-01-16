=== Simple Revisions Delete ===
Contributors: briKou
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7Z6YVM63739Y8
Tags: admin, plugin, blog, developper, metabox, ajax, WordPress, UX, ui, jquery, revision, revisions, database, purge, cleanup, clean, tools, best, post, edition, editing, delete, remove, bulk, bulk-action, nojs, CPT, custom post types, post type
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Simple Revisions Delete add a discreet link within a post submit box to let you purge (delete) its revisions via AJAX. Bulk action is also available.


== Description ==

= What does it do? =
It helps you keep a clean database by removing unnecessary posts revisions. Unlike other similar plugins, it lets you delete only specific posts revisions, not all your site revisions at once.
The plugin is perfectly integrated in the WordPress back-end, and uses native core functions to safely delete revisions.
It is very lightweight, very simple to use, and just does the job!

= How does it work? =

The plugin adds a discreet link in the post submit box, next to the default revisions counter (see screenshots section).
When you click on it, it will purge the appropriate post revisions via AJAX (no JS is also support).
It also add a new bulk action option in the post/page row view to let you purge revisions of multiple posts at once.


NOTE: There is no admin page for this plugin - none is needed.


= Post Types support =
The default supported post types are **post** and **page**, but you can easily add custom post types or remove default post types with the following hook:
`
function bweb_wpsrd_add_post_types( $postTypes ){
	$postTypes[] = 'additional-cpt';
	$postTypes[] = 'another-cpt';
	return $postTypes;
}
add_filter( 'wpsrd_post_types_list', 'bweb_wpsrd_add_post_types' );
`


= Languages =
The plugin only bears a few sentences, but you can easily translate them through .MO & .PO files. Currently available languages are:

* English
* French

Become a translator and send me your translation! [Contact-me](http://b-website.com/contact "Contact")

[CHECK OUT MY OTHER PLUGINS](http://b-website.com/category/plugins-en "More plugins by b*web")


**Please ask for help or report bugs if anything goes wrong. It is the best way to make the community benefit!**

= Known Issue =
If you are using W3 Total Cache plugin or other caching plugins which use Object Caching, you may not see any notification after a bulk action. 
To prevent this, turn off the **Object caching** service or juste refer to the page URL to see how much revisions have been deleted (rev_purged=XX).

== Installation ==

1. Upload and activate the plugin (or install it through the WP admin console)
2. That's it, it is ready to use!


== Frequently Asked Questions ==

= Who can purge my posts revisions? =
Only users who can delete a post can purge its revisions.

= Does it work with multisite? =
Yes.

= Does it work if javascript is not activated? =
Yes, but only when editing a post, not with the bulk action.


== Screenshots ==

1. The link location
2. Processing...
2. Done!
3. Bulk action

== Changelog ==

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

= 1.0 =
* First release.