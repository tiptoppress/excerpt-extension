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

function apply_the_excerpt_social_buttons($text) {

	$ret = "";
	if (isset($settings["hide_social_buttons"]) && $settings["hide_social_buttons"])
	{
		global $settings;		

		$more_text = '[&hellip;]';
		if( isset($settings["excerpt_more_text"]) && $settings["excerpt_more_text"] )
			$more_text = ltrim($settings["excerpt_more_text"]);

		if ($everything_is_link)
			$excerpt_more_text = ' <span class="cat-post-excerpt-more">'.$more_text.'</span>';
		else
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
	) );
	$excerpt_override_length         = $instance['excerpt_override_length'];
	$excerpt_override_more_text      = $instance['excerpt_override_more_text'];
	$hide_social_buttons             = $instance['hide_social_buttons'];
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


