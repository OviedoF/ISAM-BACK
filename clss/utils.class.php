<?php
class Utils{
	public function decryptVar($uriString){
        return base64_decode(strrev(base64_decode($uriString)));
    }
    public function encryptVar($uriString){
        return base64_encode(strrev(base64_encode($uriString)));
    }
    public function hideID($id){
    	return ($id*10000)/2;
    }
    public function showID($id){
    	return ($id/10000)*2;
    }


    public function json_last_error_msg() {
    	/*
		0 = JSON_ERROR_NONE
		1 = JSON_ERROR_DEPTH
		2 = JSON_ERROR_STATE_MISMATCH
		3 = JSON_ERROR_CTRL_CHAR
		4 = JSON_ERROR_SYNTAX
		5 = JSON_ERROR_UTF8
	    */
	    $ERRORS = array(
            JSON_ERROR_NONE => 'No error',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error or json is null',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        $error = json_last_error();
        return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
    }
}

?>