<?php

// It is likely this gets included prior to scanning users/bit_setup_inc.php
$userDir = dirname( dirname( __FILE__ ) ).'/users/';
require_once( $userDir.'BitPermUser.php' );
 
class SampleUser extends BitPermUser {
	function SampleUser() {
		parent::BitPermUser();
	}

	// validate the user in the bitweaver database - validation is case insensitive, and we like it that way!
	function validateBitUser( $pLogin, $pass, $challenge, $response ) {
		global $gBitSystem;
		$ret = NULL;
		if( empty( $pLogin ) ) {
			$this->mErrors['login'] = 'User not found';
		} elseif( empty( $pass ) ) {
			$this->mErrors['login'] = 'Password incorrect';
		} else {
			global $gBitDbType, $gBitDbHost, $gBitDbUser, $gBitDbPassword, $gBitDbName;
			$authDb = new BitDbAdodb();

			// keep a copy of the original Db vars
			$tempDbType = $gBitDbType;
			$tempDbHost = $gBitDbHost;
			$tempDbUser = $gBitDbUser;
			$tempDbPassword = $gBitDbPassword;
			$tempDbName = $gBitDbName;

			$gBitDbType = 'mysql';
			$gBitDbHost = 'auth.samplecorp.com';
			$gBitDbUser = 'data';
			$gBitDbPassword = 's3cr3t';
			$gBitDbName = 'masterbw';

			$loginVal = strtoupper( $pLogin ); // case insensitive login
			$loginCol = ' UPPER(`'.(strpos( $pLogin, '@' ) ? 'email' : 'login').'`)';
			// first verify that the user exists
			$query = "select `email`, `login`, `user_id`, `password` from `".BIT_DB_PREFIX."users_users` where " . $authDb->convert_binary(). " $loginCol = ?";
			$result = $authDb->query( $query, array( $loginVal ) );
			if( !$result->numRows() ) {
				$this->mErrors['login'] = 'User not found';
			} else {
				$res = $result->fetchRow();
				$userId = $res['user_id'];
				$user = $res['login'];
				// TikiWiki 1.8+ uses this bizarro conglomeration of fields to get the hash. this sucks for many reasons
				$hash = md5( strtolower($user) . $pass . $res['email']);
				$hash2 = md5($pass);
				// next verify the password with 2 hashes methods, the old one (pass)) and the new one (login.pass;email)
				// TODO - this needs cleaning up - wolff_borg
				if( !$gBitSystem->isFeatureActive( 'feature_challenge' ) || empty($response) ) {
					$query = "select `user_id` from `".BIT_DB_PREFIX."users_users` where " . $authDb->convert_binary(). " $loginCol = ? and (`hash`=? or `hash`=?)";
					$result = $authDb->query( $query, array( $loginVal, $hash, $hash2 ) );
					if ($result->numRows()) {
						$query = "update `".BIT_DB_PREFIX."users_users` set `last_login`=`current_login`, `current_login`=? where `user_id`=?";
						$result = $authDb->query($query, array( $gBitSystem->getUTCTime(), $userId ));
						$ret = $userId;
					} else {
						$this->mErrors['login'] = 'Password incorrect';
					}
				} else {
					// Use challenge-reponse method
					// Compare pass against md5(user,challenge,hash)
					$hash = $authDb->getOne("select `hash`  from `".BIT_DB_PREFIX."users_users` where " . $authDb->convert_binary(). " $loginCol = ?", array( $pLogin ) );
					if (!isset($_SESSION["challenge"])) {
						$this->mErrors['login'] = 'Invalid challenge';
					}
					//print("pass: $pass user: $user hash: $hash <br/>");
					//print("challenge: ".$_SESSION["challenge"]." challenge: $challenge<br/>");
					//print("response : $response<br/>");
					if ($response == md5( strtolower($user) . $hash . $_SESSION["challenge"]) ) {
						$ret = $userId;
						$this->update_lastlogin( $userId );
					} else {
						$this->mErrors['login'] = 'Invalid challenge';
					}
				}
			}

			// reassign the globals in case anything else is depending on them.
			$gBitDbType = $tempDbType;
			$gBitDbHost = $tempDbHost;
			$gBitDbUser = $tempDbUser;
			$gBitDbPassword = $tempDbPassword;
			$gBitDbName = $tempDbName;
		}
		return( $ret );
	}
	 
}

?>
