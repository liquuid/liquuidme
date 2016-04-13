<?php
/*
Plugin Name: Podcast Channels
Plugin URI: http://wordpress.org/extend/plugins/podcast-channels/
Description: Provide metadata for iTunes and multiple podcast feeds
Author: Alan Trewartha
Version: 0.28
Author URI: http://freakytrigger.co.uk/author/alan/
*/ 



// 1 - Add the artist, duration and explicit metadata to the Media Library editing screens
add_filter('attachment_fields_to_edit', 'podcast_channels_audio_metadata_fields', 10, 2);
add_filter('attachment_fields_to_save', 'podcast_channels_audio_metadata_save', 10, 2);

function podcast_channels_audio_metadata_fields($form_fields, $post)
{	if ( substr($post->post_mime_type, 0, 5) == 'audio' )
	{	$id=$post->ID;															// post is passed as an object here
		$metadata=wp_get_attachment_metadata($id);
		
		if ($metadata['podcast_artist']=="")
		{	$file=get_attached_file($id, true);
			$metadata['podcast_artist']= podcast_channels_get_id3($file,'comments','artist');
			$suggested=" <b>suggested value</b> read from ID3 tags";
		}
		
		$form_fields['podcast_artist'] = array(
			'label' => "Artist",
			'value' => $metadata['podcast_artist']
		);

		$suggested="";
		if ($metadata['podcast_duration']=="")
		{	$file=get_attached_file($id, true);
			$metadata['podcast_duration']=podcast_channels_get_id3($file,'playtime_string');
			$suggested=" <b>suggested value</b> read from ID3 tags";
		}
		
		$form_fields['podcast_duration'] = array(
			'label' => "Duration",
			'helps' => "hh:mm:ss (for iTunes podcast data)$suggested",
			'value' => $metadata['podcast_duration']
		);
		$form_fields['podcast_explicit'] = array(
			'label' => "Explicit",
			'input' => 'html',
			'html'  => pod_md_explicit_menu($metadata['podcast_explicit'],"attachments[".$id."][podcast_explicit]"),
			'helps' => '(for iTunes podcast data)'
		);
	}
	return $form_fields;
}

function podcast_channels_audio_metadata_save($post, $attachment)
{	$id=$post['ID'];															// post is passed an array this time!
	$metadata=wp_get_attachment_metadata($id);
	
	if ( isset($attachment['podcast_duration']) )
		$metadata['podcast_duration']= $attachment['podcast_duration'];
	if ( isset($attachment['podcast_explicit']) )
		$metadata['podcast_explicit']= $attachment['podcast_explicit'];
	if ( isset($attachment['podcast_artist']) )
		$metadata['podcast_artist']= $attachment['podcast_artist'];
	wp_update_attachment_metadata( $id, $metadata );
	
	return $post;
}



// 2 - On upload check the duration and add it to the attachment metadata
// use a global here as there is no way to know what the file is in the wp_generate_attachment_metadata filter
add_filter('wp_handle_upload', 'podcast_channels_audio_metadata_ready');
add_filter('wp_generate_attachment_metadata', 'podcast_channels_audio_metadata_set');

$audio_upload_metadata=array();
function podcast_channels_audio_metadata_ready($upload)
{	global $audio_upload_metadata;
	if (substr($upload['type'], 0, 5) == 'audio' )
	{	$audio_upload_metadata['podcast_duration']=podcast_channels_get_id3($upload['file'],'playtime_string');
		$audio_upload_metadata['podcast_artist']=podcast_channels_get_id3($upload['file'],'artist');
	}
	return $upload;
}

function podcast_channels_audio_metadata_set($metadata)
{	// $metadata is an empty array if upload is not an image
	global $audio_upload_metadata;
	return array_merge($metadata, $audio_upload_metadata);
}

// interface to getid3 library
function podcast_channels_get_id3($file,$tag, $comment_tag='')
{	if (!class_exists('getID3'))
		require_once(ABSPATH . WPINC . '/ID3/getid3.php' );
	$getID3 = new getID3;
	$fileinfo = $getID3->analyze($file);
	getid3_lib::CopyTagsToComments($fileinfo);
	//print_r($fileinfo);
	if ($tag!='comments')
		return $fileinfo[$tag];
	return $fileinfo[$tag][$comment_tag][0];
}



// 3 - Add two new general options for the blog: copyright and rss_image
// smuggle this into location with jQuery as there is no real hook to do so
// also add in the CSS and JS functions we need for the main management page
add_action('admin_head', 'podcast_channels_admin_head');
function podcast_channels_admin_head()
{	if (strpos($_SERVER['REQUEST_URI'],"/wp-admin/options-general.php")!==false)
	{	?><script>
			jQuery(document).ready(function(){
				extra_options='<tr><th scope="row"><label for="site_copyright">Site Copyright</label></th><td><input name="site_copyright" type="text" id="site_copyright" size="30" value="<? form_option('site_copyright') ?>" /> <span class="setting-description">Added to RSS2 feed (by Podcast Channels). Add it to your footer with <tt>echo get_option(\'site_copyright\')</tt></span></td></tr>';
				extra_options+='<tr><th scope="row"><label for="site_rss_image">RSS Feed Image</label></th><td><input name="site_rss_image" type="text" id="site_rss_image" size="30" value="<? form_option('site_rss_image') ?>" /> <span class="setting-description">Added to RSS2 feed (by Podcast Channels). By convention it should be 144x144px</span></td></tr>';
				jQuery("label[for='admin_email']").parents("tr").after(extra_options);
				jQuery("input[name='page_options']").val(jQuery("input[name='page_options']").val() + ",site_copyright,site_rss_image");
			});
		</script><?
	}
	else if (strpos($_SERVER['REQUEST_URI'],"podcast-channels.php")!==false)
	{	$podcast_channels_url=get_settings('siteurl');
		if(substr($podcast_channels_url, -1, 1) != '/') $podcast_channels_url .= '/';
		$podcast_channels_url.='wp-content/plugins/podcast-channels/';
		?>
		<link rel='stylesheet' href='<? echo $podcast_channels_url; ?>podcast-channels.css' type='text/css' media='all' />
		<script type='text/javascript' src='<? echo $podcast_channels_url; ?>podcast-channels.js'></script>
		<?
	}
}

// new 2.7 filter to add the new options. 2.5/2.6 uses the jQuery method above to add the names to input[name='page_options']
add_filter( 'whitelist_options', 'podcast_channels_extra_options_whitelist' );
function podcast_channels_extra_options_whitelist($whitelist_options)
{	$whitelist_options['general'][]="site_copyright";
	$whitelist_options['general'][]="site_rss_image";
	return $whitelist_options;
}



// 4 - Add the management page into the WP admin menus
add_action ('admin_menu', 'podcast_channels_admin_menu');
function podcast_channels_admin_menu()
{	global $wp_version;
	if ($wp_version<2.7)
		add_management_page ("Podcast Channels", 'Podcast Channels', 10, __FILE__, "podcast_channels_admin_page");
	else
		add_submenu_page ('upload.php', "Podcast Channels", 'Podcast Channels', 10, __FILE__, "podcast_channels_admin_page");
}



// 5 - The main management page
function podcast_channels_admin_page()
{	global $pod_md_fields, $podcast_channels, $wp_version;

	// process the POSTed form
	$process_posts=$_POST;
	if ($process_posts)
	{	$podcast_channels=array();
	
		// the home definition is a special case, tag it to the front of the definitions array
		if (!isset($process_posts['channel_definition']))  $process_posts['channel_definition']=array();
		if (!isset($process_posts['channel_definition0'])) $process_posts['channel_definition0']="false";
		array_unshift($process_posts['channel_definition'],$process_posts['channel_definition0']);
		$podcast_channels['definitions']=$process_posts['channel_definition'];

		// get indexes of the channels - new channels are given a timestamp ID when first generated
		$indexes=array_flip($process_posts['channel_serial']);
		
		// dump these parameters
		unset($process_posts['cat'], $process_posts['channel_serial']);
		unset($process_posts['channel_definition'], $process_posts['channel_definition0']);

		// step through the metadata => array pairs
		foreach($process_posts as $metadata=>$md_array)
			foreach($md_array as $indexin=>$value)
				if ($value!="" && $value!=$pod_md_fields['defaults'][$metadata])
				{	if (strpos($metadata, "itunes:category")!==false)
						$podcast_channels['metadata'][$indexes[$indexin]]['itunes:category'].=$value."||";
					else
					{	if ($indexes[$indexin]==0 || $value!=$podcast_channels['metadata'][0][$metadata])
							$podcast_channels['metadata'][$indexes[$indexin]][$metadata]=$value;
					}
				}
		
	}
	else
		if(!$podcast_channels = get_option('podcast-channels-data')) $podcast_channels = array();

	
	// $podcast_channels array is all setup now - display the default settings and the channel template...
	?>
	<div class="wrap">   
		<h2>Podcast Channels</h2>
		<FORM method=post action="<? 	echo ($wp_version<2.7)?"edit":"upload"; ?>.php?page=podcast-channels/podcast-channels.php">
		<!-- FORM method=post action="import.php?page=podcast-channels/podcast-channels.php" -->
	
			<div class="channel_data">
				<span><b>Site Defaults</b> <input type=checkbox name="channel_definition0" <?
						if ($podcast_channels['definitions'][0]!="false") echo "checked";
					?> value="is_home_channel()" /> add to Home Feed</span>
				<input type=hidden name="channel_serial[]" value="0">
				<input type=hidden name="title[]"><input type=hidden name="link[]"><input type=hidden name="copyright[]">
				<table>
					<tr><? pod_md_fields_show('itunes:author');		pod_md_fields_show('itunes:name') ?></tr>
					<tr><? pod_md_fields_show('itunes:summary');	pod_md_fields_show('itunes:email') ?></tr>
					<tr><? pod_md_fields_show('itunes:subtitle');	?></tr>
					<tr><? pod_md_fields_show('itunes:image');		?></tr>
					<tr><? pod_md_fields_show('itunes:keywords');	pod_md_fields_show('itunes:explicit');  ?></tr>
					<tr><? pod_md_fields_show('itunes:category'); ?></tr>
					<tr><? pod_md_fields_show('meta:episode_category'); pod_md_fields_show('meta:episode_author');?></tr>
					<tr class="not_default"><? pod_md_fields_show('meta:feedburn_redirect');  pod_md_fields_show('itunes:new-feed-url');	?></tr>
				</table>
			</div>
	
			<div style="text-align:right" id='new_channel'>	
				<p><? wp_dropdown_categories('hide_empty=0'); ?><button class="button" onclick="add_channel('#cat');return false;">Add Category Channel</button></p>
				<p><input id=tags type=text> <button class="button" onclick="add_channel('#tags');return false;">Add Conditional Channel</button></p>
			</div>
	
			<p><INPUT class="button button-primary" type=submit value="Save Channel Data"></p>
	
		</FORM>
	
		<div id='template' class="channel_data">
			<span><b>Channel</b> X Y</span>
			<input type=hidden name="channel_serial[]" value="">
			<input type=hidden name="channel_definition[]" value="">
			<table><tr>
				<td><select onchange="return add_field(this);"><? pod_md_fields_menu(); ?></select></td>
				<td><button style="float:right" class="button" onclick="jQuery(this).parents('.channel_data').remove(); return false">Remove Channel</button></td>
			</tr></table>
		</div>
	</div>
	<script>
	<?

	// use jQuery to display user-defined channels
	for($i=1; $i<count($podcast_channels['definitions']); $i++)
	{	// duplicate the template to make the channel
		if (preg_match("/^is_category\((\d+)\)$/",$podcast_channels['definitions'][$i], $match))
			echo "jQuery('#cat').val('".$match[1]."'); new_channel_field=add_channel('#cat',$i);\n";
		else
			echo "jQuery('#tags').val('".$podcast_channels['definitions'][$i]."'); new_channel_field=add_channel('#tags',$i);\n";

		// add each field in the channel...
		foreach($podcast_channels['metadata'][$i] as $metadata=>$value)
		{	if ($metadata=="itunes:explicit" && $value==$podcast_channels['metadata'][0]['itunes:explicit'])
				unset($podcast_channels['metadata'][$i]['iTunes:explicit']);
			else
				echo "new_channel_field.val('".$metadata."'); add_field(new_channel_field,'".$value."');\n";
		}
		
	}
	echo "</script>";
	
	// finally update the podcast channels option
	update_option('podcast-channels-data', $podcast_channels);
	if ($_GET['hack']=='set_legacy_media_duration') set_legacy_media_duration();
}


function pod_md_fields_show($metadata)
{	global $pod_md_fields, $podcast_channels;
	if ($metadata=="meta:feedburn_redirect" || $metadata=="itunes:new-feed-url") $class=" class='not_default'";
	echo "<th".$class.">".$pod_md_fields['labels'][$metadata]."</th>";

	if ($metadata=="itunes:explicit")
		echo "<td id='explicit_menu'>".pod_md_explicit_menu($podcast_channels['metadata'][0][$metadata])."</td>";
	else if ($metadata=="meta:episode_author")
		echo "<td id='episode_author_menu'>".pod_md_episode_author_menu($podcast_channels['metadata'][0][$metadata])."</td>";
	else if ($metadata=="meta:episode_category")
		echo "<td id='episode_category_menu'>".pod_md_episode_category_menu($podcast_channels['metadata'][0][$metadata])."</td>";
	else if ($metadata=="itunes:category")
		echo "<td colspan=3 id='cat_menus'>".pod_md_cat_menu($podcast_channels['metadata'][0][$metadata])."</td>";
	else
		echo "<td><input type=text name='".$metadata."[]' value='".$podcast_channels['metadata'][0][$metadata]."' default='".$pod_md_fields['defaults'][$metadata]."' /></td>";
}

function pod_md_episode_author_menu($set_episode_author, $name="meta:episode_author[]")
{	$menu_html="<select name='$name' id='$name'>";
	foreach(array("Post author", "Channel author", "Media artist") as $episode_author)
	{	$is_selected=($episode_author==$set_episode_author)?" selected":"";
		$menu_html.= "<option value='$episode_author' $is_selected>$episode_author</option>";
	}
	$menu_html.= "</select>";
	return $menu_html;
}

function pod_md_episode_category_menu($set_episode_category, $name="meta:episode_category[]")
{	$menu_html="<select name='$name' id='$name'>";
	foreach(array("Post categories", "Channel categories", "Blank") as $episode_category)
	{	$is_selected=($episode_category==$set_episode_category)?" selected":"";
		$menu_html.= "<option value='$episode_category' $is_selected>$episode_category</option>";
	}
	$menu_html.= "</select>";
	return $menu_html;
}

function pod_md_fields_menu()
{	global $pod_md_fields;
	echo "<option value='_'>Add field...</option>";
	foreach($pod_md_fields['labels'] as $metadata=>$label)
		echo "<option value='".$metadata."'>".$label."</option>";
}

function pod_md_explicit_menu($set_explicit, $name="itunes:explicit[]")
{	$menu_html="<select name='$name' id='$name'>";
	foreach(array("No", "Yes", "Clean") as $explicit_val)
	{	$is_selected=($explicit_val==$set_explicit)?" selected":"";
		$menu_html.= "<option value='$explicit_val' $is_selected>$explicit_val</option>";
	}
	$menu_html.= "</select>";
	return $menu_html;
}

function pod_md_cat_menu($set_cats)
{	global $pod_md_cats;
	$set_cats=explode("||", $set_cats);
	$menu="";
	for ($i=""; $i!="+++"; $i.="+")
	{	$menu.= "<select name='itunes:category".$i."[]'><option value=''></option>";
		foreach($pod_md_cats as $cat)
		{	if (strpos($cat, "|")===false)
			{	$cat_parent=$cat;
				$cat_disp=$cat;
			}
			else
				$cat_disp=str_replace("$cat_parent|","-",$cat);
			$is_selected=($set_cats[0]==$cat)?" selected":"";
			$menu.= "<option value='".htmlentities($cat)."'".$is_selected.">".htmlentities($cat_disp)."</option>";
		}
		$menu.= "</select>";
		array_shift($set_cats);
	}
	return $menu;
}



// 6 - Start putting metadata into the feeds - first check if we have an active channel, set a global to use later...
$podcast_channel_active=false;
add_action('rss2_ns', 'podcast_channels_itunes_namespace');
function podcast_channels_itunes_namespace()
{	global $podcast_channel_active;
	// put in the itunes NS
	if ($podcast_channel_active!==false) echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"'."\n";
}

function podcast_channels_set_active()
{	global $podcast_channel_active, $pod_md_fields;
	if(!$podcast_channels = get_option('podcast-channels-data')) return;

	// go through the channel definitions
	$definitions=$podcast_channels['definitions'];
	for( $i=0; $i<count($definitions); $i++)
	{	$check_definition=(stristr($definitions[$i], "return"))?$definitions[$i]:"return ".$definitions[$i].";";
		if (eval($check_definition))
		{	// merge the site defaults, the users site defaults and the channel data
			$podcast_channel_active=array_merge($pod_md_fields['defaults'],$podcast_channels['metadata'][0],$podcast_channels['metadata'][$i]);
			// EXCEPTION: revert the new-feed-url to what's in the channel
			$podcast_channel_active['itunes:new-feed-url']=$podcast_channels['metadata'][$i]['itunes:new-feed-url'];
			$podcast_channel_active['meta:feedburn_redirect']=$podcast_channels['metadata'][$i]['meta:feedburn_redirect'];
			break;
		}
	}
}

// the PHP used by the site default feed. is_home doesn't work on the home feed :-(
function is_home_channel() {	global $wp; return (array_keys($wp->query_vars) == array("feed")); }



// 6 - Title and link are provided by WP, so we need to filter them if the user wants them changed...
add_filter('get_wp_title_rss','podcast_channels_bloginfo_title');
function podcast_channels_bloginfo_title($title)
{	return podcast_channels_bloginfo($title, 'title_rss');
}

add_filter('bloginfo_rss','podcast_channels_bloginfo',10,2 );
function podcast_channels_bloginfo($content, $show)
{	global $podcast_channel_active;

	if ($show=='url' && $podcast_channel_active['link'])
	{	$content= $podcast_channel_active['link'];
		unset($podcast_channel_active['link']);
	}
	
	// title is concatenated from "name + title_rss" so replace name and blank the title_rss
	if ($show=='name' && $podcast_channel_active['title'])
		$content= $podcast_channel_active['title'];

	if ($show=='title_rss' && $podcast_channel_active['title'])
	{	$content="";
		unset($podcast_channel_active['title']);
	}

	return $content;
}



// 7 - Now we are free to add per channel metadata
add_action('rss2_head', 'podcast_channels_channel_md'); 
function podcast_channels_channel_md()
{	global $podcast_channel_active;

	// add copyright
	$site_image=get_option('site_rss_image');
	if ($site_image && $podcast_channel_active['itunes:image']=="")
		echo "<image><url>".($site_image)."</url><title>".get_bloginfo('name')."</title><link>".get_bloginfo('url')."</link></image>\n";

	// add image
	$site_copyright=get_option('site_copyright');
	if ($site_image && $podcast_channel_active['copyright']=="")
		echo "<copyright>".podchan_xml_escape(htmlentities($site_copyright,ENT_QUOTES,'UTF-8'))."</copyright>\n";

	if ($podcast_channel_active===false) return;

	// add iTunes metadata at last!
	foreach ($podcast_channel_active as $metadata=>$value)
	{	if ($metadata=="itunes:category")										// multiple categories
		{	$value=explode("||", $value);
			foreach ($value as $cat)
			{	$cat_node= '<itunes:category text="'.htmlentities($cat).'"';
				if (strpos($cat_node,"|")===false)
					$cat_node.=' />';
				else
					$cat_node=str_replace("|", '"><itunes:category text="', $cat_node).' /></itunes:category>';
				if ($cat!="") echo $cat_node."\n";
			}
		}
		
		else if ($metadata=="itunes:image" && $value!="")						// image is formed like this
			echo "<".$metadata." href='".($value)."' />\n";

		else
		{	if ($metadata=="itunes:name") echo "<itunes:owner>";				// owner and email are sandwiched with this

			// most of the extra metadata fields are simply formed like this		
			if ($value!="" && substr($metadata,0,5)!="meta:") echo "<".$metadata.">".podchan_xml_escape(htmlentities($value,ENT_QUOTES,'UTF-8'))."</".$metadata.">\n";

			if ($metadata=="itunes:email") echo "</itunes:owner>\n";
		}
	}
	
}



// 8 - Per item metadata
add_action('rss2_item', 'podcast_channels_item_md'); 
function podcast_channels_item_md()
{	global $podcast_channel_active, $wpdb;
	if ($podcast_channel_active===false) return;
	
	// find the first enclosures metadata (only first as iTunes only believes in one enclosure)
	$post_custom=get_post_custom();
	$encs=(array) $post_custom['enclosure'];
	$enclosure = split("\n", $encs[0]);
	$enc_record = & $wpdb->get_row("SELECT id FROM $wpdb->posts WHERE guid ='".rtrim($enclosure[0])."'  LIMIT 1");
//	$enc_metadata=get_post_meta($enc_record->id,"_wp_attachment_metadata", true);
	$enc_metadata=wp_get_attachment_metadata($enc_record->id);
	$file=get_attached_file($enc_record->id, true);

	if ($podcast_channel_active['meta:episode_author']=="Media artist")
	{	if (!$enc_metadata['podcast_artist'])
		{	$enc_metadata['podcast_artist']= podcast_channels_get_id3($file,'comments','artist');
			wp_update_attachment_metadata( $enc_record->id, $enc_metadata );
		}
		$author=$enc_metadata['podcast_artist'];
	}
	else if ($podcast_channel_active['meta:episode_author']=="Channel author")
		$author=$podcast_channel_active['itunes:author'];
	else
		$author=get_the_author();
	echo "<itunes:author>".podchan_xml_escape(wp_specialchars(strip_tags($author)))."</itunes:author>";
	
	$subtitle=(has_excerpt())?get_the_excerpt():substr(get_the_content(),0,250);
	echo "<itunes:subtitle>".podchan_xml_escape(wp_specialchars(strip_tags($subtitle)))."</itunes:subtitle>";

	echo "<itunes:summary>".podchan_xml_escape(wp_specialchars(strip_tags( substr(apply_filters('the_content', get_the_content()), 0,4000) )))."</itunes:summary>";
	
	if ($enc_metadata['podcast_duration'])
		echo "<itunes:duration>".$enc_metadata['podcast_duration']."</itunes:duration>";

	if ($enc_metadata['podcast_explicit'])
		echo "<itunes:explicit>".$enc_metadata['podcast_explicit']."</itunes:explicit>";

	//<itunes:keywords>salt, pepper, shaker, exciting</itunes:keywords>
}

add_filter('the_category_rss', 'podcast_channels_category_rss',10,2);
function podcast_channels_category_rss($the_list, $type)
{	global $podcast_channel_active;
	if ($podcast_channel_active===false) return;
	
	if ($podcast_channel_active['meta:episode_category']=="Blank") $the_list="";
	if ($podcast_channel_active['meta:episode_category']=="Channel categories")
	{	$the_list="";
		foreach(explode("||", $podcast_channel_active['itunes:category']) as $cat)
			$the_list .= "\n\t\t<category><![CDATA[" . html_entity_decode( $cat ) . "]]></category>\n";
	}
	return $the_list;
}


function podchan_xml_escape($string)
{	return str_replace(array('&','"',"'",'<','>'), array('&amp;','&quot;','&apos;','&lt;','&gt;'), $string ); }


global $pod_md_fields, $pod_md_cats ;
$pod_md_fields = array(
	'labels'	=>	array(
		'title'				=>'Title',
		'copyright'			=>'Copyright',
		'link'				=>'Link',
		'itunes:author' 	=>'Author/artist',
		'itunes:summary'	=>'Summary',
		'itunes:subtitle'	=>'Subtitle',
		'itunes:image'		=>'Image',
		'itunes:keywords'	=>'Keywords',
		'itunes:explicit'	=>'Explicit',
		'itunes:category'	=>'Categories',
		'itunes:name'		=>'Owner name',
		'itunes:email'		=>'Owner email',
		'itunes:new-feed-url'=>'Feed moving to',
		'meta:episode_author' => 'Episode author',
		'meta:episode_category' => 'Episode category',
		'meta:feedburn_redirect'=>'Feedburner URL',
		),
	'defaults'	=> array(
		'title'				=>"",
		'copyright'			=>attribute_escape(get_option('site_copyright')),
		'link'				=>"",
		'itunes:author' 	=>get_bloginfo('name'),
		'itunes:summary'	=>get_bloginfo('description'),
		'itunes:subtitle'	=>get_bloginfo('description'),
		'itunes:image'		=>attribute_escape(get_option('site_rss_image')),
		'itunes:keywords'	=>"",
		'itunes:explicit'	=>"",
		'itunes:category'	=>"",
		'itunes:name'		=>get_bloginfo('name'),
		'itunes:email'		=>get_bloginfo('admin_email'),
		'itunes:new-feed-url'=>"",
		'meta:episode_author' => "",
		'meta:episode_category' => "",
		'meta:feedburn_redirect'=>'',
		)
);

$pod_md_cats = array(
	'Arts', 'Arts|Design', 'Arts|Fashion & Beauty', 'Arts|Food', 'Arts|Literature', 'Arts|Performing Arts', 'Arts|Visual Arts',
	'Business', 'Business|Business News', 'Business|Careers', 'Business|Investing', 'Business|Management & Marketing', 'Business|Shopping',
	'Comedy',
	'Education', 'Education|Education Technology', 'Education|Higher Education', 'Education|K-12', 'Education|Language Courses', 'Education|Training',
	'Games & Hobbies', 'Games & Hobbies|Automotive', 'Games & Hobbies|Aviation', 'Games & Hobbies|Hobbies', 'Games & Hobbies|Other Games', 'Games & Hobbies|Video Games',
	'Government & Organizations', 'Government & Organizations|Local', 'Government & Organizations|National', 'Government & Organizations|Non-Profit', 'Government & Organizations|Regional',
	'Health', 'Health|Alternative Health', 'Health|Fitness & Nutrition', 'Health|Self-Help', 'Health|Sexuality',
	'Kids & Family',
	'Music',
	'News & Politics',
	'Religion & Spirituality', 'Religion & Spirituality|Buddhism', 'Religion & Spirituality|Christianity', 'Religion & Spirituality|Hinduism', 'Religion & Spirituality|Islam', 'Religion & Spirituality|Judaism', 'Religion & Spirituality|Other', 'Religion & Spirituality|Spirituality',
	'Science & Medicine', 'Science & Medicine|Medicine', 'Science & Medicine|Natural Sciences', 'Science & Medicine|Social Sciences',
	'Society & Culture', 'Society & Culture|History', 'Society & Culture|Personal Journals', 'Society & Culture|Philosophy', 'Society & Culture|Places &amp Travel',
	'Sports & Recreation', 'Sports & Recreation|Amateur', 'Sports & Recreation|College & High School', 'Sports & Recreation|Outdoor', 'Sports & Recreation|Professional',
	'Technology', 'Technology|Gadgets', 'Technology|Tech News', 'Technology|Podcasting', 'Technology|Software How-To',
	'TV & Film'
	);


// some officially unused functions to extract metadata from podpress info etc
function convert_podpress_to_enclosure()
{	global $wpdb;
	$podp_base=get_option('podPress_config');
	$podp_base=$podp_base['mediaWebPath'];

	$results = $wpdb->get_results("select * from wp_postmeta where meta_key='podPressMedia'");
	foreach ($results AS $result)
	{	$podp_data=unserialize($result->meta_value);

		foreach($podp_data as $enc_source)
		{	if (strpos($enc_source['URI'], "http")===false)
				$enc_source['URI']=$podp_base."/".$enc_source['URI'];
			
			echo $result->post_id . " = " . $enc_source['URI'] . "<br />";
			do_enclose( $enc_source['URI'], $result->post_id ); 
		}
	}
}

function set_legacy_media_duration()
{	global $wpdb;
	echo "<h3>Setting Legacy Media Duration</h3>";
	$results = $wpdb->get_results("select ID from wp_posts where post_mime_type='audio/mpeg'");
	foreach ($results AS $result)
	{	$id=$result->ID;
		$metadata=wp_get_attachment_metadata($id);
		$file=get_attached_file($id, true);
		if (!$metadata['podcast_duration'])
		{	$metadata['podcast_duration']=podcast_channels_get_id3($file,'playtime_string');
			echo "Attachment ".$id." = ". $metadata['podcast_duration']. "<br />";
			wp_update_attachment_metadata( $id, $metadata );
		}
	}
}

add_action('template_redirect', 'podcast_channels_feed_redirect');
function podcast_channels_feed_redirect() {
	global $podcast_channel_active;
	podcast_channels_set_active();
	if (is_feed() && $podcast_channel_active['meta:feedburn_redirect'] && !preg_match("/feedburner|feedvalidator/i", $_SERVER['HTTP_USER_AGENT']))
	{
		header("Location:" . trim($podcast_channel_active['meta:feedburn_redirect']));
		header("HTTP/1.1 302 Temporary Redirect");
		exit();
	}

}

?>