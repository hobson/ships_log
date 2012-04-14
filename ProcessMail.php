<?php
include_once ("./_random_stuff_/process_text.php");
include_once ("./_random_stuff_/decode_email.php");
include_once ("./_random_stuff_/stuff.php");
include_once ("./_random_stuff_/add_article.php");

if(DEBUG)
 echo "Within " . basename(__FILE__) . " ..." . EOL . "  dblink = $dblink" . EOL;

if (!($pw = ($_GET['pw'] )))
  return;
if (isset($_GET['dbname']) )
  $dbname = $_GET['dbname'];
else
  $dbname = $dbrecipients['knowledge@totalgood.com']; // just use the default database for the knowledge@totalgood.com e-mail inbox
if (strcmp($pw,$pw_ProcessMail)!=0) {
  if(DEBUG)
    echo "Within " . basename(__FILE__) . " ..." . EOL . "  pw = '$pw'" . EOL;
  return;
  }

// Create date strings for use as defaults for EntryDate & UploadDate
date_default_timezone_set('GMT');
$unixdate = trim(date('Y-m-d H:i:s')) ;
$unixdate_for_filename = trim(date('YmdHisO')) ;
if (DEBUG)
  echo "Unixdate=".$unixdate. EOL;

$more_files = array();
$indeces_to_clear = array();
$AuthorID = 0;

// Action HL, need function that recursively searches directories among the paths in an array adding them to a list of file paths and identifying a "category" for each file by its immediate parent directory
// Action HL, identify file type by MIME definition and/or file extension
// Action HL, allow use of wild cards in each of the filenames in the text_location field. expand them into arrays then merge them to produce the list of all files to process
if ( ($text_location = $_GET['file']) && ($text_location_array = explode(';',$text_location)) ) {
  if(DEBUG)
    print_r($text_location_array);
  $indeces_to_clear = array();
  foreach($text_location_array as $tli => $tl) {
    if (is_dir($tl)) {
      if ($dh = opendir($tl)) { 
        while (($filename = readdir($dh)) !== false) {
          if ((strlen($filename)>4) && (substr_compare($filename, ".txt", -4, 4)==0)) {
            // ActionHL, add some check for text file here, either extension .txt or nonascii character search
            $more_files[]= $tl . $filename;
            } // if (substr...
          } // while $filename
        closedir($dh); 
        } // if ($dh = opendir ...
      $indeces_to_clear[] = $tli;
      } // if (is_dir($tl)...
    } // foreach($text_location...
  foreach($indeces_to_clear as $tli)
    unset($text_location_array[$tli]);
  $text_location_array = array_merge($text_location_array,$more_files);
  if(DEBUG)
    print_r($text_location_array);
  foreach($text_location_array as $tli => $tl) {
    $field_edit = array();
    $other_fields = array(array());
    $attachments = array();
    if (($text_url = trim($tl)) && (file_exists($text_url)) && (is_readable($text_url))) {
      if (!($filedate = trim(date("Y-m-d H:i:s", filectime($text_url))))) // action HL, this seems like a useless (filectime,date,trim never fail), really should be checking for the 0 filectime tag which is a 1970 epoch for UNIX      
        return; // action HL, error handling
      $file_userinfo = posix_getpwuid(fileowner($text_url));
      foreach($dbusernames as $un => $authid) {
        //echo("username = {$file_userinfo['name']} ?= $un = $authid" . EOL);
        if (stristr($file_userinfo['name'],$un)) { // look for the first user name that contain a case insinsitive version of the database user name
          $AuthorID = $authid;
          break;
          } //if (stristr($file_userinfo...
        }  // foreach($dbusernames...
      if (DEBUG)
        echo ("ProcessMail: Found the text file with change date $filedate at $text_url<br />" . EOL);
//      if (strncasecmp(get_file_type("$text_url"),"text/plain",11)) {
//        if (DEBUG)
//          echo ("ProcessMail: $text_url does not appear to be a plain text file.<br />" . EOL);
//        continue; //foreach($text_location_array ...
//      } 
      $plainmsg = file_get_contents($text_url); // action HL, can't figure out how to pass flags like "FILE_TEXT" to functions like this
      file_put_contents($text_url.'.'.$unixdate_for_filename,$plainmsg); // copy the file to a new file name, tagged with the current date before writing the processed contents to the old fild
      process_text($plainmsg,$field_edit,$other_fields); # all_fields is an array of arrays, the 2 arrays being official Article fields, and other fields that affect other tables
      $old_fields = $field_edit;
      $old_other_fields = $other_fields;
      if( isset($other_fields['DataFilename']) && count($other_fields['DataFilename']) ) {
        if(DEBUG)
          echo "Within " . basename(__FILE__) . " " . basename(__FUNCTION__) . "(): found a DataFilename = {$other_fields['DataFilename'][0]}..." . EOL;
        if(strlen(trim($other_fields['DataFilename'][0])>0))
          $attachments = load_data_files($other_fields['DataFilename']);
        // ActionHL, glean other date information from file modification timestamp, similar to how e-mail is processed
        // ActionHL, centralize e-mail and text-file processing by putting text file into e-mail object and running it through the same processing as e-mails
        // ActionHL, create function to determine whether a phrase/word contains common categories or equipment names so that the folder name or Text contents can be searched to identify the category and/or equipment fields
//        
        } // if ( isset($other_fields...
      $pi = pathinfo($tl);
      update_fields($field_edit,$other_fields,$unixdate,$filedate,$pi['basename'],$AuthorID);
      upload_attachments($attachments,$other_fields); // this will clear the DataFilename field (array) before it tries to upload the files, because there is no attachment, need to undo this clearing and selectively replace just the names for which attachments are present in an e-mail, or load the attachments as if they were attached, first
      $ArticleID = add_article($field_edit, $other_fields,$dbname); // this takes care of the add_data_file() call to register the images that were uploaded
//      $new_fields = array(); // actionHL: it doesn't appear that new_fields is used for anything anymore
      // search through all the fields to see if any new ones have been added based on file meta-data (filename or date) or server info (time), if so, record them in $new_fields
//      foreach($field_edit as $fn => $fc) { // ActionHL: need to do this for $other_fields (location info may have been gleaned from photo EXIF data)
//        if (!isset($old_fields["$fn"]) && (strlen(trim($fc))>0))
//          $new_fields["$fn"]=$fc; 
//        } // foreach($field_edit...
      if ( (isset($field_edit['ID']) && ($ArticleID != $field_edit['ID'])) || ( isset($old_fields['ID']) && ($old_fields['ID'] != $ArticleID) ) ) {
        //$plainmsg = change_text_file_line($text_url,'ID',"$ArticleID");
        unset($field_edit['ID']);
//        unset($new_fields['ID']);
      }
      $field_edit['ID']="$ArticleID";
      write_text_file($text_url,$field_edit,$other_fields);
    } // if ($text_url = trim($tl)...
  } // foreach($text_location_array ...  
  return;
} // if file...
if (DEBUG)
 echo "Connecting to imap server $tgun@{$tgem}INBOX using $tgpw<br>" . EOL;
$mbox = imap_open($tgem.$tgib,$tgun,$tgpw)
  or die("can't connect: " . imap_last_error());
$ov = imap_check($mbox); # overview of the mailbox, including the number of messages
if (DEBUG)
  echo "Found {$ov->Nmsgs} e-mails on the server<br>" . EOL;
if (DEBUG) {
  echo("Here are all the mail server folders:" . EOL);
  print_r($folder = imap_list($mbox,$tgem.$tgib,NULL));
  } // if (DEBUG)
  exit;
$result = imap_fetch_overview($mbox,"1:{$ov->Nmsgs}",0); # actionHL: this takes a while, find something else to do while waiting for com to e-mail server
if (DEBUG)
 echo( count($result) . " messages to process...." . EOL );
foreach ($result as $msg) {
  if (DEBUG) {
    echo "({$msg->msgno}) ID:{$msg->message_id}, UID:{$msg->uid}" . EOL . " From:{$msg->from}, Subject:{$msg->subject}" . EOL;
    print_r($msg);
    }  // if (DEBUG)
  $validatedDB = 0;
  foreach($dbrecipient as $recipient => $dbn) {
    if (strncasecmp($msg->to,$recipient) == 0) {
      $validatedDB = $dbn;
      break;
    } // if strncasecmp($msg->to ...
  } // foreach($dbrecipient
  if ((DEBUG) && ($validatedDB>0))
    echo "found knowledge base entry: #{$msg->msgno} ({$msg->date}) From: {$msg->from} Subject: {$msg->subject}<br>" . EOL;
  foreach($dbauthors as $authoremail => $authid) {
    if (strncasecmp($msg->from,$authoremail)==0) {  // ActionHL: use perlregex to check for noncanonical "from" field formats like "JoeBlow <joeblow@totalgood.com>"
      $AuthorID = $authid;
      break;
      } // if strncasecmp($msg->from ...
    } // foreach($dbauthors as ...
  if (!($AuthorID) || !($validatedDB))
    continue; // foreach result as msg
  list($htmlmsg, $plainmsg, $charset, $attachments) = decode_email($mbox, $msg->msgno); // php is smart enough to return these by reference itself to maximize performance
  $field_edit = array();
  $other_fields = array(array());
  process_text($plainmsg,$field_edit,$other_fields); # $other_fields is an array of arrays, the 2 arrays being official Article fields, and other fields that affect other tables
//  if(!isset($field_edit['AuthorID']))
  $timestamp = strtotime($msg->date);
  $emaildate = trim(date( 'Y-m-d H:i:s', $timestamp ));
  update_fields($field_edit,$other_fields,$unixdate,$emaildate,trim($msg->subject),$AuthorID);
//    } // if !isset($field_edit['ID']...
  upload_attachments($attachments,$other_fields);
  add_article($field_edit, $other_fields, $validatedDB);
  // ActionHL: might be more efficient to move them all at once and more robust to use CP_UID to avoid confusing the message number sequence whil processing
  imap_mail_move($mbox, "{$msg->msgno}", $tgib.'.Archive.'.$validatedDB); # ,CP_UID )  if the sequence is UIDs instead of message numbers
  } # for each $msg
imap_close($mbox);
?>

