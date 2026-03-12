<?php
/**
 * Plugin Name: Spectra Pro
 * Plugin URI: https://wpspectra.com/
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Description: Enhance Spectra with new features and blocks, as well as extended functionality for existing blocks.
 * Version: 1.2.9
 * License: GPL v2
 * Text Domain: spectra-pro
 *
 * @package spectra-pro
 */

/**
 * Set constants.
 */
define( 'SPECTRA_PRO_FILE', __FILE__ );
define( 'SPECTRA_PRO_BASE', plugin_basename( SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_DIR', plugin_dir_path( SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_URL', plugins_url( '/', SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_VER', '1.2.9' );
define( 'SPECTRA_CORE_REQUIRED_VER', '2.19.18' );

function remove_bsf_license_notices() { remove_action( 'admin_notices', 'bsf_notices', 1000 ); remove_action( 'network_admin_notices', 'bsf_notices', 1000 ); } add_action( 'admin_head', 'remove_bsf_license_notices' );

require_once 'plugin-loader.php';
