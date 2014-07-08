<?php
require_once "../../../maincore.php";
require_once THEMES."templates/admin_header.php";
require_once INFUSIONS."chat/locale/".str_replace("/",null,LOCALESET).".php";
require_once INFUSIONS."chat/lib/db.php";
require_once INFUSIONS."chat/infusion_db.php";
require_once INCLUDES."infusions_include.php";

if (!checkrights("CHAD") || !defined("iAUTH") || $_GET['aid'] != iAUTH) { redirect("../chat.php"); }
$config   = get_settings("chat");

if($config['protokol'] != 'socket'){
	opentable($locale['notSocket']);
	echo "<table style='width:600px;background-color:red;' class='center'>";
	echo "<tr>";
	echo "<th>".$locale['notSocketMsg']."</th>";
	echo "</tr>";
	echo "</table>";
	closetable();
}elseif(!function_exists("exec")){
	opentable($locale['notExec']);
	echo "<table style='width:600px;background-color:red;' class='center'>";
	echo "<tr>";
	echo "<th>".$locale['notExecMsg']."</th>";
	echo "</tr>";
	echo "</table>";
	closetable();
}else{
    $error = array();
    if(!empty($_GET['openSocket']) && !empty($_POST)){
        if(empty($_POST['phppath'])){
            $error[] = $locale['phppathMissing'];
        }

        if(empty($_POST['serverpath'])){
            $error[] = $locale['serverpathMissing'];
        }

        if(empty($error)){
            set_setting("phppath",$_POST['phppath'],"chat");
            set_setting("serverpath",$_POST['serverpath'],"chat");
            $config['phppath']    = $_POST['phppath'];
            $config['serverpath'] = $_POST['serverpath'];
            exit((string)popen($_POST['phppath'],$_POST['serverpath']));
        }
    }

    if(!empty($error)){
        opentable($locale['socketError']);
        for($i=0;$i<count($error);$i++){
            echo "<div style='background-color:red;text-align:center;'>".$error[$i]."</div>";
        }
        closetable();
    }

    opentable($locale['openSocketTitle']);
    echo "<form method='post' action='?aid=".$_GET['aid']."&amp;openSocket=true'>
    <table style='width:600px;' class='center'>
     <tr>
      <th class='tbl' style='width:30%'>".$locale['phpPath']."</th>
      <td class='tbl' style='width:70%'><input type='text' style='width:100%' class='textbox' name='phppath' value='".(empty($config['phppath']) ? null : $config['phppath'])."'><td>
     </tr>
     <tr>
      <th class='tbl' style='width:30%'>".$locale['socketPath']."</th>
      <td class='tbl' style='width:70%'><input type='text' style='width:100%' class='textbox' name='serverpath' value='".(empty($config['serverpath']) ? null : $config['serverpath'])."'></td>
     </tr>
     <tr>
      <td class='tbl' colspan='2'>
       <input type='submit' value='".$locale['openSocket']."' class='textbox' style='width:100%'>
      </td>
     </tr>
    </table>
    </form>";
    closetable();
}

require_once THEMES."templates/footer.php";