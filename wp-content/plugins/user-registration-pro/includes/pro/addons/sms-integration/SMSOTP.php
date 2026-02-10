<?php
/**
 * UserRegistrationSMSIntegration SMSOTP
 *
 * @class    SMSOTP
 * @package  UserRegistrationSMSIntegration/SMSOTP
 * @category SMSOTP
 * @author   WPEverest
 * @since  1.0.0
 */

namespace WPEverest\URSMSIntegration;

use lfkeitel\phptotp\{Base32,Totp};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SMSOTP Class
 */
class SMSOTP {
	/**
	 * Generate SMS OTP of provided length and validity using SMSOTP algorithm.
	 *
	 * @param [int] $user_id User Id.
	 * @param [int] $length OTP Length.
	 * @param [int] $validity OTP Validity ( in minutes ).
	 * @return string
	 */
	public static function generate_otp( $user_id, $length, $validity ) {

		$secret = Totp::GenerateSecret( 32 );

		$totp = new Totp( 'sha1', 0, $validity );

		$key = $totp->GenerateToken( $secret, null, $length );

		update_user_meta( $user_id, 'user_registration_sms_verification_otp_key', $key );

		return $key;
	}

	/**
	 * Check whether the submitted OTP matches the stored OTP key.
	 *
	 * @param [int]    $user_id User Id.
	 * @param [string] $user_key OTP Code.
	 * @return bool
	 */
	public static function verify_otp( $user_id, $user_key ) {
		if ( 'PENDING' === get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
			$true_key = get_user_meta( $user_id, 'user_registration_sms_verification_otp_key', true );

			if ( '' !== $true_key ) {

				if ( $true_key === $user_key ) {
					return true;
				} else {
					return false;
				}
			}
		}

		return false;
	}
}

new SMSOTP();
