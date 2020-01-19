<?php
include __DIR__ . "/logging.php";

define("Hash_Key", "fa3365ac3758864fffade2be294e4267311d1a8414005bb839a17bd298a8bd56"); //DO NOT CHANGE (while maintaining the current batch)
define("codOK", "p0r5c43"); //DO NOT CHANGE (while maintaining the current batch)
define("log_enabled", 0); //Enable logs for code decryption status
define("redirectLocation", "http://"); //Redirect if code is incorrect


/*
 * @param string $encrypted - message encrypted with safeEncrypt()
 * @param string $key - encryption key
 */
function safeDecrypt(string $encrypted, string $key){   
    $decoded = base64_decode($encrypted);
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

    $plain = sodium_crypto_secretbox_open(
        $ciphertext,
        $nonce,
        $key
    );
    if (!is_string($plain)) {
        throw new Exception('Invalid MAC');
    }
	
	sodium_memzero($ciphertext);
    sodium_memzero($key);
    return $plain;
}


//base2 XOR function with string to dec to bin handling, not used right now..
function _xor($x1,$x2){
	$x1=(string)(decbin(intval($x1)));
	$x2=(string)(decbin(intval($x2)));
	$x1c=strlen($x1);
	$x2c=strlen($x2);
	if($x1c<$x2c){
		//x2>
		for($i=0;$i<$x2c-$x1c;$i++)
			$x1=0 . $x1;
		for($i=0;$i<$x2c;$i++)
			$x2[$i] = intval( boolval($x1[$i]) xor boolval($x2[$i]) );
		return $x2;
	}
	else{
		//x1>
		for($i=0;$i<$x1c-$x2c;$i++)
			$x2=0 . $x2;
		for($i=0;$i<$x1c;$i++)
			$x1[$i] = intval( boolval($x1[$i]) xor boolval($x2[$i]) );
		return $x1;
	}
}


//replace spaces back with +
function replaceSpaces($x){
	$x = str_replace(' ', '+', $x);
	return $x;
}


//Error log creating function
function logReadError(string $err, $code){
	$log = new Logging();
	$log->lfile("./logs/CodeReadErrors.log");
	$log->lwrite("(code:" . $code . ") " . $err);
	$log->lclose();
}




//exit fallback if code is incorrect with logging (if enabled)
function exitRedirect($cod_err, string $err_desc = ""){

if(log_enabled)
switch ($cod_err){
    case 0:
        logReadError("empty field", $cod_err);
        break;
    case 1:
        logReadError("decrypt fail " . $err_desc, $cod_err);
        break;
    case 2:
        logReadError("codOK invalid", $cod_err);
        break;
	case 3:
		logReadError("code already activated", $cod_err);
        break;
	case 4:
		logReadError("client code inexistent in database", $cod_err);
		break;
}

echo $cod_err;
//header("Location: " . redirectLocation); /* Redirect browser */
exit();
}

if(isset($_GET['cod'])){
	//Verificare cod MAC si decryptare string
	$ciphertext = replaceSpaces($_GET['cod']);
	try{
		$plaintext = safeDecrypt($ciphertext, hex2bin(Hash_Key));
	}catch(Exception $t){exitRedirect(1,$t);}

	#echo "string decryptat cu codOK: ";echo $plaintext;echo "<br>";/////////
	
	//Verifying codOK and substracting it from string
	if(substr($plaintext, 0, strlen(codOK)) == codOK){
		$plaintext = substr_replace($plaintext,'',0,strlen(codOK));
	
		#echo "string without codOK: ";echo $plaintext;echo "<br>";/////////
	
	}else exitRedirect(2);
	
	//Do Stuff Below..
	
	echo $plaintext;
	
	
	}

?>