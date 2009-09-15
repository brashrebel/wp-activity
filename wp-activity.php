<?php
/*
    Plugin Name: WP-Activity
    Plugin URI: http://www.driczone.net/blog/wp-activity
    Description: Display activity stream on your community site
    Author: Dric
    Version: 0.4
    Author URI: http://www.driczone.net
*/

/*  Copyright 2009  Dric  (email : cedric@driczone.net)

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
if ( !isset($_SESSION)) {
		session_start();
	}
$act_version="0.4";
$options = get_option('act_settings');

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

function act_install()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $table = $wpdb->prefix."activity";
    $structure = "CREATE TABLE $table (
        id INT(9) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        act_type VARCHAR(20) NOT NULL,
        act_date DATETIME,
        act_params TEXT,
	UNIQUE KEY id (id)
    );";
    dbDelta($structure);
    $options['act_prune'] = '500';
    $options['act_date_format'] = 'yyyy/mm/dd';
    $options['act_connect']= true;
    $options['act_profiles']= true;
    $options['act_posts']= true;
    $options['act_comments']= true;
    add_option('act_settings', $options);
    wp_schedule_event(time(), 'daily', 'act_cron_install');
}
register_activation_hook( __FILE__, 'act_install' );

//we add actions to hooks to log their events
add_action('send_headers', 'act_session');
add_action('profile_update', 'act_profile_edit');
add_action('publish_post', 'act_post_add');
add_action('comment_post', 'act_comment_add');
add_action('add_link', 'act_link_add');
 


function act_cron(){
  global $wpdb, $options;
  $wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$options['act_prune']);
  
}
add_action('act_cron_install','act_cron');
function act_header(){
  echo '<link type="text/css" rel="stylesheet" href="' . ACT_URL. 'wp-activity.css" />' . "\n";
}
add_action('wp_head', 'act_header');

function act_profile_option(){
  global $wpdb, $user_ID;
  $act_private = get_usermeta($user_ID, 'act_private');
  ?>
  <h3><?php _e('Activity events', 'wp-activity'); ?></h3>
  <table>
    <tr>
		  <th><?php _e('Hide my activity :', 'wp-activity'); ?></th>
		  <td><input type="checkbox" id="act_private" name="act_private" <?php if ($act_private){ echo 'checked="checked"'; }?> value="true" /></td>
    </tr>
  </table>
  <?php
}
add_action('show_user_profile', 'act_profile_option');

function act_profile_update(){
  global $user_ID, $_POST;
  update_usermeta($user_ID,'act_private',isset($_POST['act_private']) ? true : false);
}
add_action('personal_options_update', 'act_profile_update');

function act_session(){
  global $wpdb, $user_ID, $options;
  if ($options['act_connect'] and !get_usermeta($user_ID, 'act_private')){
    if (!$_SESSION['act_logged'] and is_user_logged_in()){
      $time=mysql2date("Y-m-d H:i:s", time());
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'CONNECT', '".$time."', '')");
      $url = parse_url(get_option('home'));
      $_SESSION['act_logged']= time();
    }
  }
}

function act_reinit(){
  if ($_SESSION['act_logged']){ unset($_SESSION['act_logged']);}
}
add_action('wp_login', 'act_reinit');
add_action('wp_logout', 'act_reinit');

function act_profile_edit($user){
  global $wpdb, $user_ID, $options;
  if ($options['act_profiles'] and !get_usermeta($user_ID, 'act_private')){
    $time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'PROFILE_EDIT', '".$time."', $user)");
  }
}

function act_post_add($post){
  global $wpdb, $user_ID, $options;
  if ($options['act_post'] and !get_usermeta($user_ID, 'act_private')){
    $time=mysql2date("Y-m-d H:i:s", time());
    if ($wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."activity WHERE act_params=$post AND act_type='POST_ADD'") > 0){
      $type='POST_EDIT';
    }else{
      $type='POST_ADD';
    }
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, $type, '".$time."', $post)");
  }
}

function act_comment_add($comment){
  global $wpdb, $user_ID, $options;
  if ($options['act_comment'] and !get_usermeta($user_ID, 'act_private')){
    $time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'COMMENT_ADD', '".$time."', $comment)");
  }
}

function act_link_add($link){
  global $wpdb, $user_ID, $options;
  if ($options['act_links'] and !get_usermeta($user_ID, 'act_private')){
    $time=mysql2date("Y-m-d H:i:s", time());
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LINK_ADD', '".$time."', $link)");
  }
}

function act_stream($number='30', $title=''){
global $wpdb;
  if ($title == ''){
    $title='<h2>'.__("Recent Activity", 'wp-activity').'</h2>';
  }
  $wp_url = get_bloginfo('wpurl');
  echo $title.'<ul id="activity">';
  $users = $wpdb->get_results("SELECT ID, display_name, user_nicename FROM $wpdb->users");
  foreach ($users as $user) {
		$users_nicename[$user->ID]=$user->user_nicename;
		$users_display[$user->ID]=$user->display_name;
	}
  $sql  = "SELECT * FROM ".$wpdb->prefix."activity ORDER BY id DESC LIMIT $number";
	if ( $logins = $wpdb->get_results( $sql)){
    foreach ( (array) $logins as $act ){
      //$user_nicename = get_the_author_meta('user_nicename',$act->user_id);
      $user_nicename = $users_nicename[$act->user_id];
      echo '<li class="login">'.nicetime($act->act_date).' - ';
      switch ($act->act_type){
        case 'CONNECT':
          echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$users_display[$act->user_id].'</a> '.__('has logged.', 'wp-activity');
          break;
        case 'COMMENT_ADD':
          $act_comment=get_comment($act->act_params);
          $act_post=get_post($act_comment->comment_post_ID);
          echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act_comment->comment_author.'</a> '.__('commented', 'wp-activity').' <a href="'.$act_post->post_name.'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a>';
          break;
        case 'POST_ADD':
          $act_post=get_post($act->act_params);
          echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$users_display[$act_post->post_author].'</a> '.__('published', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
          break;
        case 'POST_EDIT':
          $act_post=get_post($act->act_params);
          echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$users_display[$act_post->post_author].'</a> '.__('edited', 'wp-activity').' <a href="'.$act_post->post_name.'">'.$act_post->post_title.'</a>';
          break;
        case 'PROFILE_EDIT':
          echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$users_display[$act->user_id].'</a> '.__('has updated his profile.', 'wp-activity');
          break;
        case 'LINK_ADD':
          $link = get_bookmark($act->act_params);
          if ($link->link_visible == 'Y'){
            echo '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$users_display[$act->user_id].'</a> '.__('has added a link to', 'wp-activity').' <a href="'.$link->link_url.'" title="'.$link->link_description.'" target="'.$link->link_target.'">'.$link->link_name.'</a>.';
          }
          break;
        default:
          break;
      }
    }
    echo '</li>';
  }
  echo '</ul>';
}

function nicetime($posted_date) {
    $act_opt=get_option('act_settings');
    $date_format = $act_opt['act_date_format'];
    $in_seconds = strtotime($posted_date);          
    $diff = time()-$in_seconds;
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
 
    if ($months>0) {
        // over a month old, just show date
        switch ($date_format){
          case 'dd/mm/yyyy':
            return substr($posted_date,6,2).'/'.substr($posted_date,4,2).'/'.substr($posted_date,0,4);
            break;
          case 'mm/dd/yyyy':
            return substr($posted_date,4,2).'/'.substr($posted_date,6,2).'/'.substr($posted_date,0,4);
            break;
          case 'yyyy/mm/dd':
          default:
            return substr($posted_date,0,4).'/'.substr($posted_date,4,2).'/'.substr($posted_date,6,2);
            break;
        }
    } else {
        if ($weeks>0) {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' '.__('week', 'wp-activity').($weeks>1?'s':'');
            $relative_date .= $days>0?($relative_date?', ':'').$days.' '.__('day', 'wp-activity').($days>1?'s':''):'';
        } elseif ($days>0) {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' '.__('day', 'wp-activity').($days>1?'s':'');
            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.__('hour', 'wp-activity').($hours>1?'s':''):'';
        } elseif ($hours>0) {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' '.__('hour', 'wp-activity').($hours>1?'s':'');
            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.__('minute', 'wp-activity').($minutes>1?'s':''):'';
        } elseif ($minutes>0) {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' '.__('minute', 'wp-activity').($minutes>1?'s':'');
        } else {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' '.__('second', 'wp-activity').($seconds>1?'s':'');
        }
    }
    // show relative date and add proper verbiage
    return sprintf(__('%s ago', 'wp-activity'), $relative_date);
}

function act_admin_menu(){
  add_options_page('WP-Activity', 'WP-Activity', 8, 'wp-activity', 'act_admin');
}
add_action('admin_menu', 'act_admin_menu');

function act_admin(){
  global $wpdb, $act_version;
  $act_opt=get_option('act_settings');
  extract($act_opt);
  if ( isset($_POST['act_action'] ) ){
    switch ($_POST['act_action']){
      case "clean":
        $sql="DELETE FROM ".$wpdb->prefix."activity";
		    if ( $results = $wpdb->query( $sql ) ){
		      echo '<div id="message" class="updated fade"><p><strong>'.__('Activity logs deleted.', 'wp-activity').'</strong></div>';
		    }
        break;
      default:
          $options['act_connect']=$_POST['act_connect'];
          $options['act_profiles']=$_POST['act_profiles'];
          $options['act_posts']=$_POST['act_posts'];
          $options['act_comments']=$_POST['act_comments'];
          $options['act_prune']=$_POST['act_prune'];
          $options['act_date_format']=$_POST['act_date_format'];
          update_option('act_settings', $options);
        break;
    }
	}
  ?>
  <div class="wrap">
  	<h2>WP-Activity</h2>
  	<form action='' method='post'>
  	<table class="form-table">
      <tr valign="top">
        <th scope="row"><?php _e('Date format : ','wp-activity') ?></th>
  	    <td><select name="act_date_format">
        <option <?php if($act_date_format == 'yyyy/mm/dd') {echo"selected='selected' ";} ?>value ="yyyy/mm/dd">yyyy/mm/dd</option>
        <option <?php if($act_date_format == 'mm/dd/yyyy') {echo"selected='selected' ";} ?>value ="mm/dd/yyyy">mm/dd/yyyy</option>
        <option <?php if($act_date_format == 'dd/mm/yyyy') {echo"selected='selected' ";} ?>value ="dd/mm/yyyy">dd/mm/yyyy</option>
      </select><br /><?php _e('For events that are more than a month old only, or if you dont use relative dates.','wp-activity') ?></td>
  	  </tr><tr>
      <th><h3><?php _e('Events logging', 'wp-activity') ?></h3></th>
      </tr><tr>
      <th><?php _e('Rows limit in database : ', 'wp-activity') ?></th><td><input type="text" name="act_prune" value="<?php echo $act_prune ?>" /></td>
  	  </tr><tr>
      <th><?php _e('Store login events : ', 'wp-activity') ?></th><td><input type="checkbox" <?php if($act_connect){echo 'checked="checked"';} ?> name="act_connect" /></td>
      </tr><tr>
      <th><?php _e('Store profile update events : ', 'wp-activity') ?></th><td><input type="checkbox" <?php if($act_profiles){echo 'checked="checked"';} ?> name="act_profiles" /></td>
      </tr><tr>
      <th><?php _e('Store post creation/update events : ', 'wp-activity') ?></th><td><input type="checkbox" <?php if($act_posts){echo 'checked="checked"';} ?> name="act_posts" /></td>
      </tr><tr>
      <th><?php _e('Store new comment events : ', 'wp-activity') ?></th><td><input type="checkbox" <?php if($act_comments){echo 'checked="checked"';} ?> name="act_comments" /></td>
      </tr>
      </table>
      <br />
      <h3><?php _e('Update/clean tables', 'wp-activity') ?></h3>
      <p><?php _e('Warning : cleaning activity table erase all activity logs.', 'wp-activity') ?></p>
      <select name="act_action">
        <option value ="update"><?php _e('Update settings', 'wp-activity') ?></option>
  			<option value ="clean"><?php _e('Clean activity table', 'wp-activity') ?></option>        
      </select>
      <input type='submit' class='button-secondary delete' name='submit' value='<?php _e('Submit', 'wp-activity') ?>' />
    </form>
    <br />
    <h4><?php echo sprintf(__('WP-Activity is a plugin made in France by <a href="http://www.driczone.net">Dric</a>. Version <strong>%s</strong>.', 'wp-activity'), $act_version) ?></h4>
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
  act_stream($number, $title);
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
