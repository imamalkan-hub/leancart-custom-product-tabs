<?php
/**
 * Plugin Name: LeanCart Custom Product Tabs
 * Plugin URI:  https://drivewaysealinglab.com/
 * Description: Add custom tabs such as "Technical Specifications", "Shipping & Returns", and "Usage" to the product page. Content is defined on a single line in the admin panel.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-product-tabs
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_CUSTOM_PRODUCT_TABS_DEMO_SITE' ) ) {
	define( 'LEANCART_CUSTOM_PRODUCT_TABS_DEMO_SITE', 'https://drivewaysealinglab.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Custom_Product_Tabs {

	const SLUG = 'custom-product-tabs';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Custom Product Tabs',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_CUSTOM_PRODUCT_TABS_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'custom-product-tabs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Custom Product Tabs' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'custom-product-tabs' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'tabs', __('Tabs', 'custom-product-tabs'), __('One per line: Title|Content (HTML accepted)', 'custom-product-tabs'), 'textarea' );
	}


	public static function hooks() {
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'tabs' ), 20 );
	}

	private static function parsed() {
		$raw = LeanCart_Framework::setting( 'tabs' );
		$out = array();
		foreach ( explode( "\n", $raw ) as $line ) {
			$line = trim( $line );
			if ( ! $line || false === strpos( $line, '|' ) ) { continue; }
			list( $title, $body ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( $title ) {
				$key = 'lc_tab_' . count( $out );
				$out[ $key ] = array( 'title' => sanitize_text_field( $title ), 'body' => $body, 'key' => $key );
			}
		}
		return $out;
	}

	public static function tabs( $tabs ) {
		$idx = 0;
		foreach ( self::parsed() as $tab ) {
			$tabs[ $tab['key'] ] = array(
				'title'    => $tab['title'],
				'priority' => 40 + $idx++,
				'callback' => array( __CLASS__, 'render' ),
			);
		}
		return $tabs;
	}

	public static function render( $key ) {
		$tabs = self::parsed();
		if ( isset( $tabs[ $key ] ) ) {
			echo wp_kses_post( do_shortcode( $tabs[ $key ]['body'] ) );
		}
	}


}

LeanCart_Custom_Product_Tabs::init();

