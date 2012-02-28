<?
include '../dbconnection.php';

// update IM chatbox convo *****************************************************
function updateChatBox() {
    // grab newly added msgs to convo
    // mods by Wally Feb 29/2012
    // instead of using the latest lastMsgID in the DB I'm sending it on the URL from 
	// icemelon_im.php and using it to manage what new messages to send back
	// fixes multiple window/tab's competing taking messages from eachother off the queue problem
	
    if(isset($_GET['newBox'])) {
        list($msgID) = mysql_fetch_array(mysql_query("SELECT m.msgID FROM im_msg m WHERE m.chatBoxID='$_GET[chatBoxID]' ORDER BY m.msgID DESC LIMIT 4,1"));
        $q = mysql_query("SELECT m.username,m.msg,m.msgID FROM im_msg m WHERE m.chatBoxID='$_GET[chatBoxID]' AND '$_GET[lastMsgID]'<m.msgID ORDER BY m.timestamp ASC");
    } else {
        if($_GET['userID'] == 1)
            $q = mysql_query("SELECT m.username,m.msg,m.msgID FROM im_chatbox b, im_msg m WHERE m.chatBoxID=b.chatBoxID AND b.username1='$_GET[username]' AND m.chatBoxID='$_GET[chatBoxID]' AND $_GET[lastMsgID]<m.msgID ORDER BY m.timestamp ASC");
        elseif($_GET['userID'] == 2)
            $q = mysql_query("SELECT m.username,m.msg,m.msgID FROM im_chatbox b, im_msg m WHERE m.chatBoxID=b.chatBoxID AND b.username2='$_GET[username]' AND m.chatBoxID='$_GET[chatBoxID]' AND '$_GET[lastMsgID]'<m.msgID ORDER BY m.timestamp ASC");
    }
    $newConvo = '';
    if(mysql_num_rows($q)) {
        while($r = mysql_fetch_array($q)) {
            $username = ($r['username']==$_GET['username']) ? "<b style='color:#993300'>$r[username]</b>" : "<b style='color:#006699'>$r[username]</b>";   
            $newConvo .= "$username: $r[msg] <br>";
            $last_msgID = $r['msgID'];
        }
        // update msgID
        if($_GET['userID'] == 1)
            mysql_query("UPDATE im_chatbox SET msgID1='$last_msgID' WHERE chatBoxID='$_GET[chatBoxID]' AND accountID='$_GET[accountID]' AND username1='$_GET[username]' LIMIT 1");
        elseif($_GET['userID'] == 2)
            mysql_query("UPDATE im_chatbox SET msgID2='$last_msgID' WHERE chatBoxID='$_GET[chatBoxID]' AND accountID='$_GET[accountID]' AND username2='$_GET[username]' LIMIT 1");
    }
//Wally Mods: send back msg in a var and lastMsgID to be stored on the client
    echo $_GET['jsoncallback'] .'({ "msg":"'.$newConvo.'" , "lastMsgID":"'.$last_msgID.'" });';
}

// create new IM chatbox *******************************************************
if(isset($_GET['submit_newChatBox'])) {
    // check if chatBox exists in db
    $q = mysql_query("SELECT chatBoxID,username1 FROM im_chatbox WHERE accountID='$_GET[accountID]' AND
                        ( (username1='$_GET[username1]' AND username2='$_GET[username2]') OR (username1='$_GET[username2]' AND username2='$_GET[username1]') )
                        LIMIT 1");
    // chatBox doesnt exist
    if(!mysql_num_rows($q)) {
        mysql_query("INSERT INTO im_chatbox (accountID,username1,username2) VALUES ('$_GET[accountID]','$_GET[username1]','$_GET[username2]')");
        $q = mysql_query("SELECT chatBoxID FROM im_chatbox WHERE accountID='$_GET[accountID]' AND username1='$_GET[username1]' AND username2='$_GET[username2]'");
        list($chatBoxID) = mysql_fetch_array($q);
        // user1 = this person, user2 = recipient
        $userID = 1;
    // chatBox exists, need to figure out userID
    } else {
        list($chatBoxID, $username1) = mysql_fetch_array($q);
        // open chatbox flag
        if($username1 == $_GET['username1']) {
            mysql_query("UPDATE im_chatbox SET IMclosed1='0' WHERE accountID='$_GET[accountID]' AND username1='$_GET[username1]' AND username2='$_GET[username2]' LIMIT 1");
            $userID = 1;
        } else {
            mysql_query("UPDATE im_chatbox SET IMclosed2='0' WHERE accountID='$_GET[accountID]' AND username1='$_GET[username2]' AND username2='$_GET[username1]' LIMIT 1");
            $userID = 2;
        }
    }

    echo $_GET['jsoncallback'] ."({ \"chatBoxID\":\"$chatBoxID\" , \"userID\":\"$userID\" });";

// add new IM to db ************************************************************
} elseif(isset($_GET['submit_newMsg'])) {
    // insert IM
    mysql_query("INSERT INTO im_msg (chatBoxID,username,msg,timestamp) VALUES ('$_GET[chatBoxID]','$_GET[username]','$_GET[msg]',now())");
    // update last activity
    mysql_query("UPDATE im_user SET last_activity=now() WHERE accountID='$_GET[accountID]' AND username='$_GET[username]' LIMIT 1");
    // update convo
    updateChatBox();

// update IM chatbox ajax call *************************************************
} elseif(isset($_GET['submit_updateConvo'])) {
    updateChatBox();

// determine IM boxes that need creation ***************************************
} elseif(isset($_GET['submit_missingChats'])) {

    $chatBoxIDsStr = substr( $_GET['chatBoxIDsStr'], 0,-1 );
    if(empty($chatBoxIDsStr)) {
        $q = mysql_query("SELECT c.username1,c.username2 FROM im_chatbox c, im_msg m WHERE c.accountID='$_GET[accountID]' AND m.chatBoxID=c.chatBoxID AND
                        ( (c.username1='$_GET[username]' AND (c.msgID1<m.msgID OR c.IMclosed1='0') ) OR
                          (c.username2='$_GET[username]' AND (c.msgID2<m.msgID OR c.IMclosed2='0') ) )
                          GROUP BY m.chatBoxID");
    } else {
        $q = mysql_query("SELECT c.username1,c.username2 FROM im_chatbox c, im_msg m WHERE c.accountID='$_GET[accountID]' AND m.chatBoxID=c.chatBoxID AND
                        ( (c.username1='$_GET[username]' AND c.chatBoxID NOT IN ($chatBoxIDsStr) AND (c.msgID1<m.msgID OR c.IMclosed1='0') ) OR
                          (c.username2='$_GET[username]' AND c.chatBoxID NOT IN ($chatBoxIDsStr) AND (c.msgID2<m.msgID OR c.IMclosed2='0') ) )
                          GROUP BY m.chatBoxID");
    }

   if(mysql_num_rows($q)) {
        $chatBoxIDs = Array();
        while($r = mysql_fetch_array($q)) {
            $username = ($_GET['username']==$r['username1']) ? $r['username2'] : $r['username1'];
            $chatBoxIDs[]= $username;
        }
        echo $_GET['jsoncallback'] .'('.json_encode($chatBoxIDs).');';
    }

// refresh whos online list ****************************************************
} elseif(isset($_GET['submit_refreshOnline'])) {
    // extract list of users
    $limit_time = time() - 300; // 5 Minute time out. 60 * 5 = 300
    if(!empty($_GET['friends'])) {
        function formatFriends($friend) {
            return "'" .trim($friend) ."'";
        }
        $friends = implode(',', array_map( 'formatFriends', explode(',', $_GET['friends']) ) );
        $q = mysql_query("SELECT username FROM im_user WHERE accountID='$_GET[accountID]' AND username IN ($friends) AND UNIX_TIMESTAMP(last_activity) >= $limit_time AND username!='$_GET[username]' ORDER BY username ASC");
    } elseif($_GET['premium']>26 && !empty($_GET['filter'])) { // mapping $premium = $_GET[premium]-26
        $q = mysql_query("SELECT username FROM im_user WHERE accountID='$_GET[accountID]' AND filterGroup='$_GET[filter]' AND UNIX_TIMESTAMP(last_activity) >= $limit_time AND username!='$_GET[username]' ORDER BY username ASC");
    } else
        $q = mysql_query("SELECT username FROM im_user WHERE accountID='$_GET[accountID]' AND UNIX_TIMESTAMP(last_activity) >= $limit_time AND username!='$_GET[username]' ORDER BY username ASC");
    $onlineUsers = '';
    while($r = mysql_fetch_array($q))
        $onlineUsers .= "<a>$r[username]</a> ";
    echo $_GET['jsoncallback'] .'('.json_encode($onlineUsers).');';

// close chatbox - set flag in db **********************************************
} elseif(isset($_GET['submit_closeChatBox'])) {
    // 2 mysql calls to check for username1 vs username2
    mysql_query("UPDATE im_chatbox SET IMclosed1='1' WHERE chatBoxID='$_GET[chatBoxID]' AND username1='$_GET[username]' AND accountID='$_GET[accountID]' LIMIT 1");
    mysql_query("UPDATE im_chatbox SET IMclosed2='1' WHERE chatBoxID='$_GET[chatBoxID]' AND username2='$_GET[username]' AND accountID='$_GET[accountID]' LIMIT 1");
    // can delete IMs and chat if both users close box
    //$q = mysql_query("SELECT chatBoxID FROM im_chatbox WHERE chatboxID='$_GET[chatBoxID]' AND IMclosed1='1' AND IMclosed2='1' AND accountID='$_GET[accountID]' LIMIT 1");
    //if(mysql_num_rows($q)) {
    //    delete im_chatbox - chatbox
    //    delete im_msg - all corresponding msgs
    //}

    echo $_GET['jsoncallback'] .'({"chatBoxID":"'.$_GET['chatBoxID'].'"});';

// log off *********************************************************************
} elseif(isset($_GET['submit_logOff'])) {
    mysql_query("UPDATE im_user SET loggedOff='1' WHERE username='$_GET[username]' AND accountID='$_GET[accountID]' LIMIT 1");
    echo $_GET['jsoncallback'] .'({"username":"'.$_GET['username'].'"});';

// log off *********************************************************************
} elseif(isset($_GET['submit_logOn'])) {
    mysql_query("UPDATE im_user SET loggedOff='0' WHERE username='$_GET[username]' AND accountID='$_GET[accountID]' LIMIT 1");
    echo $_GET['jsoncallback'] .'({"username":"'.$_GET['username'].'"});';
}
?>