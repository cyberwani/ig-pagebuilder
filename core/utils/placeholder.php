<?php
/**
 * Manage placeholders on Php files
 *
 * Set & Get placeholder on Php files
 *
 * @author		InnoGears Team <support@www.innogears.com>
 * @package		IGPGBLDR
 * @version		$Id$
 */

// define array of placeholders php
global  $placeholders;
$placeholders = array();
$placeholders['widget_title']   = '_IG_WIDGET_TIGLE_';
$placeholders['extra_class']    = '_IG_EXTRA_CLASS_';
$placeholders['index']          = '_IG_INDEX_';
$placeholders['custom_style']   = '_IG_STYLE_';
$placeholders['standard_value'] = '_IG_STD_';
$placeholders['wrapper_append'] = '_IG_WRAPPER_TAG_';

/**
 * Add placeholder to string
 * Ex:	ig_pb_add_placeholder( 'Text %s', 'widget_title' )	=>	'Progress bar _IG_WIDGET_TIGLE_'
*/ 
function ig_pb_add_placeholder( $string, $placeholder, $expression = '' ){
	global $placeholders;
	if ( ! isset( $placeholders[$placeholder] ) )
		return NULL;
	if ( empty( $expression ) )
		return sprintf( $string, $placeholders[$placeholder] );
	else
		return sprintf( $string, sprintf( $expression, $placeholders[$placeholder] ) );
}

/**
 * Replace placeholder with real value
 * Ex:	str_replace( '_IG_STYLE_', $replace, $string );   =>  ig_pb_remove_placeholder( $string, 'custom_style', $replace )
*/ 
function ig_pb_remove_placeholder( $string, $placeholder, $value ){
	global $placeholders;
	if ( ! isset( $placeholders[$placeholder] ) )
		return $string;
	return str_replace( $placeholders[$placeholder], $value, $string );
}

// get placeholder value
function ig_pb_get_placeholder( $placeholder ){
	global $placeholders;
	if ( ! isset( $placeholders[$placeholder] ) )
		return NULL;
	return $placeholders[$placeholder];
}