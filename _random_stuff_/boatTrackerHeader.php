<?php
	$coord_stack = array();
  $url = "http://weblog.skymate.com/sm/page/australis";
  $content = "";
  if (is_readable($url)) {
    $fp = fopen( $url, 'r' ) or die("alert('Skymate url appears to be readable but php was unable to open it.');");
    while( !feof( $fp ) ) {
      $buffer = trim( fgets( $fp, 4096 ) );
      $content .= $buffer;
      }
	  $ps = $content;
	  $ix = 0;
	  $entry = 'class="style13" COLSPAN="2">&nbsp;';
	  while ($ps = stristr($ps, $entry))
	  {
		  $title = stristr($ps, $entry);
		  $title = substr($title, 34);
		  $title = addslashes(trim(substr($title, 0, strpos($title, "&nbsp;"))));
		  $desc = stristr($ps, "<p>");
		  $desc = addslashes(trim(substr($desc, 3, strpos($desc, "</p>")-3)));
		  $LatLon = stristr($ps, "Lat:");
		  $LatLon = sscanf($LatLon, "Lat:%f&deg;&nbsp;Lon:%f");
		  echo "markers.push(new Array($LatLon[0], $LatLon[1], \"$desc\", \"$title\"));\n";
		  $ps = substr($ps, 40);
		  $ix++;
	  }
	  echo "markers.reverse();\n";
	  }
	else
	  echo("alert('Unable to read skymate.com weblog to load position reports.');\n");
  if ($qs = $_SERVER['QUERY_STRING']) {
    $latlon_xmark = array();
    $latlon_xmark = explode($qs,",");
    echo "markers.push(new Array($latlon_xmark[0],$latlon_xmark[0],\"This is the position referred to in the article\",\"Position referred to in the article\"));\n"; 
    }
?>

