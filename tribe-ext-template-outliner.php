<?php
/**
 * Plugin Name:       The Events Calendar Extension: Template Outliner
 * Plugin URI:        https://theeventscalendar.com/extensions/---the-extension-article-url---/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-template-outliner
 * Description:       Live, on-site visual guide to tribe views templates.
 * Version:           0.1.0
 * Extension Class:   Tribe\Extensions\Template_Outliner\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-template-outliner
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Template_Outliner;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( Main::class )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 *
		 * @since 1.0.0
		 */
		private $class_loader;

		/**
		 * @var Settings
		 *
		 * @since 1.0.0
		 */
		private $settings;

		/**
		 * @var Version
		 *
		 * @since 1.0.0
		 */
		private $version = '1.0.0';

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 *
		 * @since 1.0.0
		 */
		public function construct() {
			// Dependency requirements and class properties can be defined here.
			$this->add_required_plugin( 'Tribe__Events__Main', '4.4' );
		}

		/**
		 * Get this plugin's options prefix.
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * @since 1.0.0
		 *
		 * @see \Tribe\Extensions\Example\Settings::set_options_prefix()
		 *
		 * @return string
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', 'tribe-ext-template-outliner' );
		}

		/**
		 * Get Settings instance.
		 *
		 * @since 1.0.0
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			// Load plugin textdomain.
			load_plugin_textdomain( 'tribe-ext-template-outliner', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			// Only show to the roles allowed in the settings.
			$user = wp_get_current_user();
			$allowed = $this->settings->get_option( 'roles', [ 'administrator' ] );
			
			$is_allowed = array_intersect( $allowed, (array) $user->roles );

			if ( empty( $is_allowed )  ) {
				return;
			}

			// Insert filter and action hooks here
			$this->assets();
			add_action( 'wp_print_footer_scripts', [ $this, 'panel' ], 1 );
			add_action( 'tribe_template_before_include', [ $this, 'tribe_template_before_include' ], 10, 3 );
			add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu_item' ], 999 );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @since 1.0.0
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';

					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'tribe-ext-template-outliner' ), $this->get_name(), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( 'tribe-ext-template-outliner' . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * @since 1.0.0
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					__NAMESPACE__ . '\\',
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * Enqueue assets and localize scripts.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function assets() {
			tribe_asset(
				tribe( 'tec.main' ),
				'tribe_ext_template_outliner_script',
				plugins_url( '/src/resources/tribe-ext-template-outliner.js', __FILE__ ),
				['jquery' ],
				'init',
				[
					'localize'     => [
						'name' => 'template_outliner_vars',
						'data' => [
							'color' =>  $this->settings->get_option( 'color', 'red' ),
						],
					]
				]
			);

			tribe_asset( 
				tribe( 'tec.main' ),
				'tribe_ext_template_outliner_style',
				plugins_url( '/src/resources/tribe-ext-template-outliner.css', __FILE__ ),
				[],
				'init'
			);
		}

		/**
		 * Appends the info panel to the body.
		 * 
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function panel() {
			?>
			<div id="tribe-ext-template-outliner-panel">
				<h1>Hold the Control key to "freeze" the panel and its data. Double-click input field to copy value to clipboard.</h1>
				<ul>
                    <li><span>Plugin file:</span> <input id='tribe_ext_tod_plugin_file' value='{$path}' readonly /></li>
					<li><span>Theme path:</span> <input id='tribe_ext_tod_theme_path' value='[your theme]/tribe/{$hook_name}.php' readonly /></li>
				</ul>
				<ol>
                    <li><span><code>pre_html</code> filter:</span> <input id='tribe_ext_tod_pre_html' value='tribe_template_pre_html:{$hook_name}' readonly /></li>
					<li><span><code>before_include</code> action:</span> <input id='tribe_ext_tod_before_include' value='tribe_template_before_include:{$hook_name}' readonly /></li>
					<li><span><code>after_include</code> action:</span> <input id='tribe_ext_tod_after_include' value='tribe_template_after_include:{$hook_name}' readonly /></li>
					<li><span><code>template_html</code> filter:</span> <input id='tribe_ext_tod_template_html' value='tribe_template_html:{$hook_name}' readonly /></li>
				</ol>
                <div>
                    Useful resources: 
                    <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/themers-guide/">Themer's Guide</a>
                    | <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/customizing-template-files-2/">Customizing Template Files</a>
                    | <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/template-hooks/">Template Hooks</a>
                </div>
			</div>
			<?php
		}

		/**
		 * Adds the info span before each template.
		 *
		 * @since 1.0.0
		 *
		 * @param string $file      Complete path to include the PHP File
		 * @param array  $name      Template name
		 * @param self   $template  Current instance of the Tribe__Template
		 * 
		 * @return void
		 */
		function tribe_template_before_include( $file, $name, $template ) {
			$path = explode( '/plugins/', $file );
			if ( empty( $path[1]) ) {
				return;
			}

			$path = $path[1];
			$origin_folder_appendix = array_diff( $template->get_template_folder(), $template->get_template_origin_base_folder() );

			if ( $origin_namespace = $template->template_get_origin_namespace( $file ) ) {
				$legacy_namespace = array_merge( (array) $origin_namespace, $name );
				$namespace        = array_merge( (array) $origin_namespace, $origin_folder_appendix, $name );
			} else {
				$legacy_namespace = $name;
				$namespace        = array_merge( $origin_folder_appendix, $legacy_namespace );
			}

			// Setup the Hook name
			$legacy_hook_name = implode( '/', $legacy_namespace );
			$hook_name        = implode( '/', $namespace );
			echo "<span 
				class='tribe-ext-template-outliner'
				data-plugin-file='{$path}'
				data-theme-path='[your theme]/tribe/{$hook_name}.php'
				data-pre-html-filter='tribe_template_pre_html:{$hook_name}'
				data-before-include-action='tribe_template_before_include:{$hook_name}'
				data-after-include-action='tribe_template_after_include:{$hook_name}'
				data-template-html-filter='tribe_template_html:{$hook_name}'
			></span>";
		}

		/**
		 * Adds toggle link to WP Admin Bar.
		 * 
		 * @since 1.0.0
		 * 
		 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference
		 * 
		 * @return void
		 */
		function admin_bar_menu_item( &$wp_admin_bar ) {
			$args = array(
				'id' => 'tribe-ext-template-outliner-toggle',
				'title' => 'Toggle Outliner',
				'href' => '#',
				'meta' => array(
					'target' => '_self',
					'class' => 'tribe-ext-template-outliner-toggle',
					'title' => 'Toggle on/off the Tribe Template Outliner.'
				)
			);
			
			$wp_admin_bar->add_node($args);
		}
	} // end class
 
} // end if class_exists check
