<?php
include_once("process_sql.php");
include_once("add_data_file.php"); 
include_once("calculate_totalgood.php");

if(DEBUG)
 echo "within " . basename(__FILE__) . " ..." . EOL . "  dblink = $dblink".EOL;
 
function add_author($ArticleID, &$field_edit) {
  if(DEBUG_VERBOSE)
    echo("ArticleID = $ArticleID" . EOL . "Author={$field_edit['AuthorID']}".EOL);
  if(!isset($field_edit['AuthorID']))
    // ActionHL: provide process for new authors
    return;
  $row = array('ArtID' => $ArticleID,'PersonID' => $field_edit['AuthorID'],'Relationship' => 'author');
  // This check for a duplicate relationship table entry for the author is probably redundant as database engine will search for key violations during the add_row operation
  $duperow = find_dupe('Art2Per', $row);
  if(isset($duperow['ArtID'])) {
    if(DEBUG) 
      echo("Duplicate art2per entry found for article $ArticleID and person (author) {$field_edit['AuthorID']}.".EOL);
    return;  
    }
  if(DEBUG) {
    echo("adding author row to ".Art2PerTable.EOL);
    print_r($row); }
  add_row(Art2PerTable,$row); // actionHL, need to round here and in the database to the same precision  
}

function add_location($ArticleID, &$other_fields) {
  if (DEBUG) {
    print_r($other_fields); }
  if(!isset($other_fields['LocationLat']) || !isset($other_fields['LocationLon']) ) 
    return;
if(DEBUG_VERBOSE) {
  echo "is_float(other_fields['LocationLon'])" . is_float($other_fields['LocationLon']) . EOL;
  echo "is_float(other_fields['LocationLat'])" . is_float($other_fields['LocationLat']) . EOL;
  echo "abs(other_fields['LocationLon'])" . abs($other_fields['LocationLon']) . EOL;
  echo "abs(other_fields['LocationLat'])" . abs($other_fields['LocationLat']) . EOL;
}
  // assume that all Location arrays are the same length as the 'LocationLat' array and iterate through them all
  $location_fields = array();
  $location_field_names = array();
  foreach($other_fields['LocationLat'] as $LocIndex => $LocLat) {
    $LocLon = $other_fields['LocationLon'][$LocIndex];
    if ( !is_float($LocLat) || !is_float($other_fields['LocationLon'][$LocIndex]) || ((abs($LocLat)<GPSMinValue) && (abs($other_fields['LocationLon'][$LocIndex])<GPSMinValue)) )
      continue;
    $neighbor_row = find_neighbor('Location',array('Lat' => $LocLat,'Lon' => $LocLon),array(GPSMatchTolerance,GPSMatchTolerance));
    if ($LocationID = (isset($other_fields['LocationID'][$LocIndex]) ? $other_fields['LocationID'][$LocIndex] : $neighbor_row['ID'])) {
      if ( !find_dupe( Art2LocTable, array('ArtID'=>$ArticleID,'LocID'=>$LocationID) ) ) {
        if (DEBUG)
          echo "Adding an Art2Loc entry for $ArticleID to $LocationID".EOL;
        add_row(Art2LocTable,array('ArtID'=>$ArticleID,'LocID'=>$LocationID)); 
        }
      else { // if !find_dupe(Art2LocTable...else
        if (DEBUG)
          echo "An Art2Loc entry for $ArticleID to $LocationID already exists".EOL;
        } // if !find_dupe(Art2LocTable...else      
      } // if ($LocationID=...
    else { // need to create a location entry and put its ID in the Art2Loc Table
      foreach($other_fields as $on => $of) { 
        if (strncmp($on,'Location',8)==0)
          if ($location_field_name = substr($on,8,strlen($on)))
            $location_fields["$location_field_name"] = $of[$LocIndex];
        } // for each $other_fields
      if (DEBUG) {
        echo "Adding a new location with the following fields:";
        print_r($location_fields); }
      add_row('Location',$location_fields);
      if($LocationID = mysql_insert_id()) 
        add_row(Art2LocTable,array('ArtID'=>$ArticleID,'LocID'=>$LocationID)); 
      else {
        if (DEBUG)
          echo "add_article PHP Error: Unable to determine the new Location ID.<br />".EOL;
        continue; // foreach(($other_fields...
        } // else
      } // else (if ($LocationID = )
    } // for each($other_fields['LocationLat']...
  } // function add_location($other_fields)


// $field_edit should contain a single set of fields for one Article
// but $other fields is an array of arrays containing as many locations or datafiles as the Article needs
function add_article(&$field_edit,&$other_fields,$dn=DefaultDatabaseName) { 
  if (DEBUG) {
     echo "add_article inputs: <br />".EOL;
     print_r($field_edit);
     print_r($other_fields);
     print_r($other_fields['DataFilename']);
     echo "database name = '$dn'" . EOL;  
     }
  global $dblink;
  mysql_select_db($dn , $dblink)
    or die("Couldn't open $dn in mysql database.");
  if( isset($field_edit['ID']) && ($ArticleID = $field_edit['ID']) && (article_exists($ArticleID)>0) ) { // if this is an edit of an existing article then just replace the fields that are included
    unset($field_edit['ID']);
    if (DEBUG_VERBOSE)
      echo "Changing article $ArticleID".EOL;
    change_row('Article',$field_edit,array('ID'=>$ArticleID));
// Location Processing for new articles
// ActionHL, need to work on method for editing rather than adding other_fields
// ActionHL, for instance the section of code below for new articles checks for existing Location IDs, need to do the same for updating articles
// Lots of parallel code that needs to be consolidated
    add_location($ArticleID,$other_fields);
    $DataTitle = $DataCaption = $DataDescription = '';
    if ( (DEBUG) && (isset($other_fields['DataFilename'])) ) {
      $cof = count($other_fields['DataFilename']);
      echo "count = $cof<br />".EOL;
      }
    if ( (DEBUG_VERBOSE) && (isset($other_fields['DataFilename'])) ) {
      print_r($other_fields['DataFilename']); }
    if(isset($other_fields['DataFilename']) && count($other_fields['DataFilename'])) {
      if (DEBUG) {
        echo "Adding these files to the database..<br />".EOL;
        print_r($other_fields['DataFilename']);}
      foreach($other_fields['DataFilename'] as $dfi => $dfn) {
        if (isset($other_fields['DataTitle'][$dfi]))
          $DataTitle = $other_fields['DataTitle'][$dfi];
        if (isset($other_fields['DataCaption'][$dfi]))
          $DataCaption = $other_fields['DataCaption'][$dfi];
        if (isset($other_fields['DataDescription'][$dfi]))
          $DataDescription = $other_fields['DataDescription'][$dfi];
        add_data_file($dfn,$ArticleID,$DataTitle,$DataCaption,$DataDescription);
        } // foreach($otherfields['DataFilename'] ...
      } // if isset($other_fields['DataFilename'...
    // ActionHL, need to add the ability to changed columns/values outside of the main Article table, e.g. location, picture, person, boat
// Author Processing for new articles
// Action HL: need to create entries in Person table and/or Art2Per table covering the author identified by the AuthorID field in field_edit
  } // if(isset($field_edit('ID...
    
// Processing for existing articles (user has already indicated an article ID
  else { // if isset(field_edit['ID'] ... else
    unset($field_edit['ID']);
    if(!isset($field_edit['EntryDate'])) 
      $field_edit['EntryDate'] = $field_edit['UploadDate'];
    if(!isset($field_edit['StartDate']) )
      $field_edit['StartDate'] = $field_edit['UploadDate'];
    # ActionHL: update the TotalGood field based on the others and a formula similar to what's in the spreadsheet, and use the same function in LoadData, eliminating/bypassing spreadsheet formulat
    $field_edit['TotalGood'] = calculate_totalgood($field_edit); 
    if (DEBUG_VERBOSE) 
      print_r($field_edit);
    if ( $N_dupes = mysql_num_rows(find_dupes( 'Article',array('Title'=>escape_sql($field_edit['Title']),'StartDate'=>escape_sql($field_edit['StartDate'])) )) ) {
      echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: $N_dupes duplicates for article '{$field_edit['Title']}' on '{$field_edit['StartDate']}'".EOL;
      return;
      }
    add_row('Article',$field_edit);  // actionHL, change add_row function to return the ID or key row value to avoid the next 2 checks for the ID value
    if( !($ArticleID = mysql_insert_id()) )
      if( !($ArticleID = $field_edit['ID']) ) {
        if (DEBUG)
          echo "Line " .__LINE__." in ". basename(__FILE__) . " " . __FUNCTION__ ."() Error: Unable to determine the new article ID.".EOL;
        return 0;
        } // if !(articleID = field_edit[ID])
// Location Processing for existing articles
    add_location($ArticleID,$other_fields);

// DataFile Processing for existing articles    
    $DataTitle = $DataCaption = $DataDescription = '';
    if(isset($other_fields['DataFilename']) && count($other_fields['DataFilename'])) { // figure out a way to consolidate this with the add_author() function at the end, after the branch between updating and inserting a new article
      if (DEBUG) {
        echo "Adding these files to the database..<br />".EOL;
        print_r($other_fields['DataFilename']);}
      foreach($other_fields['DataFilename'] as $dfi => $dfn) {
        if (isset($other_fields['DataTitle'][$dfi]))
          $DataTitle = $other_fields['DataTitle'][$dfi];
        if (isset($other_fields['DataCaption'][$dfi]))
          $DataCaption = $other_fields['DataCaption'][$dfi];
        if (isset($other_fields['DataDescription'][$dfi]))
          $DataDescription = $other_fields['DataDescription'][$dfi];
        // actionHL, modify the add_data_file function to just look for all Data... fields in the other_fields array and add those as columns of the new row, (model after add_location function)
        add_data_file($dfn,$ArticleID,$DataTitle,$DataCaption,$DataDescription);
        } // foreach($otherfields['DataFilename'] ...
      } // if isset($other_fields['DataFilename'...

// Author Processing for existing articles
// Action HL: need to create entries in Person table and/or Art2Per table covering the author idnetified by the AuthorID field
    
// make sure there aren't any identical articles already loaded
    unset($row);      
    if ($result = process_sql("SELECT ID from Article WHERE ((Title = '".escape_sql($field_edit['Title'])."') AND (StartDate = '".escape_sql($field_edit['StartDate'])."'))")) # action HL: should probably check other fields too
      $row = mysql_fetch_assoc($result);
    if ((!$row)||(!$row['ID'])) { # if no other similar articles exist
      $cns=array();
      $f=array();
      foreach($field_edit as $i => $fe) {
        $cns[]=$i;
        $f[]=escape_sql($fe);
        } // foreach... 
      if(count($cns)) {
        $query = "INSERT INTO Article (".implode(',',$cns).") VALUES ('".implode("','",$f)."')";          
        process_sql($query);
      } // if count($column_names
    } // if ((!row)||(!$row['ID'...   
  } //  if(isset($field_edit['ID']) else ...
  add_author($ArticleID,$field_edit);
  update_totalgood($ArticleID);
//  $field_edit = array();
  $field_edit = get_article($ArticleID);
  foreach($field_edit as $fei => $fec) {
    if (strlen(trim($fec))<1)
      unset($field_edit[$fei]);
  }
  return $ArticleID;
} // function add_article($field_edit,$other_fields,$dn=$dbdn)
?>
