<?php
function act_export(){
  global $wpdb;
  if(isset($_POST['act_export']) and check_admin_referer('wp-activity-export','act_export_csv')){
    $act_sqlorderby_sec = '';
    if (isset($_POST['act_type_filter'])){
    $act_type_filter = esc_html($_POST['act_type_filter']);
    $act_user_sel = esc_html($_POST['act_user_sel']);
    if ($act_user_sel <> 'all' and !empty($act_user_sel)){
      $sql_username = get_userdata($act_user_sel);
      $sqlfilter .= ' AND u.id = '.$act_user_sel.' AND act_type NOT IN ("LOGIN_FAIL", "ACCESS_DENIED")';
      $act_args .= '&act_user_sel='.$act_user_sel;
    }
    if ($act_type_filter <> 'all' and !empty($act_type_filter)){
      $sqlfilter .= 'AND act_type = "'.$act_type_filter.'"';
    }
    $act_args .= '&act_type_filter='.$act_type_filter;
    if ($act_type_filter == 'LOGIN_FAIL' or $act_type_filter == 'all'){
      $sqlfilter .= ') UNION ALL (SELECT null as display_name, user_id as id, act_type, act_date, act_params, id FROM '.$wpdb->prefix.'activity WHERE act_type = "LOGIN_FAIL" AND SUBSTRING_INDEX(act_params, "###", 1) = "'.$sql_username->display_name.'"';
    }
  }
  $sqlfilter .= ')';
  if (isset($_POST['act_order_by'])){
    $act_order_by = esc_html($_POST['act_order_by']);
  }else{
    $act_order_by = 'order_date';
  }

  switch ($act_order_by) {
  	case 'order_user' :
  		$sqlorderby = 'display_name ASC, act_date DESC';
  		break;
  	case 'order_type' :
  		$sqlorderby = 'act_type ASC, act_date DESC';
  		break;
  	case 'order_date' :
  	default :
  		$sqlorderby = 'act_date DESC';
  		break;
  }

    $act_recent_sql  = "(SELECT u.display_name as display_name, u.id as id, act_type, act_date, act_params, a.id as act_id FROM ".$wpdb->prefix."activity AS a, ".$wpdb->users." AS u WHERE a.user_id = u.id ".$sqlfilter." ORDER BY ".$sqlorderby;
    if ( $logins = $wpdb->get_results($wpdb->prepare($act_recent_sql))){
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private",false);
      header("Content-Type: application/csv-tab-delimited-table; charset=utf-8");
      header("Content-Disposition: attachment; filename=wp-activity.csv");
      header("Content-Transfer-Encoding: binary");
      echo __("Date", 'wp-activity').';'.__("User", 'wp-activity').';'.__("Event Type", 'wp-activity').';'.__("Applies to", 'wp-activity').";\n";
      foreach ( (array) $logins as $act ){
         $act_id_tab[] = $act->act_id;
         echo $act->act_date.';';
         switch ($act->act_type){
          case 'LOGIN_FAIL' :
            $act_fail_tab = explode ("###", $act->act_params);
            echo $act_fail_tab[0].';'.$act->act_type.';'.$act_fail_tab[1].';';
            break;
          case 'CONNECT':
            echo $act->display_name.';'.$act->act_type.';'.$act->act_params.';';
            break;
          case 'COMMENT_ADD':
            $act_comment=get_comment($act->act_params);
            $act_post=get_post($act_comment->comment_post_ID);
            echo $act->display_name.';'.$act->act_type.';'.$act_post->post_title.';';
            break;
          case 'POST_ADD':
          case 'POST_EDIT':
            $act_post=get_post($act->act_params);
            echo $act->display_name.';'.$act->act_type.';'.$act_post->post_title.';';
            break;
          case 'PROFILE_EDIT':
            echo $act->display_name.';'.$act->act_type.';;';
            break;
          case 'LINK_ADD':
            $link = get_bookmark($act->act_params);
            if ($link->link_visible == 'Y'){
              echo $act->display_name.';'.$act->act_type.';'.$link->link_name.';';
            }
            break;
          default:
            break;
        }
        echo "\n"; 
      }
      //delete exported data if requested
      if ($_POST['act_del_exported'] == true ){
        $act_del = implode(",", $act_id_tab);
        $del_sql = "DELETE FROM ".$wpdb->prefix."activity WHERE id IN(".$act_del.")";
        $wpdb->query($wpdb->prepare($del_sql));
      }
    }else{
      echo 'Zombie frenzy ! They gonna eat our brains ! ...No, in fact something goes wrong with the sql query : '.$wpdb->print_error();
    }
  }else{
    echo "Alien Invasion ! We all gonna die ! ...No, in fact this is a security check failure.";
  }
  die();
}
?>