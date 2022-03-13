<?php
/**
 * Plugin Name:     Cloud Upload for Formidable Forms
 * Plugin URI:      https://joneswebdesigns.com
 * Description:     A Formmidable Forms Addon that allows for the uploading of media items to an S3 compatible bucket.  A new field is created with the Form Builder to allow for easy implementation.  Works for Amazon S3 and Digital Ocean Spaces.
 * Author:          Jones Web Designs & Kishore Sahoo
 * Author URI:      https://joneswebdesigns.com
 * Text Domain:     formidable-digitalocean-spaces
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Formidable_Digitalocean_Spaces
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'FORMIDABLE_DIGITALOCEAN_SPACES_FILE' ) ) {
	define( 'FORMIDABLE_DIGITALOCEAN_SPACES_FILE', __FILE__ );
}

// Include the main class.
include_once dirname( FORMIDABLE_DIGITALOCEAN_SPACES_FILE ) . '/includes/class-formidable-digitalocean-spaces.php';

// Returns the main instance of Container.
function formidable_digitalocean_spaces() {
	return \Upnrunn\Formidable_Digitalocean_Spaces::instance();
}

// Global for backwards compatibility.
$GLOBALS['formidable_digitalocean_spaces'] = formidable_digitalocean_spaces();
