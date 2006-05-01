<?php

global $gBitSystem, $gBitSmarty;
$gBitSystem->registerPackage( 'samplecorp', dirname( __FILE__).'/' );
 
if( $gBitSystem->isPackageActive( 'samplecorp' ) ) {

	$gBitSystem->setConfig( 'user_class', 'SampleUser' );

	// SampleUser is our instantiated users class
	require_once( SAMPLECORP_PKG_PATH.'SampleUser.php' );
	$gBitSystem->setConfig( 'user_class', 'SampleUser' );
}

?>
