<?
# loosely based on an online tutorial

function find_attachments($connection,$mno) {
  $structure=imap_fetchstructure($connection,$mno);
//  print_r($structure);
  if(!(isset($structure->parts) && count($structure->parts)>0))
    return 0;
  echo "there should be ".count($structure->parts)." structure parts.\n";
  $attachments = array();
  $attachments2 = array();
  for($i = 0; $i < count($structure->parts); $i++) {
	  $attachments[$i] = array('is_attachment' => false,'filename' => '','name' => '','attachment' => '');
	  if($structure->parts[$i]->ifdparameters) 
		  foreach($structure->parts[$i]->dparameters as $object) 
			  if(strtolower($object->attribute) == 'filename') {
				  $attachments[$i]['is_attachment'] = true;
				  $attachments[$i]['filename'] = $object->value;
			  }		
	  if($structure->parts[$i]->ifparameters) 
		  foreach($structure->parts[$i]->parameters as $object) 
			  if(strtolower($object->attribute) == 'name') {
				  $attachments[$i]['is_attachment'] = true;
				  $attachments[$i]['name'] = $object->value;
			  }
	  if($attachments[$i]['is_attachment']) {
	    if($attachments[$i]['filename'])
	      $fn =  $attachments[$i]['filename'];
	    else
	      $fn = $attachments[$i]['name'];
	    echo "Filename: $fn !!!!!!!!!!!!!!!!!!!!!\n";
		  $attachments2["$fn"] = imap_fetchbody($connection, $mno, $i+1);
		  if($structure->parts[$i]->encoding == 3)  // 3 = BASE64
			  $attachments2["$fn"] = base64_decode($attachments2["$fn"]);
		  elseif($structure->parts[$i]->encoding == 4) // 4 = QUOTED-PRINTABLE
			  $attachments2["$fn"] = quoted_printable_decode($attachments2["$fn"]);
	  }
  } // for structure parts
  return $attachments2;
} // function find_attachments



?>


