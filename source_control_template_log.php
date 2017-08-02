<?php 

  function print_template_log($id) {
  
    global $wpdb;
    $table_name = $wpdb->prefix . "version_controls";
    $table_template_name = $wpdb->prefix . "version_control_templates";
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_template_name WHERE id = %d", $id));
    $template_changes = $wpdb->get_results($wpdb->prepare("SELECT v.theme_timestamp, v.job_no, v.description, t.orig_file_name, t.file_name, t.id FROM $table_template_name as t, $table_name as v WHERE t.deleted IS NULL AND t.orig_file_name = %s AND t.job_id = v.id ORDER BY t.template_timestamp DESC", $record->orig_file_name));
    ?>   
      <a href="<?php echo(get_option("siteurl")); ?>/wp-admin/admin.php?page=wp-source-control/source_control_search.php">Back</a>
      <div class="wrap">
      <h2>WordPress Source Control Log</h2>
    	<div class="postbox " style="display: block; ">
    	<div class="inside">
      <?php foreach ($template_changes as $template_changed) { ?>
        <h4><?php echo(date('l jS \of F Y h:i:s A', $template_changed->theme_timestamp)); ?> UTC &#8211; <?php echo($template_changed->job_no); ?> &#8211; <?php echo($template_changed->description); ?></h4>
        
    	  <table class="data">
    	  	<thead>
    	  		<tr>
    	  			<th scope="col">Name</th>
    	  			<th></th>
    	  			<th></th>
    	  		</tr>
    	  	</thead>
    	  	<tbody>
    	  			<tr>
    	  				<td  style='width: 800px;'><?php echo($template_changed->orig_file_name); ?></td>
    	  				<?php if (preg_match("/.php$|.js$|.css$|.txt$/", $template_changed->file_name)) { ?>
    		  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=template_view&id=<?php echo($template_changed->id); ?>">View</a></td>
    		  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=template_diff&id=<?php echo($template_changed->id); ?>">Diff</a></td>
    		  			<?php } else { ?>
    		  				<td colspan="2"></td>
    		  			<?php } ?>
    	  			</tr>
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