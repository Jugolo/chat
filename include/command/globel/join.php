<?php

function globel_join($channels){
  foreach(explode(",", $channels) as $chan){
    join_channel($chan);
  }
}

function join_channel($channel){
   if(preg_match("/^\#([a-zA-Z]*?)$/", $channel)){
      $user = get_user();
      $user->join_channel($channel);
      send_channel($channel, "JOIN: ".$user->nick());
   }else{
      send("ERROR: invalid channel on join command");
   }
}
