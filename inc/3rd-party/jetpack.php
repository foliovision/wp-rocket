<?php
/**
 * Compatibility file with the Jetpack plugin.
 * https://wordpress.org/plugins/jetpack/
 *
 * @package WP Rocket
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

// Is Jetpack installed and active?
if ( class_exists( 'Jetpack' ) ) :

	/**
	 * Improvement with Jetpack: auto-detect the XML sitemaps for the preload option
	 *
	 * @since 2.8
	 * @author Remy Perona
	 */
	if (
		Jetpack::is_module_active( 'sitemaps' )
		&& function_exists( 'jetpack_sitemap_uri' )
	) {
		/**
		 * Add Jetpack option to WP Rocket options
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $options WP Rocket options array.
		 * @return Array Updated WP Rocket options array
		 */
		function rocket_add_jetpack_sitemap_option( $options ) {
			$options['jetpack_xml_sitemap'] = 0;

			return $options;
		}
		add_filter( 'rocket_first_install_options', 'rocket_add_jetpack_sitemap_option' );

		/**
		 * Sanitize jetpack option value
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $inputs Array of inputs values.
		 * @return Array Array of inputs values
		 */
		function rocket_jetpack_sitemap_option_sanitize( $inputs ) {
			$inputs['jetpack_xml_sitemap'] = ! empty( $inputs['jetpack_xml_sitemap'] ) ? 1 : 0;

			return $inputs;
		}
		add_filter( 'rocket_inputs_sanitize', 'rocket_jetpack_sitemap_option_sanitize' );

		/**
		 * Add Jetpack sitemap to preload list
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $sitemaps Array of sitemaps to preload.
		 * @return Array Updated Array of sitemaps to preload
		 */
		function rocket_add_jetpack_sitemap( $sitemaps ) {
			if ( get_rocket_option( 'jetpack_xml_sitemap', false ) ) {
				$sitemaps['jetpack'] = jetpack_sitemap_uri();
			}

			return $sitemaps;
		}
		add_filter( 'rocket_sitemap_preload_list', 'rocket_add_jetpack_sitemap' );

		/**
		 * Add Jetpack sub-option to WP Rocket settings page
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $options WP Rocket options array.
		 * @return Array Updated WP Rocket options array
		 */
		function rocket_sitemap_preload_jetpack_option( $options ) {
			$options[] = array(
				'parent'       => 'sitemap_preload',
				'type'         => 'checkbox',
				'label'        => __( 'Jetpack XML Sitemaps', 'rocket' ),
				'label_for'    => 'jetpack_xml_sitemap',
				'label_screen' => sprintf( __( 'Preload the sitemap from the Jetpack plugin', 'rocket' ), 'Jetpack' ),
				'default'      => 0,
			);
			$options[] = array(
				'parent'        => 'sitemap_preload',
				'type'          => 'helper_description',
				'name'          => 'jetpack_xml_sitemap_desc',
				'description'   => sprintf( __( 'We automatically detected the sitemap generated by the %s plugin. You can check the option to preload it.', 'rocket' ), 'Jetpack' ),
			);

			return $options;
		}
		add_filter( 'rocket_sitemap_preload_options', 'rocket_sitemap_preload_jetpack_option' );
	} // End if().

	/**
	 * Support Jetpack's EU Cookie Law Widget.
	 *
	 * @see https://jetpack.com/support/extra-sidebar-widgets/eu-cookie-law-widget/
	 *
	 * @since 2.9.12
	 * @author Jeremy Herve
	 */
	if ( Jetpack::is_module_active( 'widgets' ) ) :

		/**
		 * Add the EU Cookie Law to the list of mandatory cookies before generating caching files.
		 *
		 * @since 2.9.12
		 * @author Jeremy Herve
		 *
		 * @param array $cookies List of mandatory cookies.
		 */
		function rocket_add_jetpack_cookie_law_mandatory_cookie( $cookies ) {
			$cookies['jetpack-eu-cookie-law'] = 'eucookielaw';

			return $cookies;
		}
		add_filter( 'rocket_cache_mandatory_cookies' , 'rocket_add_jetpack_cookie_law_mandatory_cookie' );

		// Don't add the WP Rocket rewrite rules to avoid issues.
		add_filter( 'rocket_htaccess_mod_rewrite', '__return_false' );

		/**
		 * Add Jetpack cookie when:
		 * 	- Jetpack is active.
		 * 	- Jetpack's Extra Sidebar Widgets module is active.
		 * 	- The widget is active.
		 *	- the rocket_jetpack_eu_cookie_widget option is empty or not set.
		 *
		 * @since 2.9.12
		 * @author Jeremy Herve
		 */
		function rocket_activate_jetpack_cookie_law() {
			$rocket_jp_eu_cookie_widget = get_option( 'rocket_jetpack_eu_cookie_widget' );

			if (
				is_active_widget( false, false, 'eu_cookie_law_widget' )
				&& empty( $rocket_jp_eu_cookie_widget )
			) {
				add_filter( 'rocket_htaccess_mod_rewrite'    , '__return_false' );
				add_filter( 'rocket_cache_mandatory_cookies' , 'rocket_add_jetpack_cookie_law_mandatory_cookie' );

				// Update the WP Rocket rules on the .htaccess file.
				flush_rocket_htaccess();

				// Regenerate the config file.
				rocket_generate_config_file();

				// Set the option, so this is not triggered again.
				update_option( 'rocket_jetpack_eu_cookie_widget', 1, true );
			}
		}
		add_action( 'admin_init', 'rocket_activate_jetpack_cookie_law' );

	endif; // End if Widgets module is active check.

endif; // End if Jetpack is active check.

/**
 * Remove cookies if Jetpack gets deactivated.
 *
 * @since 2.9.12
 * @author Jeremy Herve
 */
function rocket_remove_jetpack_cookie_law_mandatory_cookie() {
	remove_filter( 'rocket_htaccess_mod_rewrite' , '__return_false' );
	remove_filter( 'rocket_cache_mandatory_cookies', '_rocket_add_eu_cookie_law_mandatory_cookie' );

	// Update the WP Rocket rules on the .htaccess file.
	flush_rocket_htaccess();

	// Regenerate the config file.
	rocket_generate_config_file();

	// Delete our option.
	delete_option( 'rocket_jetpack_eu_cookie_widget' );
}
add_action( 'deactivate_jetpack/jetpack.php', 'rocket_remove_jetpack_cookie_law_mandatory_cookie', 11 );
