<?php
/*
Plugin Name: Folksy Product Listing Widget and Shortcode for Wordpress
Plugin URI: http://www.wpsupprt.co.uk/folksylisting/
Description: Easily add your Folksy items using a widget or shortcode into your wordpress website.
Author: WPSupprt
Version: 1.1.8.2
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
*/


/***************************
* constants
***************************/
 

if(!defined('FL_PLUGIN_DIR')) {
	define('FL_PLUGIN_DIR', plugin_dir_url( __FILE__ ));
}

$path = WP_PLUGIN_URL . '/' . 'folksy-product-listing-widget-and-shortcode';
define('PLUGIN_PATH', $path);
define('MAIN_PATH', PLUGIN_PATH . '/');
define('MPO_JS_PATH', MAIN_PATH . 'js/');
define('FL_CSS_PATH', MAIN_PATH . 'css/');
define('FL_IMAGES_PATH', MAIN_PATH . 'assets/');

/***************************
* Base dir folksy
***************************/
global $folksylist_base_dir;
$folksylist_base_dir = dirname(__FILE__);


/***************************
* Settings Options
***************************/

$folksylistitems_settings = get_option('folksylistitems_settings');

/***************************
* includes
***************************/
include($folksylist_base_dir . '/includes/simple_html_dom.php');

function get_http_response_code($url) {
          $headers = get_headers($url);
          return substr($headers[0], 9, 3);
}

class folksylistWidget extends WP_Widget {
  
      function folksylistWidget() {
          parent::WP_Widget( false, $name = 'Folksy Listing Widget' );
      }
      
   
      function widget( $args, $instance ) {
          
          extract( $args );
          $shop_section = $instance['shop_section'];
          $title = apply_filters( 'widget_title', $instance['title'] );
          echo $before_widget;
          if ($title) {
              echo $before_title . $title . $after_title;
          }
          /***************************
          * Display Widget
          ***************************/
              global $folksylistitems_settings;
              $shopusername = $folksylistitems_settings['shopusername'];
              if ( isset($folksylistitems_settings['showbuy_button'])){
              $buybutton = $folksylistitems_settings['showbuy_button'];
              }
              if ( isset($folksylistitems_settings['show_pluginlink'])){
              $pluginlink = $folksylistitems_settings['show_pluginlink'];
              }
              if ( isset($folksylistitems_settings['shop_description'])){
              $shopdescription = $folksylistitems_settings['shop_description'];
              }
              if($shop_section){
              $url = 'http://folksy.com/shops/'.$shopusername.'?shop_section='.$shop_section.'';
              }else{
              $url = 'http://folksy.com/shops/'.$shopusername;
              }
              
              if(get_http_response_code($url) != "404"){
              $html = file_get_html($url); 
              if (method_exists($html,"find")) {
               // then check if the html element exists to avoid trying to parse non-html
               if ($html->find('html')) {

                  foreach($html->find("ul[class='gallery']") as $element){
                       $newItem = new FolksyItem;
                       $itemnum = 0;
                       
                       echo '<ul class="folksylist">';
                        // Parse the news item's.
                        foreach ($element->find('li') as $item) {
                              echo '<li class="folksylistitem folksylistitem-'.$itemnum.'">';  
                              foreach ($item->find('a') as $link) {
                                foreach ($item->find('img') as $image) {
                                $newItem->set_image($image->src);
                                echo '<a href="http://folksy.com'.$link->href .'" target="_blank"><img src="'.$newItem->get_image().'" class="folksyitemimage"></a><br />';
                                }
                                foreach ($item->find('h3') as $title) {
                                echo '<h3 class="folksyitemtitle"><a href="http://folksy.com'.$link->href .'" target="_blank">'.$title->plaintext.'</a></h3>';
                                }
                                foreach ($item->find('p') as $spec) {
                                if(isset($folksylistitems_settings['showbuy_button'])){
                                 echo '<div style="float:right"><a href="http://folksy.com'.$link->href .'" target="_blank"><span class="buybutton-'.$folksylistitems_settings['button_color'].'">Buy on Folksy</span></a></div>';
                                }  
                                echo '<p class="folksyitemprice">'.$spec->plaintext.'</p>';
                                }
                                
                                echo "</li>";
                              }
                          $itemnum ++;
                        }
                      echo "</ul>";
                      if(isset($folksylistitems_settings['show_pluginlink'])){
                      echo  "<br /><div class='plugincopyright'><a href='http://wpsupprt.co.uk/folksylisting/' target='_blank'>Folksy Listing by wpsupprt.co.uk</a></div>";
                      }
                  }//For each
                }// If Find
              }//If method
           
              $html->clear();
              unset($html);
              }else{
                  echo "Username Not found or URL incorrect, check your settings.";
              } 
          echo $after_widget;
  

      }
   
      function update($new_instance, $old_instance) {
      // processes widget options to be saved
      $instance = $old_instance;
      $instance['shop_section'] = strip_tags( $new_instance['shop_section'] );
      $instance['title'] = strip_tags( $new_instance['title'] );
      return $instance;
      }
   
      function form( $instance ) {
          $title = esc_attr( $instance['title'] );
          echo 'Title of your Widget:<br /> <input id="'.$this->get_field_id( 'title' ).'" type="text" name="'.$this->get_field_name( 'title' ).'" value="'.$title.'" /><br /><br />';
          echo 'Name of your Shop Section:<br /> <input id="'.$this->get_field_id( 'shop_section' ).'" type="text" name="'.$this->get_field_name( 'shop_section' ).'" value="'.$instance['shop_section'].'" /><br />Only enter if you want to target a specific shop section<br /><span style="font-size:10px;">It should look like this in Folksy <br /> http://folksy.com/shops/YOURSHOPUSERNAME?shop_section=<b>YOUR_SECTION</b></span>';
      } 

}
class FolksyItem {
    var $image;
    var $title;
    var $description;
    var $sourceurl;

    function get_image( ) {
        return $this->image;
    }

    function set_image ($new_image) {
        $this->image = $new_image;
    }

    function get_title( ) {
        return $this->title;
    }

    function set_title ($new_title) {
        $this->title = $new_title;
    }

    function get_description( ) {
        return $this->description;
    }

    function set_description ($new_description) {
        $this->description = $new_description;
    }

    function get_sourceurl( ) {
        return $this->sourceurl;
    }

    function set_sourceurl ($new_sourceurl) {
        $this->sourceurl = $new_sourceurl;
    }
}

/***************************
* Admin menu
***************************/

function folksylist_settings_menu() {
	global $folksylist_admin_page;
	// add settings page
	$folksylist_admin_page = add_options_page(__('Folksy Listing', 'folksylist'), __('Folksy Listing', 'folksylist'), 'manage_options', 'folksylistitems-settings', 'folksylist_admin_page');
}
add_action('admin_menu', 'folksylist_settings_menu');

function folksylist_admin_page() {
	global $folksylistitems_settings;
	ob_start(); 
  if ( ! isset( $folksylistitems_settings['showbuy_button'] ) )
   $folksylistitems_settings['showbuy_button'] = '';
?>
	<div class="wrap">
		
			<!--Donate-->
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target=_blank">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="ASGWP38CZQR64">
            <input type="image" src="<?php echo FL_IMAGES_PATH; ?>/banner-donate.png" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
            </form>
      <form method="post" action="options.php">
		
			<?php settings_fields('folksylistitems_settings_group'); ?>
      <h4><?php _e('Folksy Shop Username', 'folksylist'); ?></h4>
			<p>
				http://folksy.com/shops/ <input id="folksylistitems_settings[shopusername]" name="folksylistitems_settings[shopusername]" type="text" value="<?php echo $folksylistitems_settings['shopusername']; ?>"/>
				<label class="description" for="folksylistitems_settings[shopusername]"><br /><?php _e('Enter your Folksy Shop username, found in your Folksy shop URL http://folksy.com/shops/YOURSHOPUSERNAME', 'folksylist'); ?></label>
			</p>
      <h4><?php _e('Display Shop Description', 'folksylist'); ?></h4>
			<p>
				<input id="folksylistitems_settings[shop_description]" name="folksylistitems_settings[shop_description]" type="checkbox" value="1" <?php if ( ! isset( $folksylistitems_settings['shop_description'] ) || $folksylistitems_settings['shop_description'] != '1' ) { }else{print "checked";} ?> />
				<label class="description" for="folksylistitems_settings[shop_description]"><?php _e('Check this box if you want to display your shop description from Folksy, it will display above your listings when using the shortcode', 'folksylist'); ?></label>
			</p>
      <h4><?php _e('Display Buy Button', 'folksylist'); ?></h4>
			<p>
				<input id="folksylistitems_settings[showbuy_button]" name="folksylistitems_settings[showbuy_button]" type="checkbox" value="1" <?php if ( ! isset( $folksylistitems_settings['showbuy_button'] ) || $folksylistitems_settings['showbuy_button'] != '1' ) { }else{print "checked";}//checked(1, $folksylistitems_settings['showbuy_button']); ?> />
				<label class="description" for="folksylistitems_settings[showbuy_button]"><?php _e('Check this box if you want to display a Buy Button', 'folksylist'); ?></label>
			</p>
      <h4><?php _e('Choose Buy Button Color style', 'feralf'); ?></h4>
			<p>
				<?php $styles = array('red', 'blue', 'green', 'pink', 'black'); ?>
				<select name="folksylistitems_settings[button_color]" id="folksylistitems_settings[button_color]">
					<?php foreach($styles as $style) { ?>
						<option value="<?php echo $style; ?>" <?php selected($folksylistitems_settings['button_color'], $style); ?>><?php echo $style; ?></option>
					<?php } ?>
				</select>
				<div class="description"><?php _e('Choose the color you wish to use for the buttons', 'feralf'); ?></div>
			</p>
      <h4><?php _e('Display Plugin Link', 'folksylist'); ?></h4>
			<p>
				<input id="folksylistitems_settings[show_pluginlink]" name="folksylistitems_settings[show_pluginlink]" type="checkbox" value="1" <?php if ( ! isset( $folksylistitems_settings['show_pluginlink'] ) || $folksylistitems_settings['show_pluginlink'] != '1' ) { }else{print "checked";} ?> />
				<label class="description" for="folksylistitems_settings[show_pluginlink]"><?php _e('Check this box if you want to display the plugin link', 'folksylist'); ?></label>
			</p>		
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Options', 'folksylist'); ?>" />
			</p>
		
		</form>
		
	</div>
	<?php
	echo ob_get_clean();
}
function folksylist_register_settings() {
	// creates our settings in the options table
	register_setting('folksylistitems_settings_group', 'folksylistitems_settings');
}
add_action('admin_init', 'folksylist_register_settings');


/***************************
* Button CSS
***************************/


add_action('admin_init', 'fl_plugin_load');
add_action('init', 'fl_plugin_load');
function fl_plugin_load()
{
	wp_register_style('folksylist-form-css', FL_PLUGIN_DIR . 'css/fl-buttons.css');
  wp_enqueue_style("folksylist-form-css",FL_PLUGIN_DIR . 'css/fl-buttons.css');
}


/***************************
* Register Widget
***************************/

add_action( 'widgets_init', 'folksylistWidgetInit' );
 
function folksylistWidgetInit() {
    register_widget( 'folksylistWidget' );
}

/***************************
* Display Shortcode
***************************/

// Folksy Listing shortcode
function folksylist_shortcode($atts){
   extract(shortcode_atts(array('shop_section' => '',), $atts));
   $output = "";
	  global $folksylistitems_settings;
    $shopusername = $folksylistitems_settings['shopusername'];
    if ( isset($folksylistitems_settings['showbuy_button'])){
    $buybutton = $folksylistitems_settings['showbuy_button'];
    }
    if ( isset($folksylistitems_settings['show_pluginlink'])){
    $pluginlink = $folksylistitems_settings['show_pluginlink'];
    }
    if ( isset($folksylistitems_settings['shop_description'])){
    $shopdescription = $folksylistitems_settings['shop_description'];
    }
    if($atts){
    $url = 'http://folksy.com/shops/'.$shopusername.'?shop_section='.$shop_section.'';
    }else{
    $url = 'http://folksy.com/shops/'.$shopusername;
    }
		
    
    if(get_http_response_code($url) != "404"){
    $html = file_get_html($url); 
    if (method_exists($html,"find")) {
     // then check if the html element exists to avoid trying to parse non-html
     if ($html->find('html')) {
        if(isset($folksylistitems_settings['shop_description'])){
         
         foreach($html->find('p[class=shop-description]') as $shoptxt){
          echo '<div class="shopdescription">'.$shoptxt->plaintext.'</div>';   
         }    
   
        } 
        
        foreach($html->find("ul[class='gallery']") as $element){
             $newItem = new FolksyItem;
             $itemnum = 0;

             echo '<ul class="folksylist">';
              // Parse the news item's.
              foreach ($element->find('li') as $item) {
                    $output.= '<li class="folksylistitem folksylistitem-'.$itemnum.'">';
                    foreach ($item->find('a') as $link) {
                      foreach ($item->find('img') as $image) {
                      $newItem->set_image($image->src);
                      $output.= '<a href="http://folksy.com'.$link->href .'" target="_blank"><img src="'.$newItem->get_image().'" class="folksyitemimage"></a><br />';
                      }
                      foreach ($item->find('h3') as $title) {
                      $output.= '<h3 class="folksyitemtitle"><a href="http://folksy.com'.$link->href .'" target="_blank">'.$title->plaintext.'</a></h3>';
                      }
                      foreach ($item->find('p') as $spec) {
                      if(isset($folksylistitems_settings['showbuy_button'])){
                       $output.= '<div style="float:right"><a href="http://folksy.com'.$link->href .'" target="_blank"><span class="buybutton-'.$folksylistitems_settings['button_color'].'">Buy on Folksy</span></a></div>';
                      }
                      $output.= '<p class="folksyitemprice">'.$spec->plaintext.'</p>';
                      }
                      
                      $output.= "</li>";
                    }
                $itemnum ++;
              }
            $output.= "</ul>";
            if(isset($folksylistitems_settings['show_pluginlink'])){
            $output.= "<br /><div class='plugincopyright'><a href='http://wpsupprt.co.uk/folksylisting/' target='_blank'>Folksy Listing by wpsupprt.co.uk</a></div>";
            }
        }//For each
      }// If Find
    }//If method
 
    $html->clear();
    unset($html);
    }else{
        $output.= "Username Not found or URL incorrect, check your settings.";
    }
	return $output;
}
add_shortcode('folksylisting', 'folksylist_shortcode');
?>