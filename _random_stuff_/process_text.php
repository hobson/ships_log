<?php
include_once("stuff.php"); // without this DEBUG isn't set and online version can print out global debug messages (but I moved them inside the function) whenever it is included in other files, before process_text isn't called

function change_text_file_line($text_url,$field_name,$field_value) {
  if ( ($text = file_get_contents($text_url)) && is_writeable($text_url)) {
    if (!$handle = fopen($text_url, 'w')) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot open file ($text_url)".EOL;
      } // if fopen for writing fails
    elseif ($line = explode(EOL,$text)) {
      $n = count($line);
      foreach($line as $l => $li) {
        if((preg_match('/^\s*'.$field_name.'\s*:\s*(.+)\s*$/i', $li, $matches))&&($matches[1])) { // whitespace then case insensitive column name then colon then anything then whitespace then end-of-string
          if (DEBUG)
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."(): Found a matched line to change.";
          if (fwrite($handle, "$field_name: $field_value" . EOL) === FALSE) { 
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write $fn field to file ($text_url)".EOL;
            break; // foreach($line
            } // if fwrite fails            
          }
        else {
          if ( fwrite($handle, $li.EOL) == FALSE ) {
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write $fn field to file ($text_url)".EOL;
            break; // foreach($line...
            }
          } // else (if preg_match...
        } // foreach($line ... 
      // actionHL: should do the same for StartDate, EndDate, and any other values that have been "created" or are not the same as the fields in the text file
      fclose($handle);        
      } // elseif ($lines=...
    } // if $text = ...
  else
    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: No new fields provided or $text_url is not writeable.".EOL;
  return file_get_contents($text_url); // should edit the lines in memory and reassemble the text file rather than rereading it
  }

function append_text_file_lines($text_url,$new_fields,&$original_file_contents) {  
  if ((count($new_fields)>0) && (is_writeable($text_url))) {
    if (!$handle = fopen($text_url, 'w')) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot open file ($text_url)".EOL;
      } // if fopen for writing fails
    else {  
      foreach($new_fields as $fn => $fc) {
        //if (strcasecmp($fn,"UploadDate")==0) // ActionHL: allow a list of exceptions instead of this one hard-coded one
        //  continue; // foreach($new_fields...
        if (fwrite($handle, "$fn: $fc" . EOL) === FALSE) { 
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write $fn field to file ($text_url)".EOL;
          break; // foreach($new_field
          } // if fwrite fails
        } // foreach($new_field...)
      if (fwrite($handle, $original_file_contents) === FALSE) { // write the original file contents after the new lines containing the ID, StartDate, or any other "spontaneously" created fields
        echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write original file text to file ($text_url)".EOL;
        } // if fwrite fails
      // actionHL: should do the same for StartDate, EndDate, and any other values that have been "created" or are not the same as the fields in the text file
      fclose($handle);        
      } // if ($handle=fopen... else 
    } // if count($new_fields)
  else
    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: No new fields provided or $text_url is not writeable.".EOL;
  return;
  }

// identical to calling append_text_file_lines but with original_file_contents set to an empty string
function write_text_file($text_url,&$fields,&$other_fields) {  
//  if(DEBUG) {
//    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() fields array:".EOL;
//    print_r($fields);
//    }
  if ((count($fields)>0) && (is_writeable($text_url))) {
    if (!$handle = fopen($text_url, 'w')) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot open file ($text_url)".EOL;
      } // if fopen for writing fails
    else {  
      foreach($fields as $fn => $fc) { // need to sort the fields in a consistent order
        $fn = trim($fn);
        $fc = trim($fc);
        //if (strcasecmp($fn,"UploadDate")==0) // ActionHL: allow a list of exceptions instead of this one hard-coded one
        //  continue; // foreach($fields...
        if ((strlen($fn)<1)||(strlen($fc)<1)) {
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: field name '$fn' or value '$fc' was zero lengh.".EOL;
          continue; // foreach($fields 
          }
        if ((strncmp($fn,"0",1)==0)) {
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: field name '$fn' is '0'.".EOL;
          continue; // foreach($fields 
          }
        if (fwrite($handle, "$fn: $fc". EOL) === FALSE) { 
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write $fn field to file ($text_url)".EOL;
          break; // foreach($fields
          } // if fwrite fails
        } // foreach($new_field...)
      foreach($other_fields as $fn => &$field_array) {
         // all $other_fields should be arrays, but sometimes a fieldname of '' and field value of 0 gets through and trips all this up
        if (is_array($field_array)==TRUE) {
          $field_array = array_map('trim',$field_array);
          $fc = implode(';',$field_array);
          $fn = trim($fn);
          }
        else {
          if (DEBUG) {
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Warning: nonarray element found in other_fields array...".EOL;
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() name: '$fn'".EOL;
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() value: '$field_array'".EOL;
            }
          unset($other_fields[$fn]); // not sure if this is wise within a foreach loop
          }
          
        if ((strlen("$fn")<1)||(strlen($fc)<count($field_array))||(strlen("$fc")<1)) { // ActionHL, need to check the length of each $field_array element individually before combining with the implode command
          if (DEBUG) {
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: field name '$fn' or value '$fc' was zero length.".EOL; }
          continue; // foreach($fields 
          }
        if ((strcasecmp(trim("$fn"),"0")==0)) {
          if (DEBUG) {
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: field name '$fn' is '0'.".EOL; }
          continue; // foreach($fields 
          }
        if (fwrite($handle, "$fn: $fc". EOL) === FALSE) { 
          if (DEBUG) {
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Cannot write $fn field to file ($text_url)".EOL; }
          break; // foreach($fields
          } // if fwrite fails
        } // foreach($other_field...)
      // actionHL: should do the same for StartDate, EndDate, and any other values that have been "created" or are not the same as the fields in the text file
      fclose($handle);        
      } // if ($handle=fopen... else 
    } // if count($fields)
  else {
    if (DEBUG) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: No new fields provided or $text_url is not writeable.".EOL; } }
  return;
  }

// use data from other sources to fill in blanks in the database entries
function update_fields(&$field_edit,&$other_fields,$upload_date,$creation_date,$subject,$AuthorID) {
  if(!isset($field_edit['AuthorID']))
    $field_edit['AuthorID']=$AuthorID;
  if(!isset($field_edit['Title'])) 
    $field_edit['Title'] = $subject;
  if((!isset($field_edit['UploadDate'])) || (!$field_edit['UploadDate'])) {
    $field_edit['UploadDate'] = $upload_date;
    } // if ( !isset($field_edit['UplaodDate...
  if( (!isset($field_edit['StartDate'])) || (!$field_edit['StartDate']) )  // ActionHL, for existing articles need to log edit dates somewhere
    $field_edit['StartDate'] = $creation_date; // ActionHL: read EXIF data for photographs and use this to determine dates and locations
  if( (!isset($field_edit['EntryDate']))   || (!$field_edit['EntryDate']) )
    $field_edit['EntryDate'] = $creation_date;
  if (isset($other_fields['LocationLat'])) {
    foreach($other_fields['LocationLat'] as $LocIndex => $LocLat) {
      if (!isset($other_fields['LocationName'][$LocIndex])||strlen(trim($other_fields['LocationName'][$LocIndex]))<1) {
        if(isset($field_edit['Title']))
          $other_fields['LocationName'] = $field_edit['Title']     . " (" . $LocIndex .")";
        elseif(isset($field_edit['Category']))
          $other_fields['LocationName'] = $field_edit['Category']  . " (" . $LocIndex .")";
        elseif(isset($field_edit['Equipment']))
          $other_fields['LocationName'] = $field_edit['Equipment'] . " (" . $LocIndex .")";
        } // if !isset(...LocationName
      } // foreach(...LocationLat
    } // if isset(...LodcationLat
  } // function update_fields(...
  
// takes as input a line of text and outputs an integer: 3=definitely a title, 2 probably a title, 1 = 60% chance it's a title, 0 = not a title
function is_title(&$line) {
  //$nocapwords = array('a','an','the','of','in','on'); // get a more complete list of words not capitalized in titles, also sort them by the first character and index them so that when you're looking for capital letters at the beginning of words you can also check for these noncap words quickly if it isn't capitalized
  $p=1; // the title probability can only go down from here
  //$words=str_word_count(trim($line),1); // this allows any delimiter between words, I'm only interested in space delimiters
  $words=split(" ",trim($line)); // this allows any delimiter between words, I'm only interested in space delimiters
  //$p *= 2/max(abs((NumWordsInTypicalTitle-count($words)))^1.2,2); 
  $wordcount = 0; 
  foreach($words as $word) {
    // see if the word is capitalized and less than 12 characters long
    $titleword = preg_grep("/^\p{Lu}[\p{Ll}'\-]{2,12}$/", $word);  // what about \p{Lt} (title case)
    if(count($titleword) && strlen($titleword[0])>2) {
      $wordcount++;
    }
  }
  $p *= 2/max(abs(NumWordsInTypicalTitle-$wordcount)^1.2,2);
  $p *= 2/max(1+abs(.5-($wordcount/(count($words)+1)))^1.2,2); // actionHL: redesign this weighting function  
}

// actionHL: return the true/false success value separately from the actual value by returning an array of 2 values
// takes as input a line of text and outputs the latitude or longitude value
// -170.10 or 170.10 deg W or -170 deg 6.0' or 170deg06.0'W etc
// there's probably a much more elegant perl reg exp that uses the | symbol, I just haven't figured out how to group the | arguments without parentheses
function is_lon(&$line) {
  $sign = 1.0;
  // 1 = sign; 2 = decimal degrees, 3 = deg sym, 4 = E/W
  if (preg_match("/^\\s*(-{0,1})0{0,2}(\\d{1,3}[.]{0,1}\\d{0,12})\\s*(deg){0,1}\\s*(E|W|East|West|e|w|east|west){0,1}\\s*$/", $line, $matches)) {
    if (strlen($matches[1])>0)
      $sign = -1.0;
    if (count($matches)>4)
      if (($matches[4][0]=='W')||($matches[4][0]=='w')) {
        $sign = -1;
//        if (DEBUG)
//          echo "West detected".EOL;
        }
    $lon = $sign*$matches[2];
    }
  // 1 = sign; 2 = integer degrees, 3 = deg sym, 4 = minutes, 5 = E/W
  elseif (preg_match("/^\\s*(-{0,1})0{0,2}(\\d{1,3})\\s{0,2}(deg|\\s)\\s{0,2}(\\d{0,2}[.]{0,1}\\d{0,13})\\s*[']\\s*(E|W|East|West|e|w|east|west){0,1}\\s*$/", $line, $matches)) {
    if (strlen($matches[1])>0)
      $sign = -1.0;
    if (count($matches)>4) {
      $matches[4]=$matches[4]/60;
      if ($matches[4]>=1)
        return 0;
      if (count($matches)>5) 
        if (($matches[5][0]=='W')||($matches[5][0]=='w')) {
          $sign = -1.0;
//          if (DEBUG)
//            echo "West detected".EOL;
          }
        }
    $lon = $sign*$matches[2]+$sign*$matches[4];
    }
  else
    return 0;
  if ($lon == 0)
    $lon = 1e-15; // so that we can use the exact zero value as an indication of a bad latitude value or string
  if (($lon<minLongitude)|($lon>maxLongitude))
    return 0;
  return $lon;
  }

// actionHL: return the true/false success value separately from the actual value by returning an array of 2 values
// takes as input a line of text and outputs the latitude or longitude value
// -08.10 or 80.10 deg S or -08 deg 6.0' or 08deg06.0'S etc
// there's probably a much more elegant perl reg exp that uses the | symbol, I just haven't figured out how to group the | arguments without parentheses
function is_lat(&$line) {
  //echo "line=".$line."<br>" . EOL;
  //  $i = preg_match("/^\\s*(-{0,1})0{0,1}(\\d{1,3})/",$line,$matches);
  //echo "i=$i" . EOL;
  //print_r($matches);
  $sign = 1.0;
  // 1 = sign; 2 = decimal degrees, 3 = deg sym, 4 = N/S
  if (preg_match("/^\\s*(-{0,1})0{0,2}(\\d{1,3}[.]{0,1}\\d{0,12})\\s*(deg){0,1}\\s*(N|S|North|South|n|s|north|south){0,1}\\s*$/", $line, $matches)) {
    if (strlen($matches[1])>0)
      $sign = -1.0;
    if (count($matches)>4)
      if (($matches[4][0]=='S')||($matches[4][0]=='s')) {
        $sign = -1;
//        if (DEBUG)
//          echo "South detected".EOL;
        }
    $lat = $sign*$matches[2];
    }
  // 1 = sign; 2 = integer degrees, 3 = deg sym, 4 = decimal minutes, 5 = N/S
  elseif (preg_match("/^\\s*(-{0,1})0{0,2}(\\d{1,3})\\s{0,2}(deg|\\s)\\s{0,2}(\\d{0,2}[.]{0,1}\\d{0,13})\\s*[']\\s*(N|S|North|South|n|s|north|south){0,1}\\s*$/", $line, $matches)) {
    if (strlen($matches[1])>0)
      $sign = -1.0;
    if (count($matches)>4) {
      $matches[4]=$matches[4]/60;
      if ($matches[4]>=1)
        return 0;
      if (count($matches)>5) 
        if (($matches[5][0]=='S')||($matches[5][0]=='s')) {
          $sign = -1.0;
//        if (DEBUG)
//          echo "South detected".EOL;
        }
      }
    $lat = $sign*$matches[2]+$sign*$matches[4];
    }
  else
    return 0;
  if ($lat == 0)
    $lat = 1e-15; // so that we can use the exact zero value as an indication of a bad latitude value or string
  if (($lat<minLatitude)|($lat>maxLatitude))
    return 0;
  return $lat;
  }

function process_text(&$text,&$field_edit,&$other_fields) {
  $colname = array('ID','Title','Category','Equipment','StartDate','EndDate','EntryDate','UploadDate','AuthorID','Witnesses','Pub','Polish','Research','Confidence','Worth','Interest','TotalGood','Text');
  $funname = array_fill(0,count($colname)+1,'');
  # subfield field names for Locations and DataFiles (pictures)
  $other_colname = array('LocationID','LocationName','LocationCategory','LocationDescription','LocationAddress','LocationStreetNum','LocationStreet','LocationApt','LocationLandmarkID','LocationLandmarkType','LocationLandmarkName','LocationLandmarkDirection','LocationNeighborhood','LocationCity','LocationState','LocationPostCode','LocationCountry','LocationLat','LocationLon','LocationAccuracy','LocationGMTOffset',
                         'DataID','DataFilename','DataCaption','DataDescription','DataTitle');
  $other_funname = array(''          ,''            ,''                ,''                   ,''               ,''                 ,''              ,''           ,''                  ,''                    ,''                    ,''                         ,''                    ,''            ,''             ,''                ,''               ,'is_lat'     ,'is_lon'     ,''                ,''                 ,
                         ''      ,''            ,''           ,''               ,'');
if(DEBUG) {
  print_r($other_colname);
  print_r($other_funname);
}
#  echo "PROCESS BODY -------------------" . EOL;
#  echo $text.EOL;
#  echo "------------------- PROCESS BODY" . EOL;
  $j = 0;
  $textdone = 0;
  $lastline = -1;
//  $field_edit = array();
//  $other_fields = array(array()); // other fields capture other tables in database which are used for many to many relationships, so an article could have many entries in each of these fields
// ActionHL: need to consolidate flags like textdone, lastline, lastlinetext
  if ($line = explode(EOL,$text)) {
    $n = count($line);
    if (DEBUG)
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Status: Lines in body: $n<br />" . EOL;
    $nextlinetext=0;
    foreach($line as $l => $li) {
      $linedone = 0;
      reset($colname);
      foreach($colname as $ci => $cn) {
//        if (DEBUG) {
//          $li_short = substr($li,0,64);
//          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Status: searching for '$cn' in '$li_short...'" . EOL);
//          }
        // ActionHL: $matches[1] test within if statement seems superfluous
        // ActionHL: see if using $li_short instead of $li would speed up preg_match at all, but don't forget to deal with $matches for (.+) properly
        if((preg_match('/^\s*'.$cn.'\s*:\s*(.+)\s*$/i', $li, $matches))&&($matches[1])) { // whitespace then case insensitive column name then colon then anything then whitespace then end-of-string
          if (DEBUG) {
            $li_short = substr($li,0,64);
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Found a match for $cn in $li_short...".EOL; 
            }
          $s = trim($matches[1]);
          if (isset($field_edit[$cn]) && strlen($field_edit[$cn]>0)) {
          // ActionHL: to check to see if this field has already been set and display a warning or abort
            echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: text appears to have duplicate entries for the field name '$cn'.".EOL;
            echo " The text line begins with: '$li_short'." . EOL;
            echo " The file begins with: " . substr($text,0,200) . EOL;
            }
          // allow susequent text file fields to override earlier fields with the same name
          if(strlen($funname[$ci])>0)
            $field_edit["{$cn}"]=call_user_func($funname[$ci],$s); // if an interpreter function is defined then use it (e.g. numerical value limiting)
          else
            $field_edit["{$cn}"]=$s; // just copy the raw string into the field
          if (strcmp($cn,'Text')==0) {
            $textdone = 1;
            $nextlinetext = 1; }
          else
            $nextlinetext = 0;
          $linedone = 1;
          $lastline = $l;
          break; // foreach($colname...
          } // if preg_match
        } # foreach($colname
      if(!$linedone) {
        reset($other_colname);
        foreach($other_colname as $ci => $cn) {
          if((preg_match('/^'.$cn.':\s*(.+)\s*$/i', $li, $matches))&&($matches[1])) {
            if (DEBUG) {
              $li_short = substr($li,0,64);
              echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Found a match for $cn in $li_short...".EOL; 
              }
            $s = trim($matches[1]);
            // Action HL, is array of array initialization required for each row like this?
//            if (!isset($other_fields["{$cn}"])) 
//              $other_fields["{$cn}"] = array();
            if(strlen($other_funname[$ci])>0) {
              $tmp_vars = explode(';',$s);
              foreach ($tmp_vars as $tmp_ind => $tmp_var)
                $other_fields["{$cn}"][$tmp_ind]=call_user_func($other_funname[$ci],trim($tmp_var)); // if an interpreter function is defined then use it (particularly for numerical value bounds limiting)
              }
            else
              $other_fields["{$cn}"]=explode(';',$s); # the other fields need additional processing before escape_sql() should be used
            $linedone = 1;
            $lastline = $l;
            break; // foreach($other_colname...
            } # if preg_match
          } # foreach($other_colname
        if ( ($nextlinetext==1) && isset($field_edit['Text']))
          $field_edit['Text'] .= (EOL.$line[++$lastline]);
        } // if !$linedone
      // ActionHL: do we need to abort text processing if no field name was found at the beginning of a line?
      //if ($linedone==0) {
      //  if(!isset($field_edit['Text']))
      //    $field_edit['Text'] = $line[++$lastline].EOL;      
      //  break; # foreach($line... # no proper field name formating was found, so we're done, don't do any more lines
      //  } // if ($linedone==0) ...
      } # foreach($line
    } # if ($line...)
  # put any leftover text in the body of the article ('Text' field)
#  echo "lastline: $lastline" . EOL . "count: ".count($line).EOL;
//  if ($linedone==0) {
//    actionHL, is it OK to just keep appending any leftover lines to Text whether or not a Text field has previously been found and processed that contained?
//    if(!isset($field_edit['Text']))
//      $field_edit['Text'] = $line[++$lastline].EOL;
//    for($i=$lastline+1;$i<count($line);$i++)
//      $field_edit['Text'] .= ($line[$i]).EOL;
//    }
  if (DEBUG)
    echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() strlen(text): ".strlen($field_edit['Text']).EOL;
  } # function process_text
?>
