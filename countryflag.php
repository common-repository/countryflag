<?php
/*
Plugin Name: CountryFlag
Plugin URI: http://www.teledir.de/wordpress-plugins
Description: Displays the country flag according to the ip of the visitor and optional additional informations in the sidebar of your blog via widget interface or anywhere else via function call. Check out more <a href="http://www.teledir.de/wordpress-plugins">Wordpress Plugins</a> and <a href="http://www.teledir.de/widgets">Widgets</a>.
Version: 0.5
Author: teledir
Author URI: http://www.teledir.de
*/
 
/**
 * v0.5 29.04.2010 minor xhtml fix
 * v0.4 18.06.2009 very small security improvement
 * v0.3 16.06.2009 svn error fix
 * v0.2 16.06.2009 compressed javascript
 * v0.1 09.06.2009 initial release
 */

class CountryFlag {
  var $id;
  var $title;
  var $plugin_url;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;

  function CountryFlag() {
    $this->id         = 'countryflag';
    $this->title      = 'CountryFlag';
    $this->version    = '0.5';
    $this->plugin_url = 'http://www.teledir.de/wordpress-plugins';
    $this->name       = 'CountryFlag v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;
	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

    add_action('widgets_init', array( &$this, 'initWidget')); 
  }

  function optionMenu() {
    add_options_page($this->title, $this->title, 8, __FILE__, array(&$this, 'optionMenuPage'));
  }

  function optionMenuPage() {
?>
<div class="wrap">
<h2><?=$this->title?></h2>
<div align="center"><p><?=$this->name?> <a href="<?php print( $this->plugin_url ); ?>" target="_blank">Plugin Homepage</a></p></div> 
<?php
  if(isset($_POST[ $this->id ])) {
    /**
     * nasty checkbox handling
     */
    foreach(array('show_wikipedia', 'show_maps') as $field ) {
      if(!isset($_POST[$this->id][$field])) {
        $_POST[$this->id][$field] = '0';
      }
    }

    $this->updateOptions( $_POST[ $this->id ] );

    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved!', $this->id ) . '</strong></p></div>'; 
  }
?>
<form method="post" action="options-general.php?page=<?=$this->id?>/<?=$this->id?>.php">

<table class="form-table">

<tr valign="top">
  <th scope="row"><?php _e('Title', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[title]" type="text" id="" class="code" value="<?=$this->options['title']?>" /><br /><?php _e('Title is shown above the Widget. If left empty can break your layout in widget mode!', $this->id); ?></td>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="countryflag[show_wikipedia]" type="checkbox" id="" value="1" <?php echo $this->options['show_wikipedia']=='1'?'checked="checked"':''; ?> />
<?php _e('Show a link to wikipedia according to the current country?', $this->id); ?></label>
</th>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Wikipedia link', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[wikipedia_url]" type="text" id="" class="code" value="<?=$this->options['wikipedia_url']?>" />
  <br /><?php _e('Base link to wikipedia.', $this->id); ?></td>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="countryflag[show_maps]" type="checkbox" id="" value="1" <?php echo $this->options['show_maps']=='1'?'checked="checked"':''; ?> />
<?php _e('Show a link to Google Maps according to the current country?', $this->id); ?></label>
</th>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Google Maps link', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[maps_url]" type="text" id="" class="code" value="<?=$this->options['maps_url']?>" />
  <br /><?php _e('Base link to Google Maps.', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Border', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[border]" type="text" id="" class="code" value="<?=$this->options['border']?>" />
  <br /><?php _e('Border width in pixel. Set to 0 to hide border.', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Border color', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[border_color]" type="text" id="" class="code" value="<?=$this->options['border_color']?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Background color', $this->id); ?></th>
  <td colspan="3"><input name="countryflag[background_color]" type="text" id="" class="code" value="<?=$this->options['background_color']?>" /></td>
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
        
		update_option($this->id, $this->options);
	}
  
  function loadOptions() {
    $this->options = get_option($this->id);

    if( !$this->options ) {
      $this->options = array(
        'installed' => time(),
        'border' => 1,
        'border_color' => 'cccccc',
        'background_color' => 'f7f7f7',
        'show_wikipedia' => 0,
        'show_maps' => 0,
        'wikipedia_url' => 'http://en.wikipedia.org',
        'maps_url' => 'http://maps.google.com/maps?q=',
        'title' => 'CountryFlag'
			);

      add_option($this->id, $this->options, $this->name, 'yes');
      
      if(is_admin()) {
        add_filter('admin_footer', array(&$this, 'addAdminFooter'));
      }
    }

  }

  function initWidget() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget($this->title . ' Widget', array($this, 'showWidget'), null, 'widget_countryflag');
    }
  }

  function showWidget( $args ) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['title'], $after_title, $this->getCode(), $after_widget );
  }

  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version);
    print('<script type="text/javascript" src="http://j.maxmind.com/app/geoip.js"></script>');
    printf('<link rel="stylesheet" href="%s/styles/%s.css" type="text/css" media="screen" />'. "\n", $this->url, $this->id);
    if(intval($this->options['border']) > 0 || !empty($this->options['background_color'])) {
      printf('<style type="text/css">#countryflag {border: %dpx solid #%s; background-color: #%s;}</style>'. "\n", $this->options['border'], $this->options['border_color'], $this->options['background_color']);
    }
    printf('<script type="text/javascript">var countryflag={url:\'%s\',wikipedia:\'%s\',maps:\'%s\'};</script>', $this->url, intval($this->options['show_wikipedia'])==1?$this->options['wikipedia_url']:'',intval($this->options['show_maps'])==1?$this->options['maps_url']:'');
  }

  function getCode() {
      return sprintf('<div id="%s"><script type="text/javascript" src="%s/js/%s.js"></script><br /><small>%s by <a href="http://www.teledir.de" target="_blank">Teledir</a></small></div>', $this->id, $this->url, $this->id, $this->title);
  }
}

function countryflag_display() {

  global $CountryFlag;

  if($CountryFlag) {
    echo $CountryFlag->getcode();
  }
}

add_action( 'plugins_loaded', create_function( '$CountryFlag_53kpl', 'global $CountryFlag; $CountryFlag = new CountryFlag();' ) );

?>