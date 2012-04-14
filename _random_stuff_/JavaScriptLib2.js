//alert('This is the beginning of the included javascript file');

function getXMLHTTPRequest() {
//alert('This is the get XMLHTTPRequest function');
  try {
    request = new XMLHttpRequest();
    } catch(err1) {
    try {
      request = new ActiveXObject("Msxml2.XMLHTTP");
      } catch(err2) {
      try {
        request = new ActiveXObject("Microsoft.XMLHTTP");
        } catch(err3) {
      request = false;
      }
    }
  }
  return request;
}

var req = getXMLHTTPRequest();

function useResponse() { 
//alert('This is the useResponse function');
  if (req.readyState == 4) {
    if (req.status == 200) {
      var mytext = req.responseText;
      document.getElementById('maincenter').innerHTML = req.responseText;
    } else {
      document.getElementById('maincenter').innerHTML = "<p>Server isn't done yet. Status = " + req.status + "</p>";
    }
  } else {
    document.getElementById('maincenter').innerHTML = "<p>Retrieving article text from the database...</p>";
  }
}

function getServerText(ids,query) {
  if(query) {
    if(ids)
      serve_url="ships_log.php?wrap=1&id="+ids+"&"+query;
    else
      serve_url="ships_log.php?wrap=1&"+query;
    }
  else
    if(ids)
      serve_url="ships_log.php?wrap=1&id="+ids;
    else {
      alert("Invalid getServerText function call");
      return;
    }
  //alert("This is url being passed to ships log: "+ serve_url);
  req.open('GET',serve_url,true);
  req.onreadystatechange=useResponse;
  req.send(null);
}

//alert('This is the middle of the included file');

// month is zero offset (0-11)
function monthText(month) {
  switch(month) {
    case 0:
      return "January";
		case 1:
		  return "February";
		case 2:
		  return "March";
		case 3:
		  return "April";
		case 4:
		  return "May";
		case 5:
		  return "June";
		case 6:
		  return "July";
		case 7:
		  return "August";
		case 8:
		  return "September";
		case 9:
		  return "October";
		case 10:
		  return "November";
		case 11:
		  return "December";    
		default:
		  return ("InvalidMonthText("+month+")")
		}
}

function zeroFill(number,digits) {
  s = "" + number;
  for(d=digits-1;d>0;d--)
    if (number<Math.pow(10,d))
      s = "0" + s;
  return s;
}

var leapYear = 2008

// month is zero offset (0-11)
function monthDays(month,year) {
  switch(month) {
    case 0: // Jan
		case 2:
		case 4:
		case 6:
		case 7:
		case 9:
		case 11: // Dec
      return 31
		case 1: // Feb, must deal with leap years
		  var ld = 0
		  if ( (year % 4) ==0)
		    ld = 1;
		  return 28+ld
		case 3:
		case 5:
		case 8:
		case 10: // Nov
		  return 30
		default:
		  return 0
		}
  }

// Get the current date for use in date-based links to database
var now = new Date();

// month is zero offset (0-11), but date is 1 offset (1-31)
function displayRecent(months) {
  //alert("date = " + zeroFill(now.getDate(),2));
  var s0 = "critcol=StartDate&critmin="
  var s1 = "000000&critmax="
  var s2 = "235959"
  var s3 = "</a><br />\n"
  var y1 = now.getFullYear()
  var m1 = now.getMonth()
  var d1 = now.getDate()
  var y0 = y1
  var d0 = d1
  var m0 = m1-months // actionHL: need to use decrementMonth() or subtractMonths() functions
  while (m0<0) {
     y0 = y0-1
     m0 = m0 + 12
     }
  while (d0>monthDays(m0,y0))
     d0 = d0-1
  getServerText('',s0 + y0 + zeroFill(m0+1,2) + zeroFill(d0,2) + s1 + y1 + zeroFill(m1+1,2)  + zeroFill(d1,2) + s2)
}

// month is zero offset (0-11), but date is 1 offset (1-31)
function displayRecentLink(months) {
  var s0 = "<a href=\"javascript:getServerText('','critcol=StartDate&critmin="
  var s1 = "000000&critmax="
  var s2 = "000000')\">"
  var s3 = "</a><br />\n"
  var y1 = now.getFullYear()
  var m1 = now.getMonth()
  var d1 = now.getDate()
  var y0 = y1
  var d0 = d1
  var m0 = m1-months
  while (m0<0) {
     y0 = y0-1
     m0 = m0 + 12
     }
  while (d0>monthDays(m0,y0))
     d0 = d0-1
  document.write(s0 + y0 + zeroFill(m0+1,2) + zeroFill(d0,2)  + s1 + y1 + zeroFill(m1+1,2)  + zeroFill(d1,2) + s2 + "Recent " +months+" Months" +s3)
}

// designed to work for negative months as well
function addMonths(months, date) {
//  var m = date.getMonth()
  date.setMonth(date.getMonth() + months ) // 0-11 // no % 12 or rollover of the year needed here, the setMonth function takes care of it!
 // date.setFullYear(date.getFullYear() + Math.floor((m + months) / 12))
  return date
}


function subtractMonths(months, date) {
  date = addMonths(-1*months, date)
  return date
}


// add 1 month to the date and rollover to January and advance the year if necessary
function incrementMonth(date) {
  d = date.getDate();
  date.setDate(Math.min(d,28)); // so that incrementing the month doesn't cause an invalid date (don't know if javascript will report an error or not)
  if (date.getMonth()>10) {
    date.setFullYear(date.getFullYear()+1);
    date.setMonth(0); // month is 0-11, date is 1-31
    }
  else {
    date.setMonth(date.getMonth()+1);
    }
  date.setDate(Math.min(monthDays(date.getMonth()),d)); 
  return date;
}

// add 1 month to the date and rollover to January and advance the year if necessary
function decrementMonth(date) {
//  alert("decMonths = " + date.getFullYear() + "/" + date.getMonth() + "/" + date.getDate());
  var d = date.getDate();
  date.setDate(Math.min(d,28)); // so that incrementing the month doesn't cause an invalid date (don't know if javascript will report an error or not)
//  alert("decMonths1 = " + date.getFullYear() + "/" + date.getMonth() + "/" + date.getDate());
  if (date.getMonth()<1) {
    date.setFullYear(date.getFullYear()-1);
    date.setMonth(11); // month is 0-11, date is 1-31
    }
  else {
//    alert("decMonths else = " + date.getFullYear() + "/" + date.getMonth() + "/" + date.getDate());
//    alert("decMonths m = " + m);
    date.setMonth(date.getMonth()-1);
//    alert("decMonths else2 = " + date.getFullYear() + "/" + date.getMonth() + "/" + date.getDate());
    }
//  alert("monthdays = " + monthDays(date.getMonth()))
  date.setDate(Math.min(monthDays(date.getMonth(),date.getYear()),d)); 
//  alert("decMonths2 = " + date.getFullYear() + "/" + date.getMonth() + "/" + date.getDate());
  return date;
}

// add any number of days to a date, rolling over the month and year as necessary
// action HL: for large numbers of days, need to add to year first before counting up the days to the date of interest, keeping track of leap years
// action HL: setDate() probably handles all this without any help! try positive and negative offsets to get/setDate()!
function addDays(days, date) {
  var d = date.getDate()+days;
  var m = date.getMonth();
  if (days > (monthDays(date.getMonth(),date.getYear()) - date.getDate())) {
    days -= (monthDays(date.getMonth(),date.getYear()) - date.getDate() + 1);
    date.setDate(1);
    date = incrementMonth(date);
    while (days > (monthDays(date.getMonth(),date.getYear()) - date.getDate())) {
      date = incrementMonth(date);
      days -= monthDays(date.getMonth(),date.getYear()); // this accounts for leap years by putting 29 days in Feb as needed
      }
    }
  date.setDate(date.getDate() + days); 
  return date;
}

// add 1 day to a date and rollover to the first of the next month or next year as necessary
function incrementDay(date) {
  if (date.getDate()>=(monthDays(date.getMonth(),date.getYear())-1)) { // this accounts for leap years by putting 29 days in Feb as needed
    date = incrementMonth(date); // incrementMonth will rollover to January and advance the year if necessary
    date.setDate(1); // month is 0-11, date is 1-31
    }
  else {
    date.setDate(date.getDate()+1);
    }
  return date;
}

// add 1 day to a date and rollover to the first of the next month or next year as necessary
function decrementDay(date) {
  if (date.getDate()<=1) { // month is 0-11, date is 1-31
    date = decrementMonth(date); // incrementMonth will rollover to January and advance the year if necessary
    date.setDate(monthDays(date.getMonth(),date.getYear())); 
    }
  else {
    date.setDate(date.getDate()-1);
    }
  return date;
}

// month is zero offset (0-11), but date seems to be 1 offset (1-31)
var t0 = new Date();
t0.setMonth(8);
t0.setDate(1);
t0.setFullYear(2008);


function displayMonthLinks(months) {  
  var s0 = "<a href=\"javascript:getServerText('','critcol=StartDate&critmin="
  var s1 = "01000000&critmax="
  var s2 = "01000000')\">"
  var s3 = "</a><br />\n"
  //alert("Months = " + months)
  var order = 1
  if (months<=0)
    order = -1
  //alert("order = " + order) 
  months = Math.abs(Math.min(months,(now.getFullYear() - t0.getFullYear())*12 + now.getMonth() - t0.getMonth() + 1))
  //alert("Months2 = " + months )
  var date0 = new Date();
  var date1 = new Date();
  if(order>0) {
    date0 = addMonths(1,t0)
    date1 = addMonths(months+1,date0)
    while((date0.getFullYear()<date1.getFullYear()) || (date0.getMonth()<date1.getMonth())) {
      y = date0.getFullYear(); // necessary because increment month might advance the year before all text is written to browser
      m = date0.getMonth(); // necessary because increment month might advance the year before all text is written to browser
      date0 = incrementMonth(date0);
      document.write(s0 + y + zeroFill(m+1,2) + s1 + date0.getFullYear() + zeroFill(date0.getMonth()+1,2) + s2 + monthText(m) + " " + y + s3)
      }
    }
  else {
    var date0 = subtractMonths(months-1,date0)
    var date1 = addMonths(1,date1)
    while((date1.getFullYear()>date0.getFullYear()) || (date1.getMonth()>date0.getMonth())) { // is it possible to use > on the Date() objects directly? assignment using = seems to work
      //alert("date0 = " + date0.getFullYear() + "/" + date0.getMonth() + "/" + date0.getDate());
      y = date1.getFullYear(); // necessary because decrementMonth() may decrease the year before all text is written to browser
      m = date1.getMonth(); // necessary because decrementMonth() will decrease the month before all text is written to browser
      date1 = decrementMonth(date1);
//      alert("y/m = " + y + "/" + m);
//      alert("date1 = " + date1.getFullYear() + "/" + date1.getMonth() + "/" + date1.getDate());
      document.write(s0 + date1.getFullYear() + zeroFill(date1.getMonth()+1,2) + s1 + y + zeroFill(m+1,2) + s2 + monthText(date1.getMonth()) + " " + date1.getFullYear() + s3)
      }
    }
  }




//alert('This is the end of the included file');

