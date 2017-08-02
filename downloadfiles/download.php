<?php
$zip_file = $_GET["path"];
if ((preg_match("/wordpress_\d+\.zip$/",$zip_file)) || (preg_match("/wordpress_\d+\.sql$/",$zip_file))) {
	$content_type = $_GET["content_type"];
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Type: $content_type");
	header("Content-Disposition: attachment; filename=".$zip_file.";" );
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($zip_file));
	echo file_get_contents($zip_file);
}
?>