<?php 
  require_once('source_control_path.php');
  wp_enqueue_script('jquery');

  wp_register_script( 'my-source-control-admin', plugins_url('admin_source_control.js', __FILE__) );
  wp_enqueue_script('my-source-control-admin');
  
  global $wpdb;

  $table_name = $wpdb->prefix . "version_controls";
  $version = $wpdb->get_row("SELECT * FROM $table_name WHERE deleted IS NULL ORDER BY theme_timestamp ASC LIMIT 1");
  
  $template_table = $wpdb->prefix . "version_control_templates";
  
  $my_templates = $wpdb->get_results($wpdb->prepare("SELECT * FROM $template_table WHERE job_id = %d",$version->id));

  $post_table = $wpdb->prefix . "posts";
  $version_post_table = $wpdb->prefix . "version_control_posts";
  $posts_changed = $wpdb->get_results("SELECT * FROM $post_table as p, $version_post_table as v WHERE p.id = v.revision_id AND v.job_id = '".$version->id."' AND (p.post_type = 'revision') OR (p.post_type IN ('post', 'page') AND p.post_status = 'trash') ORDER BY p.post_modified ASC");
  
  if (!$version) {
    echo "<div id=\"message\" class=\"updated below-h2\"><p>You don't have any more commits to delete.</p></div>";
    exit();
  }
  
  if ($_POST["submit"] == "Delete Oldest Commit") {
    $root_dir = Path::normalize(dirname(__FILE__).'../../../../');
    foreach($my_templates as $template) {
          $wpdb->update($template_table, array('deleted' => "1"), array('id' => $template->id));
    }

    rrmdir($root_dir."/wp-content/source_control/version_".$version->theme_timestamp); 
    
    foreach($posts_changed as $post) {
      $wpdb->query($wpdb->prepare("DELETE FROM $version_post_table WHERE id = %d", $post->id));
    }
    $wpdb->update($table_name, array('deleted' => "1"), array('id' => $version->id));
    echo("<div id=\"message\" class=\"updated below-h2\"><p>Oldest commit was deleted</p></div>");  
  } else { ?>
  <style>
	.inside th {
		text-align: left;
	}
	.inside table {
		margin-left: 10px;
	}
	tbody tr.even td, tr.odd th {
		background: #cac9c9;
	}
	tbody tr.odd td, tr.odd th {
		background: #dfdfdf;
	}
	.inside table {
		width: 100%;
	}
	</style>
  <div class="wrap">
  <h2>WP Source Control Clean Up</h2>
  <p>This page allows you to delete your oldest commit. Overtime your commits will take up too much space on your server, so if they are really old, delete them here.</p>
  <p>Scroll down to the bottom of the page and click "Delete Oldest Commit"</p>
  <div class="postbox" style="display: block;">
  <div class="inside">
  <form action="" method="post">
    <p>Oldest Commit</p>
		<h4><?php echo(date('l jS \of F Y h:i:s A', $version->theme_timestamp)); ?> UTC &#8211; <?php echo($version->job_no); ?> &#8211; <?php echo($version->description); ?></h4>
		<table class="data">
		  	<thead>
		  		<tr>
		  			<th scope="col">Name</th>
		  			<th></th>
		  			<th></th>
		  		</tr>
		  	</thead>
		  	<tbody>
            <?php foreach ($my_templates as $template) { ?>
		  			<tr>
		  				<td  style='width: 800px;'><?php echo($template->orig_file_name); ?></td>
		  				<?php if (preg_match("/.php$|.js$|.css$|.txt$/", $template->file_name)) { ?>
			  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=template_view&id=<?php echo($template->id); ?>">View</a></td>
			  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=template_diff&id=<?php echo($template->id); ?>">Diff</a></td>
			  			<?php } else { ?>
			  				<td colspan="2"></td>
			  			<?php } ?>
		  			</tr>
		  		<?php } ?>
		  		
				  <?php $seen_posts_array = array(); ?>
					<?php foreach ($posts_changed as $post_changed) { ?>
					  <?php if (!in_array(get_permalink($post_changed->post_parent),$seen_posts_array)) { ?>
  						<tr>
  							<td><input type="checkbox" name="checkin_post_ids[]" value="<?php echo($post_changed->ID); ?>"/></td>
  							<td style='width: 800px;'><?php echo(get_permalink($post_changed->post_parent)); ?></td>
  							<?php 
                  $seen_posts_array = array_merge($seen_posts_array, array(get_permalink($post_changed->post_parent)));
  								$datetime = strtotime($post_changed->post_date);
  								$mysqldate = date("l jS \of F Y h:i:s A", $datetime);
  							?>
  							<td><?php echo($mysqldate); ?> UTC</td>
  							<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=current_post_diff&id=<?php echo($post_changed->ID); ?>">Diff</a></td>
  						</tr>
  					<?php } ?>
					<?php } ?>
		  		
		  		
		  	</tbody>
		  </table>
  		<p class="submit">
  			<input type="submit" name="submit" value="Delete Oldest Commit" />
  		</p>
  </form>
  </div>
  </div>
  </div>
  
<?php } ?>

<?php SCTomM8::add_social_share_links("http://wordpress.org/extend/plugins/wp-source-control/"); ?>

<div style="clear: both; margin-bottom: 100px;"></div>