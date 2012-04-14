<?php
define('EOL',"\n"); // should be defined based on architecture where PHP is running (Windows = \r\n, Linux = \n)
if ( (isset($_SERVER['SERVER_NAME'])) && (isset($_SERVER['DOCUMENT_ROOT'])) ) {
  $dburl = $_SERVER['SERVER_NAME']; // surprisingly don't seem to have to add mysql. prefix to this URL at totalgood.com
  $docpath = $_SERVER['DOCUMENT_ROOT']; }
else {
  $docpath='';
  $dburl='';
  }
//echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() dburl: $dburl and docpath: $docpath".EOL;

function debug_ErrorHandler($errno, $errstr, $errfile, $errline) {
  print("PHP Error [$errno] [$errstr] at $errline in $errfile.<br \>" . EOL);
}
if (!(strcasecmp($dburl,'localhost')) && (!(strncasecmp($docpath,'/media/disk/Boat/',17)))) {
 # don't debug unless running on localhost and the path is only my laptop Boat directory runinning in Ubuntu
 define('LOCAL_RUN',TRUE);
// echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Running locally...".EOL;
 }
else {
  define('LOCAL_RUN',FALSE);
  }

// be careful not to try to redefine constants, becasue the second define will be ignored without a warning
if (LOCAL_RUN) {
  define('DEBUG',FALSE);
  define('DEBUG_VERBOSE',FALSE);
  error_reporting(E_ALL);
  set_error_handler('debug_ErrorHandler');
//  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() _SERVER variable is:" . EOL;
//  print_r($_SERVER);
//  echo EOL;
//  print_r($docpath);
//  echo EOL;
}
else {
 define('DEBUG',FALSE);
 define('DEBUG_VERBOSE',FALSE);
}

if(strcasecmp($dburl,"localhost")!=0) {
  if ((strncasecmp($dburl,'www.',4)==0) && (strlen($dburl)>4)) {
    $dburl = substr($dburl,4,strlen($dburl));
    }
  $dburl = 'mysql.'.$dburl;
  }
$dbun = "your_mysql_user_name_here";
$dbpw = 'your_sql_password_here';
$dbdn = "boat_knowledge";
$tgem = '{mail.totalgood.com:993/imap/ssl/novalidate-cert}';
$tgpw = 'your_smtp_mail_server_password_here';
$tgun = "example@totalgood.com"; // some shared hosts require you to log onto mail servers with a username that is the full e-mail address
$tgib = 'INBOX';
$pw_ProcessMail = 'another_password_for_processing_mail';
// process mail script will accept e-mails from these addressees for uploading text and images to the blog
// the second value is an author ID for the automatic by-line of articles
// multiple e-mail addresses can be associated with one by-line (author) ID
$dbauthors = array('username@servername.com' => 1, 'anotheruser@anotherserver.com' => 2 );
// you can have multiple ships_log databases, each with their own e-mail address
$dbrecipients = array('emailname@emailserver.com' => 'mysql_database_name');
// usernames for logging onto those databases, multple names can be used for each db (but I thought it was using the $dbun above?)
$dbusernames = array('username' => 1);
# non security-sensitive constants, are constants more or less secure than variables for things like passwords (above)
#$ext2mime = array('jpeg'=>'image/jpeg','jpg'=>'image/jpeg','wav'=>'audio/wav';
include_once("ext2mime.php");

$security_condition = " AND (Article.Pub = 1)";

define('MinAttachmentSize',2); // attachments have to be at least this long after trimming to avoid being ignored as empty
define('LatLonDigits',12); // 12 digits of latlon precision in degrees means that on earth you should be accurate to 1 billionth of a degree which is about 1 mm
define('Art2PicTable','Art2Dat');
define('Art2LocTable','Art2Loc');
define('Art2PerTable','Art2Per');
define('PictureTable','Data');
define('RelativePathPrefix','./');
define('PictureFolder','data/');
define('ShibaDocPath','/media/disk/Boat/Notes and Blogs/totalgood.com/');
define('DreamhostDocPath','/home/.lemonade/hobson/totalgood.com/');
define('MaxDataFileSize',101000002); // tell user 100 million byte files are the max allowed (not 100 megabyte), but leave 1% +1 byte margin in the memory allowance
define('DefaultDatabaseName','boat_knowledge'); 
define('NumWordsInTypicalTitle',5); // the typical "perfect" article title should have only 5 words, anything more than double that (10) is not likely a title at all, but just a phrase
define('minLatitude',-90.0); // the minimum acceptable latitude
define('minLongitude',-180.0); // the minimum acceptable longitude
define('maxLatitude',90.0); // the maximum acceptable latitude
define('maxLongitude',180); // the maximum acceptable longitude // actionHL: might want to consider accepting 360 deg, at least in the positive/east direction
define('GPSMatchTolerance',1e-8); // the maximum distance between two "identical" GPS waypoints or locations to still consider them the same, 1e-7 degrees is about 1 cm
define('GPSMinValue',1e-15); // minimum latitude or longitude before the value is considered invalid (used in functions that return exactly 0 for failure and to check for 0,0 values passed from e-mails or text files)
?>
