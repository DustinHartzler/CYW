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
 * @package   WC-Memberships/Frontend
 * @author    SkyVerge
 * @category  Frontend
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Frontend class
 *
 * @since 1.0.0
 */
class WC_Memberships_Frontend {


	/** @var array of post IDs that content restriction has been applied to **/
	private $content_restriction_applied = array();

	/** @var string Product content restriction password helper **/
	private $product_restriction_password = null;

	/** @var array cart items with member discounts helper **/
	private $_cart_items_with_member_discounts = null;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Enqueue JS and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// Handle frontend actions
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ) {
			add_action( 'wp_loaded', array( $this, 'cancel_membership' ) );
			add_action( 'wp_loaded', array( $this, 'renew_membership' ) );
		} else {
			add_action( 'init', array( $this, 'cancel_membership' ) );
			add_action( 'init', array( $this, 'renew_membership' ) );
		}

		add_action( 'woocommerce_booking_add_to_cart', array( $this, 'fix_wc_bookings' ), 1 );

		// Add cart & checkout notices
		add_action( 'wp', array( $this, 'add_cart_member_login_notice' ) );

		// optional login/link buttons on checkout / thank you pages
		add_action( 'woocommerce_before_template_part', array( $this, 'maybe_render_checkout_member_login_notice' ) );

		// Exclude restricted content
		add_filter( 'the_posts', array( $this, 'exclude_restricted_posts' ), 10, 2 );
		add_filter( 'get_pages', array( $this, 'exclude_restricted_pages' ), 10, 2 );
		add_filter( 'get_terms', array( $this, 'exclude_restricted_terms' ), 10, 3 );

		// Exclude view-restricted product variations
		add_filter( 'woocommerce_variation_is_visible', array( $this, 'variation_is_visible' ), 10, 2 );

		// Todo: hide view-restricted variations once https://github.com/woothemes/woocommerce/pull/8068 gets merged
		// add_filter( 'woocommerce_hide_invisible_variations', array( $this, 'hide_invisible_variations' ), 10, 3 );

		// Redirect content & products (redirect restriction mode)
		add_filter( 'wp', array( $this, 'redirect_restricted_content' ) );
		add_filter( 'wp', array( $this, 'hide_restricted_content_comments' ) );

		// Restrict (filter) content (hide_content restriction mode)
		add_filter( 'the_content', array( $this, 'restrict_content' ) );
		add_filter( 'the_excerpt', array( $this, 'restrict_content' ) );
		add_filter( 'comments_open', array( $this, 'maybe_close_comments' ) );

		// Remove restricted comments from comment feeds
		add_filter( 'the_posts', array( $this, 'exclude_restricted_comments' ), 10, 2 );

		// Hide prices & thumbnails for view-restricted products
		add_filter( 'woocommerce_get_price_html', array( $this, 'hide_restricted_product_price' ), 10, 2 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'maybe_remove_product_thumbnail' ), 5 );

		// Restrict product viewing by hijacking WooCommerce product password protection (hide_content restriction mode)
		add_action( 'woocommerce_before_single_product', array( $this, 'maybe_password_protect_product' ) );

		// Restrict product visibility
		add_filter( 'woocommerce_product_is_visible', array( $this, 'product_is_visible' ), 10, 2 );

		// Restrict product purchasing
		add_filter( 'woocommerce_is_purchasable',           array( $this, 'product_is_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'product_is_purchasable' ), 10, 2 );

		// Show product purchasing restriction message
		add_action( 'woocommerce_single_product_summary', array( $this, 'single_product_purchasing_restricted_message' ), 30 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'single_product_member_discount_message' ), 31 );

		// Member discount badges
		add_action( 'woocommerce_before_shop_loop_item_title',   'wc_memberships_show_product_loop_member_discount_badge', 10 );
		add_action( 'woocommerce_before_single_product_summary', 'wc_memberships_show_product_member_discount_badge', 10 );

		// Sho memberships on my account dashboard
		add_action( 'woocommerce_before_my_account', array( $this, 'my_account_memberships' ) );

	}


	/**
	 * Enqueue frontend scripts & styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'wc-memberships-frontend', wc_memberships()->get_plugin_url() . '/assets/css/frontend/wc-memberships-frontend.min.css', WC_Memberships::VERSION );
	}


	/**
	 * Remove add to cart button for nun-purchasable booking products
	 *
	 * TODO: remove this once WC Bookings fixes their is_purchasable implementation
	 *
	 * @since 1.0.0
	 */
	public function fix_wc_bookings() {
		global $wp_filter, $product;

		if ( ! $this->product_is_purchasable( true, $product ) ) {

			$tag = 'woocommerce_booking_add_to_cart';

			if ( isset( $wp_filter[ $tag ] ) && ! empty( $wp_filter[ $tag ] ) ) {

				foreach ( $wp_filter[ $tag ] as $priority => $filters ) {

					foreach ( $filters as $key => $filter ) {
						if ( is_array( $filter['function'] ) && is_a( $filter['function'][0], 'WC_Booking_Cart_Manager' ) && 'add_to_cart' == $filter['function'][1] ) {

							unset( $wp_filter[ $tag ][ $priority ][ $key ] );
							unset( $GLOBALS['merged_filters'][ $tag ] );
						}
					}
				}
			}
		}
	}


	/**
	 * Exclude restricted posts from wp_query results
	 *
	 * @since 1.0.0
	 * @param array $posts
	 * @param WP_Query $query
	 * @return array
	 */
	public function exclude_restricted_posts( $posts, WP_Query $query ) {

		// Don't try to process if there are no posts to begin with
		if ( empty( $posts ) ) {
			return $posts;
		}

		// Sanity check: if restriction mode is not "hide", return all posts
		if ( 'hide' != get_option( 'wc_memberships_restriction_mode' ) ) {
			return $posts;
		}

		$queried_post_types = ( $query->query_vars['post_type'] && 'any' != $query->query_vars['post_type'] ) ? (array) $query->query_vars['post_type'] : array();

		// If the query wasn't for specific post types, then
		// gather data about post types by looping over each post
		// and grabbing the post type
		if ( empty( $queried_post_types ) ) {
			foreach ( $posts as $post ) {

				if ( ! in_array( $post->post_type, $queried_post_types ) ) {
					$queried_post_types[] = $post->post_type;
				}
			}
		}

		return $this->remove_restricted_posts( $posts, $queried_post_types );
	}


	/**
	 * Exclude restricted pages from get_pages calls
	 *
	 * @since 1.0.0
	 * @param array $pages
	 * @return array
	 */
	public function exclude_restricted_pages( $pages ) {

		// Don't try to process if there are no pages to begin with
		if ( empty( $pages ) ) {
			return $pages;
		}

		// Sanity check: if restriction mode is not "hide", return all pages
		if ( 'hide' != get_option( 'wc_memberships_restriction_mode' ) ) {
			return $pages;
		}

		return $this->remove_restricted_posts( $pages, array( 'page' ) );
	}


	/**
	 * Exclude restricted terms from get_terms calls
	 *
	 * @since 1.0.0
	 * @param array $terms
	 * @param array $taxonomies
	 * @param array $args
	 * @return array
	 */
	public function exclude_restricted_terms( $terms, $taxonomies, $args ) {

		// Don't try to process if there are no terms to begin with
		if ( empty( $terms ) ) {
			return $terms;
		}

		// Sanity check: if restriction mode is not "hide", return all pages
		if ( 'hide' != get_option( 'wc_memberships_restriction_mode' ) ) {
			return $terms;
		}


		// Loop over each term and see if any restrictions apply
		foreach ( $terms as $key => $term ) {

			// Fall back to first available taxonomy if term is not an object
			$taxonomy = is_object( $term ) ? $term->taxonomy : $taxonomies[0];
			$term_id  = is_object( $term ) ? $term->term_id  : $term;

			// User can't view this term at all
			if ( ! current_user_can( 'wc_memberships_view_restricted_taxonomy_term', $taxonomy, $term_id ) && ! current_user_can( 'wc_memberships_view_restricted_product_taxonomy_term', $taxonomy, $term_id ) ) {
				unset( $terms[ $key ] );
				continue;
			}

			// User can't view this delayed term
			if ( ! current_user_can( 'wc_memberships_view_delayed_taxonomy_term', $taxonomy, $term_id ) && ! current_user_can( 'wc_memberships_view_delayed_product_taxonomy_term', $taxonomy, $term_id ) ) {
				unset( $terms[ $key ] );
				continue;
			}
		}

		return array_values( $terms );
	}


	/**
	 * Remove restricted posts from an array of posts
	 *
	 * @since 1.0.0
	 * @param array $posts
	 * @param array $post_types Array of post types that posts are from
	 * @return array
	 */
	private function remove_restricted_posts( $posts, $post_types ) {

		if ( empty( $posts ) ) {
			return $posts;
		}

		// Loop over all posts and see if any of the restrictions apply to them
		foreach ( $posts as $key => $post ) {

			// Are we looking at a product? Products get special treatment ಠ‿ಠ
			$is_product = in_array( $post->post_type, array( 'product', 'product_variation' ) );

			// Determine if user can view the post at all
			$can_view = $is_product
								? current_user_can( 'wc_memberships_view_restricted_product', $post->ID )
								: current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID );

			// User does not have view access, remove from list of posts
			if ( ! $can_view ) {
				unset( $posts[ $key ] );
				continue;
			}

			// What if the content is delayed?
			$can_view_delayed = current_user_can( 'wc_memberships_view_delayed_post_content', $post->ID );

			// User can't view delayed content, remove from list of posts
			if ( ! $can_view_delayed ) {
				unset( $posts[ $key ] );
				continue;
			}
		}

		return array_values( $posts );
	}


	/**
	 * Exclude view-restricted variations
	 *
	 * @since 1.0.0
	 * @param bool $is_visible
	 * @param int $variation_id
	 * @return bool
	 */
	public function variation_is_visible( $is_visible, $variation_id ) {

		// Exclude restricted variations
		if ( ! current_user_can( 'wc_memberships_view_restricted_product', $variation_id ) && ! current_user_can( 'wc_memberships_view_delayed_product', $variation_id ) ) {
			$is_visible = false;
		}

		return $is_visible;
	}


	/**
	 * Get taxonomies that apply to provided post types
	 *
	 * @since 1.0.0
	 * @param array $post_types
	 * @return array Array with taxonomy names
	 */
	private function get_taxonomies_for_post_types( $post_types ) {

		$taxonomies = array();

		foreach ( $post_types as $post_type ) {
			$taxonomies = array_merge( $taxonomies, get_object_taxonomies( $post_type ) );
		}

		return array_unique( $taxonomies );
	}


	/**
	 * Redirect restricted content/products based on content/product restriction rules
	 *
	 * @since 1.0.0
	 * @param string $content The content
	 * @return string
	 */
	public function redirect_restricted_content( $content ) {

		if ( 'redirect' !== get_option( 'wc_memberships_restriction_mode' ) ) {
			return;
		}

		if ( is_singular() ) {

			global $post;

			$restricted = ( in_array( $post->post_type, array( 'product', 'product_variation' ) ) )
									? wc_memberships_is_product_viewing_restricted() && ! current_user_can( 'wc_memberships_view_restricted_product', $post->ID )
									: wc_memberships_is_post_content_restricted() && ! current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID );

			if ( $restricted ) {

				$redirect_page_id = get_option( 'wc_memberships_redirect_page_id' );

				$redirect_url = $redirect_page_id ? get_permalink( $redirect_page_id ) : home_url();
				$redirect_url = add_query_arg( 'r', $post->ID, $redirect_url );

				wp_redirect( $redirect_url );
				exit;
			}

		}
	}


	/**
	 * Hide restricted content/product comments
	 *
	 * @since 1.0.0
	 * @param string $content The content
	 * @return string
	 */
	public function hide_restricted_content_comments( $content ) {

		if ( 'hide_content' !== get_option( 'wc_memberships_restriction_mode' ) ) {
			return;
		}

		if ( is_singular() ) {

			global $post, $wp_query;

			$restricted = ( in_array( $post->post_type, array( 'product', 'product_variation' ) ) )
									? wc_memberships_is_product_viewing_restricted() && ! current_user_can( 'wc_memberships_view_restricted_product',      $post->ID )
									: wc_memberships_is_post_content_restricted()    && ! current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID );

			if ( $restricted ) {

				$wp_query->comment_count   = 0;
				$wp_query->current_comment = 999999;
			}

		}
	}


	/**
	 * Restrict (post) content based on content restriction rules
	 *
	 * @since 1.0.0
	 * @param string $content The content
	 * @return string
	 */
	public function restrict_content( $content ) {

		// Check if content is restricted - and this function is not being recursively called
		// from `get_the_excerpt`, which internally applies `the_content` to the excerpt, which
		// then calls this function, ... until the stack is full and I want to go home and not
		// deal with this anymore...
		if ( wc_memberships_is_post_content_restricted() && ! doing_filter( 'get_the_excerpt' ) ) {

			global $post;

			// Check if user has access to restricted content
			if ( ! current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID ) ) {

				// User does not have access, filter the content
				$content = '';

				if ( ! in_array( $post->ID, $this->content_restriction_applied ) ) {

					if ( 'yes' == get_option( 'wc_memberships_show_excerpts' ) ) {
						$content = get_the_excerpt();
					}

					$content .= '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-message wc-memberships-content-restricted-message">' . $this->get_content_restricted_message( $post->ID ) . '</div></div>';
				}
			}

			// Check if user has access to delayed content
			else if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post->ID ) ) {

				// User does not have access, filter the content
				$content = '';

				if ( ! in_array( $post->ID, $this->content_restriction_applied ) ) {

					if ( 'yes' == get_option( 'wc_memberships_show_excerpts' ) ) {
						$content = get_the_excerpt();
					}

					$content .= '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-content-delayed-message">' . $this->get_content_delayed_message( get_current_user_id(), $post->ID ) . '</div></div>';
				}

			}

			// Indicates that the content for this post has already been filtered
			$this->content_restriction_applied[] = $post->ID;
		}

		return $content;
	}


	/**
	 * Close comments when post content is restricted
	 *
	 * @since 1.0.0
	 * @param bool $comments_open
	 * @return bool
	 */
	public function maybe_close_comments( $comments_open ) {
		global $post;

		if ( $comments_open ) {

			// Are we looking at a product? Products get special treatment ಠ‿ಠ
			$is_product = in_array( $post->post_type, array( 'product', 'product_variation' ) );

			// Determine if user can view the post at all
			$comments_open = $is_product
										 ? current_user_can( 'wc_memberships_view_restricted_product', $post->ID )
										 : current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID );
		}

		return $comments_open;
	}


	/**
	 * Exclude restricted comments from comment feed
	 *
	 * @since 1.0.0
	 * @param array $posts
	 * @param WP_Query $query
	 * @return array
	 */
	public function exclude_restricted_comments( $posts, WP_Query $query ) {

		if ( is_comment_feed() && $query->comment_count ) {

			foreach ( $query->comments as $key => $comment ) {

				$post_id = $comment->comment_post_ID;

				$is_product = in_array( get_post_type( $post_id ), array( 'product', 'product_variation' ) );

				// Determine if user can view the comment post
				$can_view = $is_product
									? current_user_can( 'wc_memberships_view_restricted_product', $post_id )
									: current_user_can( 'wc_memberships_view_restricted_post_content', $post_id );

				// If not, exclude this comment from the feed
				if ( ! $can_view ) {
					unset( $query->comments[ $key ] );
				}
			}

			// Re-index and re-count comments
			$query->comments = array_values( $query->comments );
			$query->comment_count = count( $query->comments );
		}

		return $posts;
	}


	/**
	 * Hide price if a product is view-restricted in "hide content" mode
	 *
	 * @since 1.0.0
	 * @param string $price
	 * @param WC_Product $Product
	 * @return string
	 */
	public function hide_restricted_product_price ( $price, WC_Product $product ) {

		if ( 'hide_content' == get_option( 'wc_memberships_restriction_mode' ) && ! current_user_can( 'wc_memberships_view_restricted_product', $product->id ) ) {
			$price = '';
		}

		return $price;
	}


	/**
	 * Remove product thumbnail in "hide content" mode
	 *
	 * @since 1.0.0
	 */
	public function maybe_remove_product_thumbnail () {
		global $post;

		if ( 'hide_content' == get_option( 'wc_memberships_restriction_mode' ) && ! current_user_can( 'wc_memberships_view_restricted_product', $post->ID ) ) {

			remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'template_loop_product_thumbnail_placeholder' ), 10 );
		}
	}


	/**
	 * Output the product image placeholder in shop loop
	 *
	 * @since 1.0.0
	 */
	public function template_loop_product_thumbnail_placeholder() {

		if ( wc_placeholder_img_src() ) {
			echo wc_placeholder_img( 'shop_catalog' );
		}
	}


	/**
	 * Maybe password-protect a product page
	 *
	 * WP/WC gives us very few tools to restrict product viewing, so we
	 * hijack the password protection to achieve what we want.
	 *
	 * @since 1.0.0
	 */
	public function maybe_password_protect_product() {

		global $post;

		// if the product is to be restricted, and doesn't already have a password,
		// set a password so as to perform the actions we want
		if ( wc_memberships_is_product_viewing_restricted() && ! post_password_required() ) {

			if ( ! current_user_can( 'wc_memberships_view_restricted_product', $post->ID ) ) {

				$post->post_password = $this->product_restriction_password = uniqid( 'wc_memberships_restricted_' );
				add_filter( 'the_password_form', array( $this, 'restrict_product_content' ) );
			}

			else if ( ! current_user_can( 'wc_memberships_view_delayed_product', $post->ID ) ) {

				$post->post_password = $this->product_restriction_password = uniqid( 'wc_memberships_delayed_' );
				add_filter( 'the_password_form', array( $this, 'restrict_product_content' ) );
			}

		}
	}


	/**
	 * Restrict product content
	 *
	 * @since 1.0.0
	 * @param string $output
	 * @return string $output
	 */
	public function restrict_product_content( $output ) {

		global $post;

		if ( $this->product_restriction_password && $this->product_restriction_password === $post->post_password ) {


			// User does not have access, filter the content
			$output = '';

			if ( 'yes' == get_option( 'wc_memberships_show_excerpts' ) ) {
				ob_start();
				echo '<div class="summary entry-summary">';
				wc_get_template( 'single-product/title.php' );
				wc_get_template( 'single-product/short-description.php' );
				echo '</div>';
				$output = ob_get_clean();
			}

			$message = ( strpos( $post->post_password, 'wc_memberships_restricted_' ) !== false )
							 ? $this->get_product_viewing_restricted_message( $post->ID )
							 : $this->get_content_delayed_message( get_current_user_id(), $post->ID, 'view' );

			$output .= '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-restricted-content-message">' . wp_kses_post( $message ) . '</div></div>';

			$post->post_password = null;
		}

		return $output;
	}


	/**
	 * Get a list of products that grant access to a piece of content
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param string $ruleset
	 * @return array|null
	 */
	private function get_products_that_grant_access( $post_id = null, $ruleset = null ) {

		// Default to the 'current' post
		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}

		// Get applied rules
		if ( 'purchasing_discount' == $ruleset ) {
			$rules = wc_memberships()->rules->get_product_purchasing_discount_rules( $post_id );
		} else if ( in_array( get_post_type( $post_id ), array( 'product', 'product_variation' ) ) ) {
			$rules = wc_memberships()->rules->get_the_product_restriction_rules( $post_id );
		} else {
			$rules = wc_memberships()->rules->get_post_content_restriction_rules( $post_id );
		}

		// Find products that grant access
		$processed_plans = array(); // holder for membership plans that have been processed already
		$products        = array();

		foreach ( $rules as $rule ) {

			// Skip further checks if this membership plan has already been processed
			if ( in_array( $rule->get_membership_plan_id(), $processed_plans ) ) {
				continue;
			}

			$plan = wc_memberships_get_membership_plan( $rule->get_membership_plan_id() );

			if ( $plan && $plan->has_products() ) {

				foreach ( $plan->get_product_ids() as $product_id ) {
					$products[] = $product_id;
				}
			}

			// Mark this plan as processed, we do not need look into it any further,
			// because we already know if it has any products that grant access or not.
			$processed_plans[] = $rule->get_membership_plan_id();
		}

		return ! empty( $products ) ? $products : null;
	}


	/**
	 * Get and parse a restriction message
	 *
	 * General wrapper around different types of restriction messages
	 *
	 * @since 1.0.0
	 * @param string $type Restriction type
	 * @param int $post_id Post ID that is being restricted
	 * @param array $products List of product IDs that grant access. Optional
	 * @return string Restriction message
	 */
	private function get_restriction_message( $type, $post_id, $products = null ) {

		if ( ! $type ) {
			return false;
		}

		if ( ! empty( $products ) ) {

			foreach ( $products as $key => $product_id ) {

				$product = wc_get_product( $product_id );
				$link    = $product->get_permalink();
				$title   = $product->get_title();

				// Special handling for variations
				if ( $product->is_type( 'variation' ) ) {

					$attributes = $product->get_variation_attributes();

					foreach ( $attributes as $attr_key => $attribute ) {
						$attributes[ $attr_key ] = ucfirst( $attribute );
					}

					$title .= ' &ndash; ' . implode( ', ', $attributes );
				}

				$products[ $key ] = sprintf( '<a href="%s">%s</a>', esc_url( $link ), wp_kses_post( $title ) );
			}

			// Check that the message type is valid for custom messages.
			// For example, purchasing_discount messages cannot be customized per-product
			// so we must leave them out
			if ( in_array( $type, wc_memberships_get_valid_restriction_message_types() ) && 'yes' === get_post_meta( $post_id, "_wc_memberships_use_custom_{$type}_message", true ) ) {
				$message = get_post_meta( $post_id, "_wc_memberships_{$type}_message", true );
			} else {
				$message = get_option( 'wc_memberships_' . $type . '_message' );
			}

			$message = str_replace( '{products}', '<span class="wc-memberships-products-grant-access">' . $this->list_items( $products ) . '</span>', $message );
			$message = str_replace( '{login_url}', get_permalink( get_option('woocommerce_myaccount_page_id') ), $message );

		} else {
			$message = get_option( 'wc_memberships_' . $type . '_message_no_products' );
		}

		return $message;
	}


	/**
	 * Get the product viewing restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_product_viewing_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'product_viewing_restricted', $post_id, $products );

		/**
		 * Filter the product viewing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_product_viewing_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the product purchasing restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_product_purchasing_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'product_purchasing_restricted', $post_id, $products );

		/**
		 * Filter the product purchasing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_product_purchasing_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the content restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_content_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'content_restricted', $post_id, $products );

		/**
		 * Filter the product purchasing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_content_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the delayed content message
	 *
	 * @since 1.0.0
	 * @param int $user_id Optional. Defaults to current user ID.
	 * @param int $post_id Optional. Defaults to current post ID.
	 * @param string $access_type Optional. Defaults to "view". Applies to products only.
	 * @return string
	 */
	public function get_content_delayed_message( $user_id = null, $post_id = null, $access_type = 'view' ) {

		if ( ! $user_id ) {

			$user_id = get_current_user_id();
		}

		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}


		$access_time = wc_memberships()->capabilities->get_user_access_start_time_for_post( $user_id, $post_id, $access_type );

		switch ( get_post_type( $post_id ) ) {

			case 'product':
			case 'product_variation':
				if ( 'view' == $access_type ) {
					$message = __( 'This product is part of your membership, but not yet! You will gain access on {date}', WC_Memberships::TEXT_DOMAIN );
				} else {
					$message = __( 'This product is part of your membership, but not yet! You can purchase it on {date}', WC_Memberships::TEXT_DOMAIN );
				}
				break;

			case 'page':
				$message = __( 'This page is part of your membership, but not yet! You will gain access on {date}', WC_Memberships::TEXT_DOMAIN );
				break;

			case 'post':
				$message = __( 'This post is part of your membership, but not yet! You will gain access on {date}', WC_Memberships::TEXT_DOMAIN );
				break;

			default:
				$message = __( 'This content is part of your membership, but not yet! You will gain access on {date}', WC_Memberships::TEXT_DOMAIN );
				break;

		}


		/**
		 * Filter the delayed content message
		 *
		 * @since 1.0.0
		 * @param string $message Delayed content message
		 * @param int $post_id Post ID that the message applies to
		 * @param string $access_time Access time timestamp
		 */
		$message = apply_filters( 'get_content_delayed_message', $message, $post_id, $access_time );
		$message = str_replace( '{date}', date_i18n( get_option( 'date_format' ), $access_time ), $message );

		return $message;
	}


	/**
	 * Get the member discount message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post ID.
	 * @return string
	 */
	public function get_member_discount_message( $post_id = null ) {

		if ( ! $post_id ) {

			global $post;
			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id, 'purchasing_discount' );
		$message  = $this->get_restriction_message( 'product_discount', $post_id, $products );

		/**
		 * Filter the product member discount message
		 *
		 * @since 1.0.0
		 * @param string $message The discount message
		 * @param int $product_id ID of the product that has member discounts
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_member_discount_message', $message, $post_id, $products );
	}


	/**
	 * Restrict product purchasing based on restriction rules
	 *
	 * @since 1.0
	 * @param boolean $purchasable whether the product is purchasable
	 * @param WC_Product $product the product
	 * @return boolean true if $product is purchasable, false otherwise
	 */
	public function product_is_purchasable( $purchasable, $product ) {

		$product_id = $product->is_type('variation') ? $product->variation_id : $product->id;

		// Product is not purchasable if the current user can't view or purchase the product,
		// or they do not have access yet (due to dripping).
		if ( ! current_user_can( 'wc_memberships_view_restricted_product', $product_id ) || ! current_user_can( 'wc_memberships_purchase_restricted_product', $product_id ) || ! current_user_can( 'wc_memberships_purchase_delayed_product', $product_id ) ) {
			$purchasable = false;
		}

		// Double-check for variations: if parent is not purchasable, then
		// variation is neither
		if ( $purchasable && $product->is_type( 'variation' ) ) {
			$purchasable = $product->parent->is_purchasable();
		}

		return $purchasable;
	}


	/**
	 * Restrict product visibility in catalog based on restriction rules
	 *
	 * @since 1.0
	 * @param boolean $visible whether the product is visible
	 * @param inti $product_id the product id
	 * @return boolean true if product is visible, false otherwise
	 */
	public function product_is_visible( $visible, $product_id ) {

		if ( 'yes' == get_option( 'wc_memberships_hide_restricted_products' ) ) {
			if ( ! current_user_can( 'wc_memberships_view_restricted_product', $product_id ) ) {
				$visible = false;
			}
		}

		return $visible;
	}


	/**
	 * Display product purchasing restricted message
	 *
	 * @since 1.0.0
	 */
	public function single_product_purchasing_restricted_message() {

		global $product;

		// Purchasing is restricted
		if ( ! current_user_can( 'wc_memberships_purchase_restricted_product', $product->id ) ) {
			echo '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-product-purchasing-restricted-message">' . wp_kses_post( $this->get_product_purchasing_restricted_message( $product->id ) ) . '</div></div>';
		}

		// Purchasing is delayed
		else if ( ! current_user_can( 'wc_memberships_purchase_delayed_product', $product->id ) ) {
			echo '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-product-purchasing-delayed-message">' . wp_kses_post( $this->get_content_delayed_message( get_current_user_id(), $product->id, 'purchase' ) ) . '</div></div>';
		}

		// Variation-specific messages
		else if ( $product->is_type( 'variable' ) && $product->has_child() ) {

			$variations_restricted = false;

			foreach ( $product->get_available_variations() as $variation ) {

				if ( ! $variation['is_purchasable'] ) {

					$variation_id = $variation['variation_id'];

					if ( ! current_user_can( 'wc_memberships_purchase_restricted_product', $variation_id ) ) {
						$variations_restricted = true;
						echo '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-product-purchasing-restricted-message wc-memberships-variation-message js-variation-' . sanitize_html_class( $variation_id ) . '">' . wp_kses_post( $this->get_product_purchasing_restricted_message( $variation_id ) ) . '</div></div>';
					}

					else if ( ! current_user_can( 'wc_memberships_purchase_delayed_product', $variation['variation_id'] ) ) {
						$variations_restricted = true;
						echo '<div class="woocommerce"><div class="woocommerce-info wc-memberships-restriction-message wc-memberships-product-purchasing-delayed-message wc-memberships-variation-message js-variation-' . sanitize_html_class( $variation_id ) . '">' . wp_kses_post( $this->get_content_delayed_message( get_current_user_id(), $variation_id, 'purchase' ) ) . '</div></div>';
					}
				}
			}

			if ( $variations_restricted ) {
				wc_enqueue_js("
					jQuery('.variations_form')
						.on( 'woocommerce_variation_select_change', function( event ) {
							jQuery('.wc-memberships-variation-message').hide();
						})
						.on( 'found_variation', function( event, variation ) {
							if ( ! variation.is_purchasable ) {
								jQuery( '.wc-memberships-variation-message.js-variation-' + variation.variation_id ).show();
							}
						})
						.find( '.variations select' ).change();
				");
			}
		}
	}



	/**
	 * Display member discount message for product
	 *
	 * @since 1.0.0
	 */
	public function single_product_member_discount_message() {

		global $product;

		if ( wc_memberships_product_has_member_discount() && ! wc_memberships_user_has_member_discount() ) {

			$message = $this->get_member_discount_message( $product->id );

			if ( $message ) {
				echo '<div class="woocommerce"><div class="woocommerce-info wc-memberships-member-discount-message">' . wp_kses_post( $message ) . '</div></div>';
			}
		}
	}


	/**
	 * Output memberships table in My Account
	 *
	 * @since 1.0.0
	 */
	public function my_account_memberships() {

		$customer_memberships = wc_memberships_get_user_memberships();

		if ( ! empty( $customer_memberships ) ) {
			wc_get_template( 'myaccount/my-memberships.php', array( 'customer_memberships' => $customer_memberships ) );
		}
	}


	/**
	 * Cancel a membership
	 *
	 * @since 1.0.0
	 */
	public function cancel_membership() {

		if ( ! isset( $_GET['cancel_membership'] ) || ! isset( $_GET['user_membership_id'] ) ) {
			return;
		}

		$user_membership_id = absint( $_GET['user_membership_id'] );
		$user_membership    = wc_memberships_get_user_membership( $user_membership_id );
		$user_can_cancel    = current_user_can( 'wc_memberships_cancel_membership', $user_membership_id );

		if ( ! $user_membership ) {
			wc_add_notice( __( 'Invalid membership.', WC_Memberships::TEXT_DOMAIN ), 'error' );
		}

		else {

			/**
			 * Filter the valid statuses for cancelling a user membership on frontend
			 *
			 * @since 1.0.0
			 * @param array $statuses Array of statuses valid for cancellation
			 */
			$user_membership_can_cancel = in_array( $user_membership->get_status(), apply_filters( 'wc_memberships_valid_membership_statuses_for_cancel', array( 'active' ) ) );

			if ( ! $user_membership->is_cancelled() && $user_can_cancel && $user_membership_can_cancel && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wc_memberships-cancel_membership' ) ) {

				$user_membership->cancel_membership( __( 'Membership cancelled by customer.', WC_Memberships::TEXT_DOMAIN ) );

				// Message

				/**
				 * Filter the user cancelled membership message on frontend
				 *
				 * @since 1.0.0
				 * @param string $notice
				 */
				$notice = apply_filters( 'wc_memberships_user_membership_cancelled_notice', __( 'Your membership was cancelled.', WC_Memberships::TEXT_DOMAIN ) );
				wc_add_notice( $notice, 'notice' );

				/**
				 * Fires right after a membership has been cancelled by a customer
				 *
				 * @since 1.0.0
				 * @param int $user_membership_id
				 */
				do_action( 'wc_memberships_cancelled_user_membership', $user_membership_id );

			} else {

				wc_add_notice( __( 'Cannot cancel this membership.', WC_Memberships::TEXT_DOMAIN ), 'error' );
			}
		}

		wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
		exit;
	}


	/**
	 * Renew a membership
	 *
	 * @since 1.0.0
	 */
	public function renew_membership() {

		if ( ! isset( $_GET['renew_membership'] ) || ! isset( $_GET['user_membership_id'] ) ) {
			return;
		}

		$user_membership_id = absint( $_GET['user_membership_id'] );
		$user_membership    = wc_memberships_get_user_membership( $user_membership_id );
		$membership_plan    = $user_membership->get_plan();
		$user_can_renew     = current_user_can( 'wc_memberships_renew_membership', $user_membership_id );


		if ( ! $user_membership ) {

			wc_add_notice( __( 'Invalid membership.', WC_Memberships::TEXT_DOMAIN ), 'error' );
		} else {

			/**
			 * Filter the valid statuses for renewing a user membership on frontend
			 *
			 * @since 1.0.0
			 * @param array $statuses Array of statuses valid for renewal
			 */
			$user_membership_can_renew = in_array( $user_membership->get_status(), apply_filters( 'wc_memberships_valid_membership_statuses_for_renewal', array( 'expired', 'cancelled' ) ) );

			// Try to purchase the same product as before
			$original_product = $user_membership->get_product();

			if ( $original_product && $original_product->is_purchasable() ) {
				$product = $original_product;
			}

			// If that's not available, try to get the first purchasable product
			else {

				foreach ( $membership_plan->get_product_ids() as $product_id ) {

					$another_product = wc_get_product( $product_id );

					// We've found a product that can be purchased to renew access!
					if ( $another_product && $another_product->is_purchasable() ) {
						$product = $another_product;
						break;
					}
				}
			}

			// We can renew! Let's do it!
			if ( $product && $user_can_renew && $user_membership_can_renew && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wc_memberships-renew_membership' ) ) {

				woocommerce_empty_cart();
				WC()->cart->add_to_cart( $product->id, 1 );

				wc_add_notice( sprintf( __( 'Renew your membership by purchasing %s.', WC_Memberships::TEXT_DOMAIN ), $product->get_title() ), 'success' );
				wp_safe_redirect( WC()->cart->get_checkout_url() );
				exit;

			}
		}

		wc_add_notice( __( 'Cannot renew this membership. Please contact us if you need assistance.', WC_Memberships::TEXT_DOMAIN ), 'error' );

		wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
		exit;
	}


	/**
	 * Add a notice for members that they can get a discount when logged in
	 *
	 * @since 1.0.0
	 */
	public function add_cart_member_login_notice() {

		$display_in = get_option( 'wc_memberships_display_member_login_notice' );

		if ( ! is_user_logged_in() && is_cart() && in_array( $display_in, array( 'cart', 'both' ) ) ) {

			if ( $this->cart_has_items_with_member_discounts() ) {

				$message = $this->get_member_login_message();
				wc_add_notice( sprintf( $message, '<a href="' . get_permalink( get_option('woocommerce_myaccount_page_id') ) . '">', '</a>' ), 'notice' );
			}
		}
	}


	/**
	 * Maybe render checkout member login notice
	 *
	 * @since 1.1.0
	 * @param string $template_name template being loaded by WC
	 */
	public function maybe_render_checkout_member_login_notice( $template_name ) {

		// separate notice at checkout
		if ( ! is_user_logged_in() && 'checkout/form-login.php' === $template_name ) {

			$display_in = get_option( 'wc_memberships_display_member_login_notice' );

			if ( in_array( $display_in, array( 'checkout', 'both' ) ) ) {

				$message = $this->get_member_login_message();
				wc_print_notice( sprintf( $message, '', '' ), 'notice' );
			}

		}
	}


	/**
	 * Get member login message
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_member_login_message() {

		if ( count( $this->get_cart_items_with_member_discounts() ) > 1 ) {
			$message = __( 'Some items in you cart are discounted for members. %sLog in%s to claim them.' );
		} else if ( count( WC()->cart->get_cart() ) > 1 ) {
			$message = __( "An item in your cart is discounted for members. %sLog in%s to claim it." );
		} else {
			$message = __( "This item is discounted for members. %sLog in%s to claim it." );
		}

		return $message;
	}


	/**
	 * Get items in cart with member discounts
	 *
	 * @since 1.0.0
	 * @return array Array of Product IDs in cart with member discounts
	 */
	private function get_cart_items_with_member_discounts() {

		if ( ! isset( $this->_cart_items_with_member_discounts ) ) {

			$this->_cart_items_with_member_discounts = array();

			foreach ( WC()->cart->get_cart() as $item_key => $item ) {

				$product_id = isset( $item['variation_id'] ) && $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

				if ( wc_memberships()->rules->product_has_member_discount( $product_id ) ) {
					$this->_cart_items_with_member_discounts[] = $product_id;
				}
			}
		}

		return $this->_cart_items_with_member_discounts;
	}


	/**
	 * Check if cart has any items with member discounts
	 *
	 * @since 1.0.0
	 * @return bool True, if has items with member discounts, false otherwise
	 */
	private function cart_has_items_with_member_discounts() {

		$cart_items = $this->get_cart_items_with_member_discounts();
		return ! empty( $cart_items );
	}


	/**
	 * Creates a human readable list of an array
	 *
	 * @since 1.0.0
	 * @param string[] $ranges array to list items of
	 * @return string 'item1, item2, item3 or item4'
	 */
	private function list_items( $items ) {

		array_splice( $items, -2, 2, implode( ' ' . __( 'or', WC_Memberships::TEXT_DOMAIN ) . ' ', array_slice( $items, -2, 2 ) ) );
		return implode( ', ', $items );
	}


}
