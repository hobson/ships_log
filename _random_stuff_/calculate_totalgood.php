<?
include_once "process_sql.php";

function calculate_totalgood($row) {
  $tg = isset($row['Title']);  
  $tg += isset($row['Category']);
  $tg += isset($row['Equipment']);
  $tg += isset($row['StartDate']);
  $tg += isset($row['EndDate']);
  $tg += isset($row['Location']);
  $tg += isset($row['Pub']);
  $tg += isset($row['Confidence']);
  $tg += isset($row['Research']);
  $tg += isset($row['Polish']);
  $tg += isset($row['Worth']);
  $tg += isset($row['Interest']);
  return $tg;
}

function update_totalgood($ArticleID) {
 $key = array('ID'=>$ArticleID);
 $tg = calculate_totalgood(find_dupe('Article',$key));
 change_row('Article',array('TotalGood'=>$tg),$key);
}

?>
