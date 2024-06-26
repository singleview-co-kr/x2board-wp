<?php
/**
 * @copyright Rhymix Developers and Contributors
 * @link https://github.com/rhymix/rhymix
 */
namespace X2board\Includes\Classes\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;  // Exit if accessed directly.
}

if ( ! class_exists( '\\X2board\\Includes\\Classes\\Security\\\UploadFileFilter' ) ) {

	class UploadFileFilter {
		/**
		 * Generic checker
		 *
		 * @param string $file
		 * @param string $filename
		 * @return bool
		 */
		public static function check( $file, $filename = null ) {
			// Return error if the file is not uploaded.
			if ( ! $file || ! file_exists( $file ) || ! is_uploaded_file( $file ) ) {
				return false;
			}

			// Return error if the file size is zero.
			if ( ( $filesize = filesize( $file ) ) == 0 ) {
				return false;
			}

			// Get the extension.
			$ext      = $filename ? strtolower( substr( strrchr( $filename, '.' ), 1 ) ) : '';
			$mimetype = self::_getMimetype( $file, true );

			// Check the first 4KB of the file for possible XML content.
			$fp       = fopen( $file, 'rb' );
			$first4kb = fread( $fp, 4096 );
			$is_xml   = preg_match( '/<(?:\?xml|svg)\b/i', $first4kb );

			// Check SVG files.
			if ( ( $ext === 'svg' || $is_xml ) && ! self::_checkSVG( $fp, 0, $filesize ) ) {
				fclose( $fp );
				return false;
			}

			if ( $mimetype ) {
				if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif' ) ) && $mimetype !== 'image' ) {
					return false;
				}

				if ( preg_match( '/(wm[va]|mpe?g|avi|flv|mp[1-4]|as[fx]|wav|midi?|moo?v|qt|r[am]{1,2}|m4v)$/i', $file ) ) {
					if ( $mimetype !== 'video' && $mimetype !== 'audio' ) {
						return false;
					}
				}
			}

			// Check XML files.
			if ( ( $ext === 'xml' || $is_xml ) && ! self::_checkXML( $fp, 0, $filesize ) ) {
				fclose( $fp );
				return false;
			}

			// Check HTML files.
			if ( ( $ext === 'html' || $ext === 'shtml' || $ext === 'xhtml' || $ext === 'phtml' ) && ! $is_xml && ! self::_checkHTML( $fp, 0, $filesize ) ) {
				fclose( $fp );
				return false;
			}

			// Return true if everything is OK.
			fclose( $fp );
			return true;
		}

		/**
		 * Check SVG file for XSS or SSRF vulnerabilities (#1088, #1089)
		 *
		 * @param resource $fp
		 * @param int      $from
		 * @param int      $to
		 * @return bool
		 */
		protected static function _checkSVG( $fp, $from, $to ) {
			if ( self::_matchStream( '/<script|<handler\b|xlink:href\s*=\s*"(?!data:)/i', $fp, $from, $to ) ) {
				return false;
			}
			if ( self::_matchStream( '/\b(?:ev:(?:event|listener|observer)|on[a-z]+)\s*=/i', $fp, $from, $to ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Check XML file for external entity inclusion.
		 *
		 * @param resource $fp
		 * @param int      $from
		 * @param int      $to
		 * @return bool
		 */
		protected static function _checkXML( $fp, $from, $to ) {
			if ( self::_matchStream( '/<!ENTITY/i', $fp, $from, $to ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Check HTML file for PHP code, server-side includes, and other nastiness.
		 *
		 * @param resource $fp
		 * @param int      $from
		 * @param int      $to
		 * @return bool
		 */
		protected static function _checkHTML( $fp, $from, $to ) {
			if ( self::_matchStream( '/<\?(?!xml\b)|<!--#(?:include|exec|echo|config|fsize|flastmod|printenv)\b/i', $fp, $from, $to ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Match a stream against a regular expression.
		 *
		 * This method is useful when dealing with large files,
		 * because we don't need to load the entire file into memory.
		 * We allow a generous overlap in case the matching string
		 * occurs across a block boundary.
		 *
		 * @param string   $regexp
		 * @param resource $fp
		 * @param int      $from
		 * @param int      $to
		 * @param int      $block_size (optional)
		 * @param int      $overlap_size (optional)
		 * @return bool
		 */
		protected static function _matchStream( $regexp, $fp, $from, $to, $block_size = 16384, $overlap_size = 1024 ) {
			fseek( $fp, $position = $from );
			while ( strlen( $content = fread( $fp, $block_size + $overlap_size ) ) > 0 ) {
				if ( preg_match( $regexp, $content ) ) {
					return true;
				}
				fseek( $fp, min( $to, $position += $block_size ) );
			}
			return false;
		}

		protected static function _getMimetype( $file, $trim_subtype = false ) {
			if ( ! extension_loaded( 'fileinfo' ) ) {
				return false;
			}

			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$mime_type = finfo_file( $finfo, $file );
			finfo_close( $finfo );

			if ( $trim_subtype ) {
				$mime_type = strstr( $mime_type, '/', true );
			}
			return $mime_type;
		}
	}
}
/* End of file : UploadFileFilter.class.php */
