<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	Inosencio_Roles
 * @subpackage Inosencio_Roles/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		Inosencio_Roles
 */
function INOSENCIOROLES() {
	return Inosencio_Roles::instance();
}

/**
 * Gets the current user role.
 *
 * @since		1.1.0
 * @return		string Current role.
 */
function inosencio_userroles_current_role() {
	return INOSENCIOROLES()->current_role;
}