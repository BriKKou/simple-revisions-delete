<?php
/**
 * Plugin Name:       Simple Revisions Delete
 * Plugin URI:        https://b-website.com/simple-revisions-delete-free-wordpress-plugin
 * Description:       Delete your post revisions individually or all at once (purge or bulk action). Works with both the block editor and the classic editor.
 * Author:            Brice CAPOBIANCO
 * Author URI:        https://b-website.com/
 * Version:           2.0.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-revisions-delete
 * Domain Path:       /langs
 *
 * @package Simple_Revisions_Delete
 */

/*
	Copyright 2015 Brice CAPOBIANCO (contact: https://b-website.com/contact)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

defined( 'ABSPATH' ) || exit;

define( 'WPSRD_VERSION', '2.0.1' );
define( 'WPSRD_FILE', __FILE__ );
define( 'WPSRD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSRD_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSRD_BASENAME', plugin_basename( __FILE__ ) );

require_once WPSRD_PATH . 'includes/class-wpsrd-revisions.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-rest-controller.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-assets.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-classic-editor.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-block-editor.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-bulk-actions.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-notices.php';
require_once WPSRD_PATH . 'includes/class-wpsrd-plugin.php';

WPSRD_Plugin::boot();

register_activation_hook( __FILE__, array( 'WPSRD_Notices', 'on_activation' ) );
