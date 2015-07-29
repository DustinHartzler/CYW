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
 * Get valid restriction message types
 *
 * @since 1.0.0
 * @return array
 */
function wc_memberships_get_valid_restriction_message_types() {

	/**
	 * Filter valid restriction message types
	 *
	 * @since 1.0.0
	 * @param array
	 */
	return apply_filters( 'wc_memberships_valid_restriction_message_types', array(
		'content_restricted',
		'product_viewing_restricted',
		'product_purchasing_restricted'
	) );
}


if ( ! function_exists( 'wc_memberships_restrict' ) ) {

	/**
	 * Restrict content to specified membership plans
	 *
	 * @since 1.0.0
	 * @param string $content
	 * @param array $plans
	 */
	function wc_memberships_restrict( $content, $plans ) {

		$has_access = false;

		// grant access to super users
		if ( current_user_can( 'wc_memberships_access_all_restricted_content' ) ) {
			$has_access = true;
		}

		// default to use all plans if no plan is specified
		if ( empty( $plans ) ) {
			$plans = wc_memberships_get_membership_plans();
		}

		foreach ( $plans as $plan_id_or_slug ) {
			$membership_plan = wc_memberships_get_membership_plan( $plan_id_or_slug );

			if ( $membership_plan && wc_memberships_is_user_active_member( get_current_user_id(), $membership_plan->get_id() ) ) {
				$has_access = true;
				break;
			}
		}

		if ( $has_access ) {
			echo $content;
		}
	}
}


if ( ! function_exists( 'wc_memberships_is_post_content_restricted' ) ) {

	/**
	 * Check if a post/page content is restricted
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post
	 * @return bool True, if content has restriction rules, false otherwise
	 */
	function wc_memberships_is_post_content_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$rules = wc_memberships()->rules->get_post_content_restriction_rules( $post_id );

		return ! empty( $rules );
	}

}


if ( ! function_exists( 'wc_memberships_is_product_viewing_restricted' ) ) {

	/**
	 * Check if viewing a product is restricted
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post
	 * @return bool True, if product viewing is restricted, false otherwise
	 */
	function wc_memberships_is_product_viewing_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$rules = wc_memberships()->rules->get_the_product_restriction_rules( $post_id );
		$is_restricted = false;

		if ( ! empty( $rules ) ) {

			foreach ( $rules as $rule ) {

				if ( 'view' == $rule->get_access_type() ) {

					$is_restricted = true;
				}
			}
		}

		return $is_restricted;
	}

}


if ( ! function_exists( 'wc_memberships_is_product_purchasing_restricted' ) ) {

	/**
	 * Check if purchasing a product is restricted
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post
	 * @return bool True, if product purchasing is restricted, false otherwise
	 */
	function wc_memberships_is_product_purchasing_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$rules = wc_memberships()->rules->get_the_product_restriction_rules( $post_id );

		$is_resticted = false;

		if ( ! empty( $rules ) ) {

			foreach ( $rules as $rule ) {

				if ( 'purchase' == $rule->get_access_type() ) {

					$is_resticted = true;
				}
			}
		}

		return $is_resticted;
	}

}


if ( ! function_exists( 'wc_memberships_product_has_member_discount' ) ) {

	/**
	 * Check if the product (or current product) has any member discounts
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID. Optional, defaults to current product.
	 * @return boolean True, if is elgibile for discount, false otherwise
	 */
	function wc_memberships_product_has_member_discount( $product_id = null ) {

		if ( ! $product_id ) {

			global $product;
			$product_id = $product->id;
		}

		return wc_memberships()->rules->product_has_member_discount( $product_id );
	}
}


if ( ! function_exists( 'wc_memberships_user_has_member_discount' ) ) {

	/**
	 * Check if the current user is eligible for member discount for the current product
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID. Optional, defaults to current product.
	 * @return boolean True, if is elgibile for discount, false otherwise
	 */
	function wc_memberships_user_has_member_discount( $product_id = null ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! $product_id ) {

			global $product;
			$product_id = $product->id;
		}

		$product      = wc_get_product( $product_id );
		$user_id      = get_current_user_id();
		$has_discount = wc_memberships()->rules->user_has_product_member_discount( $user_id, $product_id );

		if ( ! $has_discount && $product->has_child() ) {
			foreach ( $product->get_children( true ) as $child_id ) {

				$has_discount = wc_memberships()->rules->user_has_product_member_discount( $user_id, $child_id );

				if ( $has_discount ) {
					break;
				}
			}
		}

		return $has_discount;
	}
}


if ( ! function_exists( 'wc_memberships_show_product_loop_member_discount_badge' ) ) {

	/**
	 * Get the member discount badge for the loop.
	 *
	 * @since 1.0.0
	 */
	function wc_memberships_show_product_loop_member_discount_badge() {
		wc_get_template( 'loop/member-discount-badge.php' );
	}
}


if ( ! function_exists( 'wc_memberships_show_product_member_discount_badge' ) ) {

	/**
	 * Get the member discount badge for the single product page.
	 *
	 * @since 1.0.0
	 */
	function wc_memberships_show_product_member_discount_badge() {
		wc_get_template( 'single-product/member-discount-badge.php' );
	}
}
