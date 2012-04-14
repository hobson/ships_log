<?php
include_once("process_sql.php");
  
/// Load data files into memory as a string--use this to gather data for use in add_data_file function()
function load_data_files(&$path_array) {
  if(DEBUG)
    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."()" . EOL;
  global $docpath;
  $attachments = array();
  if(!isset($path_array[0]) || !count($path_array)) {
    if(DEBUG) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): no DataFilename found..." . EOL;
      print_r ($path_array);
      echo "" . EOL;   }
    return array(); // no filenames, so no attachments to return
    } // if(!isset($path_array...
  if(DEBUG) {
    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): filepaths = .." . EOL;
    print_r ($path_array);
    echo "" . EOL;   }
  foreach($path_array as $fi => $filepath) {
    if(DEBUG)
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): trying to process data in $filepath..." . EOL;
    $pi = pathinfo($filepath);
    $directories = explode('/',$pi['dirname']); // just so we can see how deep the path is, not to do anything with it
    if (DEBUG) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Here's the directories array." . EOL;
      print_r($directories);
      echo ("Original path string = '$filepath'".EOL."pathinfo = ".EOL);
      print_r($pi);
      echo ("Current Working Directory = '".getcwd()."'".EOL);
    } // if (DEBUG)...
    $filename = $pi['basename'];
    $attachment='';
//    $urlrelfilename=trim("\"".RelativePathPrefix.PictureFolder."$filename\""); // for some reason this stopped working in July 2009, perhaps php started encoding quotes using the url (&nbsp; for spaces)" and allowing straight ascii space symbols in paths and filename strings and not processign the url encoding properly
    $urlrelfilename=trim(RelativePathPrefix.PictureFolder."$filename"); 
    if (DEBUG) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): looking in the relative path first ($urlrelfilename)." . EOL;
      echo ("fileexists = ".file_exists("$urlrelfilename") . EOL);
      //print_r(scandir(trim(RelativePathPrefix.PictureFolder)));
    } // if (DEBUG)...
    if ( file_exists("$urlrelfilename") ) { // assume the existing file in the database will do, but need to check for database entry describing it and merging the new stats with the old one or create a new one if significantly different
      if (DEBUG) {
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Seems like file already exists in relative path." . EOL;
        } // if (DEBUG)...
      // if file is in the indicated path as well as the default web data folder, then see if they are the same file
      if ( file_exists("$urlrelfilename") && strcmp($filepath,RelativePathPrefix.PictureFolder."$filename") ) {
        if (DEBUG) {
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Seems like user specified the relative path already." . EOL;
          } // if (DEBUG)...
//        } // if file_exists && ...
      if (filesize($urlrelfilename)<((MaxDataFileSize-1)*0.99)) { // leave some margin in case # bytes reported by filesize & string size are different
        $attachment = file_get_contents  ($urlrelfilename); // so load binary file into memory
        echo('attachment size = ' . strlen($attachment) . EOL);
        } // if filesize...
       // Action HL, do an MD5 check (e.g. string md5_file(string $filename[,bool $raw_output])) on the file contents to see if they are identical, if so then create a new filename based on the old ones and copy it into memory, changing the $other_fields['DataFilename"] entry to match the new one
      } // if file_exists...
      else { // if ( file_exists...&&...  
        ; // file path points to default data folder and file already exists there, so no need to do anything.
        if (DEBUG) 
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Warning: $filename already exists in the default data folder. Will assume existing file is same as new file.<br />" . EOL;
       } // if (file_exists... else
    elseif ((count($directories)>0)&&(strlen(end($directories))>0)&&(strcasecmp(end($directories),'.')!=0)) { // means more than just a basename or "./" was provided within the path to the datafile
      $urlfilepath = trim($filepath);
      if (DEBUG) {
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Seems like a full path was provided." . EOL;
        } // if (DEBUG)...
      if (file_exists($urlfilepath)) { // file exists in the indicated directory or the working directory but not in default folder
        if (DEBUG) {
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Found the file at $filepath." . EOL;
          } // if (DEBUG)...

        if (filesize($urlfilepath)<((MaxDataFileSize-1)*0.99)) { // leave some margin in case # bytes reported by filesize & string size are different
          // so basename isn't present in the default directory and user has supplied an absolute path that is valid
          $attachment = file_get_contents  ($urlfilepath); // so load binary file into memory
          echo('attachment size = ' . strlen($attachment) . EOL);
          }
        elseif (DEBUG) {
          $fs = filesize("$urlfilepath");
          $fsm = MaxDataFileSize;
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Warning: $urlfilepath is $fs bytes which is more than the $fsm bytes allowed for uploading." . EOL;
          } // elseif (DEBUG)
        } // if file_exists
      elseif(DEBUG) {
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Warning: Unable to find $urlfilepath.<br />" . EOL;
        } // elseif(DEBUG)
      // action HL, determine if these files are available locally, if so copy them to the web server data directory
      // should probably also check to see which server we're running on with   if (!strcasecmp($docpath,DreamhostDocPath) and upload them to dreamhost if we're running locally
      } // elseif((count($directories...
    else { // if file_exists (urlfile...
      if (DEBUG) {
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: seems file doesn't exist in relative path, nor was a full path provided." . EOL;
        } // if (DEBUG)...
      } // if file_exists (urlfile... else ...
      if (DEBUG) {
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Putting the attachment into the array of attachments." . EOL;
        } // if (DEBUG)...      
      $attachments[$filename]=$attachment;
    } // foreach($path_array as $filepath) 
  return $attachments; // this will have the binary data in array entries for each file to be used like the $attachments array output by decode_email
} // function load_data_files

function upload_attachments(&$attachments,&$other_fields) {
  global $docpath;
  if(DEBUG)
    echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): ..." . EOL;
  if(!isset($attachments) || (count($attachments)<1)) {
    if(DEBUG)
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): no attachments found in input array so returning without doing anything." . EOL;
    return; // no attachments, so nothing to do
    }
  $other_fields['DataFilename']=array(); // delete all the path info for all the attachments so can be labeled with just basename
//  $other_fields['RelativePath']=array(); // not necessary, add_data_file() assumes the relative path and ignores the $other_fields data
  if(DEBUG_VERBOSE)
    echo "Continuing with " . basename(__FILE__) . " upload_attachments because nonzero length attachment found..." . EOL;
  foreach ($attachments as $filename => $filedata) {
    if ((strlen(trim($filename))<1) || (strlen(trim($filedata))<MinAttachmentSize)) {
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Error: attachment size smaller than " . MinAttachmentSize . "." . EOL;
      continue;
      }
    if (DEBUG) {
      $fileN = strlen($filedata);
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Filedata is $fileN bytes in size." . EOL;
      } // if (DEBUG)...
    $other_fields['DataFilename'][]=$filename;
//    $other_fields['RelativePath'][]=PictureFolder; // not necessary, add_data_file assumes the relative path that is necessary here
    $urlrelfilename=trim(RelativePathPrefix.PictureFolder.$filename);
    if (DEBUG)
      echo "Working on attachment: $filename" . EOL;
    // ActionHL: if file already exists, change the new attachment's filename and add it
    if (!strcasecmp($docpath,'/media/disk/Boat/Notes and Blogs/totalgood.com/')) { // $docpath is a global variable set in stuff.php
        if (DEBUG) {
          echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): It seems like we're running on the local machine." . EOL;
          } // if (DEBUG)...
      
      if (file_exists($urlrelfilename)) { // go ahead an assume the existing file in the database will do for the new article
        if (DEBUG) {
          echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Seems like file already exists in the relative path." . EOL;
          } // if (DEBUG)...
        if (DEBUG) {
          echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Warning: Unable to add $filename to the database because it already exists." . EOL;
          } // if (DEBUG)...
        } // if fileexists...
      else
        if (!(file_put_contents($urlrelfilename,$filedata)))
          if (DEBUG)
            echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Error writing $filename to $urlrelfilename." . EOL;
        else
          if (DEBUG)
            echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Wrote $filename to $urlrelfilename." . EOL;
    } // if strcasecmp($docpath...
    else
      if (!file_put_contents($urlrelfilename,$filedata)) { #,FILE_BINARY)  binary is the default so no need to include it as a flag, but PHP doesn't recognize it as a global constant
        if (DEBUG) 
          echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Error: Unable to write $filename to the disk." . EOL;
        else
          if (DEBUG)
            echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Wrote $filename to $urlrelfilename." . EOL;
      } // elseif (!file_put_contents
    // actionHL: trying to make this work for multiple attachments
    } // foreach ($attachments...
} // function upload_attachments

// ActionHL: create function for add_data_file_link() to doublecheck size of small pics and adjust accordingly before adding Art2Dat link (required in LoadCSVData.php)
/// Look for the old-style integer lists that allow ranges like #-# or #&# or just # .
/// Doesn't yet do explode explode based on commas or semicolons to process lists like #,#,#,# .
/// Need to deal with ampersands in URLs appropriately using urlencode/decode or &amp or + or , or ; instead of &.
function parse_ids($s) {
  if ($ids = sscanf($s, "%d&%d")) 
    return array_merge(array(2),$ids);
  if ($ids = sscanf($s, "%d-%d")) 
    return array_merge(array(127),$ids);
  if ($ids = sscanf($s, "%d")) 
    return array_merge(array(1),$ids); # can use array_shift later to pull out this element
  return NULL;
} # function parse_ids(...

function create_small_picname($BigDatPath) {
  return preg_replace('/\.\w+$/',', sm$0', $BigDatPath); // just adds a ', sm' to the end of the file before the extension to denote a thumbnail version of the larger image with the same basename
}

# actionHL: this currently only works for jpeg files
function resizePic ($sourcefile, $dest_x=0, $dest_y=192, $jpegqual=90, $targetfile=0) {
  global $go, $verbose;
  if (!$targetfile)
    $targetfile = create_small_picname($sourcefile);
  if (strlen($targetfile) != strlen($sourcefile)+4) 
    return($sourcefile);
  if (file_exists(trim($targetfile))) {
    if(DEBUG)
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Warning: Target file for small picture already exists, so we'll assume that it's appropriate for this picture and not do anything.";
    return(basename($targetfile));
    }
  if (DEBUG) 
    echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): filename: $targetfile<br>" . EOL;
  # Get the dimensions of the source picture 
  if ($picsize=getimagesize("$sourcefile")) {
    $source_x = $picsize[0];
    $source_y  = $picsize[1];
    if (DEBUG) 
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."():Pic size: $source_x x $source_y<br>" . EOL;
    if ($source_x < $dest_x) return NULL; # desired picture size is larger than  the existing picture so report the error
    if ($source_x == $dest_x) return $sourcefile; # desired picture size is the same as the existing picture so just return that name
#    $ratio = max($dest_x,$dest_y)/max($source_x,$source_y);
    if ((!$dest_y)&&($source_x>0))
      $dest_y=$source_y*$dest_x/$source_x; # does this need to be truncated to an integer and clipped at limits?
    elseif ((!$dest_x)&&($source_y>0))
      $dest_x=$source_x*$dest_y/$source_y; # does this need to be truncated to an integer and clipped at limits?
    if (($dest_y<1) || ($dest_x<1))
      return NULL; # size of picture was invalid or zero so can't do anything
    # Create a new image object (not neccessarily true colour) 
    $source_id  = imageCreateFromJPEG("$sourcefile");
    $target_id  = imagecreatetruecolor($dest_x, $dest_y);
    $target_pic = imagecopyresampled($target_id,$source_id,0,0,0,0,$dest_x,$dest_y, $source_x,$source_y);
    imagejpeg ($target_id,"$targetfile",$jpegqual);
    return basename($targetfile);
  }
  return '';
} # function resizePic

/// Add an entry in Data and Art2Dat tables to link articles to data files.
// If data files are pictures (jpeg=jfif) then make sure a thumbnail exists, and if not, create one, posting approriate entries in Art2Dat and Data
// 1) check to see if an ID (integer indicating a Data table row) for the file was supplied.
//  1.Y) if so, then check to see that the Data row for that ID has a filename and relative path that can be used to check out the file
//    1.Y.Y) store the filename for later use
//    1.Y.N) abort -- return 0, because without a filename, can't do anything
//  1.N) if not an ID (integer indicating a Data table row), check to see that it's a string (filename)
//    1.N.N) abort -- return 0;
//    1.N.Y) create a Data row for the data file and add it to the table using its ID as the BigDatID for later processing
// 2) At this point we should have a BigDatID associated with a data file that has a Data table entry (stored in $row), but not necessarily an Art2Dat entry, so check to see if it's an image
//  2.Y) It is an image so check to see if a Data entry for a thumbnail exists 

function add_data_file($BigDatID,$ArtID=0,$Title='',$Caption='',$Description='') {
  global $ext2mime;
  global $ext2id;
  $ext = '';
  if (DEBUG)
    echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ . EOL;
  $SmallDatID = '';
  if (is_int($BigDatID)) {
    # find the picture path and filename from the BigPicID
    $row = find_dupe(PictureTable,array('ID'=>$BigDatID));
    if( !($BigDatFilename=$row['Filename']) || !($RelativePath=$row['RelativePath']) )
      return 0;
    } # if(is_int($BigDatID) ...
  else { // if (is_int($BigDatID))
    if(!is_string($BigDatID))
      return 0;
    $BigDatFilename=$BigDatID;
    $RelativePath=RelativePathPrefix.PictureFolder;
    $row = find_dupe('Data',array('Filename'=>$BigDatID));
    if (isset($row['Filename']))
      $BigDatID = $row['ID']; // change string filename back to integer ID
    else { // if(isset($row['Filename...
      $row['Filename'] = $BigDatFilename;
      $row['ID'] = '';
      $row['RelativePath'] = PictureFolder;
      $row['LocID']=0; // actionHL, process EXIF tags to get location and add the appropriate location entry in the table and to the associated article s
      $row['Title']=$Title;
      $row['Caption']=$Caption;
      $row['Description']=$Description;
      $row['Original']=1;
      $row['DerivativeID']=0;
      $row['RelativeID']=0;
      $matches=array();
      preg_match("/\.(\w+)$/", $row['Filename'],$matches); // ActionHL, confirm the extension matches the true mime type using get_mime_type() function in ProcessMail.php
      if($ext = strtolower($matches[1]))
        $row['Type'] = $ext2mime["$ext"];
      if (!$row['Type']) 
        $row['Type']='unknown';
      add_row(PictureTable,$row); # add the big picture info to the database
      # ActionHL: how does mysql_insert_id know when there are 2 or 3 keys for a row rather than a single ID
      $BigDatID = mysql_insert_id(); # the ID of the last mysql insert is the BigPicID, to replace the string filename currently in this variable
  //    if(!$sourcefile=$row['Filename'])
  //      return 0;          
      } // else (if(isset($row...)  
    } // else (if(is_int ...)
  preg_match("/\.(\w+)$/", $row['Filename'],$matches); // ActionHL, confirm the extension matches the true mime type using get_mime_type() function in ProcessMail.php
  if ($matches[1])
    $ext = strtolower($matches[1]);
  if (DEBUG)
   echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() File name extension: \"$ext\"" . EOL;  
  // at this point $BigDatID, $RelativePath, and BigDatFilename contain data file identification data from $row so $row can be deleted once $row("Type") is used
  $BigDatPath = $RelativePath.$BigDatFilename;
  $SmallDatID = ''; // Null entry until one is found or created
  // so check to see if the data file is an image
  if (isset($row['Type'])&&(strncasecmp($row['Type'],'image/',6)==0)) {
    unset($row2); // just to make sure no carry-over data is left during this array assignment below (unset is probably unnecessary)
    $row2 = find_dupe('Data',array('Filename' => create_small_picname($BigDatFilename))); // only looks at the first duplicate 
    // actionHL, if you find a duperow should check that all other articles that refer to this BigPicID also have the SmallPicID referenced properly, change_row if necessary, like what is done after resizePic below
    if(isset($row2['Filename']))
      $SmallDatID = $row2['ID']; // the duplicate row is in the Data table for the small picture, not Art2Dat, so field named 'ID' is the SmallDatID
    else {  // if (isset($row...
      $row2=$row; // duplicate all fields from the BigDatID item
      // ActionHL, resizepic will break if non jpeg images are attempted, filter here or fix resizepic
      // ActionHL, also check to see if resize has already been done, if a database entry for a small pic already exists
      if (DEBUG) 
        echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Creating a SmallPic from file \"$BigDatPath\"" . EOL;
      if ($newfilename = resizePic ($BigDatPath) ) { # use the resizePic default size and quality values
        if (strcmp($newfilename,$BigDatPath)==0)
          $SmallDatID=$BigDatID; # data file was unchanged, so the big picture and the small picture are the same
        else {
          $row2['Filename']=$newfilename;
          $row2['DerivativeID']=$BigDatID;
          $row2['LocID']=0; // process EXIF tags to create appropriate locations for each picture and record
//          $row2['RelativePath']=PictureFolder; 
          $row2['ID']='';
          $row2['Original']=0;
          add_row(PictureTable,$row2); # add the small picture info to the database
          # get the id for the new picture and add it to a row for upload to the Art2Dat relationship table
          $SmallDatID = mysql_insert_id();
          } // else (if strcmp($newfilename,$BigDatPath...
        } // if ($newfilename = resizePic ($BigDatPath) )  # use the resizePic default size and quality values
      } // if(!isset($row['Filename']))
    } //   if (isset($row['Type'])&&(strncasecmp($row['Type'],'image/',6)==0)
  else { // if (isset($row['Type'])&&(strncasecmp
    if ((isset($ext)) && isset($ext2id["$ext"]))
      $SmallDatID = $ext2id["$ext"];
    else 
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): No SmallDatID could be created because ext undefined" . EOL;

   // ActionHL, create thumbnails or icons for use with data files other than jpeg and set the appropriate SmallDatID and have entries in the Data table for them
    } // else
      
  // so at this point a $SmallDatID should contain the appropriate ID for the Data table entry containing a thumbnail
  // ActionHL, understand why the "$ArtIDs = process_sql..." without any "mysql_fetch" worked prior to this version
  //           maybe the resource simplifies to an array or scalar when SELECT results in only one column, or one element (one column and one row)
  $ArtID_done = 0; // marker to make sure an entry in the Art2Dat table covers the article requested by the user in addition to all the others that refer to this data file
  $results=process_sql("SELECT ArtID FROM ".Art2PicTable." WHERE BigDatID = $BigDatID");
  if (mysql_num_rows($results)>0) {
    if(DEBUG)
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Looks like an Art2Dat entry was found for picture ID = $BigDatID." . EOL;
    while($ArtIDs=mysql_fetch_assoc($results)) {
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Looks like $BigDatID is linked to Article {$ArtIDs['ArtID']}" . EOL;
      if(DEBUG)
        echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): changing the row that associates an article with picture $BigDatID." . EOL;
      if( is_array($ArtIDs) && isset($ArtIDs['ArtID']) && ($ArtID_fromdb=$ArtIDs['ArtID']) ) {
        if(DEBUG)
          echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): changing the row that associates article $ArtID_fromdb with picture $BigDatID." . EOL;
        change_row( Art2PicTable, array('SmallDatID' => $SmallDatID), array('ArtID' => $ArtID_fromdb,'BigDatID' => $BigDatID) );  
        if ($ArtID == $ArtID_fromdb)
          $ArtID_done = 1;
        } // if(is_array...
      } // while  
    } // if mysql_num_rows($results)
  if($ArtID_done==0) {
    if(DEBUG)
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): No Art2Dat entry was found for picture ID = $BigDatID." . EOL;
    if($ArtID) {
      if (DEBUG)
        echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Seems like user specified the relative path already." . EOL;
      add_row(Art2PicTable,array('ArtID' => $ArtID,'BigDatID' => $BigDatID,'SmallDatID' => $SmallDatID));    
      }
    else
      echo  "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): No Article was found to associate with the new picture.<br />" . EOL;
    } // if ($ArtID_done==0)
  return 1;
  } // function add_data_file($BigDatID,$ArtID=0,$Title='',$Caption='',$Description='')
?>
