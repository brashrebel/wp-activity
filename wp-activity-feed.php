<?php

$wpcontentdir = "wp-content"; //You have to change this if you renamed your wp-content directory.


$script_filename = dirname($_SERVER["DOCUMENT_ROOT"].$_SERVER['PHP_SELF']); //$_ENV["SCRIPT_FILENAME"];
$cut = strpos($script_filename, "/".$wpcontentdir."/plugins/");
$path_tab = str_split($script_filename, $cut);
require($path_tab[0]."/wp-blog-header.php");

header('Content-Type: application/rss+xml ; charset=UTF-8', true);
$options_act = get_option('act_settings');
function act_feed(){
  global $wpdb, $options_act;
  extract($options_act);
  $act_feed = wp_cache_get( 'act_feed' );
  if (!$act_feed) {
    $date = date('r', strtotime($wpdb->get_var("SELECT MAX(act_date) FROM ".$wpdb->prefix."activity")));
    $users = $wpdb->get_results("SELECT ID, display_name, user_nicename FROM $wpdb->users");
    $wp_url = get_bloginfo('wpurl');
    foreach ($users as $user) {
  		$users_nicename[$user->ID]=$user->user_nicename;
  		$users_display[$user->ID]=$user->display_name;
  	}  
    $sql="SELECT * FROM ".$wpdb->prefix."activity AS activity, ".$wpdb->prefix."users AS users WHERE activity.user_id = users.id AND activity.act_type <> 'LOGIN_FAIL' ORDER BY activity.act_date DESC";
    if ( $items = $wpdb->get_results($sql)){
      $cache = '<?xml version="1.0" encoding="utf-8"?>';
      $cache .= '<rss version="2.0"	xmlns:content="http://purl.org/rss/1.0/modules/content/"	xmlns:wfw="http://wellformedweb.org/CommentAPI/"	xmlns:dc="http://purl.org/dc/elements/1.1/"	xmlns:atom="http://www.w3.org/2005/Atom"	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"	>';
      $cache .= '<channel>';
      $cache .= '<title>'.attribute_escape(strip_tags(html_entity_decode(sprintf(__('%s activity RSS Feed', 'wp-activity'), get_bloginfo('name'))))).'</title>';
      $cache .= '<link>'.$wp_url.'</link>';
      //$cache .= '<atom:link href="http://dallas.example.com/rss.xml" rel="self" type="application/rss+xml" />
      $cache .= '<description><![CDATA['.sprintf(__('User events of %s', 'wp-activity'), get_bloginfo('name')).']]></description>';
      $cache .= '<lastBuildDate>'.$date.'</lastBuildDate>';
      $cache .= '<language>'.get_bloginfo('language').'</language>';
      foreach ( (array) $items as $item ){
        $flag= false;
        $item_date = date('r', strtotime($item->act_date));
        $user_nicename = $item->user_nicename;
        switch ($item->act_type){
          case 'CONNECT':
            $flag = ($act_feed_connect) ? true : false ;
            $item_title = __('New visit', 'wp-activity');
            $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('has logged.', 'wp-activity');
            break;
          case 'COMMENT_ADD':
            $flag = ($act_feed_comments) ? true : false ;
            $item_title = __('New comment', 'wp-activity');
            $item_comment=get_comment($item->act_params);
            $item_post=get_post($item_comment->comment_post_ID);
            $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('commented', 'wp-activity').' <a href="'.get_permalink($item_post->ID).'#comment-'.$item_comment->comment_ID.'">'.$item_post->post_title.'</a>';              
            break;
          case 'POST_ADD':
            $flag = ($act_feed_posts) ? true : false ;
            $item_title = __('New post', 'wp-activity');
            $item_post=get_post($item->act_params);
            $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('published', 'wp-activity').' <a href="'.get_permalink($item_post->ID).'">'.$item_post->post_title.'</a>';
            break;
          case 'POST_EDIT':
            $flag = ($act_feed_posts) ? true : false ;
            $item_title = __('Post edited', 'wp-activity');
            $item_post=get_post($item->act_params);
            $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('edited', 'wp-activity').' <a href="'.get_permalink($item_post->ID).'">'.$item_post->post_title.'</a>';
            break;
          case 'PROFILE_EDIT':
            $flag = ($act_feed_profiles) ? true : false ;
            $item_title = __('Profile edited', 'wp-activity');
            $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('has updated his profile.', 'wp-activity');
            break;
          case 'LINK_ADD':
            $flag = ($act_feed_links) ? true : false ;
            $item_title = __('New link', 'wp-activity');
            $link = get_bookmark($item->act_params);
            if ($link->link_visible == 'Y'){
              $item_desc = '<a href="'.$wp_url.'/author/'.$user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$item->displayname.'</a> '.__('has added a link to', 'wp-activity').' <a href="'.$link->link_url.'" title="'.$link->link_description.'" target="'.$link->link_target.'">'.$link->link_name.'</a>.';
            }
            break;
        }
        if ($flag){
          $cache .='<item>';
          $cache .='<title>'.$item_title.'</title>';
          $cache .='<pubDate>'.$item_date.'</pubDate>';
          $cache .='<description><![CDATA[<p>'.attribute_escape(strip_tags(html_entity_decode($item_desc))).'</p>]]></description>';
          $cache .='<content:encoded><![CDATA[<div style="float:left; margin:1em">'.get_avatar($item->user_id,40).'</div><p>'.attribute_escape(strip_tags(html_entity_decode($item_desc))).'</p><div style="clear:both;"></div>]]></content:encoded>';
          $cache .='<dc:creator>'.$item->displayname.'</dc:creator>';
          $cache .='<link>'.$wp_url.'</link>';
          $cache .='</item>';
        }
      }
      $cache .='</channel>';
      $cache .='</rss>';
    }
    wp_cache_set( 'act_feed', $cache, '3600' );
    return $cache;
  }else{
    return $act_feed;
  }
}
if ($options_act['act_feed_display']) {
  echo act_feed();
}else{
  echo "Error. Activity feed not allowed !";
}
  
?>
