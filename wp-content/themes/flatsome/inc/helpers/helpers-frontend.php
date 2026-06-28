<?php

/**
 * Load a template.
 *
 * @param  string $name
 * @param  array  $vars
 * @return string
 */
function flatsome_template( $name, array $vars = array() ) {
  $located_template = locate_template( 'template-parts/' . $name . '.php' );
  if ( $located_template != '' ) {
    extract( $vars );
    ob_start();
    include $located_template;
    return ob_get_clean();
  }
  return '';
}

/**
 * Converts an array into html attributes.
 *
 * @param  array  $atts
 * @return string
 */
function flatsome_html_atts( array $atts ) {
  $string = '';
  foreach ( $atts as $key => $value ) {
    if ( is_array( $value ) ) $value = implode( ' ', $value );
    $string .= "{$key}=\"{$value}\" ";
  }
  return $string;
}

/**
 * Get Flatsome Icon classes
  */
function get_flatsome_icon_class($style, $size = null){

    $classes = array();
    if($style == 'small'){ $classes[] = 'icon plain';}
    if($style == 'outline'){ $classes[] = 'icon button circle is-outline';}
    if($style == 'outline-round'){ $classes[] = 'icon button round is-outline';}
    if($style == 'fill'){ $classes[] = 'icon primary button circle';}
    if($style == 'fill-round'){ $classes[] = 'icon primary button round';}
    if($size){ $classes[] = 'is-'.$size;}

    return implode(' ', $classes);
}

/**
 * Minify CSS
  */
function flatsome_minify_css($css){
  //$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
  $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
  return $css;
}


function flatsome_dummy_text(){
	$content = '<p><strong>This is a dummy text for demo purpose</strong>. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p><p> Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>';
	return apply_filters( 'flatsome_dummy_text', $content );
}

/**
 * Find a page by title (replacement for deprecated get_page_by_title).
 *
 * @param string $title Page title.
 * @return WP_Post|null
 */
function flatsome_get_page_by_title( $title ) {
	$pages = get_posts(
		array(
			'post_type'              => 'page',
			'title'                  => $title,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	return ! empty( $pages ) ? $pages[0] : null;
}
