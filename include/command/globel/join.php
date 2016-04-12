<?php

function globel_join($channels){
  foreach(explode(",", $channels) as $chan){
    join_channel($chan);
  }
}

function join_channel($channel){
   if(preg_match("/^\#([a-zA-Z]*?)$/", $channel)){
   	  get_user()->join_channel($channel);
   }else{
      send("ERROR: invalid channel on join command");
   }
}
