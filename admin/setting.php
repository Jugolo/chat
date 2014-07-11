<?php

require_once "../../../maincore.php";
require_once THEMES."templates/admin_header.php";
require_once INFUSIONS."chat/lib/client.php";

loadLang(str_replace("/",null,LOCALESET),"English",$locale);
require_once INFUSIONS."chat/lib/db.php";
require_once INFUSIONS."chat/infusion_db.php";
require_once INCLUDES."infusions_include.php";

if (!checkrights("CHAD") || !defined("iAUTH") || $_GET['aid'] != iAUTH) { redirect("../chat.php"); }

$config   = get_settings("chat");
$error    = array();
$isUpdate = false;

if(isset($_POST['post'])){
	//vi skal nu update post :)
	$sql   = array();

    if(empty($_POST['maxNickLength']) || !trim($_POST['maxNickLength'])){
        $error[] = $locale['emptyMNL'];
    }elseif(!isnum($_POST['maxNickLength'])){
        $error[] = $locale['notIntMNL'];
    }else{
        $sql["maxNickLengt"] = cleanMySQL($_POST['maxNickLength']);
    }

	if(empty($_POST['startChannel']) || !trim($_POST['startChannel'])){
		$error[] = $locale['emptyStartChannel'];
	}elseif(!preg_match("/^#/",$_POST['startChannel'])){
		$error[] = $locale['startChannelBroken'];
	}else{
		$sql["startChannel"] = cleanMySQL($_POST['startChannel']);
	}
	
	if(empty($_POST['botUserName']) || !trim($_POST['botUserName'])){
		$error[] = $locale['emptyBotUserName'];
	}else{
		$sql["botUserName"] = cleanMySQL($_POST['botUserName']);
	}
	
	if(empty($_POST['botUserColor']) || !trim($_POST['botUserColor'])){
		$error[] = $locale['emptyBotUserColor'];
	}else{
		$sql["botUserColor"] = cleanMySQL($_POST['botUserColor']);
	}
	
	if(empty($_POST['botTextColor']) || !trim($_POST['botTextColor'])){
		$error[] = $locale['emptyBotTextColor'];
	}else{
		$sql["botTextColor"] = cleanMySQL($_POST['botTextColor']);
	}
	
	if(empty($_POST['inaktiv']) || !trim($_POST['inaktiv'])){
		$error[] = $locale['emptyInaktiv'];
	}elseif(!isnum($_POST['inaktiv'])){
		$error[] = $locale['notIntInaktiv'];
	}else{
		$sql["inaktiv"] = cleanMySQL($_POST['inaktiv']);
	}
	
	if(empty($_POST['leave']) || !trim($_POST['leave'])){
		$error[] = $locale['emptyLeave'];
	}elseif(!isnum($_POST['leave'])){
		$error[] = $locale['notIntLeave'];
	}else{
		$sql["leave"] = cleanMySQL($_POST['leave']);
	}
	
	if(empty($_POST['websocket'])){
		$error[] = $localec['emptyWebSocket'];
	}else{
		$sql['protokol'] = $_POST['websocket'] === 'true' ? 'socket' : 'ajax';
	}
	
	if(!empty($_POST['websocket']) && $_POST['websocket'] == 'true' && $config['protokol'] == 'socket'){
		if(empty($_POST['socketHost'])){
			$error[] = $locale['emptySocketHost'];
		}else{
			$sql['socketServer'] = cleanMySQL($_POST['socketHost']); 
		}
		
		if(empty($_POST['socketPort']) || !isnum($_POST['socketPort'])){
			$error[] = $locale['emptySocketPort'];
		}else{
			$sql['socketPort'] = (int)$_POST['socketPort'];
		}
	}
	
	if(count($error) == 0 && count($sql) != 0){
		foreach($sql AS $key => $value){
			if(set_setting($key, $value, "chat")){
				$config[$key] = $value;
			}
		}
		
		$isUpdate = true;
	}
}

opentable("<span title='".$locale['chatDec']."'>".$locale['chatTitle']."</span>");
echo "<form method='post' action='?aid=".$_GET['aid']."&amp;update=true&amp;what=setting'>";
echo "<table class='center' style='width:600px'>";
if(count($error) != 0){
    echo "<tr>";
	echo "<th colspan='2' class='tbl' style='background-color:red'>".$locale['Error']."</th>";
	echo "</tr>";
	for($i=0;$i<count($error);$i++) {
		echo "<tr>";
		echo "<th colspan='2' class='tbl' style='background-color:red;'>".$error[$i]."</tr>";
		echo "</tr>";
	}
}elseif($isUpdate){
	echo "<tr>";
	 echo "<th colspan='2' class='tbl' style='background-color:green'>".$locale['configUpdate']."</th>";
	echo "</tr>";
}

 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['maxNickLanght']."</th>";
  echo "<td style='width:70px;' class='tbl'><input type='number' name='maxNickLength' maxlength='255' class='textbox' style='width:100%;' value='".$config['maxNickLengt']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px' class='tbl'>".$locale['startChannel']."</th>";
  echo "<td style='width:70px' class='tbl'><input type='text' name='startChannel' maxlength='255' class='textbox' style='width:100%' value='".$config['startChannel']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['botUserName']."</th>";
  echo "<td style='width:70px class='tbl'><input type='text' name='botUserName' mexlength='255' class='textbox' style='width:100%' value='".$config['botUserName']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['botUserColor']."</th>";
  echo "<td style='width:70px;' class='tbl'><input type='text' name='botUserColor' maxlength='255' class='textbox' style='width:100%' value='".$config['botUserColor']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['botTextColor']."</th>";
  echo "<td style='width:70px;' class='tbl'><input type='text' name='botTextColor' maxlength='255' class='textbox' style='width:100%' value='".$config['botTextColor']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['inaktiv']."</th>";
  echo "<td style='width:70px;' class='tbl'><input type='text' name='inaktiv' maxlength='11' class='textbox' style='width:100%' value='".$config['inaktiv']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['leave']."</th>";
  echo "<td style='width:70px;' class='tbl'><input type='text' name='leave' maxlength='11' class='textbox' style='width:100%' value='".$config['leave']."'></td>";
 echo "</tr>";
 echo "<tr>";
  echo "<th style='width:30px;' class='tbl'>".$locale['useSocket']."</th>";
echo "<td style='width:70px;' class='tbl'><select name='websocket' class='textbox' style='width:100%'><option value='true'".($config['protokol'] == 'socket' ? ' selected' : null).">".$locale['Yes']."</option><option value='false'".($config['protokol'] != 'socket' ? ' selected' : null).">".$locale['No']."</option></select></td>";
 echo "</tr>";
if($config['protokol'] == 'socket'){
	echo "<tr>";
	 echo "<th style='width:30px;' class='tbl'>".$locale['socketHost']."</th>";
	 echo "<td style='width:70px;' class='tbl'><input name='socketHost' class='textbox' style='width:100%' value='".$config['socketServer']."'></td>"; 
	echo "</tr>";
	echo "<tr>";
	 echo "<th style='width:30px;' class='tbl'>".$locale['socketPort']."</th>";
	 echo "<td style='width:70px;' class='tbl'><input name='socketPort' class='textbox' style='width:100%' value='".$config['socketPort']."'></td>";
	echo "</tr>";
}
 echo "<tr>";
  echo "<td colspan='2' class='tbl'>";
   echo "<input type='submit' class='textbox' style='width:100%' value='".$locale['submit']."' name='post'>";
  echo "</td>";
 echo "</td>";
echo "</table>";
echo "</form>";
closetable();

require_once THEMES."templates/footer.php";