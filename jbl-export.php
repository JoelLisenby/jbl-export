<?php
/*
Plugin Name:  Export CSV
Plugin URI:   https://www.joellisenby.com/
Description:  Adds export CSV button to all standard and custom post types, including posts, pages and comments.
Version:      1.0.0
Author:       Joel Lisenby
Author URI:   https://www.joellisenby.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  jblexport
Domain Path:  /languages
*/

class JBLExport {

  public function __construct() {

    add_action('rest_api_init', function() {
      register_rest_route( 'jblexport/v2', '/posts/csv/(?P<type>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => array( $this, 'rest_route_callback_posts_csv')
      ) );
    });

    add_action('rest_api_init', function() {
      register_rest_route( 'jblexport/v2', '/comments/csv/(?P<ID>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => array( $this, 'rest_route_callback_comments_csv')
      ) );
    });

    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

  }

  public function add_meta_boxes() {
    add_meta_box( 'export_comments', 'Export', array( $this, 'export_comments_meta_box' ), 'post', 'side', 'high', null );
  }

  public function export_comments_meta_box( $post ) {
    echo '<a href="'. get_site_url() .'/wp-json/jblexport/v2/comments/csv/'. $post->ID .'" target="_blank">Export Comments</a>';
  }

  public function rest_route_callback_posts_csv( WP_REST_Request $request ) {
    $params = $request->get_url_params();
    if( post_type_exists( $params['type'] ) ) {
      $posts = get_posts(array(
        'post_type' => $params['type']
      ));
      $this->generate_csv( $params['type'], $posts );
    }
  }

  public function rest_route_callback_comments_csv( WP_REST_Request $request ) {
    $params = $request->get_url_params();
    $posts = get_comments(array(
      'post_id' => $params['ID'],
      'order' => 'ASC',
      'fields' => 'comment_author'
    ));
    $this->generate_csv( 'comments', $posts );
  }

  public function generate_csv( $post_type = null, $posts = null ) {
    if( !empty( $posts ) ) {
      $output = '';
      ob_start();
      $columns = array_keys( ( array ) $posts[0] );
      echo '"'. implode( '","', $columns ) .'"' . "\r\n";
      foreach( $posts as $post ) {
        $post_array = $this->filter_array( ( array ) $post );
        echo '"'. implode( '","', $post_array ) .'"' . "\r\n";
      }
      $output .= ob_get_clean();
      header( 'Content-Type: text/csv; charset=ISO-8859-1' );
      header( 'Content-Disposition: inline; filename= "'. $post_type .'_export.csv' );
      header( 'Content-Length: '.strlen( $output ) );
      echo $output;
    }
  }

  public function filter_array($array) {
    $out = array();
    foreach($array as $key => $val) {
      if(is_string($val)) {
        $out[$key] = $val;
      } else {
        $out[$key] = '';
      }
    }
    return $out;
  }

  public function sanitize_string( $string ) {
    $string = wp_strip_all_tags( $string );
    $string = preg_replace( "/\r|\n/", " ", $string );
    $string = addslashes( $string );
    return $string;
  }

}

new JBLExport();

?>