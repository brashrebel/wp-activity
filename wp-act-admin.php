<?php

//admin panel additions
if (!$no_admin_mess){
  add_filter( 'manage_users_columns', 'add_act_last_login_column');
  function add_act_last_login_column( $columns){
      $columns['act_last_login'] = __('Last login', 'wp-activity');
      return $columns;
  }
  
  add_filter('manage_users_custom_column',  'add_act_last_login_column_value', 20, 3);
  function add_act_last_login_column_value( $value='', $column_name, $user_id ){
    global $wpdb;
  	if ($column_name == 'act_last_login'){
    	$act_last_connect = $wpdb->get_var("SELECT act_date FROM ".$wpdb->prefix."activity WHERE user_id = '".$user_id."' AND act_type = 'CONNECT' ORDER BY act_date DESC LIMIT 0,1");
      if ($act_last_connect){
        return nicetime($act_last_connect);
      }
    }else{
      return $value;
    }
  }
  function act_rightnow_row(){
    global $wpdb, $user_ID;
    $act_last_connect = $wpdb->get_var("SELECT act_date FROM ".$wpdb->prefix."activity WHERE user_id = '".$user_ID."' AND act_type = 'CONNECT' ORDER BY act_date DESC LIMIT 1,1");
    $act_fail_count = $wpdb->get_var("SELECT COUNT(id) FROM ".$wpdb->prefix."activity WHERE act_date >= '".$act_last_connect."' AND act_type = 'LOGIN_FAIL'");
    $act_nonce = wp_create_nonce('wp-activity-list');
    echo "<tr>";
    echo " <td class=\"first b\"><a href=\"admin.php?page=act_activity&act_filter=".$act_nonce."&act_type_filter=LOGIN_FAIL\">$act_fail_count</a></td>";
  	echo " <td class=\"t spam\">" . __("Logon fails", 'wp-activity') . "</td>";
    echo "</tr>";
  }
  if ($options_act['act_log_failures']){
    add_action('right_now_content_table_end', 'act_rightnow_row');
  }
}

//menus and scripts loading
function act_admin_menu(){
  add_action( 'admin_head', 'act_header' );
  add_menu_page('WP-Activity', 'WP-Activity', 'publish_posts', 'act_activity', 'act_admin_activity', 'div');
  add_submenu_page( 'act_activity' , __('Activity Log', 'wp-activity'), __('Activity Log', 'wp-activity'), 'publish_posts', 'act_activity', 'act_admin_activity');
  $act_stats_page = add_submenu_page( 'act_activity' , __('Activity Stats', 'wp-activity'), __('Activity Stats', 'wp-activity'), 'publish_posts', 'act_stats', 'act_admin_stats');
  $act_admin_page = add_submenu_page( 'act_activity' , __('WP-Activity Settings', 'wp-activity'), __('WP-Activity Settings', 'wp-activity'), 'manage_options', 'act_admin', 'act_admin_settings');
  add_action('admin_print_styles-' . $act_stats_page, 'act_stats_scripts');
  add_action('admin_print_styles-' . $act_admin_page, 'act_admin_scripts');
}
add_action('admin_menu', 'act_admin_menu');

function act_stats_scripts(){
  global $wp_version, $is_IE;
  wp_enqueue_style('act_datepicker', ACT_URL .'jquery.ui.datepicker.css', false, '2.5.0', 'screen');
  if ($is_IE){
    wp_enqueue_script('excanvas', ACT_URL .'js/excanvas.min.js');
  } 
  wp_enqueue_script('flot', ACT_URL .'js/jquery.flot.min.js');
  if ( version_compare($wp_version, '3.3', '<') ){
    wp_enqueue_script('act_datepicker', ACT_URL .'js/jquery.ui.datepicker.min.js', array('jquery-ui-core'), false, true);
  }else{
    wp_enqueue_script('jquery-ui-datepicker');
  }
}

function act_admin_scripts(){
  wp_enqueue_script('jquery-ui-tabs');
  wp_enqueue_style('act_tabs', ACT_URL .'jquery.ui.tabs.css', false, '2.5.0', 'screen');
}

//pagination function
function act_pagination($act_count, $limit = 50, $current, $act_start = 0, $args = ''){
  // Adapted from http://www.phpeasystep.com/phptu/29.html
	$adjacents = 1;
  if ($act_start + $limit > $act_count){
    $act_last = $act_count;
  }else{
    $act_last = $act_start + $limit;
  }
	$targetpage = "?page=act_activity".$args;
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
		if ($lastpage < (7 + $adjacents*2 ))	//not enough pages to bother breaking it up
		{	
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $current)
					$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
				else
					$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
			}
		}
		elseif($lastpage > (5 + $adjacents*2))	//enough pages to hide some
		{
			//close to beginning; only hide later pages
			if($current < (1 + $adjacents*2))		
			{
				for ($counter = 1; $counter < (4 + $adjacents*2); $counter++)
				{
					if ($counter == $current)
						$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
					else
						$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
				}
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				//$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lpm1\">$lpm1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lastpage\">$lastpage</a> ";		
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents*2) > $current && $current > ($adjacents*2))
			{
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=1\">1</a> ";
				//$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=2\">2</a> ";
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				for ($counter = $current - $adjacents; $counter <= $current + $adjacents; $counter++)
				{
					if ($counter == $current)
						$pagination.= "<span class=\"page-numbers current\">$counter</span> ";
					else
						$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$counter\">$counter</a> ";					
				}
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				//$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lpm1\">$lpm1</a> ";
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=$lastpage\">$lastpage</a> ";		
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=1\">1</a> ";
				//$pagination.= "<a class=\"page-numbers\" href=\"$targetpage&act_page=2\">2</a> ";
				$pagination.= "<span class=\"page-numbers dots\">...</span> ";
				for ($counter = $lastpage - (2 + ($adjacents*2)); $counter <= $lastpage; $counter++)
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

//main pages
function act_admin_activity(){
  global $wpdb, $act_plugin_version, $act_list_limit, $options_act;
  ?>
  <div class="wrap">
  <div id="act_admin_icon" class="icon32"></div>
  <h2>WP-Activity Log <?php if($options_act['act_feed_display']){ ?><a href="<?php echo WP_PLUGIN_URL ?>/wp-activity/wp-activity-feed.php" title="<?php echo sprintf(__('%s activity RSS Feed', 'wp-activity'),get_bloginfo('name')) ?>"><img src="<?php echo WP_PLUGIN_URL ?>/wp-activity/img/rss.png" alt="" /></a><?php } ?></h2>
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
  $act_args = $act_sqlorderby_sec = '';
  if (isset($_GET['act_type_filter'])){
    $act_type_filter = esc_html($_GET['act_type_filter']);
    $act_user_sel = esc_html($_GET['act_user_sel']);
    if ($act_user_sel <> 'all' and !empty($act_user_sel)){
      $act_user_sql_filter = 'AND users.id = '.$act_user_sel.' AND act_type <> "LOGIN_FAIL" ';
      $act_args .= '&act_user_sel='.$act_user_sel;
    }else{
      $act_user_sql_filter = '';
    }
    if ($act_type_filter <> 'all' and !empty($act_type_filter)){
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
  if ( empty($act_user_sel) )
  	$act_user_sel = 'all';
  if ( empty($act_order_by) )
  	$act_order_by = 'order_date';

  switch ($act_order_by) {
  	case 'order_user' :
  		$sqlorderby = 'display_name';
  		$sqlasc = 'ASC';
      $act_sqlorderby_sec = ', act_date DESC';
  		break;
  	case 'order_type' :
  		$sqlorderby = 'act_type';
  		$sqlasc = 'ASC';
      $act_sqlorderby_sec = ', act_date DESC';
  		break;
  	case 'order_date' :
  	default :
  		$sqlorderby = 'act_date';
  		$sqlasc = 'DESC';
  		break;
  }
  ?>
  <div id="act_recent">
    <?php
      if ($_GET['act_page'] and is_numeric($_GET['act_page'])){
        $act_page = $_GET['act_page'];
      }else{
        $act_page = 1;
      }
    ?>
    <h2><?php _e("Recent Activity", 'wp-activity'); ?> <?php if($act_feed_display){ ?><a href="<?php echo WP_PLUGIN_URL ?>/wp-activity/wp-activity-feed.php" title="<?php echo sprintf(__('%s activity RSS Feed', 'wp-activity'),get_bloginfo('name')) ?>"><img src="<?php echo WP_PLUGIN_URL ?>/wp-activity/img/rss.png" alt="" /></a><?php } ?></h2>
    <?php
      $act_start = ($act_page - 1)*$act_list_limit;
      $act_recent_sql  = "SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->users." AS users WHERE activity.user_id = users.id ".$sqlfilter." ".$act_user_sql_filter."ORDER BY ".$sqlorderby." ".$sqlasc." ".$act_sqlorderby_sec; //." LIMIT ".$act_start.",".$act_list_limit;
      if ( $logins = $wpdb->get_results($wpdb->prepare($act_recent_sql)) or !empty($act_user_sql_filter) ){
        $act_count = count($logins);
        ?>
        <form id="act-filter" action="" method="get">
          <input type="hidden" name="page" value="act_activity" />
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
              $types = array('LOGIN_FAIL', 'ACCESS_DENIED', 'CONNECT', 'POST_ADD', 'POST_EDIT', 'PROFILE_EDIT', 'COMMENT_ADD', 'LINK_ADD');
              $select_type = "<select name=\"act_type_filter\">";
              $select_type .= '<option value="all"'  . (($act_type_filter == 'all') ? " selected='selected'" : '') . '>' . __('View all') . "</option>";
              foreach ((array) $types as $type)
                $select_type .= '<option value="' . $type . '"' . (($type == $act_type_filter) ? " selected='selected'" : '') . '>' . $type . "</option>";
              $select_type .= "</select>";
              echo $select_type;
              $select_order = "<select name=\"act_order_by\">";
              $select_order .= '<option value="order_date"' . (($act_order_by == 'order_date') ? " selected='selected'" : '') . '>' .  __('Order by date (DESC)', 'wp-activity') . '</option>';
              $select_order .= '<option value="order_user"' . (($act_order_by == 'order_user') ? " selected='selected'" : '') . '>' .  __('Order by user', 'wp-activity') . '</option>';
              $select_order .= '<option value="order_type"' . (($act_order_by == 'order_type') ? " selected='selected'" : '') . '>' .  __('Order by event type', 'wp-activity') . '</option>';
              $select_order .= "</select>";
              echo $select_order;
              $act_users_sql = "SELECT ID, display_name FROM ".$wpdb->users." ORDER BY display_name ASC";
              if ( $act_u_res = $wpdb->get_results($act_users_sql)){
                $act_u_sel = "<select name=\"act_user_sel\">";
                $act_u_sel .= '<option value="all"' . (($act_user_sel == 'all') ? " selected='selected'" : '') . '>' .  __('All users', 'wp-activity') . '</option>';
                foreach ( (array) $act_u_res as $act_u ){
                  $act_u_sel .= '<option value="'.$act_u->ID.'"' . (($act_user_sel == $act_u->ID) ? " selected='selected'" : '') . '>' .  $act_u->display_name . '</option>';
                }
                $act_u_sel .= "</select>";
                echo $act_u_sel;
              }
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
                    $act_fail_tab = explode ("###", $act->act_params);
                    echo '<td><span class="activity_warning">'.$act_fail_tab[0].'</span></td><td><span class="activity_warning">'.$act->act_type.'</span></td><td>'.$act_fail_tab[1].'</td>';
                    break;
                  case 'CONNECT':
                    echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td>&nbsp;</td>';
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
                    echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td>&nbsp;</td>';
                    break;
                  case 'LINK_ADD':
                    $link = get_bookmark($act->act_params);
                    if ($link->link_visible == 'Y'){
                      echo '<td>'.$act->display_name.'</td><td>'.$act->act_type.'</td><td><a href="'.$link->link_url.'" title="'.$link->link_description.'" target="'.$link->link_target.'">'.$link->link_name.'</a></td>';
                    }
                    break;
                  case 'ACCESS_DENIED' :
                    echo '<td><span class="activity_warning">&nbsp;</span></td><td><span class="activity_warning">'.$act->act_type.'</span></td><td>'.$act->act_params.'</td>';
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
        <div class="tablenav">
          <form action="" method="post">
            
            <input type="hidden" name="act_type_filter" value="<?php echo $act_type_filter; ?>" />
            <input type="hidden" name="act_order_by" value="<?php echo $act_order_by; ?>" />
            <input type="hidden" name="act_user_sel" value="<?php echo $act_user_sel; ?>" />
            <input type="submit" class="button-primary" name="act_export" value="<?php _e('Export filtered Data &raquo;','wp-activity') ?>" />
            <input type="checkbox" name="act_del_exported" /> <?php _e('Delete exported data','wp-activity') ?> 
            <br /><span class="act_info"><?php _e('If you use MS Excel and have some ugly characters, rename the file extension to .txt and open it within Excel.','wp-activity') ?></span>
            <?php wp_nonce_field('wp-activity-export','act_export_csv'); ?>
          </form>
        <?php
        act_pagination($act_count,$act_list_limit, $act_page, $act_start, $act_args);
        echo '</div>';
        echo '<div class="clearfix"></div>';
      }else{
        echo '<h3>'.__('Activity logs are empty !','wp-activity').'</h3>';
      }
    ?>
  </div>
  <?php
}

function act_admin_settings(){
  global $wpdb, $act_plugin_version, $act_list_limit;
  ?>
  <div class="wrap">
  <div id="act_admin_icon" class="icon32"></div>
  <h2><?php _e('WP-Activity Settings', 'wp-activity') ?></h2>
  <?php
  if (isset($_POST['submit']) and check_admin_referer('wp-activity-submit','act_admin')){
    if (substr($_POST['act_author_path'], -1, 1) == '/'){
      $_POST['act_author_path'] = substr($_POST['act_author_path'], 0, -1);
    }
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
    $options_act['act_author_path']=$_POST['act_author_path'];
    $options_act['act_blacklist_on']= $_POST['act_blacklist_on'];
    $options_act['act_bl_wplog']= $_POST['act_bl_wplog'];
    $options_act['act_blacklist']= $_POST['act_blacklist'];
    $options_act['act_version']=$act_plugin_version;
    if (update_option('act_settings', $options_act)){
      echo '<div id="message" class="updated fade"><p><strong>'.__('Options saved.').'</strong></p></div>';
    }
  }elseif(isset($_POST['act-reset']) and check_admin_referer('wp-activity-reset','act_admin_reset')){
    $sql="DELETE FROM ".$wpdb->prefix."activity";
		if ( $results = $wpdb->query( $sql ) ){
		  echo '<div id="message" class="updated highlight fade"><p><strong>'.__('Activity logs deleted.', 'wp-activity').'</strong></p></div>';
		}
	}elseif(isset($_POST['act-uninst']) and check_admin_referer('wp-activity-uninst','act_admin_uninst')){
    act_desactive(); //Delete activity cron
    delete_option('act_settings'); //delete activity settings
    $sql="DROP TABLE ".$wpdb->prefix."activity"; //delete activity table
		if ( $results = $wpdb->query( $sql ) ){
		  echo '<div id="message" class="updated highlight fade"><p><strong>'.sprintf(__('Activity Plugin has been uninstalled. You can now desactivate this plugin : <a href="%s">Plugins Page</a>', 'wp-activity'),get_bloginfo('wpurl').'/wp-admin/plugins.php').'</strong></p></div>';
		}      
  }elseif(isset($_POST['act_prune_now']) and check_admin_referer('wp-activity-submit','act_admin')){
    $act_success = act_cron($_POST['act_prune']);
    if ($act_success){
      echo '<div id="message" class="updated highlight fade"><p>'.__('Manual pruning done.', 'wp-activity').'</p></div>';
    }
  }
  $act_opt=get_option('act_settings');
  $act_count = $wpdb->get_var("SELECT count(ID) FROM ".$wpdb->prefix."activity");
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
    <br />
    <div id="slider">    
      <ul id="tabs">
        <li><a href="#act_date"><?php _e('Date format', 'wp-activity') ;?></a></li>
        <li><a href="#act_display"><?php _e('Display options', 'wp-activity') ;?></a></li>
        <li><a href="#act_privacy"><?php _e('Privacy options', 'wp-activity') ;?></a></li>
        <li><a href="#act_events"><?php _e('Events logging and feeding', 'wp-activity') ;?></a></li>
        <li><a href="#act_bl"><?php _e('Blacklisting', 'wp-activity') ;?></a></li>
        <li><a href="#act_reset"><?php _e('Reset/uninstall', 'wp-activity') ;?></a></li>
      </ul>
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
            <tr>
              <th><?php _e('Author Path : ', 'wp-activity') ?></th>
              <td>
                <?php echo get_bloginfo('wpurl'); ?>/<input name="act_author_path" type="text" value="<?php echo $act_author_path ?>" />
                <br /><span class="act_info"><?php _e("If you modified your author structure link or if you use WPMu, change your path. (Default : author, BuddyPress : members)", 'wp-activity'); ?></span>
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
              <th><?php _e('Rows limit in database : ', 'wp-activity') ?></th>
              <td><input type="text" name="act_prune" value="<?php echo $act_prune ?>" /> <input class="button-secondary" type="submit" name="act_prune_now" value="<?php _e('Manually prune table', 'wp-activity') ?>" /> 
              <br /><span class="act_info"><?php echo sprintf(__('There is currently %s rows in database.','wp-activity'), $act_count); ?></span>
              </td>
            </tr><tr>
              <th><?php _e('Display activity RSS feed : ', 'wp-activity') ?> <?php if($act_feed_display){ ?><a href="<?php echo WP_PLUGIN_URL ?>/wp-activity/wp-activity-feed.php" title="<?php echo sprintf(__('%s activity RSS Feed', 'wp-activity'),get_bloginfo('name')) ?>"><img src="<?php echo WP_PLUGIN_URL ?>/wp-activity/img/rss.png" alt="" /></a><?php } ?></th><td><input type="checkbox" <?php if($act_feed_display){echo 'checked="checked"';} ?> name="act_feed_display" /></td>
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
        <div id="act_bl">
          <h2><?php _e('Blacklisting','wp-activity') ?></h2>
          <table class="form-table">
            <tr>
              <th><?php _e('Activate blacklisting', 'wp-activity') ?> : </th>
              <td>
                <input type="checkbox" <?php if($act_blacklist_on){echo 'checked="checked"';} ?> name="act_blacklist_on" />
                <br /><span class="act_info"><?php _e('Blacklisting allow to deny access to your blog for specified IP addresses.','wp-activity') ?></span>
              </td>
            </tr>
            <tr>
              <th><?php _e('Blacklist on wp-login.php only', 'wp-activity') ?> : </th>
              <td>
                <input type="checkbox" <?php if($act_bl_wplog){echo 'checked="checked"';} ?> name="act_bl_wplog" />
                <br /><span class="act_info"><?php _e('Check this option if you don\'t have any frontend login form. For more blog performance, this option should be enabled when possible.','wp-activity') ?></span>
              </td>
            </tr>
            <tr>
              <th><?php _e('IP list', 'wp-activity') ?> : </th>
              <td>
                <textarea class="large-text code" rows="6" name="act_blacklist"><?php echo $act_blacklist ?></textarea>
                <br /><span class="act_info"><?php _e('One IP per line. Don\'t blacklist your own IP ! Examples:','wp-activity') ?> 192.168.10.103, 192.168.10.*, 192.168.10.[0-9]</span>
              </td>
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
    <h4 id="act_credits"><?php echo sprintf(__('WP-Activity is a plugin by <a href="http://www.driczone.net">Dric</a>. Version <strong>%s</strong>.', 'wp-activity'), $act_plugin_version ) ?></h4>
  </div>
  <?php
}
function act_admin_stats(){
  global $wpdb, $options_act;
  if ( isset($_POST['act_date_start']) && isset($_POST['act_date_end']) && check_admin_referer('act_stats', 'act_stats')) {
    $act_d_s_tab = explode('/', esc_html($_POST['act_date_start']));
    $act_d_e_tab = explode('/', esc_html($_POST['act_date_end'])); 
    switch ($options_act['act_date_format']){
      case "dd/mm/yyyy":
        $act_date_start = $act_d_s_tab[2].'/'.$act_d_s_tab[1].'/'.$act_d_s_tab[0];
        $act_date_end = $act_d_e_tab[2].'/'.$act_d_e_tab[1].'/'.$act_d_e_tab[0];
        break;
      case "mm/dd/yyyy":
        $act_date_start = $act_d_s_tab[2].'/'.$act_d_s_tab[0].'/'.$act_d_s_tab[1];
        $act_date_end = $act_d_e_tab[2].'/'.$act_d_e_tab[0].'/'.$act_d_e_tab[1];
        break;
      default:
        $act_date_start = esc_html($_POST['act_date_start']);
        $act_date_end = esc_html($_POST['act_date_end']);
    }
  }else{
    $act_date_start = date('Y-m-d',time()-604800);
    $act_date_end = date('Y-m-d');
  }
  if ( isset($_GET['act_filter']) && check_admin_referer('wp-activity-list', 'act_stats')) {
    if ( isset($_GET['act_date_start']) && isset($_GET['act_date_end'])) {
      $act_date_start = $_GET['act_date_start'];
      $act_date_end = $_GET['act_date_end'];
    }else{
      $act_date_start = date('Y-m-d',time()-604800);
      $act_date_end = date('Y-m-d');
    }
    $act_filter = esc_html($_GET['act_filter']);  
  }else{
    $act_filter = "CONNECT";
  }
  switch ($options_act['act_date_format']){
    case "dd/mm/yyyy":
      $act_date_format_js = "dd/mm/yy";
      break;
    case "mm/dd/yyyy":
      $act_date_format_js = "mm/dd/yy";
      break;
    default:
      $act_date_format_js = "yy/mm/dd";
  }
  $sql  = "SELECT * FROM ".$wpdb->prefix."activity WHERE act_date BETWEEN '".$act_date_start."' AND '".$act_date_end." 23:59:59' ORDER BY act_type ASC, act_date ASC"; //We need to set h:m:s as they are by default 00:00:00
  if ( $act_events = $wpdb->get_results( $sql)){
    $act_events_tab = array();
    $act_type = '';
    foreach ( (array) $act_events as $act_event ){
      if ($act_event->act_type <> $act_type){
        $act_type = $act_event->act_type;
        $act_events_tab[$act_type] = 0;
      }
      $act_events_tab[$act_type] += 1;
    }
  }
  $sqlfilter  = "SELECT DATE_FORMAT(act_date, '%Y-%m-%d') as act_date FROM ".$wpdb->prefix."activity WHERE act_type = '".$act_filter."' AND act_date BETWEEN '".$act_date_start."' AND '".$act_date_end." 23:59:59' ORDER BY act_type ASC, act_date ASC"; //We need to set h:m:s as they are by default 00:00:00
  if ( $act_filter_r = $wpdb->get_results($sqlfilter)){
    $act_filter_tab = array();
    $act_date = '';
    foreach ( (array) $act_filter_r as $act_event ){
      if (strtotime($act_event->act_date) <> $act_date){
        $act_date = strtotime($act_event->act_date);
        $act_filter_tab[$act_date] = 0;
      }
      $act_filter_tab[$act_date] += 1;
    }
  }
  $act_nonce = wp_create_nonce('wp-activity-list');
  $act_tab_types = array(
    'CONNECT'       => __('Successful user login(s)', 'wp-activity'),
    'LOGIN_FAIL'    => __('Login attempt(s) failed', 'wp-activity'),
    'ACCESS_DENIED' => __('Access(es) denied', 'wp-activity'),
    'PROFILE_EDIT'  => __('Profile(s) edited', 'wp-activity'),
    'POST_ADD'      => __('Post(s) created', 'wp-activity'),
    'POST_EDIT'     => __('Post(s) edited', 'wp-activity'),
    'COMMENT_ADD'   => __('Comment(s) added', 'wp-activity'),
    'LINK_ADD'      => __('Link(s) added', 'wp-activity')
    );
  echo '<script>
        jQuery().ready(function($){
          $.datepicker.regional["'.WPLANG.'"] = {
        	closeText: "'.__('Close', 'wp-activity').'",
        	prevText: "'.__('&#x3c;Prev', 'wp-activity').'",
        	nextText: "'.__('Next&#x3e;', 'wp-activity').'",
        	currentText: "'.__('Current', 'wp-activity').'",
        	monthNames: ["'.__('January').'","'.__('February').'","'.__('March').'","'.__('April').'","'.__('May').'","'.__('June').'",
        	"'.__('July').'","'.__('August').'","'.__('September').'","'.__('October').'","'.__('November').'","'.__('December').'"],
        	monthNamesShort: ["'.__('Jan_January_abbreviation').'","'.__('Feb_February_abbreviation').'","'.__('Mar_March_abbreviation').'","'.__('Apr_April_abbreviation').'","'.__('May_May_abbreviation').'","'.__('Jun_June_abbreviation').'",
        	"'.__('Jul_July_abbreviation').'","'.__('Aug_August_abbreviation').'","'.__('Sep_September_abbreviation').'","'.__('Oct_October_abbreviation').'","'.__('Nov_November_abbreviation').'","'.__('Dec_December_abbreviation').'"],
        	dayNames: ["'.__('Sunday').'","'.__('Monday').'","'.__('Tuesday').'","'.__('Wednesday').'","'.__('Thursday').'","'.__('Friday').'","'.__('Saturday').'"],
        	dayNamesShort: ["'.__('Sun').'","'.__('Mon').'","'.__('Tue').'","'.__('Wed').'","'.__('Thu').'","'.__('Fri').'","'.__('Sat').'"],
        	dayNamesMin: ["'.__('S_Sunday_initial').'","'.__('M_Monday_initial').'","'.__('T_Tuesday_initial').'","'.__('W_Wednesday_initial').'","'.__('T_Thursday_initial').'","'.__('F_Friday_initial').'","'.__('S_Saturday_initial').'"],
        	firstDay: '.get_option('start_of_week').',
        	showMonthAfterYear: false,
        	yearSuffix: ""};
          $.datepicker.setDefaults($.datepicker.regional["'.WPLANG.'"]);
        });
        </script>'; 
 echo '<script>
        jQuery().ready(function ($) {
          var dates = $( "#act_date_start, #act_date_end" ).datepicker({
            dateFormat: "'.$act_date_format_js.'",
            changeMonth: true,
            changeYear: true,
        		numberOfMonths: 1,
        		onSelect: function( selectedDate ) {
        			var option = this.id == "act_date_start" ? "minDate" : "maxDate",
        				instance = $( this ).data( "datepicker" ),
        				date = $.datepicker.parseDate(
        					instance.settings.dateFormat ||
        					$.datepicker._defaults.dateFormat,
        					selectedDate, instance.settings );
        			dates.not( this ).datepicker( "option", option, date );
        		}
        	});
        });
      </script>';
  ?>
  
  <div class="wrap">
    <div id="act_admin_icon" class="icon32"></div>
    <h2><?php _e('Activity Stats', 'wp-activity') ?></h2>
    <br />
    <div class="tablenav">
      <form id="act_stats" method="post" action="?page=act_stats">
        <p class="search-box">
          <?php _e("Date range", 'wp-activity'); ?> : 
          <input type="text" id="act_date_start" name="act_date_start" value="<?php echo nicetime($act_date_start, true, true) ?>" />
          <input type="text" id="act_date_end" name="act_date_end" value="<?php echo nicetime($act_date_end, true, true) ?>" />
          <input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
          <?php wp_nonce_field('act_stats', 'act_stats') ?>
        </p>
      </form>
      <br class="clear" />
    </div>
    <div id="act_wrap">
      <div id="dashboard-widgets" class="metabox-holder">
        <div id="dashboard_right_now" class="postbox">
          <h3><?php _e('Activity Stats', 'wp-activity') ?></h3>
          <div class="inside">
            <div class="table table_content" style="width: 25%">
              <p class="sub"><?php _e('Events number :', 'wp-activity'); ?></p>
              <table>
                <tbody>
                  <?php 
                    foreach ($act_tab_types as $act_tab_type => $act_tab_label){
                      if (($act_tab_type <> 'LOGIN_FAIL' OR $options_act['act_log_failures']) AND ($act_tab_type <> 'ACCESS_DENIED' OR $options_act['act_blacklist_on'])) {
                        $act_class ='';
                        if ($act_tab_type == 'LOGIN_FAIL' or $act_tab_type == 'ACCESS_DENIED') $act_class = 'class="spam"';
                        if ($act_tab_type == $act_filter) $act_class = 'class="waiting"';
                      ?>
                      <tr>
                        <td class="first b"><a <?php echo $act_class ?> href="?page=act_stats&act_stats=<?php echo $act_nonce ?>&act_filter=<?php echo $act_tab_type ?>&act_date_start=<?php echo $act_date_start ?>&act_date_end=<?php echo $act_date_end ?>"><?php echo ($act_events_tab[$act_tab_type]) ? $act_events_tab[$act_tab_type] : '0'; ?></a></td>
                        <td class="t"><a <?php echo $act_class ?> href="?page=act_stats&act_stats=<?php echo $act_nonce ?>&act_filter=<?php echo $act_tab_type ?>&act_date_start=<?php echo $act_date_start ?>&act_date_end=<?php echo $act_date_end ?>"><?php echo $act_tab_label ?></a> <a title="<?php echo __('See data for', 'wp-activity').' : '.$act_tab_label ?>" href="?page=act_activity&act_filter=<?php echo $act_nonce ?>&act_type_filter=<?php echo $act_tab_type ?>"><img class="act_data_report_icon" src="<?php echo WP_PLUGIN_URL ?>/wp-activity/img/report_data.png" alt="" /></a></td>
                      </tr>
                      <?php
                      }
                    } 
                  ?>
                </tbody>
              </table>
            </div>
            <div class="table table_discussion" style="width: 65%">
              <p class="sub"><?php echo $act_tab_types[$act_filter] ?></p>
              <div id="act_cat_graphs" style="width:98%;height:250px;"></div>
              <script type="text/javascript">
                jQuery().ready(function ($) {
                  xmin = <?php echo number_format(strtotime($act_date_start)*1000, 0, '.', '') ?>;
                  xmax = <?php echo number_format(strtotime($act_date_end." 23:59:59")*1000, 0, '.', '') ?>;
                  var d1 = [
                  <?php
                    $act_disp = ''; 
                    foreach ($act_filter_tab as $act_date => $act_number){
                      $act_date = $act_date*1000;
                      $act_disp .= "[".number_format($act_date, 0, '.', '').", ".$act_number."],";
                    }
                    $act_disp = rtrim($act_disp, ",");
                    echo $act_disp;
                  ?>
                    ];
                  $.plot($("#act_cat_graphs"), [
                    {
                        data: d1,
                        label: "<?php echo $act_tab_types[$act_filter] ?>",
                        bars: { show: true, barWidth : 24*60*60*1000 },
                        color: "#a3bcd3"
                    }
                  ],
                    {
                        xaxis: {
                            mode: "time",
                            minTickSize: [1, "day"],
                            tickLength: 0,
                            min: xmin,
                            max: xmax
                        },
                        yaxis: { 
                            tickDecimals: 0,
                            min: 0
                        },
                        grid: {
                            backgroundColor: { colors: ["#fff", "#eee"] },
                            hoverable: true
                        },
                        legend: {
                            show: false
                        }
                    }
                  );
                  function showTooltip(x, y, contents) {
                      $('<div id="tooltip">' + contents + '</div>').css( {
                          position: 'absolute',
                          display: 'none',
                          top: y + 5,
                          left: x + 5,
                          border: '1px solid #ccc',
                          padding: '2px',
                          'background-color': '#fff',
                          opacity: 0.80
                      }).appendTo("body").fadeIn(200);
                  }
              
                  var previousPoint = null;
                  $("#act_cat_graphs").bind("plothover", function (event, pos, item) {
                      $("#x").text(pos.x.toFixed(2));
                      $("#y").text(pos.y.toFixed(2));
                          if (item) {
                              if (previousPoint != item.dataIndex) {
                                  previousPoint = item.dataIndex;
                                  
                                  $("#tooltip").remove();
                                  var x = item.datapoint[0].toFixed(2),
                                      y = item.datapoint[1].toFixed(2);
                                  var actDate = new Date();
                                  actDate.setTime(x);
                                  showTooltip(item.pageX, item.pageY, actDate.toDateString() + "<br />" + item.series.label + " : " + parseFloat(y));
                              }
                          }
                          else {
                              $("#tooltip").remove();
                              previousPoint = null;            
                          }
                  });
                });
              </script>
            </div>
            <div class="versions"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
}
?>