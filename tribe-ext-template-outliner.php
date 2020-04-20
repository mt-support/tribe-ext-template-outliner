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
		 */
		private $class_loader;

		/**
		 * @var Settings
		 */
		private $settings;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
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
		 * TODO: Remove if not using Settings.
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
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-template-outliner.pot' file
			load_plugin_textdomain( 'tribe-ext-template-outliner', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			// don't run in the admin or anything silly like that!
			if ( 
				is_admin()
				|| defined( 'DOING_AJAX' ) && DOING_AJAX
			) {
				return;
			}

			// Don't show to the general public - in case someone actually uses it on a live site!
			$user = wp_get_current_user();
			if ( ! in_array( 'administrator', (array) $user->roles ) ) {
				return;
			}

			$this->class_loader();

			// No settings for now!
			//$this->get_settings();

			// Insert filter and action hooks here
			add_action('wp_head', [ $this, 'tribe_ext_template_outliner_styles' ], 100);
			add_action( 'wp_print_footer_scripts', [ $this, 'tribe_ext_template_outliner_scripts' ], 100 );
			add_action( 'tribe_template_before_include', [ $this, 'tribe_ext_template_outliner_tribe_template_before_include' ], 10, 3 );
			add_action( 'admin_bar_menu', [ $this, 'tribe_ext_template_outliner_admin_bar_menu_item' ], 999 );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * Delete this paragraph and the non-applicable comments below.
		 * Make sure to match the readme.txt header.
		 *
		 * Note that older version syntax errors may still throw fatals even
		 * if you implement this PHP version checking so QA it at least once.
		 *
		 * @link https://secure.php.net/manual/en/migration56.new-features.php
		 * 5.6: Variadic Functions, Argument Unpacking, and Constant Expressions
		 *
		 * @link https://secure.php.net/manual/en/migration70.new-features.php
		 * 7.0: Return Types, Scalar Type Hints, Spaceship Operator, Constant Arrays Using define(), Anonymous Classes, intdiv(), and preg_replace_callback_array()
		 *
		 * @link https://secure.php.net/manual/en/migration71.new-features.php
		 * 7.1: Class Constant Visibility, Nullable Types, Multiple Exceptions per Catch Block, `iterable` Pseudo-Type, and Negative String Offsets
		 *
		 * @link https://secure.php.net/manual/en/migration72.new-features.php
		 * 7.2: `object` Parameter and Covariant Return Typing, Abstract Function Override, and Allow Trailing Comma for Grouped Namespaces
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
		 * TODO: Delete this method and its usage throughout this file if there is no `src` directory, such as if there are no settings being added to the admin UI.
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
		 * Demonstration of getting this extension's `a_setting` option value.
		 *
		 * TODO: Rework or remove this.
		 *
		 * @return mixed
		 */
		public function get_one_custom_option() {
			$settings = $this->get_settings();

			return $settings->get_option( 'a_setting', 'https://theeventscalendar.com/' );
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		function tribe_ext_template_outliner_styles() {
			?><style> 
				#tribe-ext-template-debug-panel { 
					background-color: white; 
					border-radius: 3px;
					border: 1px solid black;
					color: black; 
					display: none; 
					font-size: 0.8rem;
					opacity: 90%;
					padding: .5em 2em;
					position: fixed; 
					width: 50%;
					z-index: 99999; 
				}


				#tribe-ext-template-debug-panel code {
					font-size: 0.8rem;
				}

                #tribe-ext-template-debug-panel h1 {
                    font-size: 16px;
                    text-align: center;
                }
				#tribe-ext-template-debug-panel ul {
					list-style: none;
				}

				#tribe-ext-template-debug-panel li { 
					display: flex; 
					margin-bottom: .25em; 
					white-space:nowrap;
				}
                #tribe-ext-template-debug-panel span {
                    width: 16em;
                }
                #tribe-ext-template-debug-panel a:after {
                    content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
                    margin: 0 3px 0 5px;
                }
				#tribe-ext-template-debug-panel input { 
					width: 100%;
				}

				.tribe-ext-template-debug {
					display: none;
				}

				.tribe-ext-template-debug-bottom {
					border-top-left-radius: 0;
					border-top-right-radius: 0;
					bottom: 0;
				}
				.tribe-ext-template-debug-left {
					border-bottom-right-radius: 0;
					border-top-right-radius: 0;
					left: 0;
				}
				.tribe-ext-template-debug-right {
					border-bottom-left-radius: 0;
					border-top-left-radius: 0;
					right: 0;
				}
				.tribe-ext-template-debug-top {
					border-bottom-left-radius: 0;
					border-bottom-right-radius: 0;
					top: 0;
				}

				.tribe-ext-template-debug-border { 
					box-shadow: inset 0px 0px 0px 1px red; 
				} 
			</style><?php
		}

		function tribe_ext_template_outliner_scripts() {
			// Append the panel to the body.
			?>
			<div id="tribe-ext-template-debug-panel">
				<h1>Hold the Control key to "lock" the panel in place. Double-click input field to copy value to clipboard.</h1>
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
			<script>
				( function( $ ) {
					var $panel = $( '#tribe-ext-template-debug-panel' );

					$( '.tribe-ext-template-outliner-toggle > a' ).on( 'click', function( event ) {
						event.stopPropagation();

						if ( 'true' === $panel.data( 'toggle-off' ) ) {
							$panel.data( 'toggle-off', 'false' );
						} else {
							$panel.data( 'toggle-off', 'true' );
							$panel.hide();
							$( '.tribe-ext-template-debug-border' ).removeClass( 'tribe-ext-template-debug-border' )
						}

						return false;
					} );

					$panel.find( 'input' ).dblclick( function () {
						$( this ).select();
						document.execCommand( 'copy' );
					} );

					$('.tribe-ext-template-debug').each(
					function( index ) {
						var $this  = $( this );
						var $next  = $this.next();
						console.log( $panel.data( 'toggle-off' ) );

						$next.on( {
							mouseenter: function( event ) {
								if ( 'true' === $panel.data( 'toggle-off' ) ) {
									return;
								}
								event.stopPropagation();
								event.stopImmediatePropagation();
								$panel.hide();
								var xCord = event.screenX;
								var yCord = event.screenY;

								if ( ! event.ctrlKey ) {
									// Class cleanup.
									$panel.removeClass( 'tribe-ext-template-debug-left tribe-ext-template-debug-right tribe-ext-template-debug-top tribe-ext-template-debug-bottom' );

									// Reposition panel to allow mouse access to entire page.
									if ( 50 > ( xCord / $( window ).width() * 100 ) ) {
										$panel.addClass( 'tribe-ext-template-debug-right' );
									} else {
										$panel.addClass( 'tribe-ext-template-debug-left' );
									}

									if ( 50 > ( yCord / $( window ).height() * 100 ) ) {
										$panel.addClass( 'tribe-ext-template-debug-bottom' );
									} else {
										$panel.addClass( 'tribe-ext-template-debug-top' );
									}
								
									// Update data in panel.
									$( '#tribe_ext_tod_plugin_file' ).val( $this.data( 'plugin-file' ) );
									$( '#tribe_ext_tod_theme_path' ).val( $this.data( 'theme-path' ) );
									$( '#tribe_ext_tod_pre_html' ).val( $this.data( 'pre-html-filter' ) );
									$( '#tribe_ext_tod_before_include' ).val( $this.data( 'before-include-action' ) );
									$( '#tribe_ext_tod_after_include' ).val( $this.data( 'after-include-action' ) );
									$( '#tribe_ext_tod_template_html' ).val( $this.data( 'template-html-filter' ) );
									
									// Add indicator class to hover target.
									$next.addClass( 'tribe-ext-template-debug-border' );
								}

								// Show the panel if it's hidden.
								$panel.show();
							},
							mouseleave: function( event ) {
								if ( 'true' === $panel.data( 'toggle-off' ) ) {
									return;
								}
								event.stopPropagation();
								event.stopImmediatePropagation();

								if ( $next.parent().hasClass( 'tribe-ext-template-debug' ) ) {
									console.log('child->parent');
								}

								$next.removeClass( 'tribe-ext-template-debug-border' );
							}
						} );

					} );

				} )(jQuery)
				
			</script>
			<?php
		}

		function tribe_ext_template_outliner_tribe_template_before_include( $file, $name, $template ) {
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
				class='tribe-ext-template-debug'
				data-plugin-file='{$path}'
				data-theme-path='[your theme]/tribe/{$hook_name}.php'
				data-pre-html-filter='tribe_template_pre_html:{$hook_name}'
				data-before-include-action='tribe_template_before_include:{$hook_name}'
				data-after-include-action='tribe_template_after_include:{$hook_name}'
				data-template-html-filter='tribe_template_html:{$hook_name}'
			></span>";
		}

		function tribe_ext_template_outliner_admin_bar_menu_item( $wp_admin_bar ) {
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
