<?php
function get_title($channel){
	$c = Channel::get($channel);
	if($c != null && $c->is_member(get_user()->id())){
		send("TITLE ".$channel.": ".$c->title());
	}else{
		send("ERROR: not member in the channel");
	}
}