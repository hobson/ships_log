<?
// decode_textfile.php
// allow $mboxOriginal code by david at hundsness dot com
// Updated by Hobson Lane at http://totalgood.com
// Updates 2009-04-07: 
//   Globals made local and returned in an array
//   Used isset() to avoid PHP STRICT runtime errors 
//   Deleted header processing as it's not required for body decoding

include_once("decode_email.php");

function decode_textfile($filename) { // return array($htmlmsg,$plainmsg,$charset,$attachments);
  $htmlmsg = $plainmsg = $charset = '';
  $attachments = array();
  $s = imap_fetchstructure($mbox,$mno);
  if ( (!isset($s->parts)) || (!$s->parts) )   // not multipart
    getpart($mbox,$mno,$s,0,$htmlmsg,$plainmsg,$charset,$attachments);  // no part-number, so pass 0
  else  // multipart: iterate through each part
      foreach ($s->parts as $partno0=>$p)
          getpart($mbox,$mno,$p,$partno0+1,$htmlmsg,$plainmsg,$charset,$attachments);
  return array($htmlmsg,$plainmsg,$charset,$attachments);
}

//function getpart(&$mbox,&$mno,&$p,$partno,&$htmlmsg,&$plainmsg,&$charset,&$attachments) {

