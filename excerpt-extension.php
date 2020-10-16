<?php
/*
Plugin Name: Excerpt Extension
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: Adds more excerpt options to the details pannel in the widgets admin from the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 4.9.8
Author URI: http://tiptoppress.com
*/

namespace termCategoryPostsPro\excerptExtension;

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

const TEXTDOMAIN = 'excerpt-extension';


/**
 * Excerpt disable/show social buttons, banner, etc.
 *
 * @param excerpt text
 * @return excerpt with social buttons, banner, etc. or not
 *
 */
function apply_widget_the_excerpt_filter( $text ) {

	global $settings;

	// length
	if (isset($settings['excerpt_length']) && ($settings['excerpt_length'] > 0))
	{
		$length = (int) $settings['excerpt_length'];
	}
	else
	{
		$length = 55; // use default
	}

	// more text
	$excerpt_more_text = "";
	if ( ! ( isset( $settings["no_excerpt_more"] ) && $settings["no_excerpt_more"] ) )
	{
		$more_text = '[&hellip;]';
		if( isset($settings["excerpt_more_text"]) && $settings["excerpt_more_text"] )
		{
			$more_text = ltrim($settings["excerpt_more_text"]);
		}
		$excerpt_more_text = ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '" title="'.sprintf(__('Continue reading %s'),get_the_title()).'">' . $more_text . '</a>';
	}
	$ret = \wp_trim_words( $text, $length, $excerpt_more_text );

	return $ret;
}

/**
 * Excerpt length in characters
 *
 * @param excerpt text
 * @return excerpt with the set length in characters
 *
 */
function excerpt_length_in_chars_filter( $text ) {

	global $settings;
	
	// length
	if (isset($settings['excerpt_length']) && ($settings['excerpt_length'] > 0))
	{
		$length = (int) $settings['excerpt_length'];
	}
	else
	{
		$length = 55; // use default
	}
	
	$text = "";
	if ( has_excerpt() )
	{
		$text = get_the_excerpt();
		$length = 9999;
	}
	else
	{
		$text = get_the_content();
	}
	$excerpt = preg_replace(" (\[.*?\])", '', $text);
	$excerpt = strip_shortcodes($excerpt);
	$excerpt = strip_tags($excerpt);
	$excerpt = substr($excerpt, 0, $length);
	// $excerpt = substr($excerpt, 0, strripos($excerpt, " ")); // no word breaks
	$excerpt = trim(preg_replace( '/\s+/', ' ', $excerpt));
	
	// more text
	if ( ! ( isset($settings["no_excerpt_more"] ) && $settings["no_excerpt_more"] ) )
	{
		if( isset($settings["excerpt_more_text"] ) && ltrim( $settings["excerpt_more_text"] ) != '' )
		{
			$excerpt .= ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '">' . esc_html( $settings["excerpt_more_text"] ) . '</a>';
		}
		else if ( $filterName = key( $wp_filter['excerpt_more'][10] ) )
		{
			$excerpt .= " " . $wp_filter['excerpt_more'][10][$filterName]['function'](0);
		}
		else
		{
			$excerpt .= ' [...]';
		}
	}

	return $excerpt;
}

/**
 * Excerpt allow HTML
 *
 * @param excerpt text
 * @return excerpt with allowed html
 *
 */
function allow_html_filter( $text ) {

	global $settings, $wp_filter;

	$allowed_elements = '<script>,<style>,<video>,<audio>,<br>,<em>,<strong>,<i>,<ul>,<ol>,<li>,<a>,<p>,<span>,<img><h1>,<h2>,<h3>,<h4>,<h5>,<h6>';
	
	if ( has_excerpt() ) {
		$text = get_the_excerpt();
		$excerpt_length = 9999;
	} else { 
		$text = get_the_content('');
		$excerpt_length = ( isset($settings["excerpt_length"]) && $settings["excerpt_length"] > 0 ) ? $settings["excerpt_length"] : 55;	
	}

	if (isset($settings['hide_shortcode']) && ($settings['hide_shortcode']))
	{
		$text = strip_shortcodes( $text );
	}
	else
	{
		$text = do_shortcode( $text );
	}
	$text = apply_filters('the_content', $text);
	$text = str_replace('\]\]\>', ']]&gt;', $text);
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);		
	$text = strip_tags($text, htmlspecialchars_decode($allowed_elements));

	$excerpt_more = "";
	if ( ! ( isset($settings["no_excerpt_more"] ) && $settings["no_excerpt_more"] ) ) {
		if( isset( $settings["excerpt_more_text"] ) && ltrim( $settings["excerpt_more_text"] ) != '')
		{
			$excerpt_more = ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '">' . esc_html( $settings["excerpt_more_text"] ) . '</a>';
		}
		else if( isset($wp_filter['excerpt_more'][10] ) && $wp_filter['excerpt_more'][10] && $filterName = key( $wp_filter['excerpt_more'][10] ) )
		{
			$excerpt_more = $wp_filter['excerpt_more'][10][$filterName]['function'](0);
		}
		else
		{
			$excerpt_more = '[...]';
		}
	}
	
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, $excerpt_more);
		$text = implode(' ', $words);
	}
	$text = str_replace('<p>', '<p class="cpwp-excerpt-text">', $text);

	return '<p>' . $text . '</p>';
}

/**
 * Filter to call functions
 *
 * @param this
 * @param instance
 *
 */
function cpwp_before_itemHTML( $widget, $instance ) {

	global $settings;
	$settings = $instance;

	if ( isset($instance['excerpt_length'] ) && ( $instance['excerpt_length'] > 0 ) )
	{
		$length = (int) $instance['excerpt_length'];
	}
	else
	{
		$length = 0; // indicate that invalid length is set	
	}

	// Excerpt length filter
	if ( isset($instance["excerpt_length"]) && ((int) $instance["excerpt_length"]) > 0 &&
			isset($instance["excerpt_override_length"]) && $instance["excerpt_override_length"])
	{
		add_filter('excerpt_length', array($widget, 'excerpt_length_filter'), 20);
	}
	
	if( isset($instance["excerpt_more_text"]) && ltrim($instance["excerpt_more_text"]) != '' &&
			isset($instance["excerpt_override_more_text"]) && $instance["excerpt_override_more_text"])
	{
		add_filter('excerpt_more', array($widget,'excerpt_more_filter'), 20);
	}

	// Show social buttons only once
	if ( ! ( isset($settings["hide_social_buttons"]) && $settings["hide_social_buttons"] ) )
	{
		if (isset($settings["show_social_buttons_only_once"]) && $settings["show_social_buttons_only_once"]) {
			remove_all_filters('the_content');
		}
	}

	// Widget excerpt 
	// - Socical button filter
	if ( isset( $settings["hide_social_buttons"] ) && $settings["hide_social_buttons"] ||
		isset( $settings["no_excerpt_more"] ) && $settings["no_excerpt_more"] ||
		isset($instance['excerpt_length'] ) && ( $instance['excerpt_length'] > 0 ) )
	{
		add_filter('cpwp_excerpt', 'termCategoryPostsPro\excerptExtension\apply_widget_the_excerpt_filter');
	}

	// Length in chars
	if ( isset( $settings["excerpt_length_in_chars"] ) && $settings["excerpt_length_in_chars"] )
	{
		add_filter('cpwp_excerpt', 'termCategoryPostsPro\excerptExtension\excerpt_length_in_chars_filter');
	}

	// Allow HTML excerpt
	if( isset( $instance['allow_html_excerpt'] ) && ( $instance['allow_html_excerpt'] ) )
	{
		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('cpwp_excerpt', 'termCategoryPostsPro\excerptExtension\allow_html_filter');
	}
}

add_action('cpwp_before_itemHTML',__NAMESPACE__.'\cpwp_before_itemHTML',10,2);

/**
 * Filter to call functions
 *
 * @param this
 * @param instance
 *
 */
function cpwp_after_itemHTML($widget,$instance) {

	global $settings;
	$settings = $instance;
	
	remove_filter('excerpt_length', array($widget,'excerpt_length_filter'));
	remove_filter('excerpt_more', array($widget,'excerpt_more_filter'));
	add_filter('get_the_excerpt', 'wp_trim_excerpt');
	remove_filter('cpwp_excerpt', array($widget,'apply_the_excerpt'));
}

add_action('cpwp_after_itemHTML',__NAMESPACE__.'\cpwp_after_itemHTML',10,2);

/**
 * Add 'line-clamp' polyfill for IE and FF
 *
 */
function line_clamp_poly_script() {
	// IE or FF
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match( '/MSIE/i', $user_agent ) || preg_match( '/Firefox/i', $user_agent ) ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.cat-post-item p').each(function(index, element) {
					var height = parseInt(jQuery(element).css('line-height')),
						count = jQuery('.cat-post-item p').css('-webkit-line-clamp')
					if (height == 'normal') {
						height = parseInt(jQuery(element).css('font-size')) * 1.2;
					}
					jQuery(element).css({'line-height' : height + 'px', 'max-height' : (height * count) + 'px'});
				});
			});
		</script>
	<?php
	}
}

add_action( 'wp_footer', __NAMESPACE__ . '\line_clamp_poly_script', 10 );
/**
 * Adds more Excerpt options
 *
 * @param this
 * @param instance
 *
 */
function form_alt_details_panel_filter( $widget, $instance ) {
	__NAMESPACE__ . '\\' . form_details_panel( $widget, $instance, 'alt_' );
}

/**
 * Adds more Excerpt options
 *
 * @param this
 * @param instance
 * @param alt_prefix
 *
 */
function form_details_panel_filter( $widget, $instance ) {
	__NAMESPACE__ . '\\' . form_details_panel( $widget, $instance, '' );
}

/**
 * Adds more Excerpt options
 *
 * @param this
 * @param instance
 * @param alt_prefix
 *
 */
function form_details_panel( $widget, $instance, $alt_prefix ) {
	if (count($instance) == 0)
	{ // new widget, use defaults
		$instance = default_settings();
	}
	
	$instance = wp_parse_args( ( array ) $instance, array(
	
		// extension options
		$alt_prefix.'excerpt_override_length'       => false,
		$alt_prefix.'excerpt_override_more_text'    => false,
		$alt_prefix.'hide_social_buttons'           => false,
		$alt_prefix.'hide_shortcode'                => false,
		$alt_prefix.'allow_html_excerpt'            => false,
		$alt_prefix.'show_social_buttons_only_once' => false,
		$alt_prefix.'excerpt_length_in_chars'       => false,
		$alt_prefix.'no_excerpt_more'               => false,
		// widget options
		$alt_prefix.'excerpt_filters'               => '',
		$alt_prefix.'excerpt_length'                => '',
	) );
	
	// extension options
	$excerpt_override_length         = $instance[$alt_prefix.'excerpt_override_length'];
	$excerpt_override_more_text      = $instance[$alt_prefix.'excerpt_override_more_text'];
	$hide_social_buttons             = $instance[$alt_prefix.'hide_social_buttons'];
	$hide_shortcode                  = $instance[$alt_prefix.'hide_shortcode'];
	$allow_html_excerpt              = $instance[$alt_prefix.'allow_html_excerpt'];
	$show_social_buttons_only_once   = $instance[$alt_prefix.'show_social_buttons_only_once'];
	$excerpt_length_in_chars         = $instance[$alt_prefix.'excerpt_length_in_chars'];
	$no_excerpt_more                 = $instance[$alt_prefix.'no_excerpt_more'];
	// widget options
	$excerpt_filters                 = $instance[$alt_prefix.'excerpt_filters'];
	$excerpt_length                  = $instance[$alt_prefix.'excerpt_length'];

	?>

	<script type="text/javascript">
		if (typeof jQuery !== 'undefined') {

			// namespace
			var exex_namespace = {
				// Show hide excerpt filter options
				toggleExcerptFilter: function(item) {
					var panel = item.parentElement.parentElement.parentElement;
					if (item.checked) {
						jQuery(panel).find('.categoryposts-data-panel-excerpt-filters').show();
					} else {
						jQuery(panel).find('.categoryposts-data-panel-excerpt-filters').hide();
					}
					
				}
			}

			jQuery( document ).ready(function () {
				// Move more excerpt options to sub-panel excerpt settings.
				jQuery( "[data-panel='<?php echo $alt_prefix ?>details'] + div > .categorypostspro-data-panel-excerpt" ).each( function( index, element ) {
					jQuery( element ).append( 
						jQuery( jQuery(this).closest( '.widget-content' ).find( '.categoryposts-data-panel-<?php echo $alt_prefix ?>use-excerpt-filter' ) )
					);
				});

				// Toggle UI excerpt filter options.
				jQuery( 'div[id$="<?php echo $widget->id ?>"] .cpwp-<?php echo $alt_prefix; ?>use-excerpt-filter input[type=checkbox]').change( function( event ) {
					exex_namespace.toggleExcerptFilter(this);
				});
			});
		}
	</script>

	<?php // Adds more Excerpt options ?>
	<div class="cpwp_ident categoryposts-data-panel-<?php echo $alt_prefix ?>use-excerpt-filter" style="border-left-color: #44809e;">
		<p class="cpwp-<?php echo $alt_prefix; ?>use-excerpt-filter">
			<label for="<?php echo $widget->get_field_id($alt_prefix."excerpt_filters"); ?>" onchange="javascript:cpwp_namespace.togglePostDetailsExcerptFilterPanel(this)">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_filters"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_filters"); ?>"<?php checked( !empty($excerpt_filters), true ); ?> />
				<span style="color:#07d;"><?php _e( 'Check to use the additional Excerpt Extension options.','categorypostspro' ); ?></span>
				
			</label>
		</p>
		<?php // Adds more Excerpt options ?>
		<div class="cpwp_ident categoryposts-data-panel-<?php echo $alt_prefix ?>excerpt-filters" style="border-left-color: #44809e;display:<?php echo ((bool) $instance[$alt_prefix.'excerpt_filters']) ? 'block' : 'none'?>">
			<!-- <p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_length"); ?>"<?php checked( !empty($excerpt_override_length), true ); ?> />
					<?php _e( 'Use widget excerpt length','categorypostspro' ); ?>
				</label>
			</p> -->
			<!-- <p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_more_text"); ?>"<?php checked( !empty($excerpt_override_more_text), true ); ?> />
					<?php _e( 'Use widget excerpt \'more\' text','categorypostspro' ); ?>
				</label>
			</p> -->
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."allow_html_excerpt"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."allow_html_excerpt"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."allow_html_excerpt"); ?>"<?php checked( (bool) $allow_html_excerpt, true ); ?> />
						<?php _e( 'Allow HTML and Shortcode','categorypostspro' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."hide_shortcode"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."hide_shortcode"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."hide_shortcode"); ?>"<?php checked( (bool) $hide_shortcode, true ); ?> />
						<?php _e( 'Hide the Shortcode','categorypostspro' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."hide_social_buttons"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."hide_social_buttons"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."hide_social_buttons"); ?>"<?php checked( (bool) $hide_social_buttons, true ); ?> />
						<?php _e( 'Hide social buttons','categorypostspro' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."show_social_buttons_only_once"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."show_social_buttons_only_once"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."show_social_buttons_only_once"); ?>"<?php checked( (bool) $show_social_buttons_only_once, true ); ?> />
						<?php _e( 'Show social buttons only once','categorypostspro' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."no_excerpt_more"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."no_excerpt_more"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."no_excerpt_more"); ?>"<?php checked( (bool) $no_excerpt_more, true ); ?> />
						<?php _e( 'Remove the excerpt \'more\' text','categorypostspro' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_length"); ?>">
					<input style="text-align: center; border-color:#b2cedd;" type="number" min="0" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_length"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_length"); ?>" value="<?php echo $excerpt_length; ?>" />
					<?php _e( 'Excerpt length (in words):','category-posts' ); ?>
				</label>
			</p>
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_length_in_chars"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_length_in_chars"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_length_in_chars"); ?>"<?php checked( !empty($excerpt_length_in_chars), true ); ?> />
					<?php _e( 'Set the excerpt lenght in characters','categorypostspro' ); ?>
				</label>
			</p>
		</div>
	</div>
	<?php
}

add_action('cpwp_after_details_panel', __NAMESPACE__.'\form_details_panel_filter', 10, 2);
add_action('cpwp_after_alt_details_panel', __NAMESPACE__.'\form_alt_details_panel_filter', 10, 2);

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings($setting) {

	return wp_parse_args( ( array ) $setting, array(
		'excerpt_override_length'           => false,
		'alt_excerpt_override_length'       => false,
		'excerpt_override_more_text'        => false,
		'alt_excerpt_override_more_text'    => false,
		'hide_social_buttons'               => false,
		'alt_hide_social_buttons'           => false,
		'hide_shortcode'                    => false,
		'alt_hide_shortcode'                => false,
		'allow_html_excerpt'                => false,
		'alt_allow_html_excerpt'            => false,
		'show_social_buttons_only_once'     => false,
		'alt_show_social_buttons_only_once' => false,
		'no_excerpt_more'                   => false,
		'alt_no_excerpt_more'               => false,
		// widget options
		'excerpt_length_in_chars'           => false,
		'alt_excerpt_length_in_chars'       => false,
		'excerpt_length'                    => 55,
		'alt_excerpt_length'                => 55,
	) );
}

add_filter('cpwp_default_settings',__NAMESPACE__.'\cpwp_default_settings');

// Plugin action links section

/**
 *  Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
 *  
 *  @return array of the widget links
 *  
 */
function add_action_links ( $links ) {
    $pro_link = array(
        '<a target="_blank" href="http://tiptoppress.com/term-and-category-based-posts-widget/?utm_source=widget_eext&utm_campaign=get_pro_eext&utm_medium=action_link">'.__('Get the expected pro widget','category-posts').'</a>',
    );
	
	$links = array_merge($pro_link, $links);
    
    return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__.'\add_action_links' );
