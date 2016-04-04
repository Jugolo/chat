<?php

function globel_login($data){
  send("LOGIN: ".(Session::add_token($data) ? "true" : "false"));
}
