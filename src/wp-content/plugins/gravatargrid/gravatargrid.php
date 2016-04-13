<?php
/*
Plugin Name: GravatarGrid
Plugin URI: http://www.teledir.de/wordpress-plugins
Description: Displays the Gravatars of all your commenters as a nice mosaic in the sidebar of your blog. Check out more <a href="http://www.teledir.de/wordpress-plugins">Wordpress Plugins</a> and <a href="http://www.teledir.de/widgets">Widgets</a>.
Version: 1.1
Author: teledir
Author URI: http://www.teledir.de
*/

/**
 * v1.1 15.04.2010 minor changes on the injected html
 * v1.0 29.06.2009 fixed gravatar rating
 * v0.9 28.06.2009 added image width and height
 * v0.8 18.06.2009 very small security improvement
 * v0.7 11.06.2009 small css fix, small xhtml fix
 * v0.6 10.06.2009 moved commenter name from &lt;a&gt; to &lt;img&gt;-title, small css fix
 * v0.5 09.06.2009 fixed double #id in widget mode
 * v0.4 05.06.2009 small fix if author_url == http://
 * v0.3 04.06.2009 added default image url, email exclude list
 * v0.2 03.06.2009 added order by comment count, filter for empty author url
 * v0.1 29.05.2009 initial release
 */

class GravatarGrid {
  var $id;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;
  var $size;
  
  function GravatarGrid() {
    $this->id         = 'gravatargrid';
    $this->version    = '1.1';
    $this->plugin_url = 'http://www.teledir.de/wordpress-plugins';
    $this->name       = 'GravatarGrid v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;
	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();
    
    $this->size = intval($this->options['width'] / $this->options['limit']) * intval($this->options['height'] / $this->options['limit']);

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

    add_action('widgets_init', array( &$this, 'initWidget')); 
  }
  
  function optionMenu() {
    add_options_page('GravatarGrid', 'GravatarGrid', 8, __FILE__, array(&$this, 'optionMenuPage'));
  }
  
  function getSelectbox($name, $items, $selected) {
    $data = '';

    foreach($items as $k => $v) {
      $data .= sprintf('<option value="%s"%s>%s</option>', $v, $k == $selected ? ' "selected="selected"' : '', $v);
    }

    return '<select name="'. $name .'">'. $data . '</select>';
  }

  function optionMenuPage() {
?>
<div class="wrap">
<h2>GravatarGrid</h2>
<div align="center"><p><?=$this->name?> <a href="<?php print( $this->plugin_url ); ?>" target="_blank">Plugin Homepage</a></p></div> 
<?php
  if(isset($_POST[ $this->id ])) {
    /**
     * nasty checkbox handling
     */
    foreach( array('link', 'nofollow', 'target_blank') as $field ) {
      if( !isset( $_POST[ $this->id ][ $field ] ) ) {
        $_POST[ $this->id ][ $field ] = '0';
      }
    }

    $this->updateOptions( $_POST[ $this->id ] );
    
    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved!', $this->id ) . '</strong></p></div>'; 
  }
?>      
<form method="post" action="options-general.php?page=gravatargrid/gravatargrid.php">

<table class="form-table">

<tr valign="top">
  <th scope="row"><?php _e('Title', $this->id); ?></th>
  <td colspan="3"><input name="gravatargrid[title]" type="text" id="" class="code" value="<?=$this->options['title']?>" /><br /><?php _e('Title is shown above the grid. If left empty can break your layout in widget mode!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Width', $this->id); ?></th>
  <td colspan="3"><input name="gravatargrid[width]" type="text" id="" class="code" value="<?=$this->options['width']?>" />
  <br /><?php _e('Width of grid. Have in mind, that you get 2px extra padding for each image per line.', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Height', $this->id); ?></th>
  <td colspan="3"><input name="gravatargrid[height]" type="text" id="" class="code" value="<?=$this->options['height']?>" />
  <br /><?php _e('Height of grid!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Max', $this->id); ?></th>
  <td colspan="3"><input name="gravatargrid[limit]" type="text" id="" class="code" value="<?=$this->options['limit']?>" />
  <br /><?php _e('Max. number of Gravatars to display in grid!', $this->id); ?></td>
</tr>
<!--
<tr valign="top">
  <th scope="row"><?php _e('Padding', $this->id); ?></th>
  <td><input name="gravatargrid[padding]" type="text" id="" class="code" value="<?=$this->options['padding']?>" />
  <br /><?php _e('Padding of Gravatar images!', $this->id); ?></td>
</tr>
-->
<tr valign="top">
  <th scope="row"><?php _e('Default image', $this->id); ?></th>
  <td><?php echo $this->getSelectbox( 'gravatargrid[default]', array('identicon' => 'identicon', 'monsterid' => 'monsterid', 'wavatar' => 'wavatar'), $this->options['default']); ?>
  <br /><?php _e('Style of default image if no Gravatar is found', $this->id); ?></td>
  <th scope="row"><?php _e('or an image url', $this->id); ?></th>
  <td><input name="gravatargrid[default_url]" type="text" id="" class="code" value="<?=$this->options['default_url']?>" /><br /><?php _e('if url is given, it overwrites the selected image style!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Image rating', $this->id); ?></th>
  <td colspan="3"><?php echo $this->getSelectbox( 'gravatargrid[rating]', array('G', 'PG', 'R', 'X'), $this->options['rating']); ?>
  <br /><a href="http://en.wikipedia.org/wiki/Motion_picture_rating_system#Ratings" target="_blank">Motion picture rating system</a> <?php _e('to ensure the images are SFW', $this->id); ?></td>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="gravatargrid[link]" type="checkbox" id="" value="1" <?php echo $this->options['link']=='1'?'checked="checked"':''; ?> />
<?php _e('Link Gravatar image to owners website', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="gravatargrid[target_blank]" type="checkbox" id="" value="1" <?php echo $this->options['target_blank']=='1'?'checked="checked"':''; ?> />
<?php _e('Open link in new window?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="gravatargrid[nofollow]" type="checkbox" id="" value="1" <?php echo $this->options['nofollow']=='1'?'checked="checked"':''; ?> />
<?php _e('Set the link to relation nofollow?', $this->id); ?></label>
</th>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Exclude emails', $this->id); ?></th>
  <td colspan="3"><textarea name="gravatargrid[exclude]" cols="50" rows="5" id="" class="code"><?=$this->options['exclude']?></textarea>
  <br /><?php _e('One email per line!', $this->id); ?></td>
</tr>

</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('save', $this->id); ?>" class="button" />
</p>
</form>

</div>
<?php
  }
  
  function updateOptions($options) {

    foreach($this->options as $k => $v) {
      if(array_key_exists( $k, $options)) {
        $this->options[ $k ] = trim($options[ $k ]);
      }
    }
        
		update_option( $this->id, $this->options );
	}
  
  function loadOptions() {
    $this->options = get_option( $this->id );
/*
      $this->options = array(
        'installed' => time(),
        'padding' => 1,
        'link' => 1,
        'target_blank' => 1,
        'nofollow' => 1,
        'title' => __( 'GravatarGrid', $this->id ),
        'default' => 'identicon', # monsterid | wavatar
        'width' => 160,
        'height' => 400,
        'limit' => 40,
        'rating' => 'R', # G | PG | R | X
			);
      $this->updateOptions($this->options);
*/

    if( !$this->options ) {
      $this->options = array(
        'installed' => time(),
        'link' => 1,
        'target_blank' => 1,
#        'padding' => 1,
        'nofollow' => 1,
        'title' => 'GravatarGrid',
        'default' => 'identicon', # monsterid | wavatar
        'width' => 160,
        'height' => 400,
        'limit' => 40,
        'rating' => 'R', # G | PG | R | X
			);

      add_option($this->id, $this->options, $this->name, 'yes');
      
      add_option($this->id. '_title', 'GravatarGrid', $this->name, 'yes');
      
      if(is_admin()) {
        add_filter('admin_footer', array(&$this, 'addAdminFooter'));
      }
    }
    
    // update 0.3
    if(!array_key_exists('default_url',$this->options) || !array_key_exists('exclude',$this->options)) {
      $this->options['default_url'] = '';
      $this->options['exclude'] = '';
      $this->updateOptions( $this->options );
    }
  }

  function initWidget() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget('GravatarGrid Widget', array($this, 'showWidget'), null, 'widget_gravatargrid');
    }
  }

  function showWidget( $args ) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['title'], $after_title, $this->getCode(), $after_widget );
  }
  
  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version );
    printf('<link rel="stylesheet" href="%s/styles/gravatargrid.css" type="text/css" media="screen" />'. "\n", $this->url);
    printf('<style type="text/css">#gravatargrid {width: %dpx !important;}</style>'. "\n", $this->options['width'] + (ceil($this->options['width'] / $this->size) * 2));
    printf('<script src="%s/js/gravatargrid.js" type="text/javascript"></script>', $this->url);
  }

  function getAuthors() {
    global $wpdb;

    $sql = "
      SELECT
        COUNT( comment_author_email ) AS total,
        MD5(LCASE(comment_author_email)) AS hash,
        comment_author_url AS url,
        comment_author AS name
      FROM
        {$wpdb->comments}
      WHERE
        comment_author_email <> ''
      AND
        comment_approved = 1
      AND
        comment_type NOT IN( 'trackback', 'pingback' )
    ";
    
    if(!empty($this->options['exclude'])) {
      $exclude = array();
      foreach(explode( "\n", strtolower($this->options['exclude'])) as $item) {
        $exclude[] = "'". trim($item). "'";
      }
      $sql .= " AND LCASE(comment_author_email) NOT IN(". implode(',', $exclude). ") ";
    }

#    if(intval($this->options['link']) == 1) {
#      $sql .= " AND comment_author_url <> '' AND comment_author_url <> 'http://' ";
#    }
        
    $sql .= "
      GROUP BY
        hash
      ORDER BY
        total DESC
    ";

    if(($limit = intval($this->options['limit'])) > 0) {
      $sql .= " LIMIT {$limit}";
    }

    return $wpdb->get_results($sql);
  }
  
  function getTitle() {
    $host = trim(strtolower($_SERVER['HTTP_HOST']));
  
    if(substr($host, 0, 4) == 'www.') {
      $host = substr($host, 4);
    }

    $titles = array('TELEDIR', 'Teledir', 'TeleDir', 'Teledir.de', 'TeleDir.de', 'www.teledir.de');
  
    return $titles[strlen($host) % count($titles)];
  }

  function getCode() {
    $authors = $this->getAuthors();

    if($authors && count($authors) > 0) {

      $data = '';

      foreach($authors as $author) {

        $item = sprintf(
          '<img src="http://www.gravatar.com/avatar/%s.jpg?s=%s&amp;d=%s&amp;r=%s" title="%s (%d)" alt="Gravatar" width="%d" height="%d" />',
          $author->hash,
          $this->size, 
          empty($this->options['default_url']) ? $this->options['default'] : $this->options['default_url'],
          $this->options['rating'],
          str_replace('"', '&quot;', $author->name),
          $author->total,
          $this->size,
          $this->size
        );
        
        if(intval($this->options['link']) == 1 && !in_array($author->url, array('', 'http://'))) {
          $item = sprintf(
            '<a href="%s" class="snap_noshots" %s%s>%s</a>', 
            $author->url,
            $this->option['target_blank'] == 1 ? ' target="_blank"' : '',
            $this->option['nofollow'] == 1 ? ' rel="nofollow"' : '',
            $item
          );
        }

        $data .= $item;
      }

      return sprintf('<div id="gravatargrid">%s<div>GravatarGrid by <a href="http://www.teledir.de" target="_blank" class="snap_noshots">%s</a></div></div>', $data, $this->getTitle());
    }
    
    return __('No comments found!');
  }
}

function gravatargrid_display() {

  global $GravatarGrid;

  if($GravatarGrid) {
    echo $GravatarGrid->getcode();
  }
}

add_action( 'plugins_loaded', create_function( '$GravatarGrid_2kd3', 'global $GravatarGrid; $GravatarGrid = new GravatarGrid();' ) );

?>