<?php
/*
Plugin Name: GlotPress Additional Links
Plugin URI: http://glotpress.org/
Description: Add additional links to the GlotPress side menu and the WordPress admin menu.
Version: 0.5
Author: GlotPress
Author URI: http://glotpress.org
Tags: glotpress, glotpress plugin, translate
License: GPLv2 or later
*/

class GP_Additional_Links {
	public $id = 'additional-links';

	public function __construct() {
		
		// Check to see if there is a user currently logged in.
		if ( is_user_logged_in() ) {
			// If someone is logged in, get their user object.
			$user_obj = wp_get_current_user();
			
			// Load the user translate key from the WordPress user meta table, using the currently logged in user id.
			$user_key = get_user_meta( $user_obj->id, 'gp_google_translate_key', true );
			
			// If there is a user key, override the global key.
			if( $user_key ) { $this->key = $user_key; }
		}

		// Add the dashboard link to the side menu.
		add_filter( 'gp_nav_menu_items', array( $this, 'gp_nav_menu_items' ), 10, 2 );
		
		// Add the admin page to the WordPress settings menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10, 1 );
	}
	
	public function gp_nav_menu_items( $items, $location ) {
		$new = array();
		
		if( $location == 'side' ) {
			$new[admin_url()] = __('Dashboard');
		}
		
		return array_merge( $new, $items );
	}
	
	// This function adds the admin settings page to WordPress.
	public function admin_menu() {
		GLOBAL $menu;

		$image = plugins_url( '/GlotPress-Logo-20px.png', __FILE__ );
		
		// Add the menu to the admin menu.
		add_menu_page(__('GlotPress'), __('GlotPress'), 'read', __FILE__, array( $this, 'redirect_to_glotpress'), $image, 1 );
		
		// We're going to hack the menu info to use a link to the front end instead of calling the 'redirect_to_glotpress' function so we save a page load.
		foreach( $menu as $tag => $mi ) {
			if( $mi[0] == __( 'GlotPress' ) ) {
				$menu[$tag][2] = gp_url_public_root();
			}
		}
		
	}

	public function redirect_to_glotpress() {
		// Just a placeholder, we're going to replace the function call with a real link in the menu_order hook.
	}
}

// Add an action to WordPress's init hook to setup the plugin.  Don't just setup the plugin here as the GlotPress plugin may not have loaded yet.
add_action( 'gp_init', 'gp_additional_links_init' );

// This function creates the plugin.
function gp_additional_links_init() {
	GLOBAL $gp_additional_links;
	
	$gp_additional_links = new GP_Additional_Links;
}