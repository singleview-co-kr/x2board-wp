<?php
/**
 * This class can be used to hash passwords using various algorithms and check their validity.
 * It is fully compatible with previous defaults, while also supporting bcrypt and pbkdf2.
 *
 * @file Password.class.php
 * @author Kijin Sung (kijin@kijinsung.com)
 * @package /classes/security
 */
namespace X2board\Includes\Classes\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( '\\X2board\\Includes\\Classes\\Security\\Password' ) ) {

	class Password {
		/**
		 * @brief Create a hash using the specified algorithm
		 * @param string $password The password
		 * @param string $algorithm The algorithm (optional)
		 * @return string
		 */
		public function create_hash( $password, $algorithm = null ) {
			if ( $algorithm === null ) {
				$algorithm = 'pbkdf2'; // $this->getCurrentlySelectedAlgorithm();
			}
			if ( ! array_key_exists( $algorithm, $this->_getSupportedAlgorithms() ) ) {
				return false;
			}
			$password = trim( $password );
			switch ( $algorithm ) {
				case 'md5':
					return md5( $password );

				case 'pbkdf2':
					$iterations = pow( 2, $this->_get_work_factor() + 5 );
					$salt       = $this->create_secure_salt( 12, 'alnum' );
					$hash       = base64_encode( $this->_pbkdf2( $password, $salt, 'sha256', $iterations, 24 ) );
					return 'sha256:' . sprintf( '%07d', $iterations ) . ':' . $salt . ':' . $hash;

				case 'bcrypt':
					return $this->_bcrypt( $password );

				default:
					return false;
			}
		}

		/**
		 * @brief Return the list of hashing algorithms supported by this server
		 * @return array
		 */
		private function _getSupportedAlgorithms() {
			$retval = array();
			if ( function_exists( 'hash_hmac' ) && in_array( 'sha256', hash_algos() ) ) {
				$retval['pbkdf2'] = 'pbkdf2';
			}
			if ( version_compare( PHP_VERSION, '5.3.7', '>=' ) && defined( 'CRYPT_BLOWFISH' ) ) {
				$retval['bcrypt'] = 'bcrypt';
			}
			$retval['md5'] = 'md5';
			return $retval;
		}

		/**
		 * @brief Return the currently configured work factor for bcrypt and other adjustable algorithms
		 * @return int
		 */
		private function _get_work_factor() {
			// $n_work_factor = $config->password_hashing_work_factor;
			// if(!$n_work_factor || $n_work_factor < 4 || $n_work_factor > 31) {
				$n_work_factor = 8;  // Reasonable default
			// }
			return $n_work_factor;
		}

		/**
		 * @brief Generate the PBKDF2 hash of a string using a salt
		 * @param string $password The password
		 * @param string $salt The salt
		 * @param string $algorithm The algorithm (optional, default is sha256)
		 * @param int    $iterations Iteration count (optional, default is 8192)
		 * @param int    $length The length of the hash (optional, default is 32)
		 * @return string
		 */
		private function _pbkdf2( $password, $salt, $algorithm = 'sha256', $iterations = 8192, $length = 24 ) {
			if ( function_exists( 'hash_pbkdf2' ) ) {
				return hash_pbkdf2( $algorithm, $password, $salt, $iterations, $length, true );
			} else {
				$output      = '';
				$block_count = ceil( $length / strlen( hash( $algorithm, '', true ) ) );  // key length divided by the length of one hash
				for ( $i = 1; $i <= $block_count; $i++ ) {
					$last = $salt . pack( 'N', $i );  // $i encoded as 4 bytes, big endian
					$last = $xorsum = hash_hmac( $algorithm, $last, $password, true );  // first iteration
					for ( $j = 1; $j < $iterations; $j++ ) { // The other $count - 1 iterations
						$xorsum ^= ( $last = hash_hmac( $algorithm, $last, $password, true ) );
					}
					$output .= $xorsum;
				}
				return substr( $output, 0, $length );
			}
		}

		/**
		 * @brief Generate the bcrypt hash of a string using a salt
		 * @param string $password The password
		 * @param string $salt The salt (optional, auto-generated if empty)
		 * @return string
		 */
		private function _bcrypt( $password, $salt = null ) {
			if ( $salt === null ) {
				$salt = '$2y$' . sprintf( '%02d', $this->_get_work_factor() ) . '$' . $this->createSecureSalt( 22, 'alnum' );
			}
			return crypt( $password, $salt );
		}

		/**
		 * @brief Check the algorithm used to create a hash
		 * @param string $hash The hash
		 * @return string
		 */
		// function checkAlgorithm($hash)
		public function check_algorithm( $hash ) {
			if ( preg_match( '/^\$2[axy]\$([0-9]{2})\$/', $hash, $matches ) ) {
				return 'bcrypt';
			} elseif ( preg_match( '/^sha[0-9]+:([0-9]+):/', $hash, $matches ) ) {
				return 'pbkdf2';
			} elseif ( strlen( $hash ) === 32 && ctype_xdigit( $hash ) ) {
				return 'md5';
			}
			// elseif(strlen($hash) === 16 && ctype_xdigit($hash)) {
			// return 'mysql_old_password';
			// }
			// elseif(strlen($hash) === 41 && $hash[0] === '*') {
			// return 'mysql_password';
			// }
			else {
				return false;
			}
		}

		/**
		 * @brief Check if a password matches a hash
		 * checkPassword($password, $hash, $algorithm = null)
		 * @param string $password The password
		 * @param string $hash The hash
		 * @param string $algorithm The algorithm (optional)
		 * @return bool
		 */
		public function check_password( $password, $hash, $algorithm = null ) {
			if ( $algorithm === null ) {
				$algorithm = $this->check_algorithm( $hash );
			}
			$password = trim( $password );
			switch ( $algorithm ) {
				case 'md5':
					return md5( $password ) === $hash || md5( sha1( md5( $password ) ) ) === $hash;
				case 'pbkdf2':
					$hash            = explode( ':', $hash );
					$hash[3]         = base64_decode( $hash[3] );
					$hash_to_compare = $this->_pbkdf2( $password, $hash[2], $hash[0], intval( $hash[1], 10 ), strlen( $hash[3] ) );
					return $this->_strcmp_constant_time( $hash_to_compare, $hash[3] );
				case 'bcrypt':
					$hash_to_compare = $this->bcrypt( $password, $hash );
					return $this->_strcmp_constant_time( $hash_to_compare, $hash );
								// case 'mysql_old_password':
				// return (class_exists('Context') && substr(Context::getDBType(), 0, 5) === 'mysql') ?
				// DB::getInstance()->isValidOldPassword($password, $hash) : false;
				// case 'mysql_password':
				// return $hash[0] === '*' && substr($hash, 1) === strtoupper(sha1(sha1($password, true)));
				default:
					return false;
			}
		}

		/**
		 * @brief Compare two strings in constant time
		 * @param string $a The first string
		 * @param string $b The second string
		 * @return bool
		 */
		private function _strcmp_constant_time( $a, $b ) {
			$diff   = strlen( $a ) ^ strlen( $b );
			$maxlen = min( strlen( $a ), strlen( $b ) );
			for ( $i = 0; $i < $maxlen; $i++ ) {
				$diff |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
			}
			return $diff === 0;
		}

		/**
		 * @brief Generate a cryptographically secure random string to use as a salt
		 * @param int    $length The number of bytes to return
		 * @param string $format hex or alnum
		 * @return string
		 */
		public function create_secure_salt( $length, $format = 'hex' ) {
			// Find out how many bytes of entropy we really need
			switch ( $format ) {
				case 'hex':
					$entropy_required_bytes = ceil( $length / 2 );
					break;
				case 'alnum':
				case 'printable':
					$entropy_required_bytes = ceil( $length * 3 / 4 );
					break;
				default:
					$entropy_required_bytes = $length;
			}

			// Cap entropy to 256 bits from any one source, because anything more is meaningless
			$entropy_capped_bytes = min( 32, $entropy_required_bytes );

			// Find and use the most secure way to generate a random string
			$is_windows = ( defined( 'PHP_OS' ) && strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' );
			if ( function_exists( 'openssl_random_pseudo_bytes' ) && ( ! $is_windows || version_compare( PHP_VERSION, '5.4', '>=' ) ) ) {
				$entropy = openssl_random_pseudo_bytes( $entropy_capped_bytes );
			} elseif ( function_exists( 'mcrypt_create_iv' ) && ( ! $is_windows || version_compare( PHP_VERSION, '5.3.7', '>=' ) ) ) {
				$entropy = mcrypt_create_iv( $entropy_capped_bytes, MCRYPT_DEV_URANDOM );
			} elseif ( function_exists( 'mcrypt_create_iv' ) && $is_windows ) {
				$entropy = mcrypt_create_iv( $entropy_capped_bytes, MCRYPT_RAND );
			} elseif ( ! $is_windows && @is_readable( '/dev/urandom' ) ) {
				$fp      = fopen( '/dev/urandom', 'rb' );
				$entropy = fread( $fp, $entropy_capped_bytes );
				fclose( $fp );
			} else {
				$entropy = '';
				for ( $i = 0; $i < $entropy_capped_bytes; $i += 2 ) {
					$entropy .= pack( 'S', rand( 0, 65536 ) ^ mt_rand( 0, 65535 ) );
				}
			}

			// Mixing (see RFC 4086 section 5)
			$output = '';
			for ( $i = 0; $i < $entropy_required_bytes; $i += 32 ) {
				$output .= hash( 'sha256', $entropy . $i . rand(), true );
			}

			// Encode and return the random string
			switch ( $format ) {
				case 'hex':
					return substr( bin2hex( $output ), 0, $length );
				case 'binary':
					return substr( $output, 0, $length );
				case 'printable':
					$salt = '';
					for ( $i = 0; $i < $length; $i++ ) {
						$salt .= chr( 33 + ( crc32( sha1( $i . $output ) ) % 94 ) );
					}
					return $salt;
				case 'alnum':
				default:
					$salt         = substr( base64_encode( $output ), 0, $length );
					$replacements = chr( rand( 65, 90 ) ) . chr( rand( 97, 122 ) ) . rand( 0, 9 );
					return strtr( $salt, '+/=', $replacements );
			}
		}
	}
}
/* End of file : Password.class.php */
