<?php
/*
Plugin Name: WP Content Source Control
Plugin URI: http://wordpress.org/extend/plugins/wp-source-control/
Description: Source Control For Your Theme Directory And Posts/Pages.
Version: 3.1.1
Author: TheOnlineHero - Tom Skroza
Author URI: http://theonlinehero-developer.blogspot.com.au/
License: GPL2
*/

if (!class_exists("SCTomM8")) {
  require_once('lib/tom-m8te.php'); 
}

require_once('source_control_path.php'); 
require_once("source_control_template_diff.php");
require_once("source_control_post_diff.php");
add_action('admin_menu', 'register_source_control_page');

function register_source_control_page() {
		
	add_menu_page('WP Source Control', 'WP Source Control', 'update_themes', 'wp-source-control/source_control_list.php', '',  '', 1);
	add_submenu_page('wp-source-control/source_control_list.php', 'Search Commit', 'Search Commit', 'update_themes', 'wp-source-control/source_control_search.php');
	add_submenu_page('wp-source-control/source_control_list.php', 'Backup / Restore', 'Backup / Restore', 'update_themes', 'wp-source-control/source_control_backup.php');
	add_submenu_page('wp-source-control/source_control_list.php', 'Clean Up', 'Clean Up', 'update_themes', 'wp-source-control/source_control_clean_up.php');

}

function wp_content_source_activate() {
   
   global $wpdb;
   $table_name = $wpdb->prefix . "version_controls";

   $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
job_no VARCHAR(50) DEFAULT '', 
description VARCHAR(255) DEFAULT '', 
theme_timestamp bigint,
PRIMARY KEY  (id)
);";

   $wpdb->query($sql);

   $table_name = $wpdb->prefix . "version_control_templates";

    // Save current template file in version_control_template
    $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
job_id mediumint(9), 
orig_file_name VARCHAR(255) DEFAULT '',
file_name VARCHAR(255) DEFAULT '', 
template_timestamp bigint,
diff_file VARCHAR(255) NOT NULL,
PRIMARY KEY  (id)
);";

    $wpdb->query($sql);

    $table_name = $wpdb->prefix . "version_control_posts";

   $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
revision_id mediumint(9), 
post_id mediumint(9),
job_id mediumint(9) DEFAULT 0,
job_deleted mediumint(9) DEFAULT 0,
current_content longtext NOT NULL,
diff_content longtext NOT NULL,
PRIMARY KEY  (id)
);";

    $wpdb->query($sql);    

    $table_name = $wpdb->prefix."version_control_templates";
    $results = $wpdb->get_results( "Select * from $table_name" );
    
    foreach ( $results as $r ) {
      // upgrade database data.
      $base_path = Path::normalize(dirname(__FILE__).'../../../');
      $orig_file_name = str_replace($base_path, "", $r->orig_file_name);
      $wpdb->update($table_name, array('orig_file_name' => $orig_file_name), array('id' => $r->id));

      $file_name = str_replace($base_path."/plugins/wp-source-control", "", $r->file_name);
      $wpdb->update($table_name, array('file_name' => $file_name), array('id' => $r->id));

      $diff_file = str_replace($base_path."/plugins/wp-source-control", "", $r->diff_file);
      $wpdb->update($table_name, array('diff_file' => $diff_file), array('id' => $r->id));
    }

    @mkdir(Path::normalize(dirname(__FILE__).'../../../downloadfiles/'));
    copy_directory(Path::normalize(dirname(__FILE__).'/downloadfiles'), Path::normalize(dirname(__FILE__).'../../../downloadfiles/'));
    
    
    add_option( "source_control_version", "2.2", "", "yes" );
    
    $table_name = $wpdb->prefix . "version_control_templates";
    $sql = "ALTER TABLE $table_name ADD deleted VARCHAR(1)";
    $wpdb->query($sql);
    
    $table_name = $wpdb->prefix . "version_controls";
    $sql = "ALTER TABLE $table_name ADD deleted VARCHAR(1)";
    $wpdb->query($sql);

}
register_activation_hook( __FILE__, 'wp_content_source_activate' );

function copy_directory($src,$dst) { 
    $dir = opendir($src); 
    try{
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    copy_directory($src . '/' . $file,$dst . '/' . $file); 
                } else { 
                    copy($src . '/' . $file,$dst . '/' . $file);
                } 
            }   
        }
        closedir($dir); 
    } catch(Exception $ex) {
        return false;
    }
    return true;
}

function rrmdir($dir) {
  // if the path has a slash at the end we remove it here
  if(substr($directory,-1) == '/')
  {
      $directory = substr($directory,0,-1);
  }
  
  // if the path is not valid or is not a directory ...
  if(!file_exists($directory) || !is_dir($directory))
  {
      // ... we return false and exit the function
      return false;
  
  //if the path is not readable
  }elseif(!is_readable($directory))
  {
      // ... we return false and exit the function
      return false;
  
  // ... else if the path is readable
  }else{
  
      // we open the directory
      $handle = opendir($directory);
  
      // and scan through the items inside
      while (false !== ($item = readdir($handle)))
      {
          // if the filepointer is not the current directory
          // or the parent directory
          if($item != '.' && $item != '..')
          {
              // we build the new path to delete
              $path = $directory.'/'.$item;
  
              // if the new path is a directory
              if(is_dir($path)) 
              {
                  // we call this function with the new path
                  rrmdir($path);
  
              // if the new path is a file
              }else{
                  // we remove the file
                  unlink($path);
              }
          }
      }
      // close the directory
      closedir($handle);
  
      // try to delete the now empty directory
      if(!rmdir($directory))
      {
          // return false if not possible
          return false;
      }
      
      // return success
      return true;
  }
}

add_action( 'save_post', 'save_post_version' );
function save_post_version( $postid ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";  
    
    $post_table = $wpdb->prefix . "posts";
    $my_revision = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $post_table WHERE post_type='revision' AND id= %d", $postid) );
    $my_post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $post_table WHERE post_type IN ('page', 'post') AND id=%d", $my_revision->post_parent) );
    
    $post_version = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d AND job_id = %d", $my_post->ID, 0));
    
    if (!$post_version) {
      $rows_affected = $wpdb->insert( $table_name, array( 'revision_id' => $postid, 'post_id'=> $my_post->ID));
    }    
}
add_action( 'trashed_post', 'delete_post_version' );
function delete_post_version( $postid ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";  
    $rows_affected = $wpdb->insert( $table_name, array( 'revision_id' => $postid, 'job_deleted' => 1));
}


function create_theme_snapshot($templates, $post_ids, $job_no, $description) { 
    if (!($post_ids == null && $templates == null)) {
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('UTC'));
        $date_time_stamp = $date->getTimestamp();
        //echo (Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp));
        @mkdir(Path::normalize(dirname(__FILE__).'../../../source_control/'));
        @mkdir(Path::normalize(dirname(__FILE__).'../../../source_control/version_'.$date_time_stamp));
        @mkdir(Path::normalize(dirname(__FILE__).'../../../source_control/version_'.$date_time_stamp."/themes"));
        @mkdir(Path::normalize(dirname(__FILE__).'../../../source_control/version_'.$date_time_stamp."/uploads"));
        
        $theme_file = Path::normalize(dirname(__FILE__)."../../../themes");
        // $upload_file = dirname(__FILE__)."../../../uploads";
        $new_theme_file = Path::normalize(dirname(__FILE__).'../../../source_control/version_'.$date_time_stamp."/themes");
        // $new_upload_file = dirname(__FILE__).'../version_'.$date_time_stamp."/uploads";

        global $wpdb;
        $table_name = $wpdb->prefix . "version_controls";
        // Save job no, timestamp to theme_version_control database.
        $rows_affected = $wpdb->insert( $table_name, array( 'job_no' => $job_no, 'description' => $description, 'theme_timestamp' => $date_time_stamp) );
        $job_id = $wpdb->insert_id;
        $table_name = $wpdb->prefix . "version_control_posts";
        
        $post_table_name = $wpdb->prefix . "posts";
        
        if ($post_ids != null) {
            foreach ($post_ids as $revision_id) {
              $my_revision = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $post_table_name WHERE post_type='revision' AND id= %d", $revision_id) );
              $my_post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $post_table_name WHERE post_type IN ('page', 'post') AND id=%d", $my_revision->post_parent) );
              $results = $wpdb->get_results("SELECT * FROM $post_table_name WHERE post_type='revision' AND post_parent = ".$my_post->ID);
              
              foreach($results as $result) {
                $wpdb->update($table_name, array('job_id' => $job_id, 'current_content' => $my_post->post_content,'diff_content' => get_diff_post($revision_id)), array( 'job_id' => 0, 'revision_id' => $revision_id));
              }
            }
        }
        if ($templates != null) {
          if (!snapshot_directory($templates, $wpdb->insert_id, $theme_file, $new_theme_file)) {
              echo "failed to copy $theme_file...\n";
          } 
            // if (!snapshot_directory($wpdb->insert_id, $upload_file, $new_upload_file)) {
            //     echo "failed to copy $upload_file...\n";
            // } 
        }
    }
}


function print_updated_template_files($src) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                print_updated_template_files($src . '/' . $file); 
            } 
            else { 
                $formatted_src = str_replace("/", "::::", $src.'/'.$file);
                $base_path = Path::normalize(dirname(__FILE__).'../../../');
                $src_file = str_replace($base_path, "", $src . '/' . $file);
                
                $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src_file) );
                if ($my_template != null){
                    if ($my_template->template_timestamp < filemtime($src.'/'.$file)) {
                        echo "<tr><td><input type=\"checkbox\" name=\"checkin_templates[]\" value=\"$formatted_src\"></td><td style='width: 800px;'>".$src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."</td><td><a href=\"".(get_option('siteurl'))."/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=current_template_diff&id=".$my_template->id."\">Diff</a></td></tr>";
                    }
                } else {
                    echo "<tr><td><input type=\"checkbox\" name=\"checkin_templates[]\" value=\"$formatted_src\"></td><td style='width: 800px;'>".$src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."</td></tr>";
                }
                 
            } 
        } 
    } 
    closedir($dir); 
    return $content;

}

function get_updated_template_files($src) {
    global $wpdb;
    $content = "";
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                $content .= get_updated_template_files($src . '/' . $file); 
            } 
            else { 
                $base_path = Path::normalize(dirname(__FILE__).'../../../');
                $src_file = str_replace($base_path, "", $src . '/' . $file);
                
                $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src_file) );
                if ($my_template != null){
                    if ($my_template->template_timestamp < filemtime($src.'/'.$file)) {
                        $content .= $src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."::::";
                    }
                } else {
                    $content .= $src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."::::";
                }
                 
            } 
        } 
    } 
    closedir($dir); 
    return $content; 
}


function snapshot_directory($templates, $job_id,$src,$dst) { 
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    try{
        @mkdir($dst); 
        @mkdir($dst."/diff/");
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    snapshot_directory($templates, $job_id, $src . '/' . $file,$dst . '/' . $file); 
                } 
                else { 
                    // Check if user wants to check the file in.
                    if (in_array(str_replace("/", "::::", $src.'/'.$file), $templates)) {
                        // User does want to check file in.
                        $base_path = Path::normalize(dirname(__FILE__).'../../../');
                        $src_file = str_replace($base_path, "", $src . '/' . $file);

                        $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src_file) );
                        

                        // Check if file exists
                        if ($my_template != null){

                            // File exists
                            if ($my_template->template_timestamp != filemtime($src.'/'.$file)) {
                                
                                $base_path = Path::normalize(dirname(__FILE__).'../../../');
                                $src_file = str_replace($base_path, "", $src . '/' . $file);
                                $dst_file = str_replace($base_path."/source_control", "", $dst . '/' . $file);
                                $dst_diff_file = str_replace($base_path."/source_control", "", $dst . '/diff/' . $file);
                                
                                $rows_affected = $wpdb->insert( $table_name, array( 'job_id'=>$job_id, 'orig_file_name' => $src_file, 'file_name' => $dst_file, 'template_timestamp' => filemtime($src.'/'.$file), 'diff_file' => $dst_diff_file) );
                                @copy($src . '/' . $file, $dst . '/' . $file);
                                
                                if (preg_match("/.php$|.js$|.css$|.txt$/", $my_template->file_name)) {
                                  create_diff_file($base_path."/source_control/".$my_template->file_name, $dst . '/' . $file, $dst . '/diff/' . $file);
                                }
                            }
                        } else if ($my_template == null) {
                            
                            // File Does not exist, So add it in.
                            $base_path = Path::normalize(dirname(__FILE__).'../../../');
                            $src_file = str_replace($base_path, "", $src . '/' . $file);
                            $dst_file = str_replace($base_path."/source_control", "", $dst . '/' . $file);
                            $dst_diff_file = str_replace($base_path, "", $dst . '/diff/' . $file);
                            $rows_affected = $wpdb->insert( $table_name, array( 'job_id'=>$job_id, 'orig_file_name' => $src_file, 'file_name' => $dst_file, 'template_timestamp' => filemtime($src.'/'.$file)) );
                            @copy($src . '/' . $file,$dst . '/' . $file);
                        } 
                    }
                     
                } 
            } 
        } 
        closedir($dir); 
    } catch(Exception $ex) {
        return false;
    }
    return true;
}

?>