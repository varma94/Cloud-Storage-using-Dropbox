<?php

// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "8d0f9d27mm4xxb9",      // Put your Dropbox API key here
	'app_secret' => "98jjtq1wfz8u70c",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');

// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	//echo "loaded access token:";
	//print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

$files = $dropbox->GetFiles("",false);

if(!empty($files)) {

    $t = array_keys($files);
    echo '<table>';
       echo '<tr>';
		echo "<th>File Name</th>";
		echo "<th>Upload Date</th>";
	  echo  '<tr>';
    for($i=0;$i<sizeof($t);$i++){
    	 $file = current($files);
      echo '<tr>';
		echo "<th>\r\n\r\n<b><a href='album.php?hash=$file->rev'>".substr($file->path,1)."</a></b>\r\n</th>";
		echo "<th>". date("Y.m.d") ."</th>";
	  echo  '<tr>';

	next($files);
    }
    echo '<table>';
    echo "<br/><br/><br/>";
}
?>


<?php 
 if(isset($_POST['sendfile'])){
 	$uploaddir = getcwd()."/";
    $uploadfile = $uploaddir . basename($_FILES['file']['name']);
    echo move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
 	$dropbox->UploadFile($_FILES['file']['name']);
 	header("Refresh:0");
}
?>

<form  action="album.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
Submit this file : <input name="file"  type="file"/><br/>
<input type="submit" value="sendfile" name="sendfile"/>
</form>

<?php
function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}


 if(isset($_GET["revd"])){
 	reset($files);
 	for($i=0;$i<sizeof($files);$i++){
 		$f = current($files);
 		if($f->rev == $_GET["revd"]){
 		  $dropbox->Delete($f->path);
 		  header("Refresh:0");
 		}

 		next($files);
            
 	}
 }


 if(isset($_GET["hash"])){
 	reset($files);
 	echo '</br></br>';
 	for($i=0;$i<sizeof($files);$i++){
 		$f = current($files);
 		$test_file = "test_download_".basename($f->path);
 		if($f->rev == $_GET["hash"]){
 		  echo "<img src='".$dropbox->GetLink($f,false)."'/></br>";
 			$dropbox->DownloadFile($f, $test_file);
 			echo "<form action=album.php method=\"get\">";
 			echo "<input type=\"hidden\" value=$f->rev name=\"revd\">";
 			echo "<input type=\"submit\" value=\"delete\">";
 			echo  "</form>";
 		}

 		next($files);
            
 	}
 }

function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}
?>

