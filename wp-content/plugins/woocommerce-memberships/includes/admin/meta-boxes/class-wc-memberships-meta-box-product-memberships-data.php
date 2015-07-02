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
 * @package   WC-Memberships/Admin/Meta-Boxes
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Memberships Data Meta Box for products
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_Product_Memberships_Data extends WC_Memberships_Meta_Box {


	/** @var string meta box ID */
	protected $id = 'wc-memberships-product-memberships-data';

	/** @var array list of supported screen IDs **/
	protected $screens = array( 'product' );


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Memberships', WC_Memberships::TEXT_DOMAIN );
	}


	/**
	 * Enqueue scripts & styles for the meta box
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_and_styles() {

		// Make sure wc-admin-meta-boxes script is loaded _after_ our script
		// so that the select2/chosen fields are initialized before tabs are
		// initialized. Otherwise the placeholder text will be cut off...
		$GLOBALS['wp_scripts']->registered['wc-admin-meta-boxes']->deps[] = 'wc-memberships-admin';
	}


	/**
	 * Get all membership plans that a product grants access to
	 *
	 * @since 1.0.0
	 * @param int $product_id
	 * @param string $return Optional. Type of data to return. One of 'object' or 'id'. Defaults to 'object'.
	 * @return array Array of WC_Membership_Plan objects
	 */
	private function get_product_membership_plans( $product_id, $return = 'object' ) {
		$plans = array();

		foreach ( wc_memberships_get_membership_plans() as $plan ) {

			if ( $plan->has_product( $product_id ) ) {
				$plans[] = 'object' == $return ? $plan : $plan->get_id();
			}
		}

		return $plans;
	}


	/**
	 * Display the memberships meta box
	 *
	 * @param WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {

		$product = wc_get_product( $post );
		$grant_access_to_plans = $this->get_product_membership_plans( $post->ID );

		// Prepare membership plan options
		$membership_plan_options = array();
		$membership_plans = wc_memberships_get_membership_plans( array(
			'post_status' => array( 'publish', 'private', 'future', 'draft', 'pending', 'trash' )
		) );

		if ( ! empty( $membership_plans ) ) {
			foreach ( $membership_plans as $membership_plan ) {

				$state = '';

				if ( 'publish' != $membership_plan->post->post_status ) {
					$state = ' ' . __( '(inactive)', WC_Memberships::TEXT_DOMAIN );
				}

				$membership_plan_options[ $membership_plan->get_id() ] = $membership_plan->get_name() . $state;
			}
		}

		// Prepare access_type options for product restriction rules
		$product_restriction_access_type_options = array(
			'view'     => __( 'view', WC_Memberships::TEXT_DOMAIN ),
			'purchase' => __( 'purchase', WC_Memberships::TEXT_DOMAIN ),
		);

		// Prepare period options
		$access_schedule_period_toggler_options = array(
			'immediate' => __( 'immediately', WC_Memberships::TEXT_DOMAIN ),
			'specific'  => __( 'specify a time', WC_Memberships::TEXT_DOMAIN ),
		);

		$period_options = array(
			'days'      => __( 'day(s)', WC_Memberships::TEXT_DOMAIN ),
			'weeks'     => __( 'week(s)', WC_Memberships::TEXT_DOMAIN ),
			'months'    => __( 'month(s)', WC_Memberships::TEXT_DOMAIN ),
			'years'     => __( 'year(s)', WC_Memberships::TEXT_DOMAIN ),
		);

		// Get applied restriction rules
		$product_restriction_rules = wc_memberships()->rules->get_rules( 'product_restriction', array(
			'object_id'         => $post->ID,
			'content_type'      => 'post_type',
			'content_type_name' => $post->post_type,
			'exclude_inherited' => false,
			'plan_status'       => 'any',
		));

		// Add empty option to create a HTML template for new rules
		$membership_plan_ids = array_keys( $membership_plan_options );
		$product_restriction_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( 'product_restriction', array(
			'object_ids'         => array( $post->ID ),
			'id'                 => '',
			'membership_plan_id' => array_shift( $membership_plan_ids ),
			'access_schedule'    => 'immediate',
			'access_type'        => '',
		) );

		// Get applied restriction rules
		$purchasing_discount_rules = wc_memberships()->rules->get_rules( 'purchasing_discount', array(
			'object_id'         => $post->ID,
			'content_type'      => 'post_type',
			'content_type_name' => $post->post_type,
			'exclude_inherited' => false,
			'plan_status'       => 'any',
		));

		// Add empty option to create a HTML template for new rules
		$purchasing_discount_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( 'purchasing_discount', array(
			'object_ids'         => array( $post->ID ),
			'id'                 => '',
			'membership_plan_id' => '',
			'discount_type'      => '',
			'discount_amount'    => '',
			'active'             => '',
		) );

		// prepare product restriction access_type options
		$purchasing_discount_type_options = array(
			'percentage' => '%',
			'amount'     => '$',
		);

		?>

		<p class="grouped-notice <?php if ( ! $product->is_type( 'grouped' ) ) : ?>hide<?php endif; ?>">
			<?php esc_html_e( "Memberships do not support grouped products.", WC_Memberships::TEXT_DOMAIN ); ?>
		</p>

		<div class="panel-wrap wc-memberships-data <?php if ( $product->is_type( 'grouped' ) ) : ?>hide<?php endif; ?>">

			<?php if ( ! SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ) : ?>
				<div class="wc-tabs-back"></div>
			<?php endif; ?>

			<ul class="memberships_data_tabs wc-tabs">
				<?php

					/**
					 * Filter product memberships data tabs
					 *
					 * @since 1.0.0
					 * @param array $tabs Associative array of memberships data tabs
					 */
					$memberships_data_tabs = apply_filters( 'wc_memberships_product_data_tabs', array(
						'restrict_product' => array(
							'label'  => __( 'Restrict Content', WC_Memberships::TEXT_DOMAIN ),
							'class'  => array( 'active' ),
							'target' => 'memberships-data-restrict-product',
						),
						'grant_access' => array(
							'label'  => __( 'Grant Access', WC_Memberships::TEXT_DOMAIN ),
							'target' => 'memberships-data-grant-access',
						),
						'purchasing_discounts' => array(
							'label'  => __( 'Discounts', WC_Memberships::TEXT_DOMAIN ),
							'target' => 'memberships-data-purchasing-discounts',
						),
					) );

					foreach ( $memberships_data_tabs as $key => $tab ) {
						$class = isset( $tab['class'] ) ? $tab['class'] : array();
						?><li class="<?php echo sanitize_html_class( $key ); ?>_options <?php echo sanitize_html_class( $key ); ?>_tab <?php echo implode( ' ' , array_map( 'sanitize_html_class', $class ) ); ?>">
							<a href="#<?php echo esc_attr( $tab['target'] ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
						</li><?php
					}

					/**
					 * Fires after the product memberships data write panel tabs are displayed
					 *
					 * @since 1.0.0
					 */
					do_action( 'wc_memberships_data_product_write_panel_tabs' );
				?>
			</ul>


			<div id="memberships-data-restrict-product" class="panel woocommerce_options_panel">

				<p class="variable-notice <?php if ( ! $product->is_type( 'variable' ) ) : ?>hide<?php endif; ?>">
					<?php esc_html_e( 'These rules affect all variations. For variation-level control use the membership plan screen.', WC_Memberships::TEXT_DOMAIN ); ?>
				</p>

				<div class="options_group">
					<div class="table-wrap">
						<?php require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/html-product-restriction-rules.php' ); ?>
					</div>
				</div>

				<div class="options_group">

					<?php woocommerce_wp_checkbox( array(
						'id'          => '_wc_memberships_use_custom_product_viewing_restricted_message',
						'class'       => 'js-toggle-custom-message',
						'label'       => __( 'Use custom message', WC_Memberships::TEXT_DOMAIN ),
						'description' => __( 'Check this box if you want to customize the <strong>viewing restricted message</strong> for this product.', WC_Memberships::TEXT_DOMAIN )
					) ); ?>

					<div class="js-custom-message-editor-container <?php if ( get_post_meta( $post->ID, '_wc_memberships_use_custom_product_viewing_restricted_message', true ) !== 'yes' ) : ?>hide<?php endif; ?>">
					<?php
					$message = get_post_meta( $post->ID, '_wc_memberships_product_viewing_restricted_message', true );
					wp_editor( $message, '_wc_memberships_product_viewing_restricted_message', array(
						'textarea_rows' => 5,
						'teeny'         => true,
					) );
					?>
					</div>

				</div>

				<div class="options_group">

					<?php woocommerce_wp_checkbox( array(
						'id'          => '_wc_memberships_use_custom_product_purchasing_restricted_message',
						'class'       => 'js-toggle-custom-message',
						'label'       => __( 'Use custom message', WC_Memberships::TEXT_DOMAIN ),
						'description' => __( 'Check this box if you want to customize the <strong>purchasing restricted message</strong> for this product.', WC_Memberships::TEXT_DOMAIN )
					) ); ?>

					<div class="js-custom-message-editor-container <?php if ( get_post_meta( $post->ID, '_wc_memberships_use_custom_product_purchasing_restricted_message', true ) !== 'yes' ) : ?>hide<?php endif; ?>">
					<?php
						$message = get_post_meta( $post->ID, '_wc_memberships_product_purchasing_restricted_message', true );
						wp_editor( $message, '_wc_memberships_product_purchasing_restricted_message', array(
							'textarea_rows' => 5,
							'teeny'         => true,
						) );
					?>
					</div>

				</div>

				<?php
					/**
					 * Fires after the product memberships data product restriction panel is displayed
					 *
					 * @since 1.0.0
					 */
					do_action( 'wc_memberships_data_options_restrict_product' );
				?>
			</div><!-- //#memberships-data-restrict-products -->


			<div id="memberships-data-grant-access" class="panel woocommerce_options_panel">

				<p class="variable-notice <?php if ( ! $product->is_type( 'variable' ) ) : ?>hide<?php endif; ?>">
					<?php _e( "These settings affect all variations. For variation-level control use the membership plan screen.", WC_Memberships::TEXT_DOMAIN ); ?>
				</p>

        <!-- Plans that this product grants access to -->
        <div class="options_group">

          <p class="form-field"><label for="_wc_memberships_membership_plan_ids"><?php esc_html_e( 'Purchasing grants access to', WC_Memberships::TEXT_DOMAIN ); ?></label>

          <?php if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ) : ?>
            <input type="hidden" class="js-membership-plan-ids" style="width: 50%;" id="_wc_memberships_membership_plan_ids" name="_wc_memberships_membership_plan_ids" data-placeholder="<?php esc_attr_e( 'Search for a membership plan&hellip;', WC_Memberships::TEXT_DOMAIN ); ?>" data-action="wc_memberships_search_membership_plans" data-multiple="true" data-selected="<?php
              $json_ids = array();

              if ( ! empty( $grant_access_to_plans ) ) {
                foreach ( $grant_access_to_plans as $plan ) {

                  if ( is_object( $plan ) ) {
                    $json_ids[ $plan->get_id() ] = wp_kses_post( html_entity_decode( $plan->get_name() ) );
                  }
                }
              }

              echo esc_attr( json_encode( $json_ids ) );
            ?>" value="<?php echo esc_attr( implode( ',', array_keys( $json_ids ) ) ); ?>" />

          <?php else : ?>
            <select name="_wc_memberships_membership_plan_ids[]" class="js-membership-plan-ids" id="_wc_memberships_membership_plan_ids" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a membership plan&hellip;', WC_Memberships::TEXT_DOMAIN ); ?>">
              <?php
                if ( ! empty( $grant_access_to_plans ) ) {
                  foreach ( $grant_access_to_plans as $plan ) {
                    echo '<option value="' . esc_attr( $plan->get_id() ) . '" selected="selected">' . esc_html( $plan->get_name() ) . '</option>';
                  }
                }
              ?>
            </select>
          <?php endif; ?>

          <img class="help_tip" data-tip="<?php esc_attr_e( 'Select which membership plans does purchasing this product grant access tp.', WC_Memberships::TEXT_DOMAIN ) ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" /></p>

        </div>

        <?php
					/**
					 * Fires after the product memberships data grant access panel is displayed
					 *
					 * @since 1.0.0
					 */
					do_action( 'wc_memberships_data_options_grant_access' );
				?>
			</div><!-- //#memberships-data-grant-access -->


			<div id="memberships-data-purchasing-discounts" class="panel woocommerce_options_panel">

				<p class="variable-notice <?php if ( ! $product->is_type( 'variable' ) ) : ?>hide<?php endif; ?>">
					<?php esc_html_e( 'These rules affect all variations. For variation-level control use the membership plan screen', WC_Memberships::TEXT_DOMAIN ); ?>
				</p>

				<div class="table-wrap">
					<?php require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/html-purchasing-discount-rules.php' ); ?>
				</div>

				<?php
					/**
					 * Fires after the membership plan purchasing discounts panel is displayed
					 *
					 * @since 1.0.0
					 */
					do_action( 'wc_memberships_data_options_purchasing_discounts' );
				?>
			</div><!-- //#memberships-data-purchase-discounts -->

			<?php
				/**
				 * Fires after the product memberships data panels are displayed
				 *
				 * @since 1.0.0
				 */
				do_action( 'wc_memberships_data_product_panels' );
			?>

			<div class="clear"></div>

		</div><!-- //.panel-wrap -->
		<?php

	}


	/**
	 * Process and save restriction rules
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function update_data( $post_id, WP_Post $post ) {

		// Update restriction & discount rules
		wc_memberships()->admin->update_rules( $post_id, array( 'product_restriction', 'purchasing_discount' ), 'post' );
		wc_memberships()->admin->update_custom_message( $post_id, array( 'product_viewing_restricted', 'product_purchasing_restricted' ) );

		// Update membership plans that this product grants access to
		$plan_ids        = $this->get_product_membership_plans( $post->ID, 'id' );
		$posted_plan_ids = isset( $_POST['_wc_memberships_membership_plan_ids'] ) ? $_POST['_wc_memberships_membership_plan_ids'] : array();

		if ( ! is_array( $posted_plan_ids ) ) {
			$posted_plan_ids = explode( ',', $posted_plan_ids );
		}

		sort( $plan_ids );
		sort( $posted_plan_ids );

		// Only continue processing if there are changes
		if ( $plan_ids != $posted_plan_ids ) {

			$removed = array_diff( $plan_ids, $posted_plan_ids );
			$new     = array_diff( $posted_plan_ids, $plan_ids );

			// Handle removed plans
			if ( ! empty( $removed ) ) {

				foreach ( $removed as $plan_id ) {
					$product_ids = get_post_meta( $plan_id, '_product_ids', true );

					if ( ( $key = array_search( $post_id, $product_ids ) ) !== false ) {
						unset( $product_ids[ $key ] );
					}

					update_post_meta( $plan_id, '_product_ids', $product_ids );
				}
			}

			// Handle new plans
			if ( ! empty( $new ) ) {

				foreach ( $new as $plan_id ) {

					$product_ids   = get_post_meta( $plan_id, '_product_ids', true );
					$product_ids[] = $post_id;

					update_post_meta( $plan_id, '_product_ids', $product_ids );
				}
			}
		}
	}

}
