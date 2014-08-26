<?php
add_to_title($locale['global_200'].$locale['chatTitle']);
add_to_head("<script type='text/javascript' src='js/system.js'></script>");
add_to_head("<script type='text/javascript' src='js/lang.js'></script>");
add_to_head("<script type='text/javascript' src='js/sound.js'></script>");
add_to_head("<script type='text/javascript' src='js/j.js'></script>");
add_to_head("<script type='text/javascript' src='js/status.js'></script>");
add_to_head("<script type='text/javascript' src='js/ajax.js'></script>");
add_to_head("<script type='text/javascript' src='js/command.js'></script>");
add_to_head("<script type='text/javascript' src='js/message.js'></script>");
add_to_head("<script type='text/javascript' src='js/cache.js'></script>");

add_to_head("<link rel='stylesheet' type='text/css' href='css/chat.css'>");

function convert_socket_server($server){
    if (!filter_var($server, FILTER_VALIDATE_IP)) {
        return gethostbyname($server);
    }

    return $server;
}

//vi laver nu en start på systemet :)
add_to_head("<script type='text/javascript'>
var system = null;
 window.onload=function(){
  system = new System({
   'li'           : '".jugoloChatClientGetLastId()."',
   'startChannel' : '".getStartChannel()."',
   'protocol' : '".$config['protokol']."',
   'channelColor' : {
      'notThisChannel' : 'blue',
      'thisChannel'    : 'green'
   },
   'browserBlock' : '".session_id()."',
   'timer'        : ".$config['timer'].",
   'user'         : {
      ".jugoloChatClientReturnUserStat()."
   },
   'yesNo' : {
     'yes' : '".Yes."',
	 'no'  : '".No."'
   },
   'systemBot' : {
      ".jugoloChatClientReturnBotStat()."
   },
   'cookie' : '".jugoloChatClientReturnCookie()."',
   'boxColor' : new Array(".jugoloChatClientReturnBoxColorArray()."),
   'socket' : {
   'server' : '".(empty($config['socketServer']) || $config['socketServer'] === null ? '' : convert_socket_server($config['socketServer']))."',
   'port'   : '".(empty($config['socketPort']) || !is_numeric($config['socketPort']) ? '' : $config['socketPort'])."'
   }
  });
  ".jugoloChatCLientAddSmylie()."

 J('#save').onClick(function(){
   system.uploadFilesClick();
 });

 system.showFileName();
 }
</script>");

//vi har brug for at kunne afspille en lyd ;)
echo "<audio id='newInChanMessage'>";
 echo "<source src='sound/dong.wav' type='audio/wave'>";
echo "</audio>";

$t = explode(",",$config['textColor']);
$textColor = array();
for($i=0;$i<count($t);$i++){
	if(preg_match("/^\[(.*?)\]\[(.*?)\]$/",$t[$i],$reg)){
		$textColor[] = "<option value='".$reg[1]."' style='background-color:".$reg[1]."'>".$reg[2]."</option>";
	}
}

opentable("<span id='chattitle'>Jugolo chat</span>");
echo "<div id='chatContainer'>
 <div id='leftContainer'>
   <div id='channelList' class='capmain'>
    <div id='statusIndikator'></div>
   </div>
   <div id='channelMain'>
   
   </div>
   <div id='text'>
    <textarea class='textbox' id='textbox' autocomplete='off' onkeypress='system.onKeyPressWrite(event,this);'></textarea>
   </div>
 </div>
 <div id='rightContainer'>
   <div id='profilImage'>
     <!-- User image is set here! -->
   </div>
   <div id='optionBar'>
    <select id='option' onChange='system.selectOption(this)' class='textbox' style='padding:0;'>
	 <option value='online' langString='OnlineList' langData='{}'></option>
	 <option value='user' langString='setting' langData='{}'></option>
	 <option value='smyile' langString='smylie' langData='{}'></option>
	 <option value='upload' langString='upload' langData='{}'></option>
	</select>
   </div>

   <div class='option' what='online'></div>

   <div class='option' what='smyile'></div>

   <div class='option' what='user'>
    <table id='settingTable'>
	 <caption langString='setting' langData='{}'></caption>
	 <tr class='sOne colorOne'>
	  <th langString='soundSet' langData='{}'></th>
	  <td>
	   <input type='checkbox' id='sound' value='true' onclick='system.clickConfig(\"sound\",this.checked);' checked class='textbox'>
	  </td>
	 </tr>
	 <tr class='sToo colorToo'>
	  <th langString='textColor' langData='{}'></th>
	  <td>
	   <select id='textColor' onChange='system.clickConfig(\"textColor\",this.value)' style='width:100%' class='textbox'>
	    ".implode("\r\n",$textColor)."
	   </select>
	  </td>
	 </tr>
	 <tr class='sOne colorOne'>
	  <th langString='lang' langData='{}'></th>
	  <td>
	   <select id='langSelect' onChange='system.clickConfig(\"lang\",this.value)' class='textbox' style='width:100%;'>
	   </select>
	  </td>
	 </tr>
	 <tr class='sOne colorToo'>
	  <th langTitle='timeEx' langData='{}'><span langString='time' langData='{}'></span></th>
	  <td><input type='text' id='time' value='' onblur='system.clickConfig(\"time\",this.value)' class='textbox' style='width:90%'></td>
	 </tr>
	</table>
   </div>

   <div class='option' what='upload'>
    <table id='uploadTable'>
     <tr class='sOne colorOne'>
      <th langString='uploadFiles' langData='{}'></th>
      <td><img src='".IMAGES."php_save.png' id='save'> <span id='fileName'></span></td>
     </tr>
     <tr class='sOne colorToo'>
      <td colspan='2'>
       <input type='submit' style='width:100%' value='' langString='uploadNow' langData='{}' onclick='system.uploadFile();'>
      </td>
     </tr>
    </table>
   </div>

 </div>
</div>

<!--upload element ;) -->
<div style='display:none'>
 <input type='file' id='upload'>
</div>

<!--Image view area -->
<div id='viewImage'>
 <div id='viewImageTitle'><img src='".IMAGES."no.png' onclick='document.getElementById(\"viewImage\").style.display=\"none\";'></div>
 <div id='viewImages'></div>
</div>
";