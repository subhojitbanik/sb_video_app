<?php
/*
Plugin Name: Video App
Plugin URI: #
Description: Video calling app.
Author: Subhojit Banik
Version: 2.0.0
Author URI: #
*/
define( 'SB_VERSION', '2.0.0' );
define( 'SB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SB_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); 
define( 'SB_VIDEO_CALL_PLUGIN_FILE', __FILE__ );
require_once( SB_PLUGIN_DIR . 'vendor/autoload.php' );
require_once( SB_PLUGIN_DIR . 'create-room.php' );
require_once( SB_PLUGIN_DIR . 'app/Post.php' );
require_once( SB_PLUGIN_DIR . 'data.php' );

register_activation_hook( SB_VIDEO_CALL_PLUGIN_FILE, 'sb_video_call_plugin_activation' );
function sb_video_call_plugin_activation() {
  
  if ( ! current_user_can( 'activate_plugins' ) ) return;
  
  global $wpdb;
  
  if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'new-page-slug'", 'ARRAY_A' ) ) {
     
    $current_user = wp_get_current_user();
    
    // create post object
    $page1 = array(
      'post_title'  => __('Join meeting'),
      'post_content'  => __('[sb-join-meeting-form]'),
      'post_status' => 'publish',
      'post_author' => $current_user->ID,
      'post_type'   => 'page',
    );
    $page2 = array(
        'post_title'  => __('Join'),
        'post_content'  => __('[sb-join-meeting]'),
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'post_type'   => 'page',
      );
    
    // insert the post into the database
    if(get_page_by_title('Join meeting')== NULL ){wp_insert_post( $page1 );}
    if(get_page_by_title('Join') == NULL){ wp_insert_post( $page2 );}
    
  }
}

function sb_create_table(){

  global $wpdb;
  $table_name = $wpdb->prefix . 'sb_video_app_details';

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  request_id varchar(255) NOT NULL,
  meeting_id varchar(255) NOT NULL,
  room_name varchar(255) NOT NULL,
  room_sid varchar(255) NOT NULL,
  meeting_time varchar(255) NOT NULL,
  meeting_date varchar(255) NOT NULL,
  tutuor_id varchar(255) NOT NULL,
  student_id varchar(255) NOT NULL,
  meeting_link varchar(255) NOT NULL,
  tutor_token varchar(255) NOT NULL,
  student_token varchar(255) NOT NULL,
  tutor_join_status mediumint(9) DEFAULT '0',
  student_join_status mediumint(9) DEFAULT '0',
  remarks mediumint(9) DEFAULT '0',
  PRIMARY KEY  (id),
  UNIQUE KEY (request_id)
) $charset_collate;";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
register_activation_hook( SB_VIDEO_CALL_PLUGIN_FILE, 'sb_create_table' );





function twilio_video_enqueue_scripts()
{
  wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');
    if(is_page('join')){
        wp_enqueue_style('sb-app-css', SB_PLUGIN_URL . 'assets/css/style.css');
        wp_enqueue_script( 'sb_vc_stopwatch-js', 'https://cdn.rawgit.com/robcowie/jquery-stopwatch/master/jquery.stopwatch.js');

    }   
    wp_enqueue_style('sb-app-main-css', SB_PLUGIN_URL . 'style.css');
    wp_enqueue_style('sb-app-extra-css', SB_PLUGIN_URL . 'extra.css');
    wp_enqueue_script('jquery');
    
    // wp_enqueue_script( 'twilio-conversations', 'https://media.twiliocdn.com/sdk/js/conversations/v0.13/twilio-conversations.min.js');        
    wp_enqueue_script('twilio-video-js', '//sdk.twilio.com/js/video/releases/2.17.1/twilio-video.min.js');
    wp_enqueue_script('sb-app-main-js', SB_PLUGIN_URL . 'assets/js/main.js');
}
add_action('wp_enqueue_scripts', 'twilio_video_enqueue_scripts');

// function sb_custom_rewrite_rules(){
//   add_rewrite_rule( 'student/([a-z]+)[/]?$', 'index.php?sessionhistory=$matches[1]', 'top' );
//   add_rewrite_rule( 'meeting/([a-z]+)[/]?$', 'index.php?join=$matches[1]', 'top' );
// }
// add_action('init','sb_custom_rewrite_rules');
// function sb_register_query_var( $vars ) {
//   $vars[] = 'join';
//   return $vars;
// }
// add_filter( 'query_vars', 'sb_register_query_var' );
// function sb_candidate_ckv($template){
//   if ( get_query_var('join') != 'sbjoin' ) {
//     return $template;
//   }else{
//     return SB_SEARCH_CANDIDATE_PLUGIN_DIR . 'templates/template_candidate_keyvotes.php';
//   }
// }
// add_action('template_include','sb_candidate_ckv');

function sb_notif_mail_fn($to,$subject,$message){
  // $to ="baniksuvo007@gmail.com";
  // $subject = "notification mail";
  // $message = "test mail";
  //$headers = array('Content-Type: text/html; charset=UTF-8','From: Fastgrades.net');
  wp_mail( $to, $subject, $message);
}
//add_action('init','sb_sendmail');
// function sb_sendmail(){
//   $to ="baniksuvo007@gmail.com";
//   $subject = "notification mail";
//   $message = "test mail";

//   sb_notif_mail_fn($to,$subject,$message);
// }