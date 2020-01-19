<?php
//version checking, on versions from [7.0.0 to 7.2.0) enable polyfill sodium compat and disable safe memory erase
//versions < 7.0.0 are not supported properly
$lowversion = 0;
if(version_compare(PHP_VERSION, '7.0.0', '<')){
	throw new Exception('PHP5 not supported');
}
elseif(version_compare(PHP_VERSION, '7.2.0', '<')){
	require_once __DIR__ . "/vendor/sodium_compat-1.5.6/autoload.php";
	$lowversion = 1;
}

/*
 * Encrypt a message
 * string $message - message to encrypt
 * string $key - encryption key
 */
function safeEncrypt(string $message, string $key){
    if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RangeException('Key is not he correct size (must be 32 bytes).');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $cipher = base64_encode(
        $nonce.
        sodium_crypto_secretbox(
            $message,
            $nonce,
            $key
        )
    );
    if(!$GLOBALS['lowversion']){
	sodium_memzero($message);
    sodium_memzero($key);
	}
    return $cipher;
}

/*
 * Decrypt a message
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
    if(!$GLOBALS['lowversion']){
	sodium_memzero($ciphertext);
    sodium_memzero($key);
	}
    return $plain;
}

//Generate new random key and store it in hex
function generateNewKey(){
$key = fopen("cipherKey.key", "w");
fwrite($key, bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
fclose($key);
}

//Check if key exists and is valid, then convert it to bin and load it in memory
function readCipherKey(){
if(!file_exists("cipherKey.key")){
echo "cipherKey.key is missing!<br>";
echo "if this is the first time generating it, you are ok<br>";
echo "generating new key..<br>";
generateNewKey();
}
$read = fopen("cipherKey.key", "r");
$key = fread($read,filesize("cipherKey.key"));
fclose($read);
if (ctype_xdigit($key)==1 && strlen($key)%2==0){
	if (mb_strlen(hex2bin((string)$key), '8bit') == SODIUM_CRYPTO_SECRETBOX_KEYBYTES){
		return hex2bin($key);
	} else echo "Key is not the correct size (must be 32 bytes)<br>";
} else echo "Key is not in valid hex format<br>";
	echo "Generating new key..<br>";
	generateNewKey();
	return readCipherKey();
}

//base2 XOR function with string to dec to bin handling
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

//functie normalizare format cu 0-uri aditionale (10 caractere) reprezentate dintr-un numar din stanga lui 'x'
function normalizeFormat($x1){
	$x1=strlen($x1) . 'x' . (string)$x1;
	return $x1;
}

//replace spaces back with +
function replaceSpaces($x){
$x = str_replace(' ', '+', $x);
return $x;
}

//functie de generare a codului unic final
function generateCode($cod_produs, $qrnr_produs, $codOK){

//generare cod verificare
$cod_verificare = bindec(_xor($cod_produs, $qrnr_produs));

//concatenare cod verificare + cod produs cu format normalizat + codOK de verificare
$concatenare = codOK . normalizeFormat($cod_produs) . $cod_verificare;

//encriptare $concatenare in codul final
$key = readCipherKey();
$ciphertext = safeEncrypt($concatenare, $key);

return $ciphertext;
}


?>