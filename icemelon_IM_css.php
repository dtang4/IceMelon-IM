<?
Header('Content-type: text/css');

include 'dbconnection.php';
list($color0,$color1,$color2,$color3) = mysql_fetch_array( mysql_query("SELECT color0,color1,color2,color3 FROM im_account WHERE accountID='$_GET[acct]' LIMIT 1") );
if(empty($color0))
    $color0 = '#f0f0f0'; // lightest, background of chatbox
if(empty($color1))
    $color1 = '#e0e0e0'; // light border
if(empty($color2))
    $color2 = '#b0b0b0'; // medium
if(empty($color3))
    $color3 = '#aaaaaa'; // dark

echo "
#IMchatwrap {
    font-family:arial; font-size:11px; width:100%; text-align:left;
    z-index:100; right:0px; bottom:0px;
    position: fixed;
    _top: expression(document.body.scrollTop+document.body.clientHeight-this.clientHeight);
    _position: absolute; 
}

#whosonline {
    font-size:11px; font-family:arial; line-height:18px; float:right; max-height:250px; overflow:auto;
    border:1px $color2 solid; border-width:1px 1px 0px 1px; width:158px; _width:160px; display:none; background:white;
    height: expression(this.scrollHeight > 250 ? '250px' : 'auto')
    }
#whosonline_list a {display:block; padding:2px 2px 2px 6px; cursor:pointer; background:white; color:black; text-decoration:none;  }
#whosonline_list a:hover {background:$color1; color:black; text-decoration:none }
#whosonline_status {margin:2px; margin-left:3px; border:1px $color1 solid; padding-left:4px; }

#chatdiv {
    width:100%; border:1px $color2 solid; background:$color0; width:100%; padding:0px; margin:0px; height:24px;
    }
#chatdiv a {text-decoration:none }
#chatdiv a:hover {text-decoration:underline }

#whosonline_tab {
    width:160px; float:right; height:24px; text-align:center; cursor:pointer; }
#whosonline_tab a {display:block; padding:7px 2px 4px 2px; font-weight:bold; background:$color1; color:black; text-decoration:none }
#whosonline_tab a:hover {background:$color2; color:black; text-decoration:none }

#chatboxes {float:right; font-family:arial; font-size:11px; color:black }
#chatboxes .chatBox {
    float:right; position:relative; height:164px; width:220px; margin-right:-1px; background:$color1;
    border:1px $color3 solid; bottom:141px; margin-bottom:-141px; _bottom:139px; _margin-bottom:-139px; _border-bottom:0px; }
#chatboxes .chatBoxUsername { padding:2px; font-weight:bold; border-bottom:1px $color1 solid; background:white }
#chatboxes .chatBoxConvo { padding:2px; height:116px; _height:118px; background:$color0; overflow:auto; }
#chatboxes .chatBoxInputDiv { padding:5px 2px 0px 2px;}
#chatboxes .chatBoxInput { border:0px; background:$color1; color:black; width:210px; font-size:11px; }
";
?>
