<?
Header('Content-type: text/javascript');

include '../dbconnection.php';

// check hash
if(empty($_GET['enc']) || ($_GET['enc']!=md5($_GET['acct'].'githubkey'))) exit();

// check if username is provided
if(empty($_GET['usr'])) exit();

// check if account exists
$account = $_GET['acct'];
$q = mysql_query("SELECT site,premium FROM im_account WHERE accountID='$account' LIMIT 1");
if(!mysql_num_rows($q)) exit();

// check URL matches that on account
list($site,$premium) = mysql_fetch_array($q);
if(!stristr($_SERVER['HTTP_REFERER'],$site)) exit();

// encrypt $premium
$premium += 26;

// generate and register user list *********************************************
$username = (isset($_GET['usr'])) ? $_GET['usr'] : exit();

// check if username exists for account
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$q = mysql_query("SELECT loggedOff FROM im_user WHERE username='$username' AND accountID='$account' LIMIT 1");
if(!mysql_num_rows($q)) { // add to db if not exists
    mysql_query("INSERT INTO im_user (accountID, username, last_activity, filterGroup) VALUES ('$account','$username',now(),'$filter')");
    $loggedOff = 0;
} else {
    list($loggedOff) = mysql_fetch_array($q);
    mysql_query("UPDATE im_user SET last_activity=now(),filterGroup='$filter' WHERE accountID='$account' AND username='$username'");
}
$localpath = ($account == 1) ? 'http://localhost/www/icemelon/IM' : 'http://www.icemelon.com/IM'; // used to set localpath for internal dev

$friends = isset($_GET['friends']) ? $_GET['friends'] : '';

include 'jquery.js';
?>
var nickname = "<?= $username ?>";
var account = "<?= $account ?>";
var loggedOff = "<?= $loggedOff ?>";
var friends = "<?= $friends ?>";
var filter = "<?= $filter ?>";
var lastMsgID=0;

// close IM box ****************************************************************
function closeChatBox(chatBox, chatBoxNo) {
    removeChatBoxParent(chatBox);
    // set closed flag in db
    var chatBoxID_val = chatBoxIDs[chatBoxNo];
    $.getJSON("<?=$localpath?>/server.php?submit_closeChatBox=yes&chatBoxID="+chatBoxID_val+"&username="+nickname+"&accountID="+account+"&jsoncallback=?");
    $(chatBox).hide("fast");
}
function removeChatBoxParent(chatBox) {

    function removeChatBox() {
        // kill reference in arrays
        for (var k=0; k<chatBoxKeys.length; k++) {
            if(chatBoxKeys[k]==chatBox) {
                chatBoxUsers[k] = 'jesuschristpose109828378368786109';
                chatBoxIDs[k] = 'removed';
            }
        }
        // kill DIV
        $(chatBox).remove();
    }
    setTimeout( removeChatBox, 250 );
}

// create IM box ***************************************************************
var chatBoxKeys  = ['asdsadsada'];
var chatBoxUsers = ['woiuewoiuu'];
var chatBoxIDs   = ['removed']; 
var chatBoxUserIDs=[];
function createChatBox(userChat) {
    var chatBoxNo = chatBoxKeys.length;
    chatBoxUsers[chatBoxNo] = userChat; // set chatboxuser
    chatBoxKeys[chatBoxNo]  = "#chatBox"+chatBoxNo; // set chatboxkeys

    // create new chatBox entry in db
    $.getJSON("<?=$localpath?>/server.php?submit_newChatBox=yes&username1="+nickname+"&username2="+userChat+"&accountID="+account+"&jsoncallback=?",
		function(returned_data) {
            chatBoxIDs[chatBoxNo] = returned_data.chatBoxID;
            chatBoxUserIDs[chatBoxNo] = returned_data.userID;

            $("#chatboxes").append( 
                "<div class=chatBox id=chatBox"+chatBoxNo+"> " +
                    "<div class=chatBoxUsername><div style='display:inline; float:right; cursor:pointer' onClick=\"closeChatBox('#chatBox"+chatBoxNo+"','"+chatBoxNo+"')\">[x]</div><div class=userChat>" + userChat + "</div></div>" +
                    "<div class=chatBoxConvo></div> " +
                    "<div class=chatBoxInputDiv><input type=text maxlength=120 class=chatBoxInput></div> " +
                "</div>" );
            bindSendIM();

            // cursor focus on newly created IM box
            $("#chatBox"+chatBoxNo+" .chatBoxInputDiv .chatBoxInput").focus();

            // initiate updateChatConvo for this ChatBox
            updateChatConvoParent(chatBoxNo);
		}
    );
}

// recursive function to poll for new IMs on existing chatBoxes ****************
function updateChatConvoParent(chatBoxNo) { // need this parent function, because of scope w/in setTimeOut - http://blog.paranoidferret.com/index.php/2007/09/06/javascript-tutorial-using-setinterval-and-settimeout/

    var $chatBoxConvo = $("#chatBox"+chatBoxNo+" .chatBoxConvo");
    var chatBoxID_val = chatBoxIDs[chatBoxNo];
    var userID_val = chatBoxUserIDs[chatBoxNo];

    function updateChatConvo() {
//        var $chatBoxConvo = $("#chatBox"+chatBoxNo+" .chatBoxConvo");
//        var chatBoxID_val = chatBoxIDs[chatBoxNo];
//        var userID_val = chatBoxUserIDs[chatBoxNo];
        if(loggedOff==0) {
            $.getJSON("<?=$localpath?>/server.php?submit_updateConvo=yes&chatBoxID="+chatBoxID_val+"&userID="+userID_val+"&username="+nickname+"&accountID="+account+"&lastMsgID="+ lastMsgID +"&jsoncallback=?",
            	function(returned_data) {
		    $chatBoxConvo.append( returned_data.msg );
                    $chatBoxConvo.animate( { scrollTop: 100000 }, "slow" ); // keeps convo scrolled up by default
		    if(returned_data.lastMsgID!='') lastMsgID=returned_data.lastMsgID;
            	}
            );
        }
        // recursive polling
        setTimeout( updateChatConvo, 5000 );
    }

    // initial chat update - grab last 10 msgs in chat
    $.getJSON("<?=$localpath?>/server.php?submit_updateConvo=yes&newBox=yes&chatBoxID="+chatBoxID_val+"&userID="+userID_val+"&username="+nickname+"&accountID="+account+"&jsoncallback=?",
    	function(returned_data) {
            $chatBoxConvo.append( returned_data.msg );
            $chatBoxConvo.animate( { scrollTop: 100000 }, "slow" ); // keeps convo scrolled up by default
		if(returned_data.lastMsgID!='') lastMsgID=returned_data.lastMsgID;
    	}
    );
    setTimeout( updateChatConvo, 5000 );
}

// allow for ENTER detection by binding keyup function *************************
function bindSendIM() {
    $("input.chatBoxInput").unbind('keyup');
    $("input.chatBoxInput").bind('keyup',
        function(e) {
            if(e.keyCode==13) {
                var newConvo = $(this).val();
                $chatBoxConvo = $(this).parents("div").parents("div").children(".chatBoxConvo");
                $chatBoxUsername = $(this).parents("div").parents("div").children(".chatBoxUsername");
                var userChat = $chatBoxUsername.children(".userChat").html();
                for (var k=0; k<chatBoxUsers.length; k++) {
                    if(chatBoxUsers[k]==userChat) { // IM box already exists
                        var chatBoxID_val = chatBoxIDs[k];
                        var chatBoxUserID_val = chatBoxUserIDs[k];
                    }
                }

                // post msg to db
                $.getJSON("<?=$localpath?>/server.php?submit_newMsg=yes&chatBoxID="+chatBoxID_val+"&msg="+newConvo+"&userID="+chatBoxUserID_val+"&username="+nickname+"&accountID="+account+"&jsoncallback=?",
            		function(returned_data) {
			
		    	if(returned_data.lastMsgID!='') lastMsgID=returned_data.lastMsgID;
                        $chatBoxConvo.append( returned_data.msg );
                        $chatBoxConvo.animate( { scrollTop: 100000 }, "slow" ); // keeps convo scrolled up by default
            		}
                );
                $(this).val(""); // clear input field
            }
        }
    );
}

// poll for creation of new chatBoxes ******************************************
function findMissingBoxes() {

    if(loggedOff==0) {
        // grab all existing valid chatBoxIDs
        var chatBoxIDsStr_val = '';
        for(var k=0; k<chatBoxIDs.length; k++) {
            if(chatBoxIDs[k]!='removed') {
                chatBoxIDsStr_val = chatBoxIDsStr_val + chatBoxIDs[k]+",";
            }
        }
    
        $.getJSON("<?=$localpath?>/server.php?submit_missingChats=yes&chatBoxIDsStr="+chatBoxIDsStr_val+"&username="+nickname+"&accountID="+account+"&jsoncallback=?",
            function(returned_data) {
                if( returned_data!="" ) {
                    usernames_return = returned_data;
                    for(var k=0; k<usernames_return.length; k++) {
                        userChat = usernames_return[k];
                        createChatBox( userChat );
                    }
                }
            }
        );
    }
    // recursive poll
    setTimeout(findMissingBoxes, 15000);
}


// load whos online list *******************************************************
function refreshWhosonline() {
    if(loggedOff==0) {
        $.getJSON("<?=$localpath?>/server.php?submit_refreshOnline=yes&username="+nickname+"&accountID="+account+"&friends="+friends+"&filter="+filter+"&premium="+<?= $premium ?>+"&jsoncallback=?",
        	function(returned_data) {
                $("#whosonline_list").html( returned_data );
                setTimeout( bindWhosonline, 200 );
        	}
        );
    }
}
function bindWhosonline() {
    $("#whosonline_list a").unbind('click');
    $("#whosonline_list a").bind('click',    
	    // create new chat box
        function() {
            var userChat = $(this).html();
            var userChatFound = 0;
            // check if IM box exists
            for (var k=0; k<chatBoxUsers.length; k++) {
                if(chatBoxUsers[k]==userChat) { // IM box already exists
                    userChatFound = 1;
                    $(chatBoxKeys[k]+" .chatBoxInputDiv .chatBoxInput").focus();
                }
            }
            if(userChatFound==0) { // create new IM box
                createChatBox(userChat);
            }
        }
    );
    // recursive refresh of whos online
    setTimeout( refreshWhosonline, 25000 );
}

// log out of IM system ********************************************************
function IMLogOff() {
    loggedOff = 1; // set parity bit

    $("#whosonline_status").html("You are logged off IM <br><a href=javascript:IMLogOn()>[Log back in IM]</a>"); // change log off to log on status
    $("#whosonline_list").html(' '); // clear whos online list

    // disable all IM input fields
    $("input.chatBoxInput").unbind('keyup');
    $("input.chatBoxInput").val('You are currently logged off IM');
    $("input.chatBoxInput").attr('readonly', true);

    // set status to logged off in db
    $.getJSON("<?=$localpath?>/server.php?submit_logOff=yes&username="+nickname+"&accountID="+account+"&jsoncallback=?");
}

// log back into IM system *****************************************************
function IMLogOn() {
    loggedOff = 0; // set parity bit
    $("#whosonline_status").html("Logged in as: <b>"+nickname+"</b> <br><a href=javascript:IMLogOff()>[Log off IM]</a>"); // change status

    // enable all IM input fields
    $("input.chatBoxInput").val('');
    $("input.chatBoxInput").removeAttr('readonly');
    bindSendIM();
  
    // set status to logged on in db
    $.getJSON("<?=$localpath?>/server.php?submit_logOn=yes&username="+nickname+"&accountID="+account+"&jsoncallback=?");

    refreshWhosonline(); // load whos online list
    findMissingBoxes(); // re-open IM windows
}

// persistent functions ********************************************************
$(document).ready(

    function() {
        findMissingBoxes();
        refreshWhosonline();
        bindSendIM();
	    $("#whosonline_tab").click(    
		    function() { 				    
                $("#whosonline").toggle("normal");
		    }
        );
    }
);
<?
$html  = "<link rel=stylesheet href='$localpath/icemelon_IM_css.php?acct=$account&color0=$color0&color1=$color1&color2=$color2&color3=$color3' type='text/css'> ";

$html .= "<div id=IMchatwrap> ";

$html .= "  <div id=whosonline> ";
$html .= "      <div id=whosonline_status> ";
if($loggedOff==1) {
$html .= "          You are logged off IM <br><a href=javascript:IMLogOn()>[Log back in IM]</a> ";
} else {
$html .= "          Logged in as: <b>$username</b> <br><a href=javascript:IMLogOff()>[Log off IM]</a> ";
}
$html .= "      </div> ";
$html .= "      <div id=whosonline_list> </div> ";
$html .= "  </div> ";
$html .= "  <div style='clear:both'></div> ";

$html .= "  <div id=chatdiv> ";
$html .= "      <div style='padding:5px 0px 0px 8px; float:left'> ";
$html .= "      IM Sponsor: <a href=http://themanwhosoldtheweb.com/3k-a-month.php target=_blank><b>Make \$3000+ a month off Craigslist</b></a> "; 
$html .= "      </div> ";

$html .= "      <div id=whosonline_tab> ";
$html .= "          <a>Who's Online</a> ";
$html .= "      </div> ";

$html .= "      <div id=chatboxes> ";
$html .= "      </div> ";
$html .= "  </div> ";

$html .= "</div> "; // end IMchatwrap
?>
document.write("<? echo $html ?>");

