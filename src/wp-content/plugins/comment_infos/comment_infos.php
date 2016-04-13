<?php

/*
=== Plugin Name ===
Contributors: Chris Benseler
Tags: comment, browser, so
Tested up to: 2.7


Display infos about each comment (browser version, SO, etc...) 

== Description ==

== Installation ==

- unzip the package in the plugins directory from your Wordpress Installation
- enable the Comments Info plugin in the Plugin area manangement
- use thge following code in the comments.php template of your theme, inside the foreach() thats iters among the comments list
	<?php if (function_exists('comment_infos')) { ?>with <span id="userAgentInfo"> <?php 
		comment_infos($comment->comment_agent); 
	?></span><?php } ?>


Plugin Name: Comments Info
Plugin URI: http://www.chrisb.com.br/files/plugins/commentsinfo.php
Description: Displays infos abaout each comment (user browser, SO, etc...)
Version: 1.0
Author: Chris Benseler
Author URI: http://www.chrisb.com.br

  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

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

class CommentInfos {
	/*
	atributos usados pela classe
	*/
	private $agentString; //string com o user agent
	private $browsersList; //lista de browsers
	private $ossList; //lista de sistemas operacionais
	private $userBrowser; //browser do usuário
 	private $userOS; //sistema operacional do usu´´io
	private $userOSVersion; //versão do sistema operacional
 
 	/*
	construtor: recebe a string do user agent e inicializa as listas de browsers e sistemas operacionais
	*/
	function __construct($string) {
		$this->agentString = $string;
		
		//lista de browsers
		$firefox = array("firefox", "Firefox", array("2.0", "3.0"));
		$opera = array("opera", "Opera", array("Presto"));
		$gp = array("granparadiso", "Firefox", array("GranParadiso"));
		$chrome = array("chrome", "Chrome", array("0.2", "1.0", "2.0"));
		
		$safari = array("safari", "Safari", array());
		$konqueror = array("konqueror", "Konqueror", array("3.5", "3", "4.0", "4.1", "4.2"));
		$ie = array("msie", "Internet Explorer", array("6.0", "6.1", "7.0", "7.1"));
		
		$list = array($firefox, $gp, $opera, $chrome, $safari, $konqueror, $ie);
		$this->browsersList = $list;
		
		//lista de OSs
		//$windows = array("windows", "Windows", array("95", "98", "ME", "XP"));
		$windows = array("windows", "Windows", array());
		$macosx = array("mac os x", "Mac OS", array("10.5"));
		$macos = array("macintosh", "Mac OS", array("10.5"));
		
		/* linux*/
		$ubuntu = array("ubuntu", "Ubuntu", array("8.10", "8.04", "7.04"));
		$debian = array("debian", "Debian", array("4.0", "3.1", "3.0", "2.2", "2.1", "2.0"));
		$mandriva = array("mandriva", "Mandriva", array());
		$suse = array("suse", "SuSe", array());
		$fedora = array("fedora", "Fedora", array());
		$redhat = array("redhat", "Red Hat", array());
		$gentoo = array("gentoo", "Gentoo", array());
		$linux = array("linux", "Linux", array());
		
		/* unix */
		$freebsd = array("freebsd", "FreeBSD", array());
		$netbsd = array("netbsd", "NetBSD", array());
		$solaris = array("solaris", "Solaris", array());
		
		/* mobiles */
		$iphone = array("iphone", "iPhone", array());
		$ipod = array("ipod", "iPod", array());
		
		/* bots */
		$wordpress = array("wordpress", "Wordpress", array());
		
		$newList = array($windows, $macosx, $macos, 
			$ubuntu, $debian, $mandriva, $suse, $fedora, $redhat, $gentoo, $linux, 
			$freebsd, $netbsd, $solaris,
			$iphone, $ipod, $wordpress);
		$this->ossList = $newList;
	}
	
	//procura pelo browser
	function searchBrowser() {
		$str = strtolower($this->agentString);
		$ret = -1;
		$list = $this->browsersList;
		foreach($list as $browser) {
			$myBrowser = $browser[0];
			if(preg_match("#" . $myBrowser . "#i", $str)) {
				if(preg_match("#msie#i", $myBrowser)) {
					return "Internet Explorer";
				} else
					$ret = $myBrowser;
				break;
				
			}
		}
		$this->userBrowser = $ret;
		return $ret;
	}
	
	//procura pelo sistema operacional
	function searchOS() {
		$str = strtolower($this->agentString);
		
		$ret = -1;
		$list = $this->ossList;
	
		foreach($list as $os) {
			$myOS = $os[0];
			if(preg_match("#" . $myOS  . "#i", $str)) {
				$ret =  $os;
				break;
			}
		}
		$this->userOS = $ret;
		return $ret[0];
	}
	
	//procura pelas versões
	function searchVersion() {
		//busca por versões do Windows
		if($this->userOS[0]=="windows") {
			return $this->searchForWindowsVersions();
		} else {
			$str = strtolower($this->agentString);
			foreach($this->userOS[2] as $version) {
				if(preg_match("#" . $version . "#i", $str)) {
					$ret = $version;
					$this->userOSVersion = $ret;
					break;
				}
			}
			return $ret;
		}
	}
	
	//faz busca pelas versões do Windows, uma vez que o user agent do windows segue uma nomenclatura diferente
	function searchForWindowsVersions() {
		$windowsVersionsList = array(
			array("Windows NT 6.0", "Vista"),
			array("Windows NT 5.2", "Server 2003"),
			array("Windows NT 5.1", "XP"),
			array("Windows NT 5.01", "2000 SP1"),
			array("Windows NT 5.0", "2000"),
			array("Windows NT 4.0", "NT 4.0"),
			array("Windows 9x 4.90", "ME"),
			array("Windows 98", "98"),
			array("Windows 95", "95"),
			array("Windows CE", "CE")
		);
		
		$ret = -1;
		$str = strtolower($this->agentString);
		foreach($windowsVersionsList as $version) {
			$myVersion = strtolower($version[0]);
			if(preg_match("#" . $myVersion  . "#i", $str)) {
				$ret =  $version[1];
				break;
			}
		}
		$this->userOSVersion = $ret;
		return $ret;
		
	}
	
	function getBrowserIcon() {
		return get_settings('siteurl') . "/wp-content/plugins/comment_infos/icon/browser/" . $this->userBrowser . ".png";
	}
	
	function getOSIcon() {
	
		if(preg_match("/" . $this->userOS[0] . "/i", "mac os x")) {
			$str = "macosx";
		} else if(preg_match("/ubuntu/i",  $this->userOS[0])) {
			$str = "linuxubuntu";
		}  else {
			$str = $this->userOS[0];
		}
	
		return get_settings('siteurl') . "/wp-content/plugins/comment_infos/icon/os/" . $str . ".png";
	
	}
}


function comment_infos($agent) {

	$commentInfos = new CommentInfos($agent);
	$browser = $commentInfos->searchBrowser();
	$os = $commentInfos->searchOS();

	$version = $commentInfos->searchVersion();

	$str = "";
	if($browser!=-1) {
		$str = " <img src='" . $commentInfos->getBrowserIcon() . "' /> " . ucwords($browser) . " on ";
	}
	
	if($os != -1) {
		$str .= " <img src='" . $commentInfos->getOSIcon() . "' /> " . ucwords($os . " " . $version); 
	}
	echo $str; 	
}
	
?>