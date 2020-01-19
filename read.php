<?php
include __DIR__ . "/encrypt.php";
include __DIR__ . "/db/connect.php";
include __DIR__ . "/logging.php";

define("max_cod_produs_char_size", 10);
define("max_qr_nr_char_size", 10);
define("log_enabled", 1);
define("redirectLocation", "http://localhost/Qr/index.html");
define("codOK", "OKE");
define("procentPuncte", 30);

//File log creating function
function logReadError(string $err, $code){
	//echo $err;
	$log = new Logging();
	$log->lfile("./logs/QrRead/errors.log");
	$log->lwrite("(code:" . $code . ") " . $err);
	$log->lclose();
}


/* Error codes:
 * 0:empty field
 * 1:decrypt fail + extra error description
 * 2:codOK invalid
 * 3:encountered chars other than x, while searching for the nr of chars of the cod_produs
 * 4:cod_produs is above the limit of " . max_cod_produs_char_size . " characters"
 * 5:string end reached while searching for the nr of chars of the cod_produs
 * 6:cod_produs doesn't exist in database
 * 7:extracted qr_nr is above the limit of " . max_qr_nr_char_size . " characters"
 * 8:qr_nr already registered in database
*/

//Exit fallback with error code handling and redirect
function exitRedirect($cod_err, string $err_desc = ""){
if(log_enabled)
switch ($cod_err) {
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
        logReadError("encountered chars other than x, while searching for the nr of chars of the cod_produs", $cod_err);
        break;
	case 4:
        logReadError("cod_produs is above the limit of " . max_cod_produs_char_size . " characters", $cod_err);
        break;
	case 5:
        logReadError("string end reached while searching for the nr of chars of the cod_produs", $cod_err);
        break;
	case 6:
        logReadError("cod_produs doesn't exist in database", $cod_err);
        break;
	case 7:
        logReadError("extracted qr_nr is above the limit of " . max_qr_nr_char_size . " characters", $cod_err);
        break;
	case 8:
        logReadError("qr_nr already registered in database", $cod_err);
        break;
	}

header("Location: " . redirectLocation); /* Redirect browser */
exit();
}


//TODO Implement database calls
function addPoints($cod_produs){
#	if(procentPuncte>1 && procentPuncte<=100)
#		$points = $cod_produs->$pret * (procentPuncte / 100);
#	elseif(procentPuncte>0 && procentPuncte<0)
#		$points = $cod_produs->$pret * procentPuncte;
}



if(isset($_GET['cod'])){

	// MAC code verification and string decryption
	$ciphertext = replaceSpaces($_GET['cod']);
	$key = readCipherKey();
	try{
	$plaintext = safeDecrypt($ciphertext, $key);
	}catch(Exception $t){exitRedirect(1,$t);}

	echo "string decryptat cu codOK: ";echo $plaintext;echo "<br>";/////////
	
	// codOK verification and substracting it from string
	if(substr($plaintext, 0, 3) == codOK){
	$plaintext = substr_replace($plaintext,'',0,3);
	
	echo "string cu codOK inlaturat: ";echo $plaintext;echo "<br>";/////////
	
	}else exitRedirect(2);
	
	
//Denormalization of the product code format, verifying it and substracting it from string
	
	#returning the number of characters of the product
	$i=0; $nrchar="";
	while($plaintext[$i]!="x"){
		$nrchar.=substr($plaintext,$i,1);
	if(!is_numeric($nrchar))exitRedirect(3);
	if(intval($nrchar)>max_cod_produs_char_size)exitRedirect(4);
	if($i>strlen($plaintext))exitRedirect(5);
		$i++;
	}
	$nrchar=intval($nrchar);
	echo "nr de caractere ale produsului: ";echo $nrchar;echo "<br>";/////////
	
	#Substracting the chars from the normalized format
	for($j=0;$j<=$i;$j++)$plaintext = substr_replace($plaintext,'',0,1);
	echo "string cu caracterele de la formatul normalizat eliminate: ";echo $plaintext;echo "<br>";/////////

	#Returning cod_produs
	$cod_produs="";
	for($i=0;$i<$nrchar;$i++)
		$cod_produs.=substr($plaintext,$i,1);
	$cod_produs = intval($cod_produs);
	
	if(debugEnabled)
		echo "cod produs extras: ";echo $cod_produs;echo "<br>";/////////
	
	//TODO Verify the existance of the product code in database
	

	#Substracting the product code from string (obtaining the verification code)
	for($i=0;$i<$nrchar;$i++)$plaintext = substr_replace($plaintext,'',0,1);
	
	if(debugEnabled)
		echo "cod de verificare extras: ";echo $plaintext;echo "<br>";/////////
	
	#Getting the qr_nr
	$qr_nr = _xor($plaintext, $cod_produs);
	if(strlen((string)bindec($qr_nr)) > max_qr_nr_char_size)exitRedirect(7);
	if(debugEnabled){
		echo "(cod verificare)XOR(cod produs)=";echo "<br>";
		echo "qr_nr extras: ";echo bindec($qr_nr);echo "<br>";/////////
	}
	
	//TODO check if qr_nr already exists in database
	
	addPoints($cod_produs);
	
	}else exitRedirect(0);

?>