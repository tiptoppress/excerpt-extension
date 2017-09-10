<?php
/*
Plugin Name: Excerpt Extension
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: Adds more excerpt options to the details pannel in the widgets admin from the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 4.8
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
 * Excerpt length in characters
 *
 * @param excerpt text
 * @return excerpt with the set length in characters
 *
 */
function excerpt_length_in_chars_filter($text) {

	global $settings;		
	$excerpt = $text;
	
	if (isset($settings["excerpt_length_in_chars"]) && $settings["excerpt_length_in_chars"]) {
		// length
		if (isset($settings['excerpt_length']) && ($settings['excerpt_length'] > 0))
			$length = (int) $settings['excerpt_length'];
		else 
			$length = 55; // use default
	
		if($source == "content" ? ($excerpt = get_the_content()) : ($excerpt = get_the_excerpt()));
		$excerpt = preg_replace(" (\[.*?\])",'',$excerpt);
		$excerpt = strip_shortcodes($excerpt);
		$excerpt = strip_tags($excerpt);
		$excerpt = substr($excerpt, 0, $length);
		// $excerpt = substr($excerpt, 0, strripos($excerpt, " ")); // no word breaks
		$excerpt = trim(preg_replace( '/\s+/', ' ', $excerpt));
		
		// more text
		if( isset($settings["excerpt_more_text"]) && ltrim($settings["excerpt_more_text"]) != '')
			$excerpt .= ' <a class="cat-post-excerpt-more" href="'. get_permalink() . '">' . esc_html($settings["excerpt_more_text"]) . '</a>';
		else if($filterName = key($wp_filter['excerpt_more'][10]))
			$excerpt .= " " . $wp_filter['excerpt_more'][10][$filterName]['function'](0);
		else
			$excerpt .= ' [...]';
	}
	return $excerpt;
}

add_filter('cpwp_excerpt', 'termCategoryPostsPro\excerptExtension\excerpt_length_in_chars_filter');

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
	else if($filterName = key($wp_filter['excerpt_more'][10]))
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
 * @param panel_id
 * @param panel_name
 * @param alt_prefix
 * @return true: override the widget panel
 *
 */
function form_details_panel_filter($widget,$instance,$panel_id,$panel_name,$alt_prefix) {
	if (count($instance) == 0)
	{ // new widget, use defaults
		$instance = default_settings();
	}
	else
	{ // updated widgets: excerpt filter and hide shortcode is on
		if (!isset($instance[$alt_prefix.'excerpt_filters']))
			$instance[$alt_prefix.'excerpt_filters'] = 'on';
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

		// widget options
		$alt_prefix.'hide_post_titles'           => '',
		$alt_prefix.'post_item_width'            => get_option('thumbnail_size_w',150) * (3/2),
		$alt_prefix.'excerpt'                    => '',
		$alt_prefix.'excerpt_length'             => 55,
		$alt_prefix.'excerpt_more_text'          => '',
		$alt_prefix.'excerpt_filters'            => false,
		$alt_prefix.'comment_num'                => '',
		$alt_prefix.'author'                     => '',
		$alt_prefix.'date'                       => '',
		$alt_prefix.'date_link'                  => '',
		$alt_prefix.'date_format'                => '',
		$alt_prefix.'align_post_title'           => 'left',
		$alt_prefix.'align_post_details'         => 'left',
		$alt_prefix.'thumbTop'                   => '',
		$alt_prefix.'thumbPosition'		         => '',
		$alt_prefix.'everything_is_link'         => false,
		$alt_prefix.'excerpt_length_in_chars'    => false,
	) );
	
	// extension options
	$excerpt_override_length         = $instance[$alt_prefix.'excerpt_override_length'];
	$excerpt_override_more_text      = $instance[$alt_prefix.'excerpt_override_more_text'];
	$hide_social_buttons             = $instance[$alt_prefix.'hide_social_buttons'];
	$hide_shortcode                  = $instance[$alt_prefix.'hide_shortcode'];
	$allow_html_excerpt              = $instance[$alt_prefix.'allow_html_excerpt'];
	$show_social_buttons_only_once   = $instance[$alt_prefix.'show_social_buttons_only_once'];
	$excerpt_length_in_chars         = $instance[$alt_prefix.'excerpt_length_in_chars'];
	
	// widget options
	$hide_post_titles                = $instance[$alt_prefix.'hide_post_titles'];
	$post_item_width                 = $instance[$alt_prefix.'post_item_width'];
	$excerpt                         = $instance[$alt_prefix.'excerpt'];
	$excerpt_length                  = $instance[$alt_prefix.'excerpt_length'];
	$excerpt_more_text               = $instance[$alt_prefix.'excerpt_more_text'];		
	$excerpt_filters                 = $instance[$alt_prefix.'excerpt_filters'];	
	$comment_num                     = $instance[$alt_prefix.'comment_num'];
	$author                          = $instance[$alt_prefix.'author'];
	$date                            = $instance[$alt_prefix.'date'];
	$date_link                       = $instance[$alt_prefix.'date_link'];
	$date_format                     = $instance[$alt_prefix.'date_format'];
	$align_post_title                = $instance[$alt_prefix.'align_post_title'];
	$align_post_details              = $instance[$alt_prefix.'align_post_details'];
	$everything_is_link              = $instance[$alt_prefix.'everything_is_link'];
	
	// position is needed to know if to display the width
	$thumbTop                 = $instance[$alt_prefix.'thumbTop'];
	$thumbPosition            = $instance[$alt_prefix.'thumbPosition'];	
	if (!$thumbPosition) {
		if ($thumbTop)
			$thumbPosition = 'top';
		else
			$thumbPosition        = 'left';
	}
	$thumb_top_use_post_item_width = isset($instance[$alt_prefix.'thumb_top_use_post_item_width']) ? $instance[$alt_prefix.'thumb_top_use_post_item_width'] : '';
	
	$style_column = (isset($instance['layout']) && $instance['layout'] == "column") ? true : false;

	?>
	<h4 <?php if($alt_prefix!=''&&(!isset($instance['alteration'])||$instance['alteration']=='none')) echo "style='display:none;'";
		if($alt_prefix!='') echo "class='cat-button-secondary'";
		?> data-panel="<?php echo $panel_id?>"><?php echo esc_html($panel_name)?>
	</h4>
	<div>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."everything_is_link"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."everything_is_link"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."everything_is_link"); ?>"<?php checked( (bool) $everything_is_link, true ); ?> />
				<?php _e( 'Everything is a link','categorypostspro' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."hide_post_titles"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."hide_post_titles"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."hide_post_titles"); ?>"<?php checked( (bool) $instance[$alt_prefix."hide_post_titles"], true ); ?> />
				<?php _e( 'Hide post titles','categorypostspro' ); ?>
			</label>
		</p>
		<p class="categorypostspro-post-details-panel-width" style="display:<?php echo !$thumb_top_use_post_item_width && (in_array($thumbPosition,array('cover','top','topdetails'))) || $style_column ? 'none' : 'block' ?>">
			<label for="<?php echo $widget->get_field_id($alt_prefix."post_item_width"); ?>">
				<?php _e('Text width:','categorypostspro')?> <input class="widefat" style="width:30%;" type="number" min="1" id="<?php echo $widget->get_field_id("post_item_width"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."post_item_width"); ?>" value="<?php echo $post_item_width; ?>" />
				<?php _e('pixels','categorypostspro'); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."align_post_title"); ?>"><?php _e( 'Post titles align','categorypostspro' ); ?></label>
			<select id="<?php echo $widget->get_field_id($alt_prefix."align_post_title"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."align_post_title"); ?>">
				<option value="left" <?php selected( $align_post_title, 'left', true ); ?>><?php _e( 'Left','categorypostspro' ); ?></option>
				<option value="right" <?php selected( $align_post_title, 'right', true ); ?>><?php _e( 'Right','categorypostspro' ); ?></option>
				<option value="center" <?php selected( $align_post_title, 'center', true ); ?>><?php _e( 'Center','categorypostspro' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."align_post_details"); ?>"><?php _e( 'Details align','categorypostspro' ); ?></label>
			<select id="<?php echo $widget->get_field_id($alt_prefix."align_post_details"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."align_post_details"); ?>">
				<option value="left" <?php selected( $align_post_details, 'left', true ); ?>><?php _e( 'Left','categorypostspro' ); ?></option>
				<option value="right" <?php selected( $align_post_details, 'right', true ); ?>><?php _e( 'Right','categorypostspro' ); ?></option>
				<option value="center" <?php selected( $align_post_details, 'center', true ); ?>><?php _e( 'Center','categorypostspro' ); ?></option>
				<option value="justify" <?php selected( $align_post_details, 'justify', true ); ?>><?php _e( 'Justify','categorypostspro' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."excerpt"); ?>" onchange="javascript:cpwp_namespace.togglePostDetailsExcerptPanel(this)">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt"); ?>"<?php checked( (bool) $instance[$alt_prefix."excerpt"], true ); ?> />
				<?php _e( 'Show post excerpt','categorypostspro' ); ?>
			</label>
		</p>
		<div class="cpwp_ident categorypostspro-post-details-panel-excerpt" style="display:<?php echo ($excerpt) ? 'block' : 'none'?>">
			<p>
				<label for="<?php echo $widget->get_field_id($alt_prefix."excerpt_length"); ?>">
					<?php _e( 'Excerpt length (in words):','categorypostspro' ); ?>					
				</label>
				<input style="text-align: center; width:30%;" type="number" min="0" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_length"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_length"); ?>" value="<?php echo $instance[$alt_prefix."excerpt_length"]; ?>" />
			</p>
			
<!--START MODIF Excerpt Extension-->
			<p>
				<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_length_in_chars"); ?>">
					<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_length_in_chars"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_length_in_chars"); ?>"<?php checked( !empty($excerpt_length_in_chars), true ); ?> />
					<?php _e( 'Set the excerpt lenght in characters','categorypostspro' ); ?>
				</label>
			</p>
<!--//END MODIF Excerpt Extension-->
			
			<p>
				<label for="<?php echo $widget->get_field_id($alt_prefix."excerpt_more_text"); ?>">
					<?php _e( 'Excerpt \'more\' text:','categorypostspro' ); ?>
				</label>
				<input class="widefat" style="width:45%;" placeholder="<?php _e('... more','categorypostspro')?>" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_more_text"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_more_text"); ?>" type="text" value="<?php echo $instance[$alt_prefix."excerpt_more_text"]; ?>" />
			</p>
			<p>
				<label for="<?php echo $widget->get_field_id($alt_prefix."excerpt_filters"); ?>" onchange="javascript:cpwp_namespace.togglePostDetailsExcerptFilterPanel(this)">
					<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_filters"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_filters"); ?>"<?php checked( !empty($excerpt_filters), true ); ?> />
					<?php _e( 'Don\'t override Themes and plugin filters','categorypostspro' ); ?>
					
<!--START MODIF Excerpt Extension-->
					<span style="color:#07d;"><?php _e( '(Check to use the additional Excerpt Extension options.)','categorypostspro' ); ?></span>
<!--//END MODIF Excerpt Extension-->
					
				</label>
			</p>
			
<!--START MODIF Excerpt Extension-->
			<div class="cpwp_ident categoryposts-data-panel-excerpt-filter" style="border-left-color: #44809e;display:<?php echo ((bool) $instance[$alt_prefix.'excerpt_filters']) ? 'block' : 'none'?>">
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_length"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_length"); ?>"<?php checked( !empty($excerpt_override_length), true ); ?> />
						<?php _e( 'Use widget excerpt length','categorypostspro' ); ?>
					</label>
				</p>
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."excerpt_override_more_text"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."excerpt_override_more_text"); ?>"<?php checked( !empty($excerpt_override_more_text), true ); ?> />
						<?php _e( 'Use widget excerpt \'more\' text','categorypostspro' ); ?>
					</label>
				</p>
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("allow_html_excerpt"); ?>" name="<?php echo $widget->get_field_name("allow_html_excerpt"); ?>"<?php checked( (bool) $allow_html_excerpt, true ); ?> />
							<?php _e( 'Allow HTML and Shortcode','categorypostspro' ); ?>
					</label>
				</p>
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id("hide_shortcode"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("hide_shortcode"); ?>" name="<?php echo $widget->get_field_name("hide_shortcode"); ?>"<?php checked( (bool) $hide_shortcode, true ); ?> />
							<?php _e( 'Hide the Shortcode','categorypostspro' ); ?>
					</label>
				</p>
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id("hide_social_buttons"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("hide_social_buttons"); ?>" name="<?php echo $widget->get_field_name("hide_social_buttons"); ?>"<?php checked( (bool) $instance["hide_social_buttons"], true ); ?> />
							<?php _e( 'Hide social buttons','categorypostspro' ); ?>
					</label>
				</p>
				<p>
					<label style="color:#07d;" for="<?php echo $widget->get_field_id("show_social_buttons_only_once"); ?>">
						<input style="border-color:#b2cedd;" type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("show_social_buttons_only_once"); ?>" name="<?php echo $widget->get_field_name("show_social_buttons_only_once"); ?>"<?php checked( (bool) $instance["show_social_buttons_only_once"], true ); ?> />
							<?php _e( 'Show social buttons only once','categorypostspro' ); ?>
					</label>
				</p>
			</div>
<!--//END MODIF Excerpt Extension-->
			
		</div>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."date"); ?>" onchange="javascript:cpwp_namespace.togglePostDetailsDatePanel(this)">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."date"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."date"); ?>"<?php checked( (bool) $instance[$alt_prefix."date"], true ); ?> />
				<?php _e( 'Show post date','categorypostspro' ); ?>
			</label>
		</p>
		<div class="cpwp_ident categorypostspro-post-details-panel-date" style="display:<?php echo ($date == 'on') ? 'block' : 'none'?>">
			<p>
				<label for="<?php echo $widget->get_field_id($alt_prefix."date_format"); ?>">
					<?php _e( 'Date format:','categorypostspro' ); ?>
				</label>
				<input class="text" placeholder="j M Y" id="<?php echo $widget->get_field_id($alt_prefix."date_format"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."date_format"); ?>" type="text" value="<?php echo esc_attr($instance[$alt_prefix."date_format"]); ?>" size="8" />
			</p>
			<p>
				<label for="<?php echo $widget->get_field_id($alt_prefix."date_link"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."date_link"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."date_link"); ?>"<?php checked( (bool) $instance[$alt_prefix."date_link"], true ); ?> />
					<?php _e( 'Make widget date link','categorypostspro' ); ?>
				</label>
			</p>
		</div>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."comment_num"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."comment_num"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."comment_num"); ?>"<?php checked( (bool) $instance[$alt_prefix."comment_num"], true ); ?> />
				<?php _e( 'Show number of comments','categorypostspro' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id($alt_prefix."author"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id($alt_prefix."author"); ?>" name="<?php echo $widget->get_field_name($alt_prefix."author"); ?>"<?php checked( (bool) $instance[$alt_prefix."author"], true ); ?> />
				<?php _e( 'Show post author','categorypostspro' ); ?>
		</p>
	</div>
	<?php

	return true; // return true: override the widget panel
}

add_filter('cpwp_details_panel',__NAMESPACE__.'\form_details_panel_filter',10,5);
add_filter('cpwp_alt_details_panel',__NAMESPACE__.'\form_details_panel_filter',10,5);

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
