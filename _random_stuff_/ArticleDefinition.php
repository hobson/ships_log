<?php
# Eventually this should be replaced with a markup language or XML that can be easily parsed and "compiled" into something that can quickly execute in PHP
# e.g. <h1><HLTAG>Article->Title</HLTAG></h1><h2><HLTAG>Article->ID->Art2Loc->LocID->Location->Lat</HLTAG></h2>
$articleinitiator = '<div>'."\n";
$articleterminator = "</div>\n<br />\n<br style=\"clear: both;\" />";

# ID
$el=new stdClass;
$el->N = 1;
$el->field       = array('ID'); // all fields that must be rounded up for this display element to be complete
$el->name        = $el->field[0];
$el->reltab      = array('');   // all relationship tables that must be consulted to find the fields
$el->reltabcond  = array_fill(0,$el->N,''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab    = array('');   // all target tables that contain the field of interest
$el->relid       = array('');   // all names of ID column in the reltable that point to the fields of interest
$el->artid       = array('');   // name of parent table's ID column in rel. table, e.g. ArtID in Art2Loc table refering to Article.ID
$el->numformat   = array('');
$el->htmlify     = array(0);    // whether this field is tainted with user data, if so needs to have all control characters htmlified
$el->tag0        = array('');   // html tags to place in front of the fields retrieved from the database
$el->tag1        = array('&nbsp;'.((strlen($el->name)>0)?'':"\n")); // html tags to place at end of fields retrieved from database
$el->eol         = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# Title
$el= new stdClass;
$el->N = 1;
$el->field   = array('Title');// all the fields that must be rounded up for this display element to be complete
$el->name    = $el->field[0];
$el->reltab  = array('');// all the relationship tables that must be consulted to find the fields
$el->reltabcond  = array_fill(0,$el->N,''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab= array('');// all the target tables that contain the field of interest
$el->relid   = array(''); // all the names of the ID column in the reltable that point to the fields of interest
$el->artid   = array(''); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array('');
$el->htmlify = array(1); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array('');  // html tags to place in front of the fields retrieved from the database
$el->tag1 = array('<br />'.((strlen($el->name)>0)?'':"\n")); // html tags to place at the end of the fields retrieved from the database
$el->eol = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# StartDate
$el= new stdClass;
$el->N = 1;
$el->field   = array('StartDate');// all the fields that must be rounded up for this display element to be complete
$el->name    = 'Date';
$el->reltab  = array('');// all the relationship tables that must be consulted to find the fields
$el->reltabcond  = array(''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab= array('');// all the target tables that contain the field of interest
$el->relid   = array(''); // all the names of the ID column in the reltable that point to the fields of interest
$el->artid   = array(''); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array('');
$el->htmlify = array(0); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array('');  // html tags to place in front of the fields retrieved from the database
$el->tag1 = array(''.((strlen($el->name)>0)?'':"\n"));  // html tags to place at the end of the fields retrieved from the database
$el->eol = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# Lat/Lon pair both displayed to the user and within the link to GoogleMaps (hence 2 Lat/Lon pairs)
$el= new stdClass;
$el->N = 4;
$el->field   = array('Lat','Lon','Lat','Lon');// all the fields that must be rounded up for this display element to be complete
$el->name    = ''; #'LatLon'; # elements with a name are wrapped with CSS defined styles
$el->reltab  = array_fill(0,$el->N,'Art2Loc');// all the relationship tables that must be consulted to find the fields
$el->reltabcond  = array_fill(0,$el->N,''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab= array_fill(0,$el->N,'Location');// all the target tables that contain the field of interest
$el->relid   = array_fill(0,$el->N,'LocID'); // all the names of the ID column in the reltable that point to the fields of interest
$el->artid   = array_fill(0,$el->N,'ArtID'); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array(6,6,2,2); // number of digits after the decimal to print out
$el->htmlify = array_fill(0,$el->N,0); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array('<span class="LatLon"><a href="http://maps.google.com/maps?q=',''  ,''     ,'&nbsp;');
$el->tag1 = array(','                                                           ,'">','&deg;','&deg;</a></span>'.((strlen($el->name)>0)?'':"\n"));
$el->eol = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# Author
$el= new stdClass;
$el->N = 1;
$el->field   = array_fill(0,$el->N,'Acronym');// all the fields that must be rounded up for this display element to be complete
$el->name    = 'Author';
$el->reltab  = array_fill(0,$el->N,'Art2Per');// all the relationship tables that must be consulted to find the fields
$el->reltabcond = array_fill(0,$el->N,'Art2Per.Relationship = \'author\''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab= array_fill(0,$el->N,'Person');// all the target tables that contain the field of interest
$el->relid   = array_fill(0,$el->N,'PersonID'); // all the names of the ID column in the reltable that point to the fields of interest
$el->artid   = array_fill(0,$el->N,'ArtID'); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array_fill(0,$el->N,'');
$el->htmlify = array_fill(0,$el->N,0); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array_fill(0,$el->N,' by ');  // html tags to place in front of the fields retrieved from the database
$el->tag1 = array_fill(0,$el->N,''.((strlen($el->name)>0)?'':"\n"));  // html tags to place at the end of the fields retrieved from the database
$el->eol = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# BigDat/SmallDat path, filename, and type: SmallDat displayed to the user, BigDat linked to. Hence 2 triplets of path/filename & type.
# IE fix 10/27/09, changed back to "<img src=" from "<object ... type=" links for small pictures displayed in main page
$el              = new stdClass;
$el->N           = 5; // pre IE fix was 6, unnecessary and dangerous, better to use count()
$el->field       = array('RelativePath','Filename','Type','RelativePath','Filename'); // fields used for this display element
$el->name        = ''; # Data'; # elements with a name are wrapped with CSS defined styles
$el->reltab      = array_fill(0,$el->N,'Art2Dat'); // all the relationship tables that must be consulted to find the fields
$el->reltabcond  = array_fill(0,$el->N,''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab    = array_fill(0,$el->N,'Data');// all the target tables that contain the field of interest
$el->relid       = array('BigDatID','BigDatID','BigDatID','SmallDatID','SmallDatID'); // names of ID columns in reltable that point to needed fields
$el->artid       = array_fill(0,$el->N,'ArtID'); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array_fill(0,$el->N,''); // number of digits after the decimal to print out
$el->htmlify = array_fill(0,$el->N,0); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array('<a href="','' ,'" type="' ,'<img src="' ,'');   # html tags to place in front of the fields retrieved from the database
// mid 2009 turned off autoplay due to annoying overlap of videos/etc in multi-article displays:
// IE fix: changed object ... type tags to img src...</img>, now only 5 elements in array (no second type field required)
$el->tag1 = array(''         ,'' ,'"> '      ,''           ,'" class="FloatRight"></img></a>'.((strlen($el->name)>0)?'':"\n")); 

#                  '"><param name=autostart VALUE=true><param name=autoplay value=true><param name=loop value=false><param name=hidden value=false></object></a>'."\n");  
$el->eol = array_fill(0,$el->N,"<br />\n"); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;

# Article Text
$el= new stdClass;
$el->N = 1;
$el->field   = array('Text');// all the fields that must be rounded up for this display element to be complete
$el->name    = $el->field[0];
$el->reltab  = array_fill(0,$el->N,'');// all the relationship tables that must be consulted to find the fields
$el->reltabcond  = array_fill(0,$el->N,''); // additional conditions (SQL WHERE clauses) on the relationship table that must be met
$el->fieldtab= array('');// all the target tables that contain the field of interest
$el->relid   = array_fill(0,$el->N,''); // all the names of the ID column in the reltable that point to the fields of interest
$el->artid   = array_fill(0,$el->N,''); // the name of the parent table's ID column in the relationship table, e.g. ArtID in the Art2Loc table refering to Article.ID
$el->numformat = array(''); // number of digits after the decimal to print out
$el->htmlify = array_fill(0,$el->N,1); // whether or not this particular field is tainted with user data, and would thus need to have all control characters htmlified
$el->tag0 = array('<br />'."\n");  // html tags to place in front of the fields retrieved from the database
$el->tag1 = array(((strlen($el->name)>0)?'':"\n")); # html tags to place at the end of the fields retrieved from the database
$el->eol = array_fill(0,$el->N,"<br />\n&nbsp; &nbsp; "); // html tags to place at the end of each line (\n) within in the field contents
$els[]=$el;


// echo "<pre>";
// print_r($els);
// echo "</pre>";

?>
