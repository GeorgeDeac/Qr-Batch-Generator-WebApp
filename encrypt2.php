<?php

define("Hash_Key", "fa3365ac3758864fffade2be294e4267311d1a8414005bb839a17bd298a8bd56");
define("codOK", "p0r5c43");

function safeEncrypt(string $message, string $key){
    if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RangeException('Key is not he correct size (must be 32 bytes)');
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

	sodium_memzero($message);
    sodium_memzero($key);
    return $cipher;
}

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
//Previously generated codes: 474 - 547
$format="0000";
for($i=1;$i<=547;$i++){
$ciphertext=$format;
if(strlen((string)$i)==1)$ciphertext=safeEncrypt("p0r5c43" . "000" . (string)$i, hex2bin(Hash_Key));
if(strlen((string)$i)==2)$ciphertext=safeEncrypt("p0r5c43" . "00" . (string)$i, hex2bin(Hash_Key));
if(strlen((string)$i)==3)$ciphertext=safeEncrypt("p0r5c43" . "0" . (string)$i, hex2bin(Hash_Key));
if(strlen((string)$i)==4)$ciphertext=safeEncrypt("p0r5c43" . (string)$i, hex2bin(Hash_Key));

//$ciphertext = safeEncrypt("p0r5c43" . "0" . (string)$i, hex2bin(Hash_Key));
echo $ciphertext;
echo "<br>";
//echo substr_replace(safeDecrypt($ciphertext, hex2bin(Hash_Key)),'',0,strlen(codOK));
}


?>