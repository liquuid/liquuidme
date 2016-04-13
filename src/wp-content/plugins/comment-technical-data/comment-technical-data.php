<?php
/*
Plugin Name: Comment Technical Data
Plugin URI: http://www.polepositionmarketing.com/library/comment-data/
Description: Adds additional technical information to comment notifications, including referrer, User-Agent, and a lot more.
Author: WP-SpamFree
Version: 1.2
Author URI: http://www.polepositionmarketing.com/
*/ 

/*  Copyright 2009-2010    Pole Position Marketing  (email : wpspamfree [at] polepositionmarketing [dot] com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Begin the Plugin

function ctd_add_technical_data( $post_id ) {
	global $current_user;
	?>
	<script type='text/javascript'>
	<!--
	refJS = escape( document[ 'referrer' ] );
	document.write("<input type='hidden' name='refJS' value='"+refJS+"'>");
	// -->
	</script>
	<?php
	}
add_action( 'comment_form', 'ctd_add_technical_data' );

function ctd_add_technical_data_to_notification( $text, $comment_id ) {

	// IP / PROXY INFO :: BEGIN
	$ip = $_SERVER['REMOTE_ADDR'];
	$ipBlock=explode('.',$ip);
	$ipProxyVIA=$_SERVER['HTTP_VIA'];
	$MaskedIP=$_SERVER['HTTP_X_FORWARDED_FOR']; // Stated Original IP - Can be faked
	$MaskedIPBlock=explode('.',$MaskedIP);
	if (eregi("^([0-9]|[0-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.([0-9]|[0-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.([0-9]|[0-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.([0-9]|[0-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])",$MaskedIP)&&$MaskedIP!=""&&$MaskedIP!="unknown"&&!eregi("^192.168.",$MaskedIP)) {
		$MaskedIPValid=true;
		$MaskedIPCore=rtrim($MaskedIP,' unknown;,');
		}
	if ( !$MaskedIP ) { $MaskedIP='[no data]'; }
	$ReverseDNS = gethostbyaddr($ip);
	$ReverseDNSIP = gethostbyname($ReverseDNS);
	
	if ( $ReverseDNSIP != $ip || $ip == $ReverseDNS ) {
		$ReverseDNSAuthenticity = '[Possibly Forged]';
		} 
	else {
		$ReverseDNSAuthenticity = '[Verified]';
		}
	// Detect Use of Proxy
	if ($_SERVER['HTTP_VIA']||$_SERVER['HTTP_X_FORWARDED_FOR']) {
		$ipProxy='PROXY DETECTED';
		$ipProxyShort='PROXY';
		$ipProxyData=$ip.' | MASKED IP: '.$MaskedIP;
		$ProxyStatus='TRUE';
		}
	else {
		$ipProxy='No Proxy';
		$ipProxyShort=$ipProxy;
		$ipProxyData=$ip;
		$ProxyStatus='FALSE';
		}
	// IP / PROXY INFO :: END


	$text .= "\r\n-------------------------------";
	$text .= "\r\n:: Additional Technical Data ::";
	$text .= "\r\n-------------------------------";
	$text .= "\r\n";

	if( $_POST[ 'refJS' ] && $_POST[ 'refJS' ] != '' ) {
		$refJS = addslashes( urldecode( $_POST[ 'refJS' ] ) );
		$refJS = str_replace( '%3A', ':', $refJS );
		$text .= "\r\nPage Referrer: $refJS\r\n";
		}
	$text .= "\r\nComment Processor Referrer: ".$_SERVER['HTTP_REFERER'];
	$text .= "\r\n";
	$text .= "\r\nUser-Agent: ".$_SERVER['HTTP_USER_AGENT'];
	$text .= "\r\n";
	$text .= "\r\nIP Address               : ".$ip;
	$text .= "\r\nRemote Host              : ".$_SERVER['REMOTE_HOST'];
	$text .= "\r\nReverse DNS              : ".$ReverseDNS;
	$text .= "\r\nReverse DNS IP           : ".$ReverseDNSIP;
	$text .= "\r\nReverse DNS Authenticity : ".$ReverseDNSAuthenticity;
	$text .= "\r\nProxy Info               : ".$ipProxy;
	$text .= "\r\nProxy Data               : ".$ipProxyData;
	$text .= "\r\nProxy Status             : ".$ProxyStatus;
	if ( $_SERVER['HTTP_VIA'] ) {
		$text .= "\r\nHTTP_VIA                 : ".$_SERVER['HTTP_VIA'];
		}
	if ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
		$text .= "\r\nHTTP_X_FORWARDED_FOR     : ".$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
	$text .= "\r\nHTTP_ACCEPT_LANGUAGE     : ".$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$text .= "\r\n";
	$text .= "\r\nHTTP_ACCEPT: ".$_SERVER['HTTP_ACCEPT'];
	$text .= "\r\n";
	$text .= "\r\nIP Address Lookup: http://www.dnsstuff.com/tools/ipall/?ip=".$ip;
	$text .= "\r\n";

	return $text;
	}
add_filter( 'comment_notification_text', 'ctd_add_technical_data_to_notification', 10, 2 );
add_filter( 'comment_moderation_text', 'ctd_add_technical_data_to_notification', 10, 2 );
?>
