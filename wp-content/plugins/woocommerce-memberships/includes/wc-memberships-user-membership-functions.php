<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Main function for returning a user membership
 *
 * Supports getting user membership by membership ID, Post object
 * or a combination of the user ID and membership plan id/slug/Post object.
 *
 * If no $id is provided, defaults to getting the membership for the current user.
 *
 * @since 1.0.0
 * @param mixed $id Optional. Post object or post ID of the user membership, or user ID
 * @param mixed $plan Optional. Membership Plan slug, post object or related post ID
 * @return WC_Memberships_User_Membership
 */
function wc_memberships_get_user_membership( $id = null, $plan = null ) {
	return wc_memberships()->user_memberships->get_user_membership( $id, $plan );
}


/**
 * Get all user membership statuses
 *
 * @since 1.0.0
 * @return array
 */
function wc_memberships_get_user_membership_statuses() {
	return wc_memberships()->user_memberships->get_user_membership_statuses();
}


/**
 * Get the nice name for a user membership status
 *
 * @since  1.0.0
 * @param  string $status
 * @return string
 */
function wc_memberships_get_user_membership_status_name( $status ) {

	$statuses = wc_memberships_get_user_membership_statuses();
	$status   = 'wcm-' === substr( $status, 0, 4 ) ? substr( $status, 4 ) : $status;
	$status   = isset( $statuses[ 'wcm-' . $status ] ) ? $statuses[ 'wcm-' . $status ] : $status;

	return is_array( $status ) && isset( $status['label'] ) ? $status['label'] : $status;
}


/**
 * Get all memberships for a user
 *
 * @since 1.0.0
 * @param int $user_id Optional. Defaults to current user.
 * @return array|null array of user memberships
 */
function wc_memberships_get_user_memberships( $user_id = null ) {
	return wc_memberships()->user_memberships->get_user_memberships( $user_id );
}


/**
 * Check if user is an active member of a particular membership plan
 *
 * @since 1.0.0
 * @param int $user_id Optional. Defaults to current user.
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @return bool True, if is an active member, false otherwise
 */
function wc_memberships_is_user_active_member( $user_id = null, $plan ) {
	return wc_memberships()->user_memberships->is_user_active_member( $user_id, $plan );
}


/**
 * Check if user is a member of a particular membership plan
 *
 * @since 1.0.0
 * @param int $user_id Optional. Defaults to current user.
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @return bool True, if is a member, false otherwise
 */
function wc_memberships_is_user_member( $user_id = null, $plan ) {
	return wc_memberships()->user_memberships->is_user_member( $user_id, $plan );
}
