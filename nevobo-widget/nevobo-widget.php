<?php
/*
Plugin Name: Nevobo Widget
Description: Laat het Nevobo programma en uitslagen zien
Version: 1.0
Author: Wilmar den Ouden
Author URI: https://wilmardenouden.nl
License: MIT
*/

define('SPVERSION','1.4.2');

class nevoboWidget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
      $widget_ops = array(
          'classname' => 'nevoboWidget',
          'description' => 'Laat het Nevobo programma en uitslagen zien',
      );
      parent::__construct( 'nevoboWidget', 'Nevobo Widget', $widget_ops );
  }

  // widget form creation
  function form($instance) {
    // Check values
    if( $instance) {
        $title = esc_attr($instance['title']);
        $url = esc_attr($instance['url']);
        $rows = esc_attr($instance['rows']);
        $club = esc_attr($instance['club']);
        $color = esc_attr($instance['color']);
    } else {
      $title = '';
      $url = '';
      $rows = '';
      $club = '';
      $color = '';
    }
    ?>

    <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'nevoboWidget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    </p>

    <p>
        <label for="<?php echo $this->get_field_id('url'); ?>"><?php _e('RSS Feed URL:', 'nevoboWidget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" type="text" value="<?php echo $url; ?>" />
    </p>
    <p>
        <label for="<?php echo $this->get_field_id('rows'); ?>"><?php _e('Amount of rows:', 'nevoboWidget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('rows'); ?>" name="<?php echo $this->get_field_name('rows'); ?>" type="text" value="<?php echo $rows; ?>" />
    </p>
    <p>
        <label for="<?php echo $this->get_field_id('club'); ?>"><?php _e('Clubname:', 'nevoboWidget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('club'); ?>" name="<?php echo $this->get_field_name('club'); ?>" type="text" value="<?php echo $club; ?>" />
    </p>
    <p>
        <label for="<?php echo $this->get_field_id('color'); ?>"><?php _e('Highlight color:', 'nevoboWidget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('color'); ?>" name="<?php echo $this->get_field_name('color'); ?>" type="text" value="<?php echo $color; ?>" />
    </p>

    <?php
  }

  // widget update
  function update($new_instance, $old_instance) {
      $instance = $old_instance;
      // Fields
      $instance['title'] = strip_tags($new_instance['title']);
      $instance['url'] = strip_tags($new_instance['url']);
      $instance['rows'] = strip_tags($new_instance['rows']);
      $instance['club'] = strip_tags($new_instance['club']);
      $instance['color'] = strip_tags($new_instance['color']);
      return $instance;
  }

  // widget display
  function widget($args, $instance) {
    extract( $args );
    echo $args['before_widget'];

    // these are the widget options
    $title = apply_filters('widget_title', $instance['title']);
    $url = $instance['url'];
    $rows = $instance['rows'];
    $club = $instance['club'];
    $color = $instance['color'];

    // Display the widget
    echo '<div class="nevoboWidget">';

    // Check if title is set
    if ( $title ) {
        echo $before_title . $title . $after_title;
    }

    // Default url
    if( !$url ) {
        $url = 'https://wilmardenouden.nl/?cat=2&feed=rss2';
    }
    // Default rows
    if( !$rows ) {
        $rows = 6;
    }


    echo $this->generateWidget($url, $rows, $color, $club);

    echo $args['after_widget'];
  }

  function generateWidget($url, $rows, $color, $club) {
    date_default_timezone_set('Europe/Amsterdam');
    require_once 'simplepie-' . SPVERSION . '/autoloader.php';
    $feed = new SimplePie();
    $feed->set_feed_url($url);
    $feed->enable_order_by_date(false);
    $feed->enable_cache();
    $feed->set_cache_location(plugin_dir_path( __FILE__ ) . 'cache');
    $feed->set_stupidly_fast(true);
    $feed->init();
    $maxitems = $feed->get_item_quantity( $rows );
    $rss_items = $feed->get_items( 0, $maxitems );

    $code = "<div class='nevoboContainer'>";

    //start processing rss
    if( $maxitems == 0) {
      die('this is check 1.5');
      return "<b>Feed bevat geen items</b>";
    }
    if(strpos($url, 'programma.rss') !== false) {
      //process programma.rss
      foreach ( $rss_items as $item ) {
        // haystack title on ':' returns part after : Subtring to remove :%20 and explode to split the teams
        $match = explode(" - ", (substr(strstr($item->get_title(), ': '), 2)));
        $clubs = "";
        if(stripos($match[0], $club) !== false) {
          //first name is home
          $clubs .= "<div class='nevoboCell nevoboClub'><font color='" . $color . "'>" . $match[0] . "</font></div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[1] . "</div>";
        } else if(stripos($match[1], $club) !== false) {
          //second name is home
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[0] . "</div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'><font color='" . $color . "'>" . $match[1] . "</font></div>";
        } else if ($club !== ""){
          //clubname wrong
          return "<b>Verening kan niet gevonden worden in de opgegeven feed</b>";
        } else {
          //no color
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[0] . "</div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[1] . "</div>";
        }

        $date = strtotime($item->get_date());
        $matchDate = strftime("%d-%m", $date);
        $matchTime = strftime("%R", $date);

        //markup html table with data
        $code .= "<div class='nevoboRow'>";
        $code .= "<div class='nevoboCell nevoboDate'><a href='" . $item->get_link() . "' target=_BLANK>" . $matchDate . "</a></div>";
        $code .= $clubs;
        $code .= "<div class='nevoboCell nevoboTime'>" . $matchTime . "</div>";
        $code .= "</div>";
      } // end foreach
    } else if(strpos($url, 'resultaten.rss') !== false) {
      //process uitslagen
      foreach ( $rss_items as $item ) {
        // haystack title on ':' returns part before : and explode to split the teams
        $titleSplit = explode(':', $item->get_title());
        $match = explode(" - ", $titleSplit[0]);
        $match[1] = strstr($match[1], ',', true); // remove result
        $clubs = "";
        if(stripos($match[0], $club) !== false) {
          //first name is home
          $clubs .= "<div class='nevoboCell nevoboClub'><font color='" . $color . "'>" . $match[0] . "</font></div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[1] . "</div>";
        } else if(stripos($match[1], $club) !== false) {
          //second name is home
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[0] . "</div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'><font color='" . $color . "'>" . $match[1] . "</font></div>";
        } else if ($club !== ""){
          //clubname wrong
          return "<b>Verening kan niet gevonden worden in de opgegeven feed</b>";
        } else {
          //no color
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[0] . "</div>";
          $clubs .= "<div class='nevoboCell nevoboDash'> - </div>";
          $clubs .= "<div class='nevoboCell nevoboClub'>" . $match[1] . "</div>";
        }

        $date = strtotime($item->get_date());
        $matchDate = strftime("%d-%m", $date);
        $result = explode("-", $titleSplit[1]);
        $result = array_map('round', $result);

        //markup html table with data
        $code .= "<div class='nevoboRow'>";
        $code .= "<div class='nevoboCell nevoboDate'><a href='" . $item->get_link() . "' target=_BLANK>" . $matchDate . "</a></div>";
        $code .= $clubs;
        $code .= "<div class='nevoboCell nevoboResult'>" . $result[0] . " - " . $result[1] . "</div>";
        $code .= "</div>";
      } // end processing rss
    } else {
      //feed not programma or uitslagen
      return "<b>Feed URL kan niet verwerkt worden, is het wel een Nevobo Feed?</b></div>";
    }
    $code .= "</div>";
    return $code;
  }
}

// register widget
add_action( 'widgets_init', function() {
  register_widget( 'nevoboWidget' );
	wp_register_style( 'nevoboWidget', plugins_url('/style.css', __FILE__));
  wp_enqueue_style( 'nevoboWidget' );
});
