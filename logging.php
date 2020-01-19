<?php
/* General logging class
 * - lfile sets path and name of log file
 * - lwrite writes message to the log file (and implicitly opens log file)
 * - lclose closes log file
 * - message is written with the format: [d/M/Y:H:i:s] (script name) message
 */
class Logging{
    // declare log file and file pointer as private properties
    private $log_file, $fp;
    // set log file (path and name)
    public function lfile($path) {
        $this->log_file = $path;
    }
    // write message to the log file
    public function lwrite($message) {
        // if file pointer doesn't exist, then open log file
        if (!is_resource($this->fp)) {
            $this->lopen();
        }
        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        // define current time and suppress E_WARNING if using the system TZ settings
        // (be sure to set the INI setting date.timezone)
        $time = @date('[d/M/Y:H:i:s]');
        // write current time, script name and message to the log file
        fwrite($this->fp, "$time ($script_name) $message" . PHP_EOL);
    }
    // close log file
    public function lclose() {
        fclose($this->fp);
    }
    // open log file (private method)
    private function lopen() {
        // in case of Windows set default log file
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $log_file_default = 'c:/php/logfile.txt';
        }
        // set default log file for Linux and other systems
        else {
            $log_file_default = '/tmp/logfile.txt';
        }
        // define log file from lfile method or use previously set default
        $lfile = $this->log_file ? $this->log_file : $log_file_default;
        // open log file for writing only and place file pointer at the end of the file
        // (if the file does not exist, try to create it)
        $this->fp = fopen($lfile, 'a') or exit("Can't open $lfile!");
    }
}

//Can receive codes such as yesterday, lastweek to convert with strtotime in cron codes, see: https://return-true.com/deleting-files-in-a-directory-older-than-today-every-week-with-php/
function handleLogDeletion($lastPeriod){
$files = array();
$index = array();
$lastPeriod = strtotime($lastPeriod);
 
if ($handle = opendir('relative/path/to/dir')) {
	clearstatcache();
	while (false !== ($file = readdir($handle))) {
   		if ($file != "." && $file != "..") {
   			$files[] = $file;
			$index[] = filemtime( 'relative/path/to/dir/'.$file );
   		}
	}
  	closedir($handle);
}
	
asort( $index );
	
foreach($index as $i => $t) {
		
	if($t < $lastPeriod) {
		@unlink('relative/path/to/dir/'.$files[$i]);
	}
	
}

}


?>