<?php
/**
 * This file is responsible for instantiating and 
 * loading all of the libraries necessary to use
 * syndication services inside of WordPress.
 * 
 * @author WriteHive <support@writehive.com>
 * @version 1.0.4
 * @link https://www.writehive.com/
 * @copyright 2011 WriteCrowd <https://www.writehive.com/>
 * @license GPL 3.0
 * 
 * WriteCrowd is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *   
 * WriteCrowd is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *   
 * You should have received a copy of the GNU General Public License
 * along with WriteCrowd.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * **********************************************
 * 	WordPress Plugin Headers
 * **********************************************
 *
 * Plugin Name: WriteHive Syndication
 * Plugin URI: https://www.writehive.com/
 * Description: Provides functionality necessary to use your WriteHive account inside of WordPress.
 * Version: 1.0.4
 * Author: WriteHive
 * Author URI: https://www.writehive.com/
 * License: GPL 3.0
 *
**/

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

// Load the required functions
require_once(ABSPATH.'wp-admin/includes/upgrade.php');

// Libraries to load
$aLibrary = array('whv_config', 'whv_actions');

// Load the libraries
foreach ($aLibrary as $sClass) {

    // Make sure the file exists
    if (file_exists(dirname(__FILE__)."/library/{$sClass}.php")) {

        // Load the class
        require_once(dirname(__FILE__)."/library/{$sClass}.php");
    }
}

// Set the path to the configuration file
Whv_Config::Init();

// Grab our class Instance
$oWriteCrowd = Whv_Actions::getInstance();

	// See if we can display PHP errors
	if (Whv_Config::Get('variables', 'enablePhpErrorReporting')) {
		
		// Turn on PHP errors
		$oWriteCrowd->setPhpErrorReporting(true);
	} else {
		
		// Turn off PHP errors
		$oWriteCrowd->setPhpErrorReporting(false);
	}

	// Set POST
	$oWriteCrowd->setPostData($_POST);

	// Set our namespace
	$oWriteCrowd->setNamespace(Whv_Config::Get('variables', 'nameSpace'));
	
	// Set plugin path
	$oWriteCrowd->setPluginPath(dirname(__FILE__));
	
	// Set plugin web path
	$oWriteCrowd->setPluginWebPath(WP_CONTENT_URL."/plugins/".Whv_Config::Get('variables', 'pluginName'));
	
	// Set the Database Object
	$oWriteCrowd->setDatabase($wpdb);
	
// Check for admin
if (is_admin()) {

	$whv_ns = $oWriteCrowd->getNameSpace();
	$whv_pn = Whv_Config::Get('variables', 'pluginName');
	if (file_exists(dirname(__FILE__)."/library/whv_admin.php")) {
		include(dirname(__FILE__)."/library/whv_admin.php");
		$oWhvAdmin = new Whv_Admin();
		$oWhvAdmin->wpdb = $wpdb;
		$oWhvAdmin->ns   = $whv_ns;
		register_activation_hook(   $whv_pn.'/'.basename(__FILE__), array($oWhvAdmin, 'runInstall'));
		register_deactivation_hook( $whv_pn.'/'.basename(__FILE__), array($oWhvAdmin, 'runUninstall'));
	}

	// Our Post Submission Ajaxer Server Function
	add_action("wp_ajax_{$oWriteCrowd->getNamespace()}", array($oWriteCrowd, 'parseAjax'));

    // Save Post
	add_action('publish_post', array($oWriteCrowd, 'handlePostData'));

    // Save Post
	// TODO: remove for 1.0.5
	add_action('wp_ajax_nopriv_whv_stat_sync', array($oWriteCrowd, 'renderStatSync'));

}

// Non-admin ajaxer
add_action("wp_ajax_nopriv_{$oWriteCrowd->getNamespace()}", array($oWriteCrowd, 'parseAjax'));

if (!function_exists( 'unregister_post_type')) {

    /**
     * This function removes custom
     * post types
     *
     * @param string $sPostType is the name of the post type to remove
     * @return bool
     */
    function unregister_post_type($sPostType) {

        // Get the list of post types
	    global $wp_post_types;

        // Make sure our post type is
        // actually set
	    if (isset($wp_post_types[$sPostType])) {

            // If so, remove it
		    unset($wp_post_types[$sPostType]);

            // Return success
		    return true;
	    }

        // Return failure
	    return false;
    }
}
