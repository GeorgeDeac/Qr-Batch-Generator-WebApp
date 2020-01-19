<?php
include __DIR__ . "/encrypt.php";
include __DIR__ . "/db/connect.php";
require_once __DIR__ . "/vendor/phpqrcode/qrlib.php";
require_once __DIR__ . "/vendor/autoload.php";

/*
4A0 - 1682 x 2378 mm
2A0	- 1189 x 1682 mm
A0 - 841 x 1189 mm
A1 - 594 x 841  mm
A2 - 420 x 594  mm
A3 - 297 x 420  mm
A4 - 210 x 297  mm
A5 - 148 x 210  mm
*/

define("debugEnabled", 0);
define("codOK", "OKE");
define("domain", "https://localhost"); //Change accordingly
define("mpdf_temp", "./temp/mpdf_temp/");
define("qrcode_temp", "./temp/qr_temp/");
define("PageFormat", array(
"4A0"  => array(0 => 1682, 1 => 2378),
"2A0"  => array(0 => 1189, 1 => 1682),
"A0"  => array(0 => 841, 1 => 1189),
"A1"  => array(0 => 594, 1 => 841),
"A2"  => array(0 => 420, 1 => 594),
"A3"  => array(0 => 297, 1 => 420),
"A4"  => array(0 => 210, 1 => 297),
"A5"  => array(0 => 148, 1 => 210)
));

//placeholder values
$cod_produs = 1112;	//from database
$qrnr_produs = 1020; //from database
$batchNo = 100; //from database, number of batches generated to date

$ECLevel = 1; //ECC constants return values L - 0 M - 1 Q - 2 H - 3 QR_ECLEVEL_L,QR_ECLEVEL_M,QR_ECLEVEL_Q,QR_ECLEVEL_H
$batch=100; //batch number, from form


$FormatSelect = "A4"; //page format, from form
$EnableInfoFooter = true; //Enable footer with date, page, batch number information
$imageSizeX = 35; //size in mm, from form, 36 default tested value
$imageSizeY = $imageSizeX; //image is 1:1 format
$qrModuleSize = null; //size of each of the barcode squares measured in pixels, each code square (also named pixels or modules) usually defaults to 4×4px with null argument
$qrBorder = 1; //the white margin boundary around the barcode, measured in code squares (eg: A 16px margin on each side for a qrBorder of 4 * qrModuleSize of 4px)

//Image Size (px) = (Pixels per Module) × (Module Size + 8)

$imageBorderOffset=0; //obsolete parameter, now the qrBorder is modified directly


//Return ECCPrefix letter
function ECCPrefix($ECLevel){return array(0 => "L", 1 => "M", 2 => "Q", 3 => "H")[$ECLevel];}

//Calculate optimal border on one direction to properly position objects in page
function optimalBorder($lengh, $imageSize, $distanceBetween = 0){return ($lengh - (floor($lengh/($imageSize+$distanceBetween)) * ($imageSize+$distanceBetween)) )/2;}


$run=1; //run batchjob, temporary variable
if(debugEnabled){$s = microtime(true);} //set initial time if debug


//Initiate new mpdf object with the selected page format
if($run){$mpdf = new \Mpdf\Mpdf([
'tempDir' => __DIR__ . mpdf_temp,
'mode' => 'utf-8',
'format' => [ PageFormat[$FormatSelect][0], PageFormat[$FormatSelect][1] ],
'setAutoTopMargin' => false,
'setAutoBottomMargin' => false,
'margin-top' => 0,
'margin-header' => 0,
'margin-bottom' => 0,
'margin-footer' => 0,
]);
$mpdf->SetDrawColor(15, 82, 0, 10); //set CMYK draw color WIP
if($EnableInfoFooter)
	$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align: bottom; font-family: serif; 
    font-size: 8pt; color: #000000; font-weight: bold; font-style: italic;">
    <tr>
        <td width="33%">{DATE j-m-Y}</td>
        <td width="33%" align="center">{PAGENO}/{nbpg}</td>
        <td width="33%" style="text-align: right;">Batch #' . $batchNo . '</td>
    </tr>
</table>');

}


//Start batch
if($run){
	
//$mpdf->WriteHTML( '' ); //Important!!! This fixes a goddamn annoying bug (f you mpdf developer)

//Qr images generation
for($i=0;$i<$batch;$i++){
	QRcode::png(domain . "/read.php?cod=" . generateCode($cod_produs, $qrnr_produs, codOK), qrcode_temp . $i . '_' . ECCPrefix($ECLevel) . '.png', $ECLevel, $qrModuleSize, $qrBorder);
	$qrnr_produs++;

}#TODO update qrnr in database

$numberOnX = floor(PageFormat[$FormatSelect][0] / ($imageSizeX - $imageBorderOffset)); //Number of images on a page on the X Y coordinates
$numberOnY = floor(PageFormat[$FormatSelect][1] / ($imageSizeY - $imageBorderOffset));

$j=0;
$c=0;

//PDF generation (i on Y, j on X)
for($p=0;$p < ceil($batch/($numberOnX * $numberOnY));$p++){
	
$mpdf->AddPage('','','','','',0,0,0,0,0,0);
$mpdf->WriteHTML( '' );

	
for($i=0;$i < $numberOnY;$i++){
	
	if($c >= $batch)break;
	if($i >= $numberOnY){$mpdf->AddPage(); $i=0; $j=0; $mpdf->WriteHTML( '' );}

for($j=0;$j < $numberOnX;$j++){

	if($c >= $batch)break;

$mpdf->SetXY($j * ($imageSizeX - $imageBorderOffset) + optimalBorder(PageFormat[$FormatSelect][0], $imageSizeX - $imageBorderOffset),
			 $i * ($imageSizeY - $imageBorderOffset) + optimalBorder(PageFormat[$FormatSelect][1], $imageSizeY - $imageBorderOffset));
$mpdf->WriteHTML('<img src="' . qrcode_temp . $c . '_' . ECCPrefix($ECLevel) . '.png" style="width:' . $imageSizeX . 'mm; height:' . $imageSizeY . 'mm;" />');
$c++;

}//j

}//i

}//p

$mpdf->Output();
}

//Calculate duration (benchmark)
if(debugEnabled){
$e = microtime(true);
echo round($e - $s, 2) . " Sec";
}

//Multithreaded workers attempt ...pthreads not working yet...
/*
class job extends Collectable {
  public $val;

  public function __construct($val){
    // init some properties
    $this->val = $val;
  }
  public function run(){
    // do some work
    $this->val = $this->val . file_get_contents('http://www.example.com/', null, null, 3, 20);
    $this->setGarbage();
  }
}

// At most 3 threads will work at once
$p = new Pool(3);

$tasks = array(
  new job('0'),
  new job('1'),
  new job('2'),
  new job('3'),
  new job('4'),
  new job('5'),
  new job('6'),
  new job('7'),
  new job('8'),
  new job('9'),
  new job('10'),
);
// Add tasks to pool queue
foreach ($tasks as $task) {
  $p->submit($task);
}

// shutdown will wait for current queue to be completed
$p->shutdown();
// garbage collection check / read results
$p->collect(function($checkingTask){
  echo $checkingTask->val;
  return $checkingTask->isGarbage();
});
*/
?>