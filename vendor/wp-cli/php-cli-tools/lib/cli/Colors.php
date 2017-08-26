<?php
/**
 * PHP Command Line Tools
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 * @author    James Logsdon <dwarf@girsbrain.org>
 * @copyright 2010 James Logsdom (http://girsbrain.org)
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace cli;

/**
 * Change the color of text.
 *
 * Reference: http://graphcomp.com/info/specs/ansi_col.html#colors
 */
class Colors {
	static protected $_colors = array(
		'color' => array(
			'black'   => 30,
			'red'	 => 31,
			'green'   => 32,
			'yellow'  => 33,
			'blue'	=> 34,
			'magenta' => 35,
			'cyan'	=> 36,
			'white'   => 37
		),
		'style' => array(
			'bright'	 => 1,
			'dim'		=> 2,
			'underline' => 4,
			'blink'	  => 5,
			'reverse'	=> 7,
			'hidden'	 => 8
		),
		'background' => array(
			'black'   => 40,
			'red'	 => 41,
			'green'   => 42,
			'yellow'  => 43,
			'blue'	=> 44,
			'magenta' => 45,
			'cyan'	=> 46,
			'white'   => 47
		)
	);
	static protected $_enabled = null;

	static protected $_string_cache = array();

	static public function enable($force = true) {
		self::$_enabled = $force === true ? true : null;
	}

	static public function disable($force = true) {
		self::$_enabled = $force === true ? false : null;
	}

	/**
	 * Check if we should colorize output based on local flags and shell type.
	 *
	 * Only check the shell type if `Colors::$_enabled` is null and `$colored` is null.
	 */
	static public function shouldColorize($colored = null) {
		return self::$_enabled === true ||
			(self::$_enabled !== false &&
				($colored === true ||
					($colored !== false && Streams::isTty())));
	}

	/**
	 * Set the color.
	 *
	 * @param string  $color  The name of the color or style to set.
     * @return string
	 */
	static public function color($color) {
		if (!is_array($color)) {
			$color = compact('color');
		}

		$color += array('color' => null, 'style' => null, 'background' => null);

		if ($color['color'] == 'reset') {
			return "\033[0m";
		}

		$colors = array();
		foreach (array('color', 'style', 'background') as $type) {
			$code = $color[$type];
			if (isset(self::$_colors[$type][$code])) {
				$colors[] = self::$_colors[$type][$code];
			}
		}

		if (empty($colors)) {
			$colors[] = 0;
		}

		return "\033[" . join(';', $colors) . "m";
	}

	/**
	 * Colorize a string using helpful string formatters. If the `Streams::$out` points to a TTY coloring will be enabled,
	 * otherwise disabled. You can control this check with the `$colored` parameter.
	 *
     * @param string   $string
	 * @param boolean  $colored  Force enable or disable the colorized output. If left as `null` the TTY will control coloring.
     * @return string
	 */
	static public function colorize($string, $colored = null) {
		$passed = $string;

		if (!self::shouldColorize($colored)) {
			$return = self::decolorize( $passed, 2 /*keep_encodings*/ );
			self::cacheString($passed, $return);
			return $return;
		}

		$md5 = md5($passed);
		if (isset(self::$_string_cache[$md5]['colorized'])) {
			return self::$_string_cache[$md5]['colorized'];
		}

		$string = str_replace('%%', '%¾', $string);

		foreach (self::getColors() as $key => $value) {
			$string = str_replace($key, self::color($value), $string);
		}

		$string = str_replace('%¾', '%', $string);
		self::cacheString($passed, $string);

		return $string;
	}

	/**
	 * Remove color information from a string.
	 *
	 * @param string $string A string with color information.
	 * @param int    $keep   Optional. If the 1 bit is set, color tokens (eg "%n") won't be stripped. If the 2 bit is set, color encodings (ANSI escapes) won't be stripped. Default 0.
	 * @return string A string with color information removed.
	 */
	static public function decolorize( $string, $keep = 0 ) {
		if ( ! ( $keep & 1 ) ) {
			// Get rid of color tokens if they exist
			$string = str_replace('%%', '%¾', $string);
			$string = str_replace(array_keys(self::getColors()), '', $string);
			$string = str_replace('%¾', '%', $string);
		}

		if ( ! ( $keep & 2 ) ) {
			// Remove color encoding if it exists
			foreach (self::getColors() as $key => $value) {
				$string = str_replace(self::color($value), '', $string);
			}
		}

		return $string;
	}

	/**
	 * Cache the original, colorized, and decolorized versions of a string.
	 *
	 * @param string $passed The original string before colorization.
	 * @param string $colorized The string after running through self::colorize.
	 * @param string $deprecated Optional. Not used. Default null.
	 */
	static public function cacheString( $passed, $colorized, $deprecated = null ) {
		self::$_string_cache[md5($passed)] = array(
			'passed'      => $passed,
			'colorized'   => $colorized,
			'decolorized' => self::decolorize($passed), // Not very useful but keep for BC.
		);
	}

	/**
	 * Return the length of the string without color codes.
	 *
	 * @param string  $string  the string to measure
     * @return int
	 */
	static public function length($string) {
		return safe_strlen( self::decolorize( $string ) );
	}

	/**
	 * Return the width (length in characters) of the string without color codes if enabled.
	 *
	 * @param string      $string        The string to measure.
	 * @param bool        $pre_colorized Optional. Set if the string is pre-colorized. Default false.
	 * @param string|bool $encoding      Optional. The encoding of the string. Default false.
     * @return int
	 */
	static public function width( $string, $pre_colorized = false, $encoding = false ) {
		return strwidth( $pre_colorized || self::shouldColorize() ? self::decolorize( $string, $pre_colorized ? 1 /*keep_tokens*/ : 0 ) : $string, $encoding );
	}

	/**
	 * Pad the string to a certain display length.
	 *
	 * @param string      $string        The string to pad.
	 * @param int         $length        The display length.
	 * @param bool        $pre_colorized Optional. Set if the string is pre-colorized. Default false.
	 * @param string|bool $encoding      Optional. The encoding of the string. Default false.
     * @return string
	 */
	static public function pad( $string, $length, $pre_colorized = false, $encoding = false ) {
		$real_length = self::width( $string, $pre_colorized, $encoding );
		$diff = strlen( $string ) - $real_length;
		$length += $diff;

		return str_pad( $string, $length );
	}

	/**
	 * Get the color mapping array.
	 *
	 * @return array Array of color tokens mapped to colors and styles.
	 */
	static public function getColors() {
		return array(
			'%y' => array('color' => 'yellow'),
			'%g' => array('color' => 'green'),
			'%b' => array('color' => 'blue'),
			'%r' => array('color' => 'red'),
			'%p' => array('color' => 'magenta'),
			'%m' => array('color' => 'magenta'),
			'%c' => array('color' => 'cyan'),
			'%w' => array('color' => 'white'),
			'%k' => array('color' => 'black'),
			'%n' => array('color' => 'reset'),
			'%Y' => array('color' => 'yellow', 'style' => 'bright'),
			'%G' => array('color' => 'green', 'style' => 'bright'),
			'%B' => array('color' => 'blue', 'style' => 'bright'),
			'%R' => array('color' => 'red', 'style' => 'bright'),
			'%P' => array('color' => 'magenta', 'style' => 'bright'),
			'%M' => array('color' => 'magenta', 'style' => 'bright'),
			'%C' => array('color' => 'cyan', 'style' => 'bright'),
			'%W' => array('color' => 'white', 'style' => 'bright'),
			'%K' => array('color' => 'black', 'style' => 'bright'),
			'%N' => array('color' => 'reset', 'style' => 'bright'),
			'%3' => array('background' => 'yellow'),
			'%2' => array('background' => 'green'),
			'%4' => array('background' => 'blue'),
			'%1' => array('background' => 'red'),
			'%5' => array('background' => 'magenta'),
			'%6' => array('background' => 'cyan'),
			'%7' => array('background' => 'white'),
			'%0' => array('background' => 'black'),
			'%F' => array('style' => 'blink'),
			'%U' => array('style' => 'underline'),
			'%8' => array('style' => 'reverse'),
			'%9' => array('style' => 'bright'),
			'%_' => array('style' => 'bright')
		);
	}

	/**
	 * Get the cached string values.
	 *
	 * @return array The cached string values.
	 */
	static public function getStringCache() {
		return self::$_string_cache;
	}

	/**
	 * Clear the string cache.
	 */
	static public function clearStringCache() {
		self::$_string_cache = array();
	}
}
