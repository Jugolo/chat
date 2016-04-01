<?php

/**
function to detect if this is cli or not. 
@return bool true if it is cli or false if it not.
*/
function is_cli(){
  return php_sapi_name() == "cli";
}
