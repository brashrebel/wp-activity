<?php

$wpcontentdir = "wp-content"; //You have to change this if you renamed your wp-content directory.


$script_filename = dirname($_SERVER["DOCUMENT_ROOT"].$_SERVER['PHP_SELF']); //$_ENV["SCRIPT_FILENAME"];
$cut = strpos($script_filename, "/".$wpcontentdir."/plugins/");
$path_tab = str_split($script_filename, $cut);
require($path_tab[0]."/wp-blog-header.php");

header("Content-Type: application/csv-tab-delimited-table");
header("Content-disposition: filename=wp-activity.csv");

if (current_user_can('administrator')){
  function act_export(){
    global $wpdb;
    if(isset($_POST['act_export']) and check_admin_referer('wp-activity-export','act_export_csv')){
      $act_sqlorderby_sec = '';
      if (isset($_POST['act_type_filter'])){
        $act_type_filter = esc_html($_POST['act_type_filter']);
        if ($act_type_filter <> 'all'){
          $sqlfilter = 'AND act_type = "'.$act_type_filter.'"';
        }
      }else{
        $sqlfilter = '';
      }
      $act_user_sel = esc_html($_POST['act_user_sel']);
      if ($act_user_sel <> 'all'){
        $act_user_sql_filter = 'AND users.id = '.$act_user_sel.' AND act_type <> "LOGIN_FAIL" ';
      }else{
        $act_user_sql_filter = '';
      }
      if (isset($_POST['act_order_by'])){
        $act_order_by = esc_html($_POST['act_order_by']);
      }else{
      	$act_order_by = 'order_date';
      }
    
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
      $act_recent_sql  = "SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->users." AS users WHERE activity.user_id = users.id ".$sqlfilter." ".$act_user_sql_filter."ORDER BY ".$sqlorderby." ".$sqlasc." ".$act_sqlorderby_sec;
      if ( $logins = $wpdb->get_results($wpdb->prepare($act_recent_sql))){
        echo __("Date", 'wp-activity').';'.__("User", 'wp-activity').';'.__("Event Type", 'wp-activity').';'.__("Applies to", 'wp-activity').";\n";
        foreach ( (array) $logins as $act ){
           echo $act->act_date.';';
           switch ($act->act_type){
            case 'LOGIN_FAIL' :
              echo $act->act_params.';'.$act->act_type.';;';
              break;
            case 'CONNECT':
              echo $act->display_name.';'.$act->act_type.';;';
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
          $del_sql = "DELETE FROM ".$wpdb->prefix."activity WHERE 1=1 ".$sqlfilter." ".$act_user_sql_filter; //stupid condition 1=1 to avoid rewriting filters vars.
          $wpdb->query($wpdb->prepare($del_sql));
          
        }  
      }
    }
  }
  act_export();
}  
?>
