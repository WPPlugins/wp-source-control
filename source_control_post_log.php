<?php 

  function print_post_log($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_controls";
    $table_post_name = $wpdb->prefix . "version_control_posts";
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_post_name WHERE revision_id = %d", $id));
    $post_changes = $wpdb->get_results("SELECT v.theme_timestamp, v.job_no, v.description, p.revision_id, p.post_id, p.id, p.job_deleted FROM $table_post_name as p, $table_name as v WHERE p.post_id = ".$record->post_id." AND p.job_id = v.id ORDER BY p.revision_id DESC");
    ?>   
      <a href="<?php echo(get_option("siteurl")); ?>/wp-admin/admin.php?page=wp-source-control/source_control_search.php">Back</a>
      <div class="wrap">
      <h2>WordPress Source Control Log</h2>
    	<div class="postbox " style="display: block; ">
    	<div class="inside">
      <?php foreach ($post_changes as $post_changed) { ?>
        <h4><?php echo(date('l jS \of F Y h:i:s A', $post_changed->theme_timestamp)); ?> UTC &#8211; <?php echo($post_changed->job_no); ?> &#8211; <?php echo($post_changed->description); ?></h4>
    	  <table class="data">
    	  	<thead>
    	  		<tr>
    	  			<th scope="col">Name</th>
    	  			<th></th>
    	  			<th></th>
    	  		</tr>
    	  	</thead>
    	  	<tbody>
			  	  <?php $seen_posts_array = array(); ?>
			  		  <?php if (!in_array(get_permalink($post_changed->post_id),$seen_posts_array)) { ?>
				  			<tr>
				  				<td style='width: 800px;'><?php echo(get_permalink($post_changed->post_id)); ?></td>
				  				<?php if ($post_changed->job_deleted == 0) { ?>
                    <?php $seen_posts_array = array_merge($seen_posts_array, array(get_permalink($post_changed->id))); ?>
				  					<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=post_view&id=<?php echo($post_changed->revision_id); ?>">View</a></td>
				  					<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=post_diff&id=<?php echo($post_changed->revision_id); ?>">Diff</a></td>
			  					
				  				<?php } else { ?>
				  					<td colspan="2">Deleted</td>
				  				<?php } ?>
				  			</tr>
				  	<?php } ?>
    	  	</tbody>
    	  </table>
      <?php } ?>
      </div>
      </div>
      </div>
      <p><a href="<?php echo(get_option("siteurl")); ?>/wp-admin/admin.php?page=wp-source-control/source_control_search.php">Back</a></p>
      
      <?php
  }
?>