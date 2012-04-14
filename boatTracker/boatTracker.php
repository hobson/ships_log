<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
<title>Map of Australis GPS Track</title>

<!-- CSS -->
<link rel="stylesheet" href="boatTracker.css" media="screen,projection" type="text/css" />

<!-- JavaScript -->
<script src="http://maps.google.com/maps?file=api&v=1&key=ABQIAAAALk4BMW_RYDEt_RvogiCDOxRPRhvawkHJ9wdWO_OSRrORpPwjdRSwBzevizOwFgEU9M8wKSSPa6p1IQ" type="text/javascript"></script>
<script type="text/javascript">
var markers = new Array();
<?php
  include_once("_random_stuff_/boatTrackerHeader.php");
?>

</script>

<script src="boatTracker.js" type="text/javascript"></script>

</head>

<body>

<div id="container"> 
<div id="map"></div>
<div id="loadstatus"> 
<div id="information">  
<strong>Distance</strong> : <span id="distanceMessage"></span> 
</div><!-- information -->
<div id="progress"></div>
</div><!-- loadstatus -->
</div><!-- contaner -->

</body>
