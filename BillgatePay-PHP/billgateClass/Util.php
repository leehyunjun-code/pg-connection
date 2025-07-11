<?php
	date_default_timezone_set('Asia/Seoul');

	class CheckSumUtil {

		const INIT_KEY = "billgatehashkey";

		private static function getRandomKey() {

			$randomBytes = BytesArrayUtil::randomBytes(4);

			return BytesArrayUtil::bytes2Hex($randomBytes);
		}

		public static function getMD5($data, $raw_output = false) {
			$hash = md5($data);

			if ($raw_output) {
				return pack('H*', $hash);
			} else {
				return $hash;
			}
		}

		public static function genCheckSum($input) {

			$randomKey = self::getRandomKey();

			return $randomKey.self::getMD5($randomKey.$input.self::INIT_KEY);
		}

		public static function diffCheckSum($input, $target) {
			$randomKey = substr($input, 0, 8);
			$iStr = substr($input, 8, strlen($input));
			$tStr = self::getMD5($randomKey.$target.self::INIT_KEY);

			if ( $iStr == $tStr ) {
				return "true";
			}else{
				return "false";
			}
		}
	}

	class BytesArrayUtil {

		public static function randomBytes($len) {
			$arr = array();

			for ($i = 0; $i < $len; $i++) {
				$arr[] = rand(0, 255);
			}

			return $arr;
		}

		public static function bytes2Hex($hash) {
			$str = "";
			
			for ($i = 0; $i < count($hash); $i++) {
				$str = $str.sprintf("%02x", $hash[$i]);
			}

			return $str;
		}

		public static function String2Hex($string) {
			$hex = array();
			
			for ($i = 0; $i < strlen($string); $i++) {
				$hex[] = dechex(ord($string[$i]));
			}
			
			return $hex;
		}

		public static function Hex2String($hex) {
			$str = array();
			
			for ($i = 0; $i < count($hex); $i++) {
				$str[] = chr(hexdec($hex[$i]));
			}
			return $str;
		}

	}

	class CommonUtil {

		public static function BgStrLen($str, $charset) {
			return mb_strwidth($str, CommonUtil::BgFileEncodingType($charset));
		}

		public static function BgSubStr($str, $sIdx, $eIdx, $charset) {
			return mb_strimwidth($str, $sIdx, $eIdx, "", CommonUtil::BgFileEncodingType($charset));
		}

		public static function BgFileEncodingType($charset = "") {

			if ( $charset != "" ) {
				$encType = $charset;
			}else{
				$tempStr = "가";

				if ( strlen($tempStr) == 3 ) {
					$encType = "UTF-8";
				}else{
					$encType = "EUC-KR";
				}
			}

			return $encType;
		}
	}

	class LogUtil {

		var $logPath;
		var $logObj;

		function __construct ($serviceCode, $path) {
			$this->logPath = $path."/".date("Y").sprintf("%02s", date("n"));

			if (!is_dir($this->logPath)) {
				mkdir($this->logPath, 0777, true);
			}

			$this->logObj = fopen($this->logPath."/".$serviceCode."_".date("Ymd").".log", "a");
		}

		function println($logStr) {
			fwrite($this->logObj, "[".date("H:i:s")."] ".$logStr."\r\n");
		}

		function close(){
			fclose($this->logObj);
		}
	}

	class ConfigInfo {
		const TRANSACTION = 1;
		const BATCH = 0;

		//서버 아이피 정보
		var $mainIP;
		var $testIP;
		var $backupIP;

		//로그 정보
		var $logFlag;
		var $logPath;

		//서비스 포트
		var $port;

		//소켓 타임 아웃
		var $timeout;

		//암호화 정보
		var $key;
		var $iv;

		//상용(1), 테스트(0)
		var $mode;

		public function __construct($fileName, $serviceCode, $serviceType = 1) {
			$configVars = self::parseIniFile($fileName);

			if ($configVars == false) {
				echo "config.ini Load Fail.";
				return;
			}

			//IP
			if ( $serviceType == self::TRANSACTION) {
				$this->mainIP = $configVars['main_ip'];
				$this->testIP = $configVars['test_ip'];
				$this->backupIP = $configVars['backup_ip'];
			}else if ( $serviceType == self::BATCH ) {
				$this->mainIP = $configVars['main_batch_ip'];
				$this->testIP = $configVars['test_batch_ip'];
				$this->backupIP = $configVars['backup_batch_ip'];
			}
			

			//로그 정보
			$this->logFlag = $configVars['log'];
			$this->logPath = $configVars['log_file'];

			//서비스 포트
			$this->port = $configVars[$serviceCode];

			//Timeout
			$this->timeout = $configVars['timeout'];

			//암호화 정보
			$this->key = $configVars['key'];
			$this->iv = $configVars['iv'];

			//상용/테스트 모드
			$this->mode = $configVars['mode'];
		}


		private function parseIniFile($fileName) {
			if (file_exists($fileName) == false || is_file($fileName) == false) return null;

			$iniFileContent = file_get_contents($fileName);
			return self::parseIniString($iniFileContent);
		}

		private function parseIniString($iniFileContent = "") {
			$iniArray = array();
			$iniFileContentArray = explode("\n", $iniFileContent);
			
			foreach ($iniFileContentArray as $iniFileContentArrayRow){
				$iniArrayKey = trim(substr($iniFileContentArrayRow, 0, strpos($iniFileContentArrayRow, '=')));
				$iniArrayValue = trim(substr($iniFileContentArrayRow, (strpos($iniFileContentArrayRow, '=')+1)));
			
				if ( $iniArrayKey == "" || substr($iniArrayKey, 0, 1) == "#") 
					continue;

				$iniArray[$iniArrayKey] = $iniArrayValue;
				
			}

			return $iniArray;
		}

		public function getMainIP() {
			return $this->mainIP;
		}

		public function getTestIP() {
			return $this->testIP;
		}

		public function getBackupIP() {
			return $this->backupIP;
		}

		public function getLogFlag() {
			return $this->logFlag;
		}

		public function getLogPath() {
			return $this->logPath;
		}

		public function getPort() {
			return $this->port;
		}

		public function getTimeout() {
			return $this->timeout;
		}

		public function getKey() {
			return base64_decode($this->key);
		}

		public function getIv() {
			return $this->iv;
		}

		public function getMode() {
			return $this->mode;
		}
	}
?>