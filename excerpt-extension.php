<?php
/*
Plugin Name: Excerpt Extension
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: Adds more excerpt options to the details pannel in the widgets admin from the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 4.7.1
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
function apply_the_excerpt_social_buttons_filter($text) {

	global $settings;		
	$ret = "";
	
	if (isset($settings["hide_social_buttons"]) && $settings["hide_social_buttons"])
	{
		// length
		if (isset($instance['excerpt_length']) && ($instance['excerpt_length'] > 0))
			$length = (int) $instance['excerpt_length'];
		else 
			$length = 55; // use default
	
		// more text
		$more_text = '[&hellip;]';
		if( isset($settings["excerpt_more_text"]) && $settings["excerpt_more_text"] )
			$more_text = ltrim($settings["excerpt_more_text"]);
		$excerpt_more_text = ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '" title="'.sprintf(__('Continue reading %s'),get_the_title()).'">' . $more_text . '</a>';
		$ret = \wp_trim_words( $text, $length, $excerpt_more_text );
	} 
	else {
		if (isset($settings["show_social_buttons_only_once"]) && $settings["show_social_buttons_only_once"]) {
			$ret = $text;
			remove_all_filters('the_content');
		 }
		else {
			$ret = "";
			$ret = $text;
		}
	}
	return $ret;
}

/**
 * Excerpt allow HTML
 *
 * @param excerpt text
 * @return excerpt with allowed html
 *
 */
function allow_html_filter($text) {
	global $settings, $extension, $wp_filter;

	$allowed_elements = '<script>,<style>,<video>,<audio>,<br>,<em>,<strong>,<i>,<ul>,<ol>,<li>,<a>,<p>,<span>,<img><h1>,<h2>,<h3>,<h4>,<h5>,<h6>';
	$new_excerpt_length = ( isset($settings["excerpt_length"]) && $settings["excerpt_length"] > 0 ) ? $settings["excerpt_length"] : 55;

	$text = get_the_content('');
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
	$excerpt_length = $new_excerpt_length;		
	if( isset($settings["excerpt_more_text"]) && ltrim($settings["excerpt_more_text"]) != '')
	{
		$excerpt_more = ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '">' . esc_html($settings["excerpt_more_text"]) . '</a>';
	}
	else if(isset($wp_filter['excerpt_more'][10]) && $wp_filter['excerpt_more'][10] && $filterName = key($wp_filter['excerpt_more'][10]))
	{
		$excerpt_more = $wp_filter['excerpt_more'][10][$filterName]['function'](0);
	}
	else
	{
		$excerpt_more = '[...]';
	}
	
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words)> $excerpt_length) {
		array_pop($words);
		array_push($words, $excerpt_more);
		$text = implode(' ', $words);
	}

	return '<p>' . $text . '</p>';
}

/**
 * Filter to call functions
 *
 * @param this
 * @param instance
 *
 */
function cpwp_before_itemHTML($widget,$instance) {

	global $settings, $extension;
	$settings = $instance;
	$extension = $widget;

	if (isset($instance['excerpt_length']) && ($instance['excerpt_length'] > 0))
		$length = (int) $instance['excerpt_length'];
	else 
		$length = 0; // indicate that invalid length is set	

	// Excerpt length filter
	if ( isset($instance["excerpt_length"]) && ((int) $instance["excerpt_length"]) > 0 &&
			isset($instance["excerpt_override_length"]) && $instance["excerpt_override_length"]) {
		add_filter('excerpt_length', array($widget, 'excerpt_length_filter'), 9999);
	}
	
	if( isset($instance["excerpt_more_text"]) && ltrim($instance["excerpt_more_text"]) != '' &&
			isset($instance["excerpt_override_more_text"]) && $instance["excerpt_override_more_text"])
	{
		add_filter('excerpt_more', array($widget,'excerpt_more_filter'), 9999);
	}
	
	add_filter('the_excerpt', 'termCategoryPostsPro\excerptExtension\apply_the_excerpt_social_buttons_filter');

	if(isset($instance['allow_html_excerpt']) && ($instance['allow_html_excerpt']))
	{
		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('the_excerpt', 'termCategoryPostsPro\excerptExtension\allow_html_filter');
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
	remove_filter('the_excerpt', array($widget,'apply_the_excerpt'));
}

add_action('cpwp_after_itemHTML',__NAMESPACE__.'\cpwp_after_itemHTML',10,2);

/**
 * Panel "More Excerpt Options"
 *
 * @param this
 * @param instance
 * @return outputs a new panel
 *
 */
function cpwp_details_panel_bottom_excerpt($widget,$instance,$alt_prefix) {
	if (count($instance) == 0)
	{ // new widget, use defaults
		$instance = default_settings();
	}
	else
	{ // updated widgets: hide shortcode is on
		if (!isset($instance[$alt_prefix.'hide_shortcode']))
			$instance[$alt_prefix.'hide_shortcode'] = 'on';
	}
	
	$instance = wp_parse_args( ( array ) $instance, array(
	
		// extension options
		$alt_prefix.'excerpt_override_length'       => false,
		$alt_prefix.'excerpt_override_more_text'    => false,
		$alt_prefix.'hide_social_buttons'           => false,
		$alt_prefix.'hide_shortcode'                => false,
		$alt_prefix.'allow_html_excerpt'            => false,
		$alt_prefix.'show_social_buttons_only_once' => false,
	) );
	$excerpt_override_length         = $instance[$alt_prefix.'excerpt_override_length'];
	$excerpt_override_more_text      = $instance[$alt_prefix.'excerpt_override_more_text'];
	$hide_social_buttons             = $instance[$alt_prefix.'hide_social_buttons'];
	$hide_shortcode                  = $instance[$alt_prefix.'hide_shortcode'];
	$allow_html_excerpt              = $instance[$alt_prefix.'allow_html_excerpt'];
	$show_social_buttons_only_once   = $instance[$alt_prefix.'show_social_buttons_only_once'];
?>
<span style="color:#07d;"><?php _e( '(Check to use the additional Excerpt Extension options.)','category-posts' ); ?></span>
<div class="cpwp_ident categoryposts-data-panel-excerpt-filter" style="display:<?php echo ((bool) $instance[$alt_prefix.'excerpt_filters']) ? 'block' : 'none'?>">
	<p>
		<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>">
			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_length"); ?>"<?php checked( !empty($excerpt_override_length), true ); ?> />
			<?php _e( 'Use widget excerpt length','category-posts' ); ?>
		</label>
	</p>
	<p>
		<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>">
			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_more_text"); ?>"<?php checked( !empty($excerpt_override_more_text), true ); ?> />
			<?php _e( 'Use widget excerpt \'more\' text','category-posts' ); ?>
		</label>
	</p>
	<p>
		<label style="color:#07d;" for="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>">
			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>" name="<?php echo $widget->get_field_name("allow_html_excerpt"); ?>"<?php checked( (bool) $allow_html_excerpt, true ); ?> />
				<?php _e( 'Allow HTML and Shortcode',TEXTDOMAIN ); ?>
		</label>
	</p>
	<p>
		<label style="color:#07d;" for="<?php echo $widget->get_field_id("hide_shortcode"); ?>">
			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("hide_shortcode"); ?>" name="<?php echo $widget->get_field_name("hide_shortcode"); ?>"<?php checked( (bool) $hide_shortcode, true ); ?> />
				<?php _e( 'Hide the Shortcode','category-posts' ); ?>
		</label>
	</p>
	<p>
 		<label style="color:#07d;" for="<?php echo $widget->get_field_id("hide_social_buttons"); ?>">
 			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("hide_social_buttons"); ?>" name="<?php echo $widget->get_field_name("hide_social_buttons"); ?>"<?php checked( (bool) $instance["hide_social_buttons"], true ); ?> />
 				<?php _e( 'Hide social buttons',TEXTDOMAIN ); ?>
 		</label>
 	</p>
	<p>
 		<label style="color:#07d;" for="<?php echo $widget->get_field_id("show_social_buttons_only_once"); ?>">
 			<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("show_social_buttons_only_once"); ?>" name="<?php echo $widget->get_field_name("show_social_buttons_only_once"); ?>"<?php checked( (bool) $instance["show_social_buttons_only_once"], true ); ?> />
 				<?php _e( 'Show social buttons only once',TEXTDOMAIN ); ?>
 		</label>
 	</p>
</div>
<?php	
}

add_action('cpwp_details_panel_bottom_excerpt',__NAMESPACE__.'\cpwp_details_panel_bottom_excerpt',10,3);

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings($setting) {

	return wp_parse_args( ( array ) $setting, array(
		'excerpt_override_length'           => false,
		'excerpt_override_more_text'        => false,
		'hide_social_buttons'               => false,
		'hide_shortcode'                    => false,
		'allow_html_excerpt'                => false,
		'show_social_buttons_only_once'     => false,
		'alt_excerpt_override_length'       => false,
		'alt_excerpt_override_more_text'    => false,
		'alt_hide_social_buttons'           => false,
		'alt_hide_shortcode'                => false,
		'alt_allow_html_excerpt'            => false,
		'alt_show_social_buttons_only_once' => false,
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
