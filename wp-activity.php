<?php
/*
    Plugin Name: WP-Activity
    Plugin URI: http://www.driczone.net/blog/plugins/wp-activity
    Description: Monitor and display blog members activity ; track and blacklist unwanted login attemps.
    Author: Dric
    Version: 1.7.1 beta 3
    Author URI: http://www.driczone.net
*/

/*  Copyright 2009-2012 Dric  (email : cedric@driczone.net)

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

// let's initializing all vars

$act_plugin_version = "1.7.1 beta 3"; //Don't change this, of course.
$act_list_limit = 50; //Change this if you want to display more than 50 items per page in admin list
$strict_logs = false; //If you don't want to keep track of posts authors changes, set this to "true"
$no_admin_mess = false; //If you don't want to get bugged by admin panel additions

$options_act = get_option('act_settings');
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	if ( defined( 'WP_SITEURL' ) ) {
    define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
  }else {
    define( 'WP_CONTENT_URL', get_bloginfo('wpurl') . '/wp-content' );
  }
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define('ACT_DIR', dirname(plugin_basename(__FILE__)));
define('ACT_URL', WP_CONTENT_URL . '/plugins/' . ACT_DIR . '/');

//Plugin can be translated, just put the .mo language file in the /lang directory
load_plugin_textdomain('wp-activity', ACT_URL . 'lang/', ACT_DIR . '/lang/');

add_action('init', 'act_process_post');

function act_process_post(){
  if(isset($_POST['act_export'])) {
    require_once(WP_PLUGIN_DIR.'/'.ACT_DIR.'/wp-act-export.php');
    act_export();
  }
}

function act_desactive() {
	wp_clear_scheduled_hook('act_cron_daily');
}

function act_update_db_check() {
    global $act_plugin_version, $options_act;
    if ($options_act['act_version'] != $act_plugin_version) {
        act_install();
    }
}
add_action('plugins_loaded', 'act_update_db_check');

function act_install()
{
    global $wpdb, $act_plugin_version, $options_act;
    wp_schedule_event(time(), 'daily', 'act_cron_daily');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $table = $wpdb->prefix."activity";
    $act_structure = "CREATE TABLE `".$table."` (
      `id` int(9) NOT NULL auto_increment,
      `user_id` bigint(20) NOT NULL,
      `act_type` varchar(20) NOT NULL,
      `act_date` datetime default NULL,
      `act_params` text,
      UNIQUE KEY `id` (`id`),
      KEY `user_id` (`user_id`),
      KEY `act_date` (`act_date`)
      );";
    dbDelta($act_structure);
    $new_options_act['act_prune'] = '1000';
    $new_options_act['act_feed_display'] = false;
    $new_options_act['act_date_format'] = 'yyyy/mm/dd';
    $new_options_act['act_date_relative']= true;
    $new_options_act['act_connect']= true;
    $new_options_act['act_profiles']= true;
    $new_options_act['act_posts']= true;
    $new_options_act['act_comments']= true;
    $new_options_act['act_links']= true;
    $new_options_act['act_feed_connect']= false;
    $new_options_act['act_feed_profiles']= true;
    $new_options_act['act_feed_posts']= true;
    $new_options_act['act_feed_comments']= true;
    $new_options_act['act_feed_links']= true;
    $new_options_act['act_icons']= 'g';
    $new_options_act['act_old']= true;
    $new_options_act['act_prevent_priv']= false;
    $new_options_act['act_log_failures']= false;
    $new_options_act['act_author_path']= 'author';
    $new_options_act['act_blacklist_on']= false;
    $new_options_act['act_bl_wplog']= true;
    $new_options_act['act_version'] = $act_plugin_version;
    add_option('act_settings', $new_options_act);
    
    if ($options_act['act_version'] != $act_plugin_version){
      act_desactive();
      if (version_compare($options_act['act_version'], '1.4', '<')){
        $options_act['act_author_path']= 'author';
      }
      if (version_compare($options_act['act_version'], '1.7', '<')){
        $options_act['act_blacklist_on']= false;
        $options_act['act_bl_wplog']= true;
      }
      $options_act['act_version'] = $act_plugin_version;
      update_option('act_settings', $options_act);
    }
    add_action('act_cron_daily', 'act_cron');
}

register_activation_hook( __FILE__, 'act_install' );
register_deactivation_hook(__FILE__, 'act_desactive');

function act_cron($prune_limit=''){
  global $wpdb, $options_act, $plugin_page;
  if ($prune_limit == ''){
    $prune_limit = $options_act['act_prune'];
  }else{
    $ret = true;
  }
  $act_count = $wpdb->get_var("SELECT count(ID) FROM ".$wpdb->prefix."activity");
  $act_delete = $act_count - $prune_limit;
  if ($act_delete > 0) {
    if ($ret == true){    
      if ($wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$act_delete)){
        return true;
      }else{
        return false;
      }
    }else{
      $wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$act_delete);
    }
  }
}

add_filter("plugin_action_links_wp-activity/wp-activity.php", 'act_plugin_action_links');
function act_plugin_action_links($links)
{
    $settings_link = '<a href="options-general.php?page=act_admin">' . __( 'Settings' ) . '</a>';
    $uninstall_link = '<a href="options-general.php?page=act_admin#act_reset">' . __( 'Uninstall' ) . '</a>';
    array_unshift($links, $settings_link, $uninstall_link);
    return $links;
}

//we add actions to hooks to log their events
if ($options_act['act_connect']){
  add_action('wp_login', 'act_session', 10, 2);
  add_action('auth_cookie_valid', 'act_session', 10, 2);
  add_action('wp_logout', 'act_reinit');
}
if ($options_act['act_profiles'] ){ 
  add_action('profile_update', 'act_profile_edit');
}
if ($options_act['act_posts']){
  add_action('publish_post', 'act_post_add');
}
if ($options_act['act_comments']){
  add_action('comment_post', 'act_comment_add');
}
if ($options_act['act_links']){
  add_action('add_link', 'act_link_add');
}
if ($options_act['act_log_failures'] ){
  add_action('wp_login_failed', 'act_login_failed');
}

function act_header(){
  $altcss = TEMPLATEPATH.'/wp-activity.css';
  echo '<link type="text/css" rel="stylesheet" href="';
  if (@file_exists($altcss)){
    echo get_bloginfo('stylesheet_directory').'/';
  }else{
    echo ACT_URL;
  }
  echo 'wp-activity.css" />';
}
add_action('wp_head', 'act_header');

function act_profile_option(){
  global $wpdb, $user_ID, $options_act;
  $act_private = get_usermeta($user_ID, 'act_private');
  ?>
  <h3><?php _e('Activity events', 'wp-activity'); ?></h3>
  <table>
    <tr>
	    <th><?php _e('Hide my activity :', 'wp-activity'); ?></th>
	    <td><input type="checkbox" id="act_private" name="act_private" <?php if ($act_private){ echo 'checked="checked"'; }?> value="true" /> <?php _e('If selected, this option makes you become invisible in activity events.', 'wp-activity'); ?></td>
    </tr>
  </table>
  <?php
}
if (!$options_act['act_prevent_priv']){
  add_action('show_user_profile', 'act_profile_option');
}

function act_real_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))  //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function act_login_failed($act_user=''){
  global $wpdb, $options_act;
  if ($act_user){
    $user_ID = 1; //event has to be linked to a wp user.
    $act_time=date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LOGIN_FAIL', '".$act_time."', '".$act_user."###".act_real_ip()."')");
    }
}

function act_profile_update(){
  global $user_ID, $_POST;
  update_usermeta($user_ID,'act_private',isset($_POST['act_private']) ? true : false);
}
add_action('personal_options_update', 'act_profile_update');

function act_session($arg='', $userlogin=''){
  global $wpdb, $options_act;
  if ( is_numeric($userlogin->ID) ){
    $user_ID = $userlogin->ID;
  }else{
    $userlogin = get_userdatabylogin($arg);
    if ($userlogin->ID){
      $user_ID = $userlogin->ID;
    }else{
      $user_ID = '';    
    }
  }
  if (!empty($user_ID) and !get_usermeta($user_ID, 'act_private') and !$_COOKIE['act_logged']){
    $act_time=date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'CONNECT', '".$act_time."', '')");
    setcookie('act_logged',time());
  }
}
function act_reinit(){
  if ($_COOKIE['act_logged']){ setcookie ("act_logged", "", time() - 3600);}
}

function act_profile_edit($act_user){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private')){
    $act_time=date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'PROFILE_EDIT', '".$act_time."', $act_user)");
  }
}

function act_post_add($act_post){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private')){
    $act_time=date("Y-m-d H:i:s", time());
    if ($wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."activity WHERE act_params=$act_post AND act_type='POST_ADD'") > 0){
      $act_type='POST_EDIT';
    }else{
      $act_type='POST_ADD';
    }
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, '".$act_type."', '".$act_time."', $act_post)");
  }
}

function act_comment_add($act_comment){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private') and $user_ID <> 0){
    $act_time=date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'COMMENT_ADD', '".$act_time."', $act_comment)");
  }
}

function act_link_add($act_link){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private')){
    $act_time=date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LINK_ADD', '".$act_time."', $act_link)");
  }
}

function act_last_connect($act_user=''){
  global $wpdb, $options_act, $user_ID;
  if (!$act_user){ $act_user = $user_ID; }
  if ($options_act['act_connect'] and !get_usermeta($act_user, 'act_private')){
    $act_last_connect = $wpdb->get_var("SELECT MAX(act_date) FROM ".$wpdb->prefix."activity WHERE user_id = '".$act_user."'");
    echo __("Last logon :", 'wp-activity')." ".nicetime($act_last_connect);
  }
}

//blacklist baby !
function act_blacklist(){
  global $options_act, $wpdb, $pagenow;
  if ((($options_act['act_bl_login'] and $pagenow == 'wp-login.php') or !$options_act['act_bl_login']) and !is_admin()){
    $act_bl_ip_array = explode("\n", trim($options_act['act_blacklist']));
    $act_client_ip = act_real_ip();
  	foreach ($act_bl_ip_array as $act_bl_ip) {
  		$act_bl_ip = str_replace(".", "\.", $act_bl_ip);
  		$act_bl_ip = str_replace("*", "[0-9\.]*", $act_bl_ip);
  		$act_bl_ip = "/^" . trim($act_bl_ip) . "$/";
  		if (preg_match($act_bl_ip, $act_client_ip)) {
        $act_time=date("Y-m-d H:i:s", time());
				$wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES(1, 'ACCESS_DENIED', '".$act_time."', '".$act_client_ip."')");
        Header("HTTP/1.1 403 Forbidden");
				die('403 Forbidden');
  		}
    }
  }
}
if ($options_act['act_blacklist_on']){
  add_action('init', 'act_blacklist', 1);
}

//display activity in frontend
function act_stream_user($act_user=''){
  global $options_act, $user_ID;
  if (!$act_user){ $act_user = $user_ID; }
  if (!get_usermeta($act_user, 'act_private')){
    act_stream_common('-1', '', true, $act_user);
  }
}

function act_stream_shortcode ($attr) {
    $attr = shortcode_atts(array('number'   => '-1',
                                 'title'    => '',), $attr);
    return act_stream_common($attr['number'], $attr['title'], true, '');
}

add_shortcode('ACT_STREAM', 'act_stream_shortcode');

function act_stream($act_number='30', $act_title=''){
  act_stream_common($act_number, $act_title, false,'');
}

/*
--- display activity in frontend ---
* $act_number = -1 : no limit
* $act_title : title of the box
* $archive : to display on a page without box
* $act_user : if user id specified, return user's activity only
*/
function act_stream_common($act_number='30', $act_title='', $archive = false, $act_user = ''){
global $wpdb, $options_act, $user_ID;
  if ($act_title == ''){
    $act_title= __("Recent Activity", 'wp-activity');
  }
  if ($options_act['act_feed_display']){
    $act_title .= ' <a href="'.WP_PLUGIN_URL.'/wp-activity/wp-activity-feed.php" title="'.sprintf(__('%s activity RSS Feed', 'wp-activity'),get_bloginfo('name')).'"><img src="'.WP_PLUGIN_URL.'/wp-activity/img/rss.png" alt="" /></a>';
  }
  if ($options_act['act_page_link'] and !$archive){
    $act_title .= ' <a href="'.get_page_link($options_act['act_page_id']).'" title="'.sprintf(__('%s activity archive', 'wp-activity'),get_bloginfo('name')).'">'.__('Archives', 'wp-activity').'</a>';
  }
  
  $wp_url = get_bloginfo('wpurl');
  $act_old_class = '';
  $act_old_flag = -1;

  echo '<h2>'.$act_title.'</h2>';
  if ($archive == false) {echo '<ul id="activity">';}else{echo '<ul id="activity-archive">';}
	$sql  = "SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->users." AS users WHERE activity.user_id = users.id";
  if ($act_user!= ''){
    $sql .= " AND user_id = '".$act_user."'";
  }else{
    $sql .= " AND act_type <> 'LOGIN_FAIL'";
  }
  $sql .= " ORDER BY act_date DESC";
  $i=1;
	if ( $act_logins = $wpdb->get_results( $sql)){
    foreach ( (array) $act_logins as $act ){
      if ($options_act['act_old'] and $act_old_flag > 0 and !$archive){
        $act_old_class = 'act-old';
      }else{
        $act_old_class = '';
      }
      if (!$act_logged[$act->user_id]){
        $act_logged[$act->user_id]="2029-01-01 00:00:01"; //hope this plugin won't be used anymore at this date...
      }
      if (((strtotime($act_logged[$act->user_id]) - strtotime($act->act_date)) > 60 AND $act->act_type == 'CONNECT') OR $act->act_type != 'CONNECT'){      
        echo '<li class="login '.$act_old_class.'">';
        if ($options_act['act_icons']== 'g'){
          echo '<img class="activity_icon" alt="" src="'.WP_PLUGIN_URL.'/wp-activity/img/'.$act->act_type.'.png" />';
        }elseif ($options_act['act_icons']== 'a'){
          if ($act->act_type == 'CONNECT' or $act->act_type == 'PROFILE_EDIT'){
            echo get_avatar( $act->user_id, '16'); ;
          }else{
            echo '<img class="activity_icon" alt="" src="'.WP_PLUGIN_URL.'/wp-activity/img/'.$act->act_type.'.png" />';
          }
        }
        switch ($act->act_type){
          case 'CONNECT':
              echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has logged.', 'wp-activity');
              if ($act->user_id == $user_ID and $options_act['act_old']){
                $act_old_flag++;
              }
            break;
          case 'COMMENT_ADD':
            $act_comment=get_comment($act->act_params);
            $act_post=get_post($act_comment->comment_post_ID);
            echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act_comment->comment_author.'</a> '.__('commented', 'wp-activity').' <a href="'.$act_post->post_name.'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a>';
            break;
          case 'POST_ADD':
            $act_post=get_post($act->act_params);
            if ($act->user_id != $act_post->post_author and !$strict_logs){ //this is a check if post author has been changed in admin post edition.
              $sql = "UPDATE ".$wpdb->prefix."activity SET user_id = '".$act_post->post_author."' WHERE id = '".$act->id."'";
              $wpdb->query( $sql);
            }else{
              echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('published', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
            }
            break;
          case 'POST_EDIT':
            $act_post=get_post($act->act_params);
            echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('edited', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
            break;
          case 'PROFILE_EDIT':
            echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has updated his profile.', 'wp-activity');
            break;
          case 'LINK_ADD':
            $act_link = get_bookmark($act->act_params);
            if ($act_link->link_visible == 'Y'){
              echo '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has added a link to', 'wp-activity').' <a href="'.$act_link->link_url.'" title="'.$act_link->link_description.'" target="'.$act_link->link_target.'">'.$act_link->link_name.'</a>.';
            }
            break;
          default:
            break;
        }
        echo '<span class="activity_date">'.nicetime($act->act_date).'</span>';
        echo '</li>';
        $i++;
      }
      $act_logged[$act->user_id] = $act->act_date;
      if ($i >$act_number){
        break;
      }
    }
  }
  echo '</ul>';
}

function nicetime($posted_date, $admin=false, $nohour=false) {
    // Adapted for something found on Internet, but I forgot to keep the url...
    $act_opt=get_option('act_settings');
    $date_relative = $act_opt['act_date_relative'];
    $date_format = $act_opt['act_date_format'];
    $posted_date = date("Y-m-d H:i:s", strtotime($posted_date) + ( get_option( 'gmt_offset' ) * 3600 ));
    $in_seconds = strtotime($posted_date);
    $diff = strtotime(date("Y-m-d H:i:s", time() + ( get_option( 'gmt_offset' ) * 3600 )));   
    $relative_date = '';
    $diff = $diff - $in_seconds;
    //echo "date : ".date("Y-m-d H:i:s", time())." - time : ".date_i18n("j F Y G \h i \m\i\n",( time() + ( get_option( 'gmt_offset' ) * 3600 ) ))." - in_seconds : ".date_i18n("j F Y G \h i \m\i\n",$in_seconds)." = diff : $diff<br />";
    $months = floor($diff/2592000);
    $diff -= $months*2419200;
    $weeks = floor($diff/604800);
    $diff -= $weeks*604800;
    $days = floor($diff/86400);
    $diff -= $days*86400;
    $hours = floor($diff/3600);
    $diff -= $hours*3600;
    $minutes = floor($diff/60);
    $diff -= $minutes*60;
    $seconds = $diff;
    if ($months>0 or !$date_relative or $admin) {
        // over a month old, just show date
        if ((!$date_relative or $admin) and !$nohour){
          $h = substr($posted_date,10);
        } else {
          $h = '';
        }
        switch ($date_format){
          case 'dd/mm/yyyy':
            return substr($posted_date,8,2).'/'.substr($posted_date,5,2).'/'.substr($posted_date,0,4).$h;
            break;
          case 'mm/dd/yyyy':
            return substr($posted_date,5,2).'/'.substr($posted_date,8,2).'/'.substr($posted_date,0,4).$h;
            break;
          case 'yyyy/mm/dd':
          default:
            return substr($posted_date,0,4).'/'.substr($posted_date,5,2).'/'.substr($posted_date,8,2).$h;
            break;
        }
    } else {
        if ($weeks>0) {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' '.($weeks>1? __('weeks', 'wp-activity'):__('week', 'wp-activity'));
            $relative_date .= $days>0?($relative_date?', ':'').$days.' '.($days>1? __('days', 'wp-activity'):__('day', 'wp-activity')):'';
        } elseif ($days>0) {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' '.($days>1? __('days', 'wp-activity'):__('day', 'wp-activity'));
            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wp-activity'):__('hour', 'wp-activity')):'';
        } elseif ($hours>0) {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wp-activity'):__('hour', 'wp-activity'));
            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wp-activity'):__('minute', 'wp-activity')):'';
        } elseif ($minutes>0) {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wp-activity'):__('minute', 'wp-activity'));
        } else {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' '.($seconds>1? __('seconds', 'wp-activity'):__('second', 'wp-activity'));
        }
    }
    // show relative date and add proper verbiage
    return sprintf(__('%s ago', 'wp-activity'), $relative_date);
}
if (is_admin()){
  require_once(WP_PLUGIN_DIR.'/'.ACT_DIR.'/wp-act-admin.php');  
}
  
add_action( 'widgets_init', 'WPActivity_load_widgets' );

function WPActivity_load_widgets() {
	register_widget('WpActivity_Widget');
	register_widget('WpActivity_user_Widget');
}
class WpActivity_Widget extends WP_Widget {

	function WpActivity_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wp-activity', 'description' => __('Display a stream of registered users events', 'wp-activity') );

		/* Widget control settings. */
		$control_ops = array( 'height' => 350, 'id_base' => 'wp-activity' );

		/* Create the widget. */
		$this->WP_Widget( 'wp-activity', __('Wp-Activity Widget', 'wp-activity'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		$number = $instance['number'];

		echo $before_widget;
		if ( $title )
			$title =  $before_title . $title . $after_title;
  act_stream_common($number, $title, false, '');
	echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = $new_instance['number'];
		return $instance;
	}

	function form( $instance ) {

		$defaults = array( 'title' => __('Recent Activity', 'wp-activity'), 'number' => '30');
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title :', 'wp-activity'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Events number :', 'wp-activity'); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" style="width:95%;" />
		</p>

	<?php
	}
}
class WpActivity_user_Widget extends WP_Widget {
	function WpActivity_user_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wp-activity', 'description' => __('Display the logged user own activity', 'wp-activity') );

		/* Widget control settings. */
		$control_ops = array( 'height' => 350, 'id_base' => 'wp-activity-user' );

		/* Create the widget. */
		$this->WP_Widget( 'wp-activity-user', __('Wp-Activity logged user own activity', 'wp-activity'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
	  global $user_ID;
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		$number = $instance['number'];
		$visitor = $instance['visitor'];

		echo $before_widget;
		if ( $title )
			$title =  $before_title . $title . $after_title;
		if ($user_ID) {
      act_stream_common($number, $title, false, $user_ID);
    }elseif ($visitor=='1'){
      act_stream_common($number, $title, false, '');
    }
	echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = $new_instance['number'];
		$instance['visitor'] = $new_instance['visitor'];
		return $instance;
	}

	function form( $instance ) {

		$defaults = array( 'title' => __('Your activity', 'wp-activity'), 'number' => '30', 'visitor' => '1');
		$instance = wp_parse_args( (array) $instance, $defaults ); 
    if ($instance['visitor']=='1'){
      $checkedyes='checked="checked"';
      $checkedno='';
    }else{
      $checkedyes='';
      $checkedno='checked="checked"';
    }
    ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title :', 'wp-activity'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Events number :', 'wp-activity'); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" style="width:95%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'visitor' ); ?>"><?php _e('When viewed by visitor', 'wp-activity'); ?> :</label><br />
			<input type="radio" <?php echo $checkedyes ?> name="<?php echo $this->get_field_name( 'visitor' ); ?>" value="1" /> <?php _e('Display all users activity', 'wp-activity'); ?><br />
		  <input type="radio" <?php echo $checkedno ?> name="<?php echo $this->get_field_name( 'visitor' ); ?>" value="0" /> <?php _e('Display nothing', 'wp-activity'); ?><br />
    </p>

	<?php
	}
}
?>
