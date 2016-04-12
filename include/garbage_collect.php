<?php
function garbage_collect(){
	garbage_channel_member();
}

function garbage_channel_member(){
	Channel::renderChannels(function($name, ChannelData $data){
		$data->controlMemberStatus();
	});
}