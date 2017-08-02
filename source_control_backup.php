<?php 
  require_once('source_control_path.php');
  wp_enqueue_script('jquery');

  wp_register_script( 'my-source-control-admin', plugins_url('admin_source_control.js', __FILE__) );
  wp_enqueue_script('my-source-control-admin');
  
  $date = new DateTime();
  $date->setTimezone(new DateTimeZone('UTC'));
  $date_time_stamp = $date->getTimestamp();

  if ($_POST["submit"] == "Backup") {
    $root_dir = Path::normalize(dirname(__FILE__).'../../../../');
    $zip_complete = Zip($root_dir, $root_dir."_".$date_time_stamp.".zip");
    $backup_complete = backup_tables($root_dir."_".$date_time_stamp,DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
    
    if ($zip_complete && $backup_complete) {
      echo("<div id=\"message\" class=\"updated below-h2\"><p>Backup Complete</p></div>");
    } else {
      echo("<div id=\"message\" class=\"updated below-h2\"><p>Backup Failed due to write permissions.</p></div>");
    }
    
  } else if ($_GET["restore"] != "") {

    copy($_GET["restore"], preg_replace("/_(.+)\.zip/i", "", $_GET["restore"]).".zip");
    Unzip(preg_replace("/_(.+)\.zip/i", "", $_GET["restore"]).".zip");
  
    $sql_file = str_replace(".zip", ".sql", $_GET["restore"]);
    restoreDatabase($sql_file, DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
    echo("<div id=\"message\" class=\"updated below-h2\"><p>Restore Complete</p></div>");
  } else if ($_GET["delete"] != "") {
    $sql_path = str_replace(".zip", ".sql", $_GET["delete"]);
    $deleted_zip = @unlink($_GET["delete"]);
    $deleted_sql = @unlink($sql_path);
    
    if ($deleted_zip && $deleted_sql) {
      echo("<div id=\"message\" class=\"updated below-h2\"><p>Backup Deleted</p></div>");  
    } else {
      echo("<div id=\"message\" class=\"updated below-h2\"><p>Backup Could Not Be Deleted</p></div>");
    }
    
  }

  function Zip($source, $destination)
  {
      if (!extension_loaded('zip') || !file_exists($source)) {
          return false;
      }

      $zip = new ZipArchive();
      if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
          return false;
      }

      $source = str_replace('\\', '/', realpath($source));

      if (is_dir($source) === true)
      {
          $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

          foreach ($files as $file)
          {
              $file = str_replace('\\', '/', $file);

              // Ignore "." and ".." folders
              if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                  continue;

              $file = realpath($file);

              if (is_dir($file) === true)
              {
                  $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
              }
              else if (is_file($file) === true)
              {
                  $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
              }
          }
      }
      else if (is_file($source) === true)
      {
          $zip->addFromString(basename($source), file_get_contents($source));
      }

      return $zip->close();
  }

  function Unzip($unzip_full_path) {  
    // assuming file.zip is in the same directory as the executing script.
    $file = $unzip_full_path;

    // get the absolute path to $file
    $path = pathinfo(realpath($file), PAthINFO_DIRNAME);

    $zip = new ZipArchive;
    if ($zip->open($file) === true) {
      for($i = 0; $i < $zip->numFiles; $i++) {
          $zip->extractTo(Path::normalize(dirname(__FILE__).'../../../../../'), array($zip->getNameIndex($i)));
          // here you can run a custom function for the particular extracted file
      }
      $zip->close();
    }
  }

  /* backup the db OR just a table */
  function backup_tables($root_dir, $host,$user,$pass,$name,$tables = '*')
  {
  
    $link = mysql_connect($host,$user,$pass);
    mysql_select_db($name,$link);
  
    //get all of the tables
    if($tables == '*')
    {
      $tables = array();
      $result = mysql_query('SHOW TABLES');
      while($row = mysql_fetch_row($result))
      {
        $tables[] = $row[0];
      }
    }
    else
    {
      $tables = is_array($tables) ? $tables : explode(',',$tables);
    }
  
    //cycle through
    foreach($tables as $table)
    {
      $result = mysql_query('SELECT * FROM '.$table);
      $num_fields = mysql_num_fields($result);
    
      $return.= 'DROP TABLE '.$table.';';
      $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
      $return.= "\n\n".$row2[1].";\n\n";
    
      for ($i = 0; $i < $num_fields; $i++) 
      {
        while($row = mysql_fetch_row($result))
        {
          $return.= "INSERT INTO ".$table." VALUES(";
          for($j=0; $j<$num_fields; $j++) 
          {
            $row[$j] = str_replace("'", "\'", $row[$j]);
            if (isset($row[$j])) { $return.= "'".$row[$j]."'" ; } else { $return.= "''"; }
            if ($j<($num_fields-1)) { $return.= ','; }
          }
          $return.= ");\n";
        }
      }
      $return.="\n\n\n";
    }
  
    //save file
    $handle = fopen($root_dir.'.sql','w+');
    fwrite($handle,$return);
    fclose($handle);
    return true;
  }


  function restoreDatabase($sql_file, $host,$user,$pass,$name) {

    $link = mysql_connect($host,$user,$pass);
    mysql_select_db($name,$link);
  
    $file_content = file($sql_file);
    $query = "";
    foreach($file_content as $sql_line){
      if(trim($sql_line) != "" && strpos($sql_line, "--") === false){
        $query .= $sql_line;
        if (substr(rtrim($query), -1) == ';'){
          $result = mysql_query($query) or die(mysql_error());
          $query = "";
        }
      }
     }
    mysql_close();
  }

  ?>
  <div class="wrap">
  <h2>WP Source Control Backup / Restore</h2>
  <p>This form allows you to backup your site's entire file system and database.</p>
  <p>Please note that you may have to upgrade this plugin after a restore cos it will restore the entire site at the time the backup was made.</p>
  <div class="postbox" style="display: block;">
  <div class="inside">
  <form action="" method="post">
  		<table class="data">
  			<thead>
  				<tr>
  					<td></td>
  				</tr>
  			</thead>
  			<tbody>	
        <?php
      
        // open this directory 
        $myDirectory = opendir(Path::normalize(dirname(__FILE__).'../../../../../'));
        $file_name = end(split("/", Path::normalize(dirname(__FILE__).'../../../../')));

        // get each entry
        while($entryName = readdir($myDirectory)) {
          if (preg_match("/^".$file_name."(.+)\.zip/i", $entryName)) {
            $dirArray[] = $entryName;
          }
        }

        // close directory
        closedir($myDirectory);
        //	count elements in array
        $indexCount	= count($dirArray);
      
        if ($indexCount > 0) {



          // sort 'em
          sort($dirArray);

          print("<tr><th>Filename</th><th>SQL Dump</th><th>Date Modified</th><th>Filesize</th></tr>\n");
          // loop through the array of files and print them all
          $base_path = Path::normalize(dirname(__FILE__).'../../../../../')."/";
          for($index=0; $index < $indexCount; $index++) {
            if (substr("$dirArray[$index]", 0, 1) != "."){ // don't list hidden files
              $filepath = $base_path.$dirArray[$index];
              $sql_filepath = str_replace(".zip", ".sql", $filepath);
              $downloadzip_path = get_option("siteurl")."/wp-content/downloadfiles/download.php?path=$filepath&content_type=application/zip";
              $downloadsql_path = get_option("siteurl")."/wp-content/downloadfiles/download.php?path=$sql_filepath&content_type=application/sql";              
              $sqlfile_name = str_replace(".zip", ".sql", $dirArray[$index]);
          		print("<tr><td><a href=\"$downloadzip_path\">$dirArray[$index]</a></td>");
          		print("<td><a href=\"$downloadsql_path\">$sqlfile_name</a></td>");
          		print("<td>");
          		print(date("l jS \of F Y h:i:s A", filemtime($filepath)));
          		print("</td>");
          		print("<td>");
          		print(filesize($filepath). " bytes");
          		print("</td>");
          		print("<td>");
          		print("<a class='restore' href='admin.php?page=wp-source-control/source_control_backup.php&restore=".$filepath."'>Restore</a>");
          		print("</td>");
          		print("<td>");
          		print("<a class='delete' href='admin.php?page=wp-source-control/source_control_backup.php&delete=".$filepath."'>Delete</a>");
          		print("</td>");
          		print("</tr>\n");
          	}
          }
        } else {
          print("<tr><td>You don't have any backups yet. To backup your site and database, click the Backup button.</td></tr>");
        }
      
      
        ?>
      
  			</tbody>
  		</table>
  		<p class="submit">
  			<input type="submit" name="submit" value="Backup" />
  		</p>
  </form>
  </div>
  </div>
  </div>

<?php SCTomM8::add_social_share_links("http://wordpress.org/extend/plugins/wp-source-control/"); ?>

<div style="clear: both; margin-bottom: 100px;"></div>