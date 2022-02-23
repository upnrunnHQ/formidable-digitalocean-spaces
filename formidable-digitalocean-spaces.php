<?php
/**
 * Plugin Name:     Digitalocean Spaces for Formidable Forms
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
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
