<?php
/**
 * Plugin Name: GLOB_BRACE Compatibility (Alpine Linux)
 * Description: Defines GLOB_BRACE for PHP on musl/Alpine where the constant is missing.
 * Version: 1.0.0
 */

if ( ! defined( 'GLOB_BRACE' ) ) {
	define( 'GLOB_BRACE', 1024 );
}
