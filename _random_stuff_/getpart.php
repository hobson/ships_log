function getpart(&$mbox,&$mno,&$p,$partno,&$htmlmsg,&$plainmsg,&$charset,&$attachments) {
  // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart
  // DECODE DATA
  $data = ($partno)?
      imap_fetchbody($mbox,$mno,$partno):  // multipart
      imap_body($mbox,$mno);  // not multipart
  // Any part may be encoded, even plain text messages, so check everything.
  if ($p->encoding==4)
      $data = quoted_printable_decode($data);
  elseif ($p->encoding==3)
      $data = base64_decode($data);
  // no need to decode 7-bit, 8-bit, or binary
  // PARAMETERS
  // get all parameters, like charset, filenames of attachments, etc.
  $params = array();
  if (isset($p->parameters) && ($p->parameters))
      foreach ($p->parameters as $x)
          $params[ strtolower( $x->attribute ) ] = $x->value;
  if (isset($p->dparameters) && ($p->dparameters))
      foreach ($p->dparameters as $x)
          $params[ strtolower( $x->attribute ) ] = $x->value;
  // ATTACHMENT
  // Any part with a filename is an attachment,
  // so an attached text file (type 0) is not mistaken as the message.
  if ((isset($params['filename'])) || (isset($params['name']))) {
    // filename may be given as 'Filename' or 'Name' or both
    $filename = (isset($params['filename']))? $params['filename'] : $params['name'];
    // filename may be encoded, so see imap_mime_header_decode()
    $attachments["{$filename}"] = $data; } // this is a problem if two files have same name
  // TEXT
  elseif (($p->type==0) && ($data)) {
    // Messages may be split in different parts because of inline attachments,
    // so append parts together with blank row.
    if (strtolower($p->subtype)=='plain')
        $plainmsg .= trim($data) ."\n\n";
    else
        $htmlmsg .= $data ."<br><br>\n";
    if (isset($params['charset']))
      $charset = $params['charset'];  // assume all parts are same charset
  }
  // EMBEDDED MESSAGE
  // Many bounce notifications embed the original message as type 2,
  // but AOL uses type 1 (multipart), which is not handled here.
  // There are no PHP functions to parse embedded messages,
  // so this just appends the raw source to the main message.
  elseif ($p->type==2 && $data) 
      $plainmsg .= trim($data) ."\n\n";
  // SUBPART RECURSION
  if ((isset($p->parts)) && (count($p->parts)>0)) 
      foreach ($p->parts as $partno0=>$p2)
          getpart($mbox,$mno,$p2,$partno.'.'.($partno0+1),$htmlmsg,$plainmsg,$charset,$attachments);
            // partno = 1.2, 1.2.1, etc.
}
?>
