<?php
/*
Plugin Name: Term and Category Based Posts Widget Excerpt Extension
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: Adds a options to the details pannel in the widgets admin that enables the control more excerpt settings
Author: TipTopPress
Version: 0.1
Author URI: http://tiptoppress.com
*/

namespace termCategoryPostsPro\excerptExtension;

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

const TEXTDOMAIN = 'term-posts-style-extension';

/**
 * Excerpt disable/show social buttons, banner, etc.
 *
 * @param excerpt text
 * @return excerpt with social buttons, banner, etc. or not
 *
 */
function apply_the_excerpt_social_buttons($text) {

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
		$ret = "";
		$ret = $text;
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
function allow_html_excerpt($text) {

	global $settings, $wp_filter;

	$allowed_elements = '<script>,<style>,<br>,<em>,<i>,<ul>,<ol>,<li>,<a>,<p>,<img>,<video>,<audio>';
	$new_excerpt_length = ( isset($settings["excerpt_length"]) && $settings["excerpt_length"] > 0 ) ? $settings["excerpt_length"] : 55;
	// if ( '' == $text ) {
		$text = get_the_content('');
		$text = strip_shortcodes( $text );
		$text = apply_filters('the_content', $text);
		$text = str_replace('\]\]\>', ']]&gt;', $text);
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);		
		$text = strip_tags($text, htmlspecialchars_decode($allowed_elements));
		$excerpt_length = $new_excerpt_length;		
		if( !empty($settings["excerpt_more_text"]) ) {
			add_filter('excerpt_more', array($widget,'excerpt_more_filter'), 9999);
		}else if($filterName = key($wp_filter['excerpt_more'][10])) {
			$excerpt_more = $wp_filter['excerpt_more'][10][$filterName]['function'](0);
		}else {
			$excerpt_more = '[...]';
		}
		
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words)> $excerpt_length) {
			array_pop($words);
			array_push($words, $excerpt_more);
			$text = implode(' ', $words);
		}
	// }
	return '<p>' . $text . '</p>';
}

/**
 * Filter to call functions
 *
 * @param this
 * @param instance
 *
 */
function cpwp_before_widget($widget,$instance) {

	global $settings;
	$settings = $instance;

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
	
	add_filter('the_excerpt', 'termCategoryPostsPro\excerptExtension\apply_the_excerpt_social_buttons');

	if(isset($instance['allow_html_excerpt']) && ($instance['allow_html_excerpt']))
	{
		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('the_excerpt', 'termCategoryPostsPro\excerptExtension\allow_html_excerpt');
	}
}

add_action('cpwp_before_widget',__NAMESPACE__.'\cpwp_before_widget',10,2);

/**
 * Panel "More Excerpt Options"
 *
 * @param this
 * @param instance
 * @return outputs a new panel
 *
 */
function cpwp_after_footer_panel($widget,$instance) {
	
	$instance = wp_parse_args( ( array ) $instance, array(
		'excerpt_override_length'    => false,
		'excerpt_override_more_text' => false,
		'hide_social_buttons'        => false,
		'allow_html_excerpt'         => false,
	) );
	$excerpt_override_length         = $instance['excerpt_override_length'];
	$excerpt_override_more_text      = $instance['excerpt_override_more_text'];
	$hide_social_buttons             = $instance['hide_social_buttons'];
	$allow_html_excerpt              = $instance['allow_html_excerpt'];
?>
<h4 style="background-color: rgba(168, 220, 0, 0.29);" data-panel="excerpt-extension"><?php _e('More Excerpt Options',TEXTDOMAIN);?></h4>
<div>
	<div class="cpwp_ident categoryposts-data-panel-excerpt-filter" style="display:<?php echo ((bool) (isset($instance['excerpt_filters']) && $instance['excerpt_filters'])) ? 'block' : 'none'?>">
		<p>
			<label for="<?php echo $widget->get_field_id("excerpt_override_length"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("excerpt_override_length"); ?>" name="<?php echo $widget->get_field_name("excerpt_override_length"); ?>"<?php checked( !empty($excerpt_override_length), true ); ?> />
				<?php _e( 'Native excerpt length','category-posts' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("excerpt_override_more_text"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("excerpt_override_more_text"); ?>" name="<?php echo $widget->get_field_name("excerpt_override_more_text"); ?>"<?php checked( !empty($excerpt_override_more_text), true ); ?> />
				<?php _e( 'Native excerpt \'more\' text','category-posts' ); ?>
			</label>
		</p>
	</div>
	<div style="display:<?php echo ((bool) (isset($instance['excerpt_filters']) && $instance['excerpt_filters'])) ? 'none' : 'block'?>">
		<p>
			Set the option Post details panel > Show post excerpt > Themes and plugins may override.
		</p>
	</div>
	<p>
 		<label for="<?php echo $widget->get_field_id("hide_social_buttons"); ?>">
 			<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("hide_social_buttons"); ?>" name="<?php echo $widget->get_field_name("hide_social_buttons"); ?>"<?php checked( (bool) $instance["hide_social_buttons"], true ); ?> />
 				<?php _e( 'Hide social buttons in widget output',TEXTDOMAIN ); ?>
 		</label>
 	</p>
	<p>
 		<label for="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>">
 			<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>" name="<?php echo $widget->get_field_name("allow_html_excerpt"); ?>"<?php checked( (bool) $instance["allow_html_excerpt"], true ); ?> />
 				<?php _e( 'Allow HTML in the excerpt',TEXTDOMAIN ); ?>
 		</label>
 	</p>
</div>
<?php	
}

add_action('cpwp_after_footer_panel',__NAMESPACE__.'\cpwp_after_footer_panel',10,2);

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings($setting) {
	$setting['excerpt_override_length']    = '';
	$setting['excerpt_override_more_text'] = '';
	$setting['hide_social_buttons']        = '';
}

add_filter('cpwp_default_settings',__NAMESPACE__.'\cpwp_default_settings');


