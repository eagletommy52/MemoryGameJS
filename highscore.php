<?php
//Open up the database
   $db = new SQLite3('highscore.db');
   if(!$db) {
      echo $db->lastErrorMsg();
   }
//Grab the top 25 scores
   $sql =<<<EOF
   SELECT rowid, initials, score FROM HIGHSCORE ORDER BY score DESC LIMIT 25;  
EOF;
   $jsonResult = [];
   $ret = $db->query($sql);
   if(!$ret){
      echo $db->lastErrorMsg();
   } else {
//Add every row into an 2-d array, also append a 'user' property so we know can highlight the users score later
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
        $row['user'] = false;
        $jsonResult[] = $row;}
   }

$INITIALS;
$SCORE;
//Check to make sure the URL has the two variables INITIALS and SCORE--if not just return the top 25 results as is
if(ISSET($_GET['INITIALS']) && ISSET($_GET['SCORE'])){
    $INITIALS = $_GET['INITIALS'];
    $SCORE = $_GET['SCORE'];
   
// So if we have gotten this far we need to check 2 conditions to see which of 4 possible scenarios we are in:
// 1. Total results are 25+, user score is in the top 25 (add user to db, drop lowest result, return user score in list where applicable)
// 2. Total results are 25+, user score does not make the top 25 (DO NOT add to db, drop lowest result in return list for display)
// 3. Total results less than 25, user score is the lowest (add to db, just append to end)
// 4. Total results less than 25, user score is not the lowest (add to db, append where applicable)

   $lastRecord = count($jsonResult)-1;
   $lastRecordId = $jsonResult[$lastRecord]['rowid'];
   if($jsonResult[$lastRecord]['SCORE'] < $SCORE && count($jsonResult)>=25) {
       // At least 25 records and user score is higher than lowest of the 25
        //echo '<br> Updating lowest score record because 25 records and score is higher than lowest <br>';
        $sql =<<<EOF
        UPDATE HIGHSCORE set initials = '$INITIALS', score = $SCORE WHERE rowid=$lastRecordId;
EOF;
        $ret = $db->exec($sql);
        if(!$ret) {
            echo $db->lastErrorMsg();
        } else {
          array_pop($jsonResult);
          foreach($jsonResult as $key => $result) {
            if($result['SCORE']<$SCORE){
                $insertRecord = ['rowid'=>0, 'INITIALS'=>$INITIALS, 'SCORE'=>$SCORE, 'user'=>true];
                array_splice($jsonResult, $key, 0, array($insertRecord));
                break;
            }
          }
        }
    } else {
        if(count($jsonResult)<25) {
            // Fewer than 25 records
            //echo '<br> Inserting a score into DB because less than 25 records <br>';
            $sql =<<<EOF
            INSERT INTO HIGHSCORE (initials, score) VALUES('$INITIALS', $SCORE);
EOF;
            $ret = $db->exec($sql);
            if(!$ret) {echo $db->lastErrorMsg();}
            else {
                if($jsonResult[$lastRecord]['SCORE'] < $SCORE){
                    //echo '<br> User score is not lowest, inserting into display at proper place <br>';
                    foreach($jsonResult as $key => $result) {
                        if($result['SCORE']<$SCORE){
                            $insertRecord = ['rowid'=>0, 'INITIALS'=>$INITIALS, 'SCORE'=>$SCORE, 'user'=>true];
                            array_splice($jsonResult, $key, 0, array($insertRecord));
                            break;
                        }
                      }
                } else {
                    //echo '<br> User score is the lowest, just push it to the end of the results array <br>';
                    $insertRecord = ['rowid'=>0, 'INITIALS'=>$INITIALS, 'SCORE'=>$SCORE, 'user'=>true];
                    array_push($jsonResult, $insertRecord);
                }
            }
        } else {
            //greater than 25 records but user score is not greater
            //echo '<br> Inserting a score at the bottom because less than 25 records but score is low <br>';
            array_pop($jsonResult);
            $insertRecord = ['rowid'=>0, 'INITIALS'=>$INITIALS, 'SCORE'=>$SCORE, 'user'=>true];
            array_push($jsonResult, $insertRecord);} 
    }
}
   $jsonResult = json_encode($jsonResult);
   echo $jsonResult;
   $db->close();
?>