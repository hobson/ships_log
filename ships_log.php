<?php
# too many SQL calls, but at least it works with multiple pictures/datafiles per article

# Input arguments and defaults
# ActionHL: This first set should probably be protected from user input, also need to use POST instead of GET
$ids = '';
if ( $s_ids = $_GET['id'] )
  $ids = explode(",", $s_ids);
if ( !($tablename = $_GET['table']) )
  $tablename = "Article";
if ( !($dbdn = $_GET['db']))
  $dbdn = "boat_knowledge";
$go = $_GET['go'];
if (! isset($go))
  $go = 1;
$verbose = $_GET['verbose'];
if (! isset($verbose))
  $verbose = 0;
$critcol = $_GET['critcol'];
if (! isset($critcol))
  $critcol = '';#'TotalGood';
$critmin = $_GET['critmin'];
if (! isset($critmin))
  $critmin = 0;
$critmax = $_GET['critmax'];
if (! isset($critmax))
  $critmax = 20110101000000;
$wrap = $_GET['wrap'];
if (! isset($wrap)) 
  $wrap = 2; # 0 = bare ascii text, 1 = wrap individual articles with html, 2 = include style sheets and other includes to create an entire html page
#echo "\$wrap=$wrap<br>";
$sort = $_GET['sort'];
if (! isset($sortcol)) 
  $sort = 'StartDate-' ;
# this is extremely clumsy:
$sortord=(int)(substr($sort,-1)."1");
$sortcol=trim($sort,"+- \t\n\r\0\x0B");
if ($sortord<0)
  $sortstring=" ORDER BY $sortcol DESC";
else
  $sortstring=" ORDER BY $sortcol ASC";

include_once ("./_random_stuff_/ArticleDefinition.php");
include_once ("./_random_stuff_/process_sql.php");

# see if there are some additional IDs, based on the critcol filter that need to be added to the article ID array
$tablename = 'Article';
$id_query = '';
if ($ids) {
  $id_query = "$tablename.ID IN (";
  foreach($ids as $id)
    $id_query .= "$id,";
  $id_query = trim($id_query,","); # silly way to delete the trailing comma
  $id_query .= ")";
  } // if($ids)
# a single query should be able to capture all the article requested
# Action HL: add option to combine multiple thresholds and ID lists with ANDs or ORs -- essentially expose the SQL to the ships_log.php interface
if( strlen($critcol)>0 ) {
  if (($ids) && (strlen($id_query)>0))
    $query = "SELECT * FROM $tablename WHERE ((($id_query) OR (($critcol <= $critmax) AND ($critcol >= $critmin)))$security_condition)$sortstring";
  else
    $query = "SELECT * FROM $tablename WHERE ((($critcol <= $critmax) AND ($critcol >= $critmin))$security_condition)$sortstring";
  } # if(strlen($critcol)>0) 
else {
  if (($ids) && (strlen($id_query)>0))
    $query = "SELECT * FROM $tablename WHERE (($id_query)$security_condition)$sortstring";
  else  { # no critcol and no IDs requested! now what? get them all?
    $query = "SELECT * FROM $tablename WHERE ((ID > 0)$security_condition)$sortstring";
    }
  }

# echo htmlentities(iconv('UTF-8', 'UTF-8//IGNORE', $query), ENT_QUOTES, 'UTF-8', false);  
if($result = process_sql($query)) # Action HL, catch exceptions
  $N_art = mysql_num_rows($result);
else $N_art = 0;

# for each aricle ID
#  for each display element
#   get the number of fields in the display element (N) 
#   for each field in a display element
#    get the # of rows in the relationship table that match the article ID (M): select bigpicid from art2dat where artid = article.id, M0 = max(mysql_num_rows(result)), if M0>1 M=M0
#   size a multidimensional array to hold all the fields (N,M)
#   for each field in a display element (N)
#    select relativepath from data where art2dat.artid = article.id AND data.id = art2dat.bigpicid
#    for each field in a relationship table (M) 
#     retrieve the appropriate field: row = mysql_fetch_assoc(result), field = row[fieldname]
#     

# for each row in the relationship table with the right article ID (could be many)
# retrieve the fields in the article table or the related tables (using the relationship table)

if($wrap>1) { // wrap = 0 = no HTML (bare field text), wrap = 1 = wrap only the articles, wrap = 2 = wrap the whole document (wrap around the set of articles to create a complete HTML document)
 $html_content = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title>Sailing Experiences</title>
  <script language="JavaScript" src="_random_stuff_/JavaScriptLib2.js" type="text/javascript"></script>
</head>
<body>
<style type="text/css" media="all">
  @import "styles.css";
</style>
EOT;
}
else
 $html_content = '';
for($i=0;$i<$N_art;$i++) {
  $html_content .= $articleinitiator;
  # $tmp = htmlentities(iconv('UTF-8', 'UTF-8//IGNORE', $html_content), ENT_QUOTES, 'UTF-8', false);  
  # $tmplen = strlen($tmp);
  # echo "at start of article html content is $tmplen long:<br>";
  # echo $tmp;
  
  $row = mysql_fetch_assoc($result); # Action HL, catch exceptions or die on failure
  $id = $row['ID'];
  # action HL, this is redundant with the previous select, just need to store all the rows in an array that can be accessed later
  # echo "i=$i, id=$id<br />\n";
  # $result = '';
  #   action HL: define CSS styles that affect layout (page breaks) and formating of Author, Dates, Text, need user-defined control of some formating
  
  # echo "Starting at top of foreach(element) loop by looping through the elements<br />\n";
  foreach($els as $j => $el) {
    $s = '';
    //echo "Working on el[$j] which starts with field {$el->field[0]}...<br />\n";
    $numrows = 1;
# action HL: do all this preprocessing within ArticleDefinition.php and perhaps then export the definition to something quickly loadable, to reduce PHP run time
    unset($result2);
    #reset($prev_result2);
    for($k=0; $k<$el->N; $k++) { // for each of the fields in this group that makes up a single display element
      if ($el->reltab[$k]) {
        $whereand = '';
        if (strlen($el->reltabcond[$k])>0) 
          $whereand = " AND {$el->reltabcond[$k]}";
        $numrows = 0;
        if($result2 = process_sql("SELECT * FROM {$el->reltab[$k]} WHERE (({$el->reltab[$k]}.{$el->artid[$k]} = $id)$whereand)")) { 
          $numrows = max($numrows,mysql_num_rows($result2));
          } // if($result2...
        }
    } # for($k=0; $k<$el->N; $k++) 
    # echo "...So there are at most $numrows entries in the reltab table for each field in this element.<br>";
    $element_fields=array();
    for($k=0; $k<$el->N; $k++) { // for each of the fields in this group that makes up a single display element
      $element_fields[]=array();
      if ($el->reltab[$k]) { // if we need to follow the trail through a relationship table then ...
        $result2 = process_sql("SELECT {$el->fieldtab[$k]}.* FROM {$el->fieldtab[$k]},{$el->reltab[$k]} WHERE {$el->reltab[$k]}.{$el->artid[$k]} = $id AND {$el->fieldtab[$k]}.ID = {$el->reltab[$k]}.{$el->relid[$k]}"); 
        $m=0;
        while($row2=mysql_fetch_assoc($result2)) {
          $element_fields[$k][$m]=$row2["{$el->field[$k]}"];
          if(DEBUG)
            echo "element_fields[$k][$m]={$element_fields[$k][$m]}<br />\n";
          $m++;
          }
        if ((DEBUG)&&($m != $numrows))
          echo "!!!!!!!!!!Error processing relationship table entries in ships_log.<br>\n";
        } // if ($el->reltab[$k...
      else { // it's not a relationship table chain so we can go directly to the Article Field and retrieve it
        $element_fields[$k][0]=$row["{$el->field[$k]}"];
        # echo "raw field found '{$element_fields[$k][$m]}' for $k,$m from {$el->field[$k]}<br />\n"; 
        }
      } # for($k=0; $k<$el->N; $k++) 
    for($m=0; $m<$numrows; $m++) { // for each of the repeats of the set of fields in this display element 
      $s_el='';
      for($k=0; $k<$el->N; $k++) { // for each of the fields in this display element
        # so now we should have all the results we need and no need to process any more sql   
        # each of the result[$k] elements could contain many rows, but they should all contain the same number of rows (if they are reltab)
        $s = $element_fields[$k][$m];
        if (DEBUG)
          echo "element_fields[$k][$m]='$s'<br />\n";

        if ($wrap) {
          if ($el->numformat[$k]) 
            $s = number_format($s,$el->numformat[$k]);
          if ($el->htmlify[$k]) // check to see if this field is tainted with user text and control characters need to be encoded for display using html
            $s = htmlentities(iconv('UTF-8', 'UTF-8//IGNORE', $s), ENT_QUOTES, 'UTF-8', false);
          if (strlen($el->eol[$k])>0)
            $s = str_replace  ("\n",$el->eol[$k],$s);
          $s_el .= $el->tag0[$k].$s.$el->tag1[$k]; # add the line initiator and terminator to the html  
          }
        else
          $s_el .= $s; // no tags to wrap around this one
      } # for($k=0; $k<$el->N; $k++) 
      if (($s_el)&&strlen($s_el))
        if (($el->name)&&($wrap)) // if this element has a CSS class or id definition then span it and terminate the line (for human readability of HTML, not display in browsers...
          $html_content .= "<span class=\"{$el->name}\">$s_el</span>\n"; # append the $rows string with the html tags around text from the database.
        else
          $html_content .= $s_el; 
      } # foreach($element_fields[$k]...
    unset($result2); // ActionHL: also need to unset when the reltab variable changes, not just when the element changes
    } # foreach($els as $j => $el) 
  $html_content .= $articleterminator;
  } # for($i=0;$i<$N_art;$i++) { ...
if($wrap>1)
  $html_content .= "</body>\n</html>\n";
echo $html_content;
mysql_close($dblink);
?>

