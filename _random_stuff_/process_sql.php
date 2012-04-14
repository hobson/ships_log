<?php
$sqlbuf = ''; # use to store all the $sql commands as a script for debugging or rerunning later
$sqlerrbuf = '';
$go = 1;
include_once ("stuff.php");

# connect to the mysql server
if(DEBUG)
 echo "within " . basename(__FILE__) . " ...\n  dburl = $dburl\n  dbun = $dbun\n";
$dblink = mysql_connect($dburl,$dbun,$dbpw);
if (! $dblink)
  die("Couldn't connect to MySQL");
mysql_set_charset('utf8',$dblink)
  or die("Couldn't set the character set to utf8 for transactions with ".$dburl);
mysql_select_db($dbdn , $dblink)
  or die("Couldn't open ".$dbdn);
if(DEBUG)
 echo "within " . basename(__FILE__) . " ...\n  dblink = $dblink\n";
function escape_sql($s) {
  $s = trim($s);
  # deal with some unescaped special characters
  $s = str_replace  ( chr(8), '\b', $s ); # try to escape all backspace characters
#  $s = str_replace  ( "\t", "\\t", $s ); # try to escape all tab characters
  # deal with some escaped special characters
  $s = str_replace  ( "\r\n", "\n", $s ); # try to convert all PC line breaks to unix-style linebreaks
#  $s = str_replace  ( "\n", "\\n", $s ); # escape all line breaks
#  $s = str_replace  ( "\r", "\\r", $s ); # escape all carriage returns
  # let mysql native function escape everything that remains
#  echo "Before escaping: $s";
  $s = mysql_real_escape_string($s);
#  echo "After escaping: $s_out";
  return($s);
  }


function process_sql($s) {
  global $sqlbuf, $sqlerrbuf, $go, $verbose;
#  $s = addslashes($s);
#  $s = mysqli_real_escape_string($s)
  $sqlbuf = $sqlbuf.$s.";\n";
  if(DEBUG)
    echo "SQL: $s<br />\n";
  if($go) {
    $response = mysql_query($s);
    $e = mysql_error();
    if ($e) {
      $sqlerrbuf = $sqlerrbuf.$e.";\n";
      $sqlbuf.= "ERR: $e<br />\n";
    # error message display 
      if(DEBUG)
        echo "<b>SQL ERR:</b>". $e ."<br />\n";
      } # if $e (an sql error has been recorded)
    return($response);
  } 
  return NULL;
} # function process_sql(...


/// Look for rows with the where conditions specified in a string, return a resource containing all matches
function find_rows($tablename,$conditions='') {
  if (strlen(trim($conditions))>0)
    $conditions = ' WHERE (' . $conditions . ')';
  $response = process_sql('SELECT * FROM '.$tablename.$conditions);
  return $response;
  }
  
/// Look for rows with the where conditions specified in a string, return an array containing only the first match
function find_row($tablename, $conditions='') {
  if ($results = find_rows($tablename,$conditions))
    return(mysql_fetch_assoc($results)) ;
  else
    return 0;
  }

// $colnameval is an associative array containing all the equality conditions
/// Find all rows in a table that have the indicated column values (specified as an associative array)
function find_dupes($tablename, $colnameval) {
  //print_r($colnameval);
  $condition_array=array();
  foreach($colnameval as $colname => $colval) {
    $condition_array[]="$tablename.$colname = '$colval'"; }
  $conditions = implode(') AND (',$condition_array);
  if (count($colnameval)>1)
    $conditions = '('.$conditions.')';  
  return find_rows($tablename,$conditions);
  } 

// ActionHL, vectorize colval and colname to allow multiple ANDed WHERE clauses and allow ORing instead of ANDing if requested
function find_dupe($tablename, $colnameval) {
  if ($results = find_dupes($tablename,$colnameval))
    return(mysql_fetch_assoc($results)) ;
  else
    return 0;
}


function article_exists($id) {
  return(mysql_num_rows(find_dupes('Article',array('ID'=>$id))));
}

function get_article($id) {
  if (DEBUG)
    echo "retrieving article $id".EOL;
  return(find_dupe('Article',array('ID'=>$id)));
  }



/// Find all rows in a table that have the indicated column values (specified as an associative array)
function find_neighbors($tablename,$colnameval,$tolerance) {
  //print_r($colnameval);
  $condition_array=array();
  $coltol_index = 0;
  foreach($colnameval as $colname => $colval) {
    $coltol = (is_array($tolerance)?$tolerance[$coltol_index]:$tolerance);
    $condition_array[]="ABS($tablename.$colname-($colval)) < $coltol"; 
    $coltol_index++;
    }
  $conditions = implode(') AND (',$condition_array);
  //if (count($colnameval)>1)
  $conditions = '('.$conditions.')';  
  return find_rows($tablename,$conditions);
  } 

// ActionHL, vectorize colval and colname to allow multiple ANDed WHERE clauses and allow ORing instead of ANDing if requested
function find_neighbor($tablename,$colnameval,$tolerance) {
  if ($results = find_neighbors($tablename,$colnameval,$tolerance)) {
    if(DEBUG)
      echo "Found ".mysql_num_rows($results)." neighbors, but only returning the first one.".EOL;
    return(mysql_fetch_assoc($results)) ; }
  else
    return 0;
}

function find_location_neighbors($tolerance) {
  $latlons = process_sql("SELECT ID,Lat,Lon,Accuracy from Location");
  while($row=mysql_fetch_assoc($latlons)) {
    $neighbors = find_neighbors('Location',array('Lat'=>$row['Lat'],'Lon'=>$row['Lon']),array($tolerance,$tolerance));
    if (mysql_num_rows($neighbors)>1) {
      // have to be at least 1 match, which is the one we started with, but all the others need to be looked at and combined or deleted
      echo "Number of neighbors found for ".$row['ID']." was " . mysql_num_rows($neighbors) . EOL;
      $original = find_dupe('Location',array('ID'=>$latlons['ID']));
      while($neighbor = mysql_fetch_assoc($neighbors)) {
        if($neighbor['ID']==$original['ID'])
          continue;
        foreach($original as $field_name => $field_value)
          if ( (strlen($neighbor["$field_name"])>strlen($field_value) ) || ( ($neighbor["$field_name"]!=0) && ($field_value==0) ) ) // need to find out what the NULL value or default value is for each field and use that instead of 0
            echo ("Seems like the original ({$original['ID']}) has been superseded by record number {$neighbor['ID']} for field '$field_name'.");
          
        } 
      }
    }
  }


function display_table($tablename,$conditions='') {
  if ($conditions)
    $conditions = ' WHERE (' . $conditions . ')';
  $response = process_sql('SELECT * FROM '.$tablename.$conditions);
  echo "<table fontsize=1><font size=1>\n";
  while ($row = mysql_fetch_row($response))
    echo "<tr><td>".implode("</td><td>", $row)."</td></tr>\n";
  echo "</font></table>\n";
  if($err_msg = mysql_error()) 
    echo "<pre>$err_msg</pre><br />\n";  
  }
  
function add_row($table,$fields) {
  $names = '';
  $values = '';
  foreach ($fields as $name => $value) {
    $value=escape_sql($value); 
    $names .= "$name,";
    $values .= "'$value',";
  }
  if ($len = strlen($names))
    $names[$len-1] = ' '; # get rid of the last comma, actionHL: substr might produce a bit cleaner SQL, but with more cpu cycles
  if ($len = strlen($values))
    $values[$len-1] = ' ';
  process_sql("INSERT INTO $table ($names) VALUES ($values)"); 
  }

   
# ActionHL: come up with a version that doesn't require the ID to be provided, i.e. search for the most similar row and edit it
function change_row($table,$fields,$keys) {
  $q = "UPDATE $table SET ";
  foreach ($fields as $name => $value) {
    $value=escape_sql($value); 
    $q .= "$name = '$value',";
  }
  $q[strlen($q)-1]=' '; // erase the last comma
  $q .= 'WHERE (';
  foreach ($keys as $name => $value) {
    $value=escape_sql($value); 
    $q .= "($table.$name = '$value') AND ";
  }
  $q[strlen($q)-5]=')'; // erase the last AND
  return process_sql(substr($q,0,strlen($q)-4)); # get rid of the last 'AND' and process
}
  
?>
