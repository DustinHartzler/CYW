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
 * Membership Rules class
 *
 * This class handles all rules-related functionality in Memberships.
 *
 * @since 1.0.0
 */
class WC_Memberships_Rules {


	/** @var array helper for lazy rules getter */
	private $rules = array();


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		require_once( wc_memberships()->get_plugin_path() .'/includes/class-wc-memberships-membership-plan-rule.php' );
	}


	/**
	 * Get rules
	 *
	 * General rules builder & getter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ruleset Ruleset type. One of 'content_restriction', 'product_restriction' or 'purchasing_discount'.
	 * @param array $args {
	 *   Optional. An array of arguments.
	 *
	 *   @type string $content_type Optional. Content type. One of 'post_type' or 'taxonomy'
	 *   @type string $content_type_name Optional. Content type name. A valid post type or taxonomy name.
	 *   @type string|int $id Optional. Post or taxonomy term ID/slug
	 *   @type bool $exclude_inherited Optional. Whether to exclude inherited rules
	 *                                 (from post type or taxonomy) when requesting
	 *                                 rules for a specific post.
	 *   @type bool $include_specific Optional. Whether to include specific (child)
	 *                                rules for specific objects, when querying for
	 *                                wide/general rules. When true, will include for example,
	 *                                term-specific rules when requesting for taxonomy rules.
	 *   @type mixed $plan_status Optional. Filter rules by plan status. Either a single plan status,
	 *                            array of statuses or 'any' for any status.
	 * }
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_rules( $ruleset, $args = array() ) {

		// Bail out if no ruleset is specified, or ruleset is invalid/not supported
		if ( ! $ruleset || ! in_array( $ruleset, array( 'content_restriction', 'product_restriction', 'purchasing_discount' ) ) ) {
			return false;
		}

		$defaults = array(
			'content_type'       => null,
			'content_type_name'  => null,
			'object_id'          => null,
			'exclude_inherited'  => false,
			'include_specific'   => false,
			'plan_status'        => 'publish'
		);

		$args = wp_parse_args( $args, $defaults );

		// Bail out if object id or content type name is provided, but content type itself is missing
		if ( ( $args['object_id'] || $args['content_type_name'] ) && ! $args['content_type'] ) {
			return false;
		}

		// Build rules for the first time
		if ( ! isset( $this->rules[ $ruleset ] ) ) {

			$this->rules[ $ruleset ] = array(
				'all'     => array(),
				'applied' => array(),
			);

			$rules = (array) get_option( 'wc_memberships_' . $ruleset . '_rules' );

			foreach ( $rules as $rule ) {

				$this->rules[ $ruleset ]['all'][] = new WC_Memberships_Membership_Plan_Rule( $ruleset, (array) $rule );
			}
		}

		// If no content type is specified, return all rules
		if ( ! $args['content_type'] ) {
			return $this->rules[ $ruleset ]['all'];
		}

		// Normalize object ID
		if ( $args['object_id'] ) {

			// If object_id is not numeric, try to get id from slug
			if ( ! is_numeric( $args['object_id'] ) ) {

				switch ( $args['content_type'] ) {

					case 'post_type':

						$post = $this->get_post_by_slug( $args['object_id'], $args['content_type_name'] );
						$args['object_id'] = is_object( $post ) ? $post->ID : null;

						break;

					case 'taxonomy':

						$term = get_term_by( 'slug', $args['object_id'], $args['content_type_name'] );
						$args['object_id'] = is_object( $term ) ? $term->term_id : null;

						break;
				}

				// Bail out if we could not determine the ID
				if ( ! $args['object_id'] ) {
					return false;
				}
			}

			// cast ID to int
			$args['object_id'] = absint( $args['object_id'] );
		}


		// Unique key for caching the applied rule results
		$applied_rule_key = http_build_query( $args );

		// Structurize the rules that apply to specific content types or objects
		if ( ! isset( $this->rules[ $ruleset ]['applied'][ $applied_rule_key ] ) ) {

			$this->rules[ $ruleset ]['applied'][ $applied_rule_key ] = array();

			foreach ( $this->rules[ $ruleset ]['all'] as $key => $rule ) {

				$apply_rule  = false;
				$plan_status = get_post_status( $rule->get_membership_plan_id() );

				// Check if the memberhip plan of this rule matches the requested status
				if ( is_array( $args['plan_status'] ) ) {
					$matches_plan_status = in_array( $plan_status, $args['plan_status'] );
				} else if ( in_array( $args['plan_status'], array( 'any', 'all' ) ) ) {
					$matches_plan_status = true;
				} else {
					$matches_plan_status = $plan_status == $args['plan_status'];
				}

				// Further processing makes sense only if plan status matches
				if ( $matches_plan_status ) {

					$rule_object_ids = $rule->get_object_ids();

					$matches_content_type       = $rule->applies_to( 'content_type', $args['content_type'] );
					$matches_content_type_name  = $rule->applies_to( 'content_type_name', $args['content_type_name'] );
					$matches_object_id          = $rule->applies_to( 'object_id', $args['object_id'] );
					$no_object_id_match         = ! $args['object_id'] && empty( $rule_object_ids );
					$no_content_type_name_match = ! $args['content_type_name'] && ! $rule->get_content_type_name();


					// No object_id & content type name, but content type matches
					if ( ( ( $no_object_id_match && $no_content_type_name_match ) || ( ! $no_object_id_match && ! $no_content_type_name_match && $args['include_specific'] ) ) && $matches_content_type ) {
						$apply_rule = true;
					}
					// No object_id, but content type & name match
					else if ( ( $no_object_id_match || $args['include_specific'] ) && $matches_content_type && $matches_content_type_name ) {
						$apply_rule = true;
					}
					// Object ID, content type & name match
					else if ( $args['object_id'] && $matches_object_id && $matches_content_type && $matches_content_type_name ) {
						$apply_rule = true;
					}

					// Handle rule inheritance. For example, rules that apply to a taxonomy
					// or post type must be applied to specific objects that match the
					// taxonomy or post type
					if ( ! $args['exclude_inherited'] && $args['object_id'] ) {

						switch ( $args['content_type'] ) {

							case 'post_type':

								// Handle post-taxonomy inheritance/relationships
								if ( $rule->applies_to( 'content_type', 'taxonomy' ) ) {

									// Does the requested post have any of the terms specified in the rule?
									if ( ! empty( $rule_object_ids ) ) {

										foreach ( $rule_object_ids as $term_id ) {

											if ( has_term( $term_id, $rule->get_content_type_name(), $args['object_id'] ) ) {

												$apply_rule = true;
												break;
											}
										}
									}

									// ... or if there are no terms specified, does it have any terms from that
									// particular taxonomy?
									else {

										$terms = get_the_terms( $args['object_id'], $rule->get_content_type_name() );

										if ( ! empty( $terms ) ) {
											$apply_rule = true;
										}
									}

								}

								// Handle post-post type inheritance
								// Rules that apply to the same post type and have no object_ids specified,
								// apply as well
								else if ( empty( $rule_object_ids ) && $matches_content_type && $matches_content_type_name ) {
									$apply_rule = true;
								}
								break;

							case 'taxonomy':

								// Does the term belong to the taxonomy?
								if ( empty( $rule_object_ids ) && $rule->applies_to( 'content_type', 'taxonomy' ) && $matches_content_type_name ) {
									$apply_rule = true;
								}

								break;

						}

					}
				}


				// Apply the rule
				if ( $apply_rule ) {

					// Rule order key
					$rule->set_rule_key( $key );
					$this->rules[ $ruleset ]['applied'][ $applied_rule_key ][] = $rule;
				}

			} // endforeach

		} // endif

		// Return rules for specific content types or objects
		return $this->rules[ $ruleset ]['applied'][ $applied_rule_key ];
	}


	/**
	 * Get content restriction rules
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param array $args associative array of arguments
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_content_restriction_rules( $args = array() ) {
		return $this->get_rules( 'content_restriction', $args );
	}


	/**
	 * Get content restriction rules for a post
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_post_content_restriction_rules( $post_id ) {

		return $this->get_content_restriction_rules( array(
			'content_type'      => 'post_type',
			'content_type_name' => get_post_type( $post_id ),
			'object_id'         => $post_id,
		) );
	}


	/**
	 * Get content restriction rules for a taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy name
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_content_restriction_rules( $taxonomy ) {

		return $this->get_content_restriction_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
		) );
	}


	/**
	 * Get content restriction rules for a taxonomy term
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy name
	 * @param string|int $term_id Term ID or slug
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_term_content_restriction_rules( $taxonomy, $term_id ) {

		return $this->get_content_restriction_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
			'object_id'         => $term_id,
		) );
	}


	/**
	 * Get content restriction rules for a post type
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type name
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_post_type_content_restriction_rules( $post_type ) {

		return $this->get_content_restriction_rules( array(
			'content_type'      => 'post_type',
			'content_type_name' => $post_type,
		) );
	}


	/**
	 * Get product restriction rules
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param array $args Associative array of arguments
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_product_restriction_rules( $args = array() ) {

		// If an object id is set, default to the product post_type
		if ( isset( $args['object_id'] ) ) {
			$args = wp_parse_args( $args, array(
				'content_type'      => 'post_type',
				'content_type_name' => 'product',
			) );
		}

		// Force 'product' as the only valid post_type
		if ( isset( $args['content_type'] ) && 'post_type' == $args['content_type'] ) {
			$args['content_type_name'] = 'product';
		}

		return $this->get_rules( 'product_restriction', $args );
	}


	/**
	 * Get product restriction rules for a product
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param int $product_id Product ID
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_the_product_restriction_rules( $product_id ) {

		return $this->get_product_restriction_rules( array(
			'object_id' => $product_id,
		) );
	}


	/**
	 * Get product restriction rules for a taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param string $taxonomy Taxonomy
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_product_restriction_rules( $taxonomy ) {

		return $this->get_product_restriction_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
		) );
	}


	/**
	 * Get product restriction rules for a taxonomy term
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param string $taxonomy Taxonomy
	 * @param string|int $term_id Term ID or slug
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_term_product_restriction_rules( $taxonomy, $term_id ) {

		return $this->get_product_restriction_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
			'object_id'         => $term_id,
		) );
	}


	/**
	 * Get purchasing discount rules
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param array $args Associative array of arguments
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_purchasing_discount_rules( $args = array() ) {

		// If an object id is set, default to the product post_type
		if ( isset( $args['object_id'] ) ) {
			$args = wp_parse_args( $args, array(
				'content_type'      => 'post_type',
				'content_type_name' => 'product',
			) );
		}

		// Force 'product' as the only valid post_type
		if ( isset( $args['content_type'] ) && 'post_type' == $args['content_type'] ) {
			$args['content_type_name'] = 'product';
		}

		return $this->get_rules( 'purchasing_discount', $args );
	}


	/**
	 * Get purchasing discount rules for a product
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param int $product_id Product ID
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_product_purchasing_discount_rules( $product_id ) {

		return $this->get_purchasing_discount_rules( array(
			'object_id' => $product_id,
		) );
	}


	/**
	 * Get purchasing discount rules for a taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param string $taxonomy Taxonomy
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_purchasing_discount_rules( $taxonomy ) {

		return $this->get_purchasing_discount_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
		) );
	}


	/**
	 * Get purchasing discount rules for a taxonomy term
	 *
	 * @since 1.0.0
	 *
	 * @see WC_Memberships::get_rules()
	 *
	 * @param string $taxonomy Taxonomy
	 * @param string|int $term_id Term ID or slug
	 * @return array|bool $rules Array of rules or false on error
	 */
	public function get_taxonomy_term_purchasing_discount_rules( $taxonomy, $term_id ) {

		return $this->get_purchasing_discount_rules( array(
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
			'object_id'         => $term_id,
		) );
	}


	/**
	 * Get a single rule by ID
	 *
	 * @since 1.0.0
	 * @param string $rule_id Rule ID
	 * @return WC_Memberships_Membership_Plan_Rule|null Instance of WC_Memberships_Membership_Plan_Rule
	 *                                                  or null, if not found
	 */
	public function get_rule( $rule_id ) {

		$found_rule = null;

		foreach ( array( 'content_restriction', 'product_restriction', 'purchasing_discount' ) as $ruleset ) {
			foreach ( $this->get_rules( $ruleset ) as $rule ) {

				if ( $rule_id == $rule->get_id() ) {
					$found_rule = $rule;
					break 2;
				}
			}
		}

		return $found_rule;
	}


	/**
	 * Get a user's purchasing discount for a specific product
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param int $product_id Product ID
	 * @return array|null Discount rules that apply for the user, or null
	 */
	public function get_user_product_purchasing_discount_rules( $user_id, $product_id ) {

		$all_discount_rules = $this->get_product_purchasing_discount_rules( $product_id );

		if ( empty( $all_discount_rules ) ) {
			return null;
		}

		$user_discount_rules = array();

		foreach ( $all_discount_rules as $rule ) {

			if ( $rule->is_active() && wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
				$user_discount_rules[] = $rule;
			}
		}

		return ! empty( $user_discount_rules ) ? $user_discount_rules : null;
	}


	/**
	 * Get content restriction rules that grant user access
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param array $args Associative array of arguments
	 * @return array|null Rules that apply for the user, or null
	 */
	public function get_user_content_restriction_rules( $user_id, $args = array() ) {

		$all_rules = $this->get_content_restriction_rules( $args );

		if ( empty( $all_rules ) ) {
			return null;
		}

		$user_rules = array();

		foreach ( $all_rules as $rule ) {

			if ( wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
				$user_rules[] = $rule;
			}
		}

		return ! empty( $user_rules ) ? $user_rules : null;
	}


	/**
	 * Get product restriction rules that grant user access
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param array $args Optional. Associative array of arguments
	 * @param string $access_type Optional. Access type. One of 'view' or 'purchase'.
	 * @return array|null Rules that apply for the user, or null
	 */
	public function get_user_product_restriction_rules( $user_id, $args = array(), $access_type = null ) {

		$all_rules = $this->get_product_restriction_rules( $args );

		if ( empty( $all_rules ) ) {
			return null;
		}

		$user_rules = array();

		foreach ( $all_rules as $rule ) {

			if ( 'view' == $access_type ) {
				$matches_access_type = in_array( $rule->get_access_type(), array( 'view', 'purchase' ) );
			}
			else if ( 'purchase' == $access_type ) {
				$matches_access_type = 'purchase' == $rule->get_access_type();
			}
			else {
				$matches_access_type = true;
			}

			if ( $matches_access_type && wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
				$user_rules[] = $rule;
			}
		}

		return ! empty( $user_rules ) ? $user_rules : null;
	}


	/**
	 * Check if a user has content access from rules
	 *
	 * Returns true if there are no rules
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param array $rules Array of rules to search access from
	 * @param int $object_id Optional. Object ID to check access for. Defaults to null.
	 * @return bool True if has access, false otherwise
	 */
	public function user_has_content_access_from_rules( $user_id, $rules, $object_id = null ) {

		if ( ! $user_id && $rules ) {
			return false;
		}

		$has_access = false;

		if ( ! empty( $rules ) ) {

			foreach ( $rules as $rule ) {

				$rule_object_ids = $rule->get_object_ids();

				// If no object ID is provided, then we are looking at rules
				// that apply to whole post types or taxonomies. In this case,
				// rules that apply to specific objects should be skipped.
				if ( ! $object_id && ! empty( $rule_object_ids ) ) {
					continue;
				}

				if ( wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
					$has_access = true;
					break;
				}
			}
		} else {
			$has_access = true;
		}

		return $has_access;
	}


	/**
	 * Check if a user has product view access from rules
	 *
	 * Returns true if there are no rules
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param array $rules Array of rules to search access from
	 * @param int $object_id Optional. Object ID to check access for. Defaults to null
	 * @return bool True if has access, false otherwise
	 */
	public function user_has_product_view_access_from_rules( $user_id, $rules, $object_id = null ) {

		$has_access = true; // start with positive access

		if ( ! empty( $rules ) ) {

			// First, determine if viewing is restricted at all
			foreach ( $rules as $rule ) {

				$rule_object_ids = $rule->get_object_ids();

				// If no object ID is provided, then we are looking at rules
				// that apply to whole post types or taxonomies. In this case,
				// rules that apply to specific objects should be skipped.
				if ( ! $object_id && ! empty( $rule_object_ids ) ) {
					continue;
				}

				if ( 'view' === $rule->get_access_type() ) {
					$has_access = false;
					break;
				}
			}

			// Second, determine if a logged in user has access from view or purchase rules
			if ( $user_id && ! $has_access ) {

				foreach ( $rules as $rule ) {

					$rule_object_ids = $rule->get_object_ids();

					// If no object ID is provided, then we are looking at rules
					// that apply to whole post types or taxonomies. In this case,
					// rules that apply to specific objects should be skipped.
					if ( ! $object_id && ! empty( $rule_object_ids ) ) {
						continue;
					}

					if ( in_array( $rule->get_access_type(), array( 'view', 'purchase' ) ) && wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return $has_access;
	}


	/**
	 * Check if a user has product purchase access from rules
	 *
	 * Returns true if there are no rules
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param array $rules Array of rules to search access from
	 * @param int $object_id Optional. Object ID to check access for. Defaults to null
	 * @return bool True if has access, false otherwise
	 */
	public function user_has_product_purchase_access_from_rules( $user_id, $rules, $object_id = null ) {

		if ( ! $user_id && $rules ) {
			return false;
		}

		$has_access = true; // start with positive access

		if ( ! empty( $rules ) && $user_id ) {

			// First, determine if purchasing is restricted at all
			foreach ( $rules as $rule ) {

				if ( 'purchase' === $rule->get_access_type() ) {
					$has_access = false;
					break;
				}
			}

			// Second, determine if user has access from view or purchase rules
			if ( ! $has_access ) {

				foreach ( $rules as $rule ) {

					if ( 'purchase' === $rule->get_access_type() && wc_memberships()->user_memberships->is_user_active_member( $user_id, $rule->get_membership_plan_id() ) ) {
						$has_access = true;
						break;
					}
				}
			}

		}

		return $has_access;
	}


	/**
	 * Check if a product has any member discount rules
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID to check
	 * @return bool True, if has purchasing discounts, false otherwise
	 */
	public function product_has_member_discount( $product_id ) {
		$rules = $this->get_product_purchasing_discount_rules( $product_id );

		if ( ! empty( $rules ) ) {
			foreach ( $rules as $key => $rule ) {

				if ( ! $rule->is_active() ) {
					unset( $rules[ $key ] );
				}
			}
		}

		return ! empty( $rules );
	}


	/**
	 * Check if user has member discounts for a specific product
	 *
	 * @since 1.0.0
	 * @param int $user_id WP_User ID
	 * @param int $product_id WC_Product ID
	 * @return bool True, if has discounts, false otherwise
	 */
	public function user_has_product_member_discount( $user_id, $product_id ) {
		$rules = $this->get_user_product_purchasing_discount_rules( $user_id, $product_id );

		if ( ! empty( $rules ) ) {
			foreach ( $rules as $key => $rule ) {

				if ( ! $rule->is_active() ) {
					unset( $rules[ $key ] );
				}
			}
		}

		return ! empty( $rules );
	}


	/**
	 * Get a post by slug
	 *
	 * @since 1.0.0
	 * @param string $slug Post slug
	 * @param string $post_type Optional. Post type, defaults to `post`
	 * @return WP_Post|null
	 */
	private function get_post_by_slug( $slug, $post_type = 'post' ) {

		if ( ! $slug ) {
			return null;
		}

		$posts = get_posts(array(
			'name'           => $slug,
			'post_type'      => $post_type,
			'posts_per_page' => 1,
		));

		return ! empty( $posts ) ? $posts[0] : null;
	}


}
