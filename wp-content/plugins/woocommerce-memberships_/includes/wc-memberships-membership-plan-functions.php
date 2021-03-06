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
 * Main function for returning a membership plan
 *
 * @since 1.0.0
 * @param mixed $post Post object or post ID of the membership plan.
 * @return WC_Memberships_Membership_Plan|bool false on failure
 */
function wc_memberships_get_membership_plan( $post = null ) {
	return wc_memberships()->plans->get_membership_plan( $post );
}

/**
 * Main function for returning all available membership plans
 *
 * @since 1.0.0
 * @param array $args Optional array of arguments. Same as for get_posts
 * @return array of WC_Memberships_Membership_Plan objects
 */
function wc_memberships_get_membership_plans( $args = null ) {
	return wc_memberships()->plans->get_membership_plans( $args );
}
