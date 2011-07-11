<?php
/*
    Plugin Name: WP-Activity
    Plugin URI: http://www.driczone.net/blog/plugins/wp-activity
    Description: Log and display users activity in backend and frontend of WordPress.
    Author: Dric
    Version: 1.3.2
    Author URI: http://www.driczone.net
*/

/*  Copyright 2009-2010 Dric  (email : cedric@driczone.net)

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

$act_plugin_version = "1.3.2"; //Don't change this, of course.
$act_list_limit = 50; //Change this if you want to display more than 50 items per page in admin list
$strict_logs = false; //If you don't want to keep track of posts authors changes, set this to "true"

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
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/' );
define('ACT_DIR', dirname(plugin_basename(__FILE__)));
define('ACT_URL', WP_CONTENT_URL . '/plugins/' . ACT_DIR . '/');

//Plugin can be translated, just put the .mo language file in the /lang directory
load_plugin_textdomain('wp-activity', WP_PLUGIN_URL.'/wp-activity/lang/', ACT_DIR . '/lang/');

function act_cron(){
  global $wpdb, $options_act, $plugin_page;
  $act_count = $wpdb->get_var("SELECT count(ID) FROM ".$wpdb->prefix."activity");
  $act_delete = $act_count - $options_act['act_prune'];
  if ($act_delete > 0) {
    $wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$delete);
  }
  
}
add_action('act_cron_install','act_cron');

function act_desactive() {
	wp_clear_scheduled_hook('act_cron_install');
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
    $new_options_act['act_version'] = $act_plugin_version;
    add_option('act_settings', $new_options_act);
    
    if ($options_act['act_version'] != $act_plugin_version){
      $options_act['act_version'] = $act_plugin_version;
      update_option('act_settings', $options_act);
    }
    wp_schedule_event(time(), 'daily', 'act_cron_install');
}
register_activation_hook( __FILE__, 'act_install' );
register_deactivation_hook(__FILE__, 'act_desactive');

add_filter("plugin_action_links_wp-activity/wp-activity.php", 'act_plugin_action_links');
function act_plugin_action_links($links)
{
    $settings_link = '<a href="options-general.php?page=wp-activity">' . __( 'Settings' ) . '</a>';
    $uninstall_link = '<a href="options-general.php?page=wp-activity#act_reset">' . __( 'Uninstall' ) . '</a>';
    array_unshift($links, $settings_link, $uninstall_link);
    return $links;
}

//we add actions to hooks to log their events
add_action('send_headers', 'act_session');
add_action('profile_update', 'act_profile_edit');
add_action('publish_post', 'act_post_add');
add_action('comment_post', 'act_comment_add');
add_action('add_link', 'act_link_add');
add_action('wp_login_failed', 'act_login_failed');
 
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
  if ($options_act['act_allow_priv']){
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
}
add_action('show_user_profile', 'act_profile_option');

function act_login_failed($act_user=''){
  global $wpdb, $options_act;
  if ($options_act['act_log_failures'] and $act_user){
    $user_ID = 1; //event has to be linked to a wp user.
    $act_time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LOGIN_FAIL', '".$act_time."', '".$act_user."')");
    }
}

function act_profile_update(){
  global $user_ID, $_POST;
  update_usermeta($user_ID,'act_private',isset($_POST['act_private']) ? true : false);
}
add_action('personal_options_update', 'act_profile_update');

function act_session(){
  global $wpdb, $user_ID, $options_act;
  if ($options_act['act_connect'] and !get_usermeta($user_ID, 'act_private')){
    if (!$_COOKIE['act_logged'] and is_user_logged_in()){
      setcookie('act_logged',time());
      $act_time=mysql2date("Y-m-d H:i:s", time());
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'CONNECT', '".$act_time."', '')");
      $act_url = parse_url(get_option('home'));
    }
  }
}

function act_reinit(){
  if ($_COOKIE['act_logged']){ setcookie ("act_logged", "", time() - 3600);}
}
add_action('wp_login', 'act_reinit');
add_action('wp_logout', 'act_reinit');

function act_profile_edit($act_user){
  global $wpdb, $user_ID, $options_act;
  if ($options_act['act_profiles'] and !get_usermeta($user_ID, 'act_private')){
    $act_time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'PROFILE_EDIT', '".$act_time."', $act_user)");
  }
}

function act_post_add($act_post){
  global $wpdb, $user_ID, $options_act;
  if ($options_act['act_posts'] and !get_usermeta($user_ID, 'act_private')){
    $act_time=mysql2date("Y-m-d H:i:s", time());
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
  if ($options_act['act_comments'] and !get_usermeta($user_ID, 'act_private')){
    $act_time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'COMMENT_ADD', '".$act_time."', $act_comment)");
  }
}

function act_link_add($act_link){
  global $wpdb, $user_ID, $options_act;
  if ($options_act['act_links'] and !get_usermeta($user_ID, 'act_private')){
    $act_time=mysql2date("Y-m-d H:i:s", time());
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

//$act_number = -1 : no limit
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
	$sql  = "SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->prefix."users AS users WHERE activity.user_id = users.id";
  if ($act_user!= ''){
    $sql .= " AND user_id = '".$act_user."'";
  }else{
    $sql .= " AND act_type <> 'LOGIN_FAIL'";
  }
  $sql .= " ORDER BY act_date DESC";
  if ($act_number > 0){
    $sql  .= " LIMIT $act_number";
  }
  $act_logged[$act_user]=mysql2date("Y-m-d H:i:s", time());
	if ( $act_logins = $wpdb->get_results( $sql)){
    foreach ( (array) $act_logins as $act ){
      if ($options_act['act_old'] and $act_old_flag > 0 and !$archive){
        $act_old_class = 'act-old';
      }else{
        $act_old_class = '';
      }
      if (((strtotime($act_logged[$act_user]) - strtotime($act->act_date)) > 60 AND $act->act_type == 'CONNECT') OR $act->act_type != 'CONNECT'){      
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
              echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has logged.', 'wp-activity');
              if ($act->user_id == $user_ID and $options_act['act_old']){
                $act_old_flag++;
              }
            break;
          case 'COMMENT_ADD':
            $act_comment=get_comment($act->act_params);
            $act_post=get_post($act_comment->comment_post_ID);
            echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act_comment->comment_author.'</a> '.__('commented', 'wp-activity').' <a href="'.$act_post->post_name.'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a>';
            break;
          case 'POST_ADD':
            $act_post=get_post($act->act_params);
            if ($act->user_id != $act_post->post_author and !$strict_logs){ //this is a check if post author has been changed in admin post edition.
              $sql = "UPDATE ".$wpdb->prefix."activity SET user_id = '".$act_post->post_author."' WHERE id = '".$act->id."'";
              $wpdb->query( $sql);
            }else{
              echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('published', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
            }
            break;
          case 'POST_EDIT':
            $act_post=get_post($act->act_params);
            echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('edited', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
            break;
          case 'PROFILE_EDIT':
            echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has updated his profile.', 'wp-activity');
            break;
          case 'LINK_ADD':
            $act_link = get_bookmark($act->act_params);
            if ($act_link->link_visible == 'Y'){
              echo '<a href="'.$wp_url.'/author/'.$act->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act->display_name.'</a> '.__('has added a link to', 'wp-activity').' <a href="'.$act_link->link_url.'" title="'.$act_link->link_description.'" target="'.$act_link->link_target.'">'.$act_link->link_name.'</a>.';
            }
            break;
          default:
            break;
        }
        echo '<span class="activity_date">'.nicetime($act->act_date).'</span>';
        echo '</li>';
      }
      $act_logged[$act_user] = $act->act_date;
    }
  }
  echo '</ul>';
}

function nicetime($posted_date, $admin=false) {
    // Adapted for something found on Internet, but I forgot to keep the url...
    $act_opt=get_option('act_settings');
    $date_relative = $act_opt['act_date_relative'];
    $date_format = $act_opt['act_date_format'];
    $in_seconds = strtotime($posted_date);   
    $relative_date = '';
    $diff = time() + (get_option('gmt_offset')*3600)-$in_seconds;
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
        if (!$date_relative or $admin){
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

function act_admin_menu(){
  $act_plugin_page = add_options_page('WP-Activity', 'WP-Activity', 8, 'wp-activity', 'act_admin');
  add_action( 'admin_head-'. $act_plugin_page, 'act_header' );
}
//if (current_user_can('administrator')){
  add_action('admin_menu', 'act_admin_menu');
  add_action('admin_init','act_admin_scripts');
//}

function act_rightnow_row(){
  global $wpdb, $user_ID;
  $act_last_connect = $wpdb->get_var("SELECT act_date FROM ".$wpdb->prefix."activity WHERE user_id = '".$user_ID."' AND act_type = 'CONNECT' ORDER BY act_date DESC LIMIT 1,1");
  $act_fail_count = $wpdb->get_var("SELECT COUNT(id) FROM ".$wpdb->prefix."activity WHERE act_date >= '".$act_last_connect."' AND act_type = 'LOGIN_FAIL'");
  
  echo "<tr>";
  echo " <td class=\"first b\"><a href=\"?page=wp-activity\">$act_fail_count</a></td>";
	echo " <td class=\"t spam\">" . __("Logon fails", 'wp-activity') . "</td>";
  echo "</tr>";
}
//if (current_user_can('administrator') and $options_act['act_log_failures']){
  add_action('right_now_content_table_end', 'act_rightnow_row');
//}

function act_admin_scripts(){
  wp_enqueue_script('jquery-ui-tabs');
  wp_enqueue_style('act_tabs', WP_PLUGIN_URL .'/wp-activity/jquery.ui.tabs.css', false, '2.5.0', 'screen');
}

function act_pagination($act_count, $limit = 50, $current, $act_start = 0, $args = ''){
  // Adapted from http://www.phpeasystep.com/phptu/29.html
	$adjacents = 3;
  if ($act_start + 50 > $act_count){
    $act_last = $act_count;
  }else{
    $act_last = $act_start + 50;
  }
	$targetpage = "?page=wp-activity".$args;
	if($current) 
		$start = ($current - 1) * $limit; 			//first item to display on this page
	else
		$start = 0;
	
	/* Setup page vars for display. */
	if ($current == 0) $current = 1;
	$prev = $current - 1;	
	$next = $current + 1;
	$lastpage = ceil($act_count/$limit);		//lastpage is = total pages / items per page, rounded up.
	$lpm1 = $lastpage - 1;

	$pagination = "<div class=\"tablenav-pages\"><span class=\"displaying-num\">".sprintf(__("Displaying %s&#8211;%s of %s"),$act_start+1, $act_last, $act_count)."</span> ";

	if($lastpage > 1)
	{	
		//previous button
		if ($current > 1) 
			$pagination.= "<a class=\"prev page-numbers\" href=\"$targetpage&act_page=$prev\">&laquo;</a> ";
		
		//pages	
		if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
		{	
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $current)
					$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
				else
					$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
			}
		}
		elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
		{
			//close to beginning; only hide later pages
			if($current < 1 + ($adjacents * 2))		
			{
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
				{
					if ($counter == $current)
						$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
					else
						$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
				}
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lpm1\">$lpm1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lastpage\">$lastpage</a> ";		
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents * 2) > $current && $current > ($adjacents * 2))
			{
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=1\">1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=2\">2</a> ";
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				for ($counter = $current - $adjacents; $counter <= $current + $adjacents; $counter++)
				{
					if ($counter == $current)
						$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
					else
						$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
				}
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lpm1\">$lpm1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lastpage\">$lastpage</a> ";		
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=1\">1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=2\">2</a> ";
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $current)
						$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
					else
						$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
				}
			}
		}
		//next button
		if ($current < $counter - 1) 
			$pagination.= "<a class=\"next page-numbers\" href=\"$targetpage&act_page=$next\">&raquo;</a>";	
	}
	$pagination.= "</div>";	
	echo $pagination;
}

function act_admin(){
  global $wpdb, $act_plugin_version, $act_list_limit;
    ?>
    <div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>WP-Activity</h2>
    <?php
    if ( isset($_GET['act_list_action']) && isset($_GET['act_check']) && check_admin_referer('wp-activity-list', 'act_filter')) {
    	$doaction = $_GET['act_list_action'];
    	if ( 'delete' == $doaction ) {
        $act_list_del = implode(",", $_GET['act_check']);
        if ($wpdb->query("DELETE FROM ".$wpdb->prefix."activity WHERE id IN(".$act_list_del.")")){
          echo '<div id="message" class="updated fade"><p><strong>'.__('Event(s) deleted.', 'wp-activity').'</strong></div>';
        }
    	}
    }
    $act_args = '';
    if (isset($_GET['act_type_filter'])){
      $act_type_filter = esc_html($_GET['act_type_filter']);
      if ($act_type_filter <> 'all'){
        $sqlfilter = 'AND act_type = "'.$act_type_filter.'"';
      }
      $act_args .= '&act_type_filter='.$act_type_filter;
    }else{
      $sqlfilter = '';
    }
    if (isset($_GET['act_order_by'])){
      $act_order_by = esc_html($_GET['act_order_by']);
      $act_args .= '&act_order_by='.$act_order_by;
    }
    if ( empty($act_type_filter) )
    	$act_type_filter = 'all';
    
    if ( empty($act_order_by) )
    	$act_order_by = 'order_date';
  
    switch ($act_order_by) {
    	case 'order_user' :
    		$sqlorderby = 'display_name';
    		$sqlasc = 'ASC';
    		break;
    	case 'order_type' :
    		$sqlorderby = 'act_type';
    		$sqlasc = 'ASC';
    		break;
    	case 'order_date' :
    	default :
    		$sqlorderby = 'act_date';
    		$sqlasc = 'DESC';
    		break;
    }
    
    if (isset($_POST['submit']) and check_admin_referer('wp-activity-submit','act_admin')){
      $options_act['act_connect']=$_POST['act_connect'];
      $options_act['act_profiles']=$_POST['act_profiles'];
      $options_act['act_posts']=$_POST['act_posts'];
      $options_act['act_comments']=$_POST['act_comments'];
      $options_act['act_links']=$_POST['act_links'];
      $options_act['act_feed_connect']=$_POST['act_feed_connect'];
      $options_act['act_feed_profiles']=$_POST['act_feed_profiles'];
      $options_act['act_feed_posts']=$_POST['act_feed_posts'];
      $options_act['act_feed_comments']=$_POST['act_feed_comments'];
      $options_act['act_feed_links']=$_POST['act_feed_links'];
      $options_act['act_feed_display']=$_POST['act_feed_display'];
      $options_act['act_prune']=$_POST['act_prune'];
      $options_act['act_date_format']=$_POST['act_date_format'];
      $options_act['act_date_relative']=$_POST['act_date_relative'];
      $options_act['act_icons']=$_POST['act_icons'];
      $options_act['act_old']=$_POST['act_old'];
      $options_act['act_page_link']=$_POST['act_page_link'];
      $options_act['act_page_id']=$_POST['act_page_id'];
      $options_act['act_prevent_priv']=$_POST['act_prevent_priv'];
      $options_act['act_log_failures']=$_POST['act_log_failures'];
      $options_act['act_version']=$act_version;
      if (update_option('act_settings', $options_act)){
        echo '<div id="message" class="updated fade"><p><strong>'.__('Options saved.').'</strong></div>';
      }
    }elseif(isset($_POST['act-reset']) and check_admin_referer('wp-activity-reset','act_admin_reset')){
      $sql="DELETE FROM ".$wpdb->prefix."activity";
  		if ( $results = $wpdb->query( $sql ) ){
  		  echo '<div id="message" class="updated highlight fade"><p><strong>'.__('Activity logs deleted.', 'wp-activity').'</strong></div>';
  		}
  	}elseif(isset($_POST['act-uninst']) and check_admin_referer('wp-activity-uninst','act_admin_uninst')){
      act_desactive(); //Delete activity cron
      delete_option('act_settings'); //delete activity settings
      $sql="DROP TABLE ".$wpdb->prefix."activity"; //delete activity table
  		if ( $results = $wpdb->query( $sql ) ){
  		  echo '<div id="message" class="updated highlight fade"><p><strong>'.sprintf(__('Activity Plugin has been uninstalled. You can now desactivate this plugin : <a href="%s">Plugins Page</a>', 'wp-activity'),get_bloginfo('wpurl').'/wp-admin/plugins.php').'</strong></div>';
  		}
    }
    $act_opt=get_option('act_settings');
    if (!is_array($act_opt)){
      echo '<span class="activity_warning">'.sprintf(__('Activity Plugin has been uninstalled. You can now desactivate this plugin : <a href="%s">Plugins Page</a>', 'wp-activity'),get_bloginfo('wpurl').'/wp-admin/plugins.php').'</span>';
    }else{
      extract($act_opt);
      ?>
      <script type="text/javascript">
        jQuery(function() {
            jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });
        });
      </script>
      <div id="slider">    
        <ul id="tabs">
          <li><a href="#act_recent"><?php _e('Recent Activity', 'wp-activity') ;?></a></li>
          <li><a href="#act_date"><?php _e('Date format', 'wp-activity') ;?></a></li>
          <li><a href="#act_display"><?php _e('Display options', 'wp-activity') ;?></a></li>
          <li><a href="#act_privacy"><?php _e('Privacy options', 'wp-activity') ;?></a></li>
          <li><a href="#act_events"><?php _e('Events logging and feeding', 'wp-activity') ;?></a></li>
          <li><a href="#act_reset"><?php _e('Reset/uninstall', 'wp-activity') ;?></a></li>
        </ul>
          <div id="act_recent">
            <?php
              if ($_GET['act_page'] and is_numeric($_GET['act_page'])){
                $act_page = $_GET['act_page'];
              }else{
                $act_page = 1;
              }
            ?>
            <h2><?php _e("Recent Activity", 'wp-activity'); ?></h2>
            <?php
              $act_start = ($act_page - 1)*$act_list_limit;
              $sql  = "SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->prefix."users AS users WHERE activity.user_id = users.id ".$sqlfilter." ORDER BY ".$sqlorderby." ".$sqlasc; //." LIMIT ".$act_start.",".$act_list_limit;
              if ( $logins = $wpdb->get_results($sql)){
                $act_count = count($logins);
                ?>
                <form id="act-filter" action="" method="get">
                  <input type="hidden" name="page" value="wp-activity" />
                  <?php wp_nonce_field('wp-activity-list', 'act_filter', false) ?>
                  <div class="tablenav">
                    <?php act_pagination($act_count,$act_list_limit, $act_page, $act_start, $act_args); ?>
                    <div class="alignleft actions">
                      <select name="act_list_action">
                        <option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
                        <option value="delete"><?php _e('Delete'); ?></option>
                      </select>
                      <input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
                      <?php
                      $types = array('LOGIN_FAIL', 'CONNECT', 'POST_ADD', 'POST_EDIT', 'PROFILE_EDIT', 'LINK_ADD');
                      $select_type = "<select name=\"act_type_filter\">";
                      $select_type .= '<option value="all"'  . (($act_type_filter == 'all') ? " selected='selected'" : '') . '>' . __('View all') . "</option>";
                      foreach ((array) $types as $type)
    	                  $select_type .= '<option value="' . $type . '"' . (($type == $act_type_filter) ? " selected='selected'" : '') . '>' . $type . "</option>";
                      $select_type .= "</select>";
                      echo $select_type;
                      $select_order = "<select name=\"act_order_by\">";
                      $select_order .= '<option value="order_date"' . (($act_order_by == 'order_date') ? " selected='selected'" : '') . '>' .  __('Order by date (DESC)', 'wp-activity') . "</option>";
                      $select_order .= '<option value="order_user"' . (($act_order_by == 'order_user') ? " selected='selected'" : '') . '>' .  __('Order by user', 'wp-activity') . "</option>";
                      $select_order .= '<option value="order_type"' . (($act_order_by == 'order_type') ? " selected='selected'" : '') . '>' .  __('Order by event type', 'wp-activity') . "</option>";
                      //$select_order .= '<option value="order_data"' . (($act_order_by == 'order_data') ? " selected='selected'" : '') . '>' .  __('Order by data', 'wp-activity') . "</option>";
                      $select_order .= "</select>";
                      echo $select_order;
                      ?>
                      <input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
                    </div>
                    <br class="clear" />
                  </div>
                  <table id="activity-admin" class="widefat">
                    <thead>
                      <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                        <th></th>
                        <th scope="col" class="manage-column"><?php _e("Date", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("User", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("Event Type", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("Applies to", 'wp-activity'); ?></th>
                      </tr>
                    </thead>
                    <tfoot>
                      <tr>
                        <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                        <th></th>
                        <th scope="col" class="manage-column"><?php _e("Date", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("User", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("Event Type", 'wp-activity'); ?></th>
                        <th scope="col" class="manage-column"><?php _e("Applies to", 'wp-activity'); ?></th>
                      </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    $act_alt = 0;
                    $i = 0;
                    foreach ( (array) $logins as $act ){
                      $i++;
                      if ($i > $act_start and $i <= ($act_start + $act_list_limit)){
                        if ($act_alt == 1){$act_alt_class = 'class="alternate"';}else{$act_alt_class = '';}
                        echo '<tr '.$act_alt_class.'>';
                        echo '<th scope="row" class="check-column"><input type="checkbox" name="act_check[]" value="'. esc_attr($act->id) .'" /></th>';
                        echo '<td>'.$i.'</td><td>'.nicetime($act->act_date, true).'</td>';
                        switch ($act->act_type){
                          case 'LOGIN_FAIL' :
                            echo '<td><span class="activity_warning">'.$act->act_params.'</span></td><td><span class="activity_warning">'.$act->act_type.'</span></td><td></td>';
                            break;
                          case 'CONNECT':
                            echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td></td>';
                            break;
                          case 'COMMENT_ADD':
                            $act_comment=get_comment($act->act_params);
                            $act_post=get_post($act_comment->comment_post_ID);
                            echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td><a href="'.get_permalink($act_post->ID).'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a></td>';
                            break;
                          case 'POST_ADD':
                          case 'POST_EDIT':
                            $act_post=get_post($act->act_params);
                            echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td><a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a></td>';
                            break;
                          case 'PROFILE_EDIT':
                            echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td></td>';
                            break;
                          case 'LINK_ADD':
                            $link = get_bookmark($act->act_params);
                            if ($link->link_visible == 'Y'){
                              echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td><a href="'.$link->link_url.'" title="'.$link->link_description.'" target="'.$link->link_target.'">'.$link->link_name.'</a></td>';
                            }
                            break;
                          default:
                            break;
                        }
                        echo '</tr>';
                        if ($act_alt == 1){$act_alt = 0;}else{$act_alt = 1;}
                      }
                    }
                    ?>
                    </tbody>
                  </table>
                </form>                
                <?php
                echo '<div class="tablenav">';
                act_pagination($act_count,$act_list_limit, $act_page, $act_start, $act_args);
                echo '</div>';
                echo '<div class="clearfix"></div>';
              }else{
                echo '<h3>'.__('Activity logs are empty !','wp-activity').'</h3>';
              }
            ?>
          </div>
          <form action='' method='post'>
          <div id="act_date">
            <h2><?php _e('Date format','wp-activity') ?></h2>
            <table class="form-table">
              <tr valign="top">
                <th scope="row"><?php _e('Date format : ','wp-activity') ?></th>
                <td>
                  <select name="act_date_format">
                    <option <?php if($act_date_format == 'yyyy/mm/dd') {echo"selected='selected' ";} ?>value ="yyyy/mm/dd">yyyy/mm/dd</option>
                    <option <?php if($act_date_format == 'mm/dd/yyyy') {echo"selected='selected' ";} ?>value ="mm/dd/yyyy">mm/dd/yyyy</option>
                    <option <?php if($act_date_format == 'dd/mm/yyyy') {echo"selected='selected' ";} ?>value ="dd/mm/yyyy">dd/mm/yyyy</option>
                  </select>
                  <br /><span class="act_info"><?php _e('For events that are more than a month old only, or if you dont use relative dates.','wp-activity') ?></span>
                </td>
      	       </tr><tr>
                <th><?php _e('Use relative dates : ', 'wp-activity') ?></th>
                <td>
                  <input type="checkbox" <?php if($act_date_relative){echo 'checked="checked"';} ?> name="act_date_relative" />
                  <br /><span class="act_info"><?php _e('Relatives dates exemples : 1 day ago, 22 hours and 3 minutes ago, etc.','wp-activity') ?></span>
                </td>
      	       </tr><tr>
      	     </table>
            <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
          </div>
          <div id="act_display">
            <h2><?php _e('Display options','wp-activity') ?></h2>
            <table class="form-table">
              <tr>
                <th><?php _e('Display icons : ', 'wp-activity') ?></th>
                <td><input type="radio" <?php if($act_icons=="g"){echo 'checked="checked"';} ?> name="act_icons" value="g" /> <?php _e('Generic icons ', 'wp-activity') ?></td>
              </tr>
              <tr><td></td><td><input type="radio" <?php if($act_icons=="a"){echo 'checked="checked"';} ?> name="act_icons" value="a" /> <?php _e('Gravatars for profile edit and connect events icons', 'wp-activity') ?></td></tr>
              <tr><td></td><td><input type="radio" <?php if($act_icons=="n"){echo 'checked="checked"';} ?> name="act_icons" value="n" /> <?php _e('No icons ', 'wp-activity') ?></td></tr>
              <tr>
                <th><?php _e('Highlight new activity since last user login : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_old){echo 'checked="checked"';} ?> name="act_old" /></td>
              </tr>
              <tr>
                <th><?php _e('Display a link to the activity archive page : ', 'wp-activity') ?></th>
                <td>
                  <input type="checkbox" <?php if($act_page_link){echo 'checked="checked"';} ?> name="act_page_link" />
                  <?php wp_dropdown_pages(array('selected' => $act_page_id, 'name' => 'act_page_id')); ?>
                  <br /><span class="act_info"><?php _e('You have to create a page first, with the [ACT_STREAM] shortcode.','wp-activity') ?></span>
                </td>
              </tr>
            </table>
            <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
          </div>
          <div id="act_privacy">
            <h2><?php _e('Privacy options','wp-activity') ?></h2>
            <table class="form-table">
              <tr>
                <th><?php _e('Prevent users to hide their activity : ', 'wp-activity') ?></th>
                <td>
                  <input type="checkbox" <?php if($act_prevent_priv){echo 'checked="checked"';} ?> name="act_prevent_priv" />
                  <br /><span class="activity_warning"><?php _e('Warning : If you activate this option, users won\'t have the choice to allow or deny the logging of their activity. For privacy respect, this option should stay desactivated.', 'wp-activity') ?></span>
                </td>
              </tr>
            </table>
            <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
          </div>
          <div id="act_events">
            <h2><?php _e('Events logging and feeding', 'wp-activity') ?></h2>
            <table class="form-table">
              </tr><tr>
                <th><?php _e('Rows limit in database : ', 'wp-activity') ?></th><td><input type="text" name="act_prune" value="<?php echo $act_prune ?>" /></td>
              </tr><tr>
                <th><?php _e('Display activity RSS feed : ', 'wp-activity') ?></th><td><input type="checkbox" <?php if($act_feed_display){echo 'checked="checked"';} ?> name="act_feed_display" /></td>
              </tr><tr>
              </tr><tr>
                <th><?php _e('Log login failures : ', 'wp-activity') ?></th>
                <td>
                  <input type="checkbox" <?php if($act_log_failures){echo 'checked="checked"';} ?> name="act_log_failures" />
                  <br /><span class="act_info"><?php _e('If you want to log all connexions attempts that failed, enable this option.','wp-activity') ?></span>
                </td>
              </tr><tr>
                <th></th><th><?php _e('Log in database', 'wp-activity') ?></th><th><?php _e('Display in feed', 'wp-activity') ?></th>
              </tr><tr>
                <th><?php _e('Login events : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_connect){echo 'checked="checked"';} ?> name="act_connect" /></td>
                <td><input type="checkbox" <?php if($act_feed_connect){echo 'checked="checked"';} ?> name="act_feed_connect" /></td>
              </tr><tr>
                <th><?php _e('Profile update events : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_profiles){echo 'checked="checked"';} ?> name="act_profiles" /></td>
                <td><input type="checkbox" <?php if($act_feed_profiles){echo 'checked="checked"';} ?> name="act_feed_profiles" /></td>
              </tr><tr>
                <th><?php _e('Post creation/update events : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_posts){echo 'checked="checked"';} ?> name="act_posts" /></td>
                <td><input type="checkbox" <?php if($act_feed_posts){echo 'checked="checked"';} ?> name="act_feed_posts" /></td>
              </tr><tr>
                <th><?php _e('New comment events : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_comments){echo 'checked="checked"';} ?> name="act_comments" /></td>
                <td><input type="checkbox" <?php if($act_feed_comments){echo 'checked="checked"';} ?> name="act_feed_comments" /></td>
              </tr><tr>
                <th><?php _e('New link events : ', 'wp-activity') ?></th>
                <td><input type="checkbox" <?php if($act_links){echo 'checked="checked"';} ?> name="act_links" /></td>
                <td><input type="checkbox" <?php if($act_feed_links){echo 'checked="checked"';} ?> name="act_feed_links" /></td>
              </tr>
            </table>
            <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
          </div>
          <?php wp_nonce_field('wp-activity-submit','act_admin'); ?>
        </form>
        <div id="act_reset">
          <h2><?php _e('Reset/uninstall', 'wp-activity') ?></h2>
          <table class="form-table">
            </tr><tr>
              <th><?php _e('Empty activity table : ', 'wp-activity') ?></th>
              <td>
                <form name="act_form_reset" method="post">
                  <?php
                    if ( function_exists('wp_nonce_field') )
    	                wp_nonce_field('wp-activity-reset','act_admin_reset');
                  ?>
                  <input type="submit" class="button" name="act-reset" value="<?php _e('Reset logs', 'wp-activity') ?>" onclick="javascript:check=confirm('<?php _e('Empty activity table ? All your activity logs will be deleted.\n\nChoose [Cancel] to Stop, [OK] to proceed.\n', 'wp-activity') ?>');if(check==false) return false;" />
                  <br /><span class="activity_warning"><?php _e('Warning : cleaning activity table erase all activity logs.', 'wp-activity') ?></span>
                </form>
              </td>
            </tr><tr>
              <th><?php _e('Uninstall plugin', 'wp-activity') ?> : </th>
              <td>
                <form name="act_form_uninst" method="post">
                  <?php
                    if ( function_exists('wp_nonce_field') )
                      wp_nonce_field('wp-activity-uninst','act_admin_uninst');
                  ?>
                  <input type="submit" class="button" name="act-uninst" value="<?php _e('Uninstall plugin', 'wp-activity') ?>" onclick="javascript:check=confirm('<?php _e('Uninstall plugin ? Settings and activity logs will be deleted.\n\nChoose [Cancel] to Stop, [OK] to proceed.\n', 'wp-activity') ?>');if(check==false) return false;" />
                  <br /><span class="activity_warning"><?php _e('Warning : This will delete settings and activity table.', 'wp-activity') ?></span>
                </form>
              </td>
            </tr>
          </table>
        </div>
      </div>
    <?php } ?>
      <br />
      <h4><?php echo sprintf(__('WP-Activity is a plugin by <a href="http://www.driczone.net">Dric</a>. Version <strong>%s</strong>.', 'wp-activity'), $act_plugin_version ) ?></h4>
    </div>
    <?php
}
add_action( 'widgets_init', 'WPActivity_load_widgets' );

function WPActivity_load_widgets() {
	register_widget( 'WpActivity_Widget' );
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
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Events number :', 'wp-activity'); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" style="width:100%;" />
		</p>

	<?php
	}
}
?>
