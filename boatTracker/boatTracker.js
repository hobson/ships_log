// for the map
 var map;
// var markers;
 var marker;
 var timeOut = 1;  // length to wait till next point is plotted
 var i = 0;
 
 // for display
 var loadingMessage;
 var distanceMessage;
 var bRowColor = false;
 var checkPointCount = 1;
 
 // for distance calculations
 var distance = 0;
 var checkPoint; 
 var checkPointDistance = 0;
 
// CREATE MAP & DISPLAY CONTROLS AND STARTING LOCATION //
function onLoad() {

	// kill if safari
	var detect = navigator.userAgent.toLowerCase();
	if((detect.indexOf("safari") + 1))
        alert("This currently crashes Safari.  We apologize, and are working on a fix.")
	else {
    loadingMessage = document.getElementById('progress');
	  distanceMessage = document.getElementById('distanceMessage');
    map = new GMap(document.getElementById("map"));
	  map.enableScrollWheelZoom();
	  map.setMapType(G_SATELLITE_MAP);
	  control = new GSmallMapControl();
	  map.addControl(control);
	  map.addControl(new GMapTypeControl());		
	  plotPoint();
	}
}


// LOAD THE XML FILE AND PLOT THE FIRST POINT //
function loadInfo(){			 
	var request = GXmlHttp.create();
		
	request.open("GET", "workoutTracker.xml", true);
	request.onreadystatechange = function() {
	if (request.readyState == 4) {
		var xmlDoc = request.responseXML;
		markers = xmlDoc.documentElement.getElementsByTagName("Trackpoint");
		plotPoint()
	}
  }
  request.send(null);
}

function plotPoint(){
	if (i < markers.length ) {			
		var Lat = markers[i][0];
		var Lng = markers[i][1];
			
		var point = new GPoint(Lng, Lat);
		
		marker = createMarker(point,i,markers.length);
			
		if (i < markers.length  && i != 0){
			
			var Lat1 = markers[i-1][0];
			var Lng1 = markers[i-1][1];
				
			var point1 = new GPoint(Lng1, Lat1);

			var points=[point, point1];
			
			//RECENTER MAP EVERY TWO POINTS
			if (i%2==0){map.recenterOrPanToLatLng(point, 2000);}
							
			map.addOverlay(new GPolyline(points, "#0000ff",3,1));
			calculateDistance(Lng, Lat, Lng1, Lat1)
		}
		
	    loadingPercentage(i);
		distanceMessage.innerHTML=Math.round(distance*100)/100 + " miles";
		
		if (i < markers.length - 1){
            window.setTimeout(plotPoint,timeOut);
        }

		i++;
	}
}

// GET THE APPROPRIATE MARKER FOR START, FINISH, CHECKPOINT, AND LINE
function createMarker(point, i, markerLength) {

	var icon = new GIcon();
	icon.shadow = "images/mm_20_shadow.png";
	icon.iconSize = new GSize(12, 20);
	icon.shadowSize = new GSize(22, 20);
	icon.iconAnchor = new GPoint(6, 18);    
	icon.infoWindowAnchor = new GPoint(9, 5);
						
	map.centerAndZoom(point, 12);
	if (i == 0 ){
		icon.image = "images/mm_20_green.png";
	} else if(i == markers.length -1){ 
		icon.iconSize = new GSize(28, 29);
		icon.image = "images/sailboat.png";
	} else {
		icon.image = "images/mm_20_yellow.png";
	}

	var marker = new GMarker(point,{icon:icon, title:markers[i][3]});
	map.addOverlay(marker);
	marker.bindInfoWindowHtml(markers[i][2],{maxWidth:500})
	return marker;
}

// LOADING BAR //
function loadingPercentage(currentPoint){
	var percentage = Math.round((currentPoint/(markers.length - 1)) * 100);
	loadingMessage.style.width = percentage +"%"; 
}

// Function based on Vincenty formula 
// Website referenced: http://www.movable-type.co.uk/scripts/LatLongVincenty.html
// Thanks Chris Veness for this!
//Also a big thank you to Steve Conniff for taking the time to intruoduce to this more accurate method of calculating distance

function calculateDistance(point1y, point1x, point2y, point2x) {  // Vincenty formula

  traveled = LatLong.distVincenty(new LatLong(point2x, point2y), new LatLong(point1x, point1y));
  traveled = traveled * 0.000621371192;  // Convert to miles from meters

  distance = distance + traveled;
  checkPointDistance = checkPointDistance + traveled;
}

/*
 * LatLong constructor:
 *
 *   arguments are in degrees, either numeric or formatted as per LatLong.degToRad
 *   returned lat -pi/2 ... +pi/2, E = +ve
 *   returned lon -pi ... +pi, N = +ve
 */
function LatLong(degLat, degLong) {
  if (typeof degLat == 'number' && typeof degLong == 'number') {  // numerics
    this.lat = degLat * Math.PI / 180;
    this.lon = degLong * Math.PI / 180;
  } else if (!isNaN(Number(degLat)) && !isNaN(Number(degLong))) { // numerics-as-strings
    this.lat = degLat * Math.PI / 180;
    this.lon = degLong * Math.PI / 180;
  } else {                                                        // deg-min-sec with dir'n
    this.lat = LatLong.degToRad(degLat);
    this.lon = LatLong.degToRad(degLong);
  }
}

/*
 * Calculate geodesic distance (in m) between two points specified by latitude/longitude.
 *
 * from: Vincenty inverse formula - T Vincenty, "Direct and Inverse Solutions of Geodesics on the 
 *       Ellipsoid with application of nested equations", Survey Review, vol XXII no 176, 1975
 *       http://www.ngs.noaa.gov/PUBS_LIB/inverse.pdf
 */
LatLong.distVincenty = function(p1, p2) {
  var a = 6378137, b = 6356752.3142,  f = 1/298.257223563;
  var L = p2.lon - p1.lon;
  var U1 = Math.atan((1-f) * Math.tan(p1.lat));
  var U2 = Math.atan((1-f) * Math.tan(p2.lat));
  var sinU1 = Math.sin(U1), cosU1 = Math.cos(U1);
  var sinU2 = Math.sin(U2), cosU2 = Math.cos(U2);
  var lambda = L, lambdaP = 2*Math.PI;
  var iterLimit = 20;
  while (Math.abs(lambda-lambdaP) > 1e-12 && --iterLimit>0) {
    var sinLambda = Math.sin(lambda), cosLambda = Math.cos(lambda);
    var sinSigma = Math.sqrt((cosU2*sinLambda) * (cosU2*sinLambda) + 
      (cosU1*sinU2-sinU1*cosU2*cosLambda) * (cosU1*sinU2-sinU1*cosU2*cosLambda));
    if (sinSigma==0) return 0;  // co-incident points
    var cosSigma = sinU1*sinU2 + cosU1*cosU2*cosLambda;
    var sigma = Math.atan2(sinSigma, cosSigma);
    var alpha = Math.asin(cosU1 * cosU2 * sinLambda / sinSigma);
    var cosSqAlpha = Math.cos(alpha) * Math.cos(alpha);
    var cos2SigmaM = cosSigma - 2*sinU1*sinU2/cosSqAlpha;
    var C = f/16*cosSqAlpha*(4+f*(4-3*cosSqAlpha));
    lambdaP = lambda;
    lambda = L + (1-C) * f * Math.sin(alpha) *
      (sigma + C*sinSigma*(cos2SigmaM+C*cosSigma*(-1+2*cos2SigmaM*cos2SigmaM)));
  }
  if (iterLimit==0) return NaN  // formula failed to converge
  var uSq = cosSqAlpha * (a*a - b*b) / (b*b);
  var A = 1 + uSq/16384*(4096+uSq*(-768+uSq*(320-175*uSq)));
  var B = uSq/1024 * (256+uSq*(-128+uSq*(74-47*uSq)));
  deltaSigma = B*sinSigma*(cos2SigmaM+B/4*(cosSigma*(-1+2*cos2SigmaM*cos2SigmaM)-
    B/6*cos2SigmaM*(-3+4*sinSigma*sinSigma)*(-3+4*cos2SigmaM*cos2SigmaM)));
  s = b*A*(sigma-deltaSigma);
  s = s.toFixed(3); // round to 1mm precision
  return s;
}

window.onload = function() {
onLoad();
}

//var m
//for (m=0;m< markers.length;m++ ) {
//  alert("Marker: "+markers[m][0]+","markers[m][1]);
//}			

