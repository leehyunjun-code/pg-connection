<?php
	require_once ("billgateClass/Util.php");
	require_once ("billgateClass/KISA_SEED_CBC.php");
?>
<?php
	class ServiceBroker {
		const REAL_MODE = 1;
		const TEST_MODE = 0;

		const SOCKET_CONNECT_ERROR           = "092001";
		const SOCKET_TIMEOUT_ERROR           = "092002";
		const DATA_LENGTH_ERROR              = "092003";
		const FILE_IO_ERROR                  = "092004";
		const UNKNOWN_ERROR                  = "999900";

		const TAG_CASHRECEIPT_BATCH_REQUEST  = "3210";
		const TAG_CASHRECEIPT_RESULT_REQUEST = "3220";
		const TAG_FILE_NAME                  = "0055";
		const TAG_FILE_SIZE                  = "0056";
		const TAG_FILE_PATH                  = "0095";
		const TAG_RESPONSE_CODE              = "1002";


		var $ip;
		var $backupIp;
		var $port;
		var $timeout;
		var $mode;
		var $logFlag;
		var $logPath;
		var $serviceCode;
		var $logger;

		function __construct($configInfo, $serviceCode) {
			
			$this->mode = $configInfo->getMode();

			if ($this->mode == self::REAL_MODE) {
				$this->ip = $configInfo->getMainIP();
			}else{
				$this->ip = $configInfo->getTestIP();
			}
			$this->backupIp = $configInfo->getBackupIP();
			$this->port = $configInfo->getPort();
			$this->timeout = $configInfo->getTimeout();
			$this->logFlag = $configInfo->getLogFlag();
			$this->logPath = $configInfo->getLogPath();
			$this->serviceCode = $serviceCode;

			if ($this->logFlag == "1") {
				$this->logger = new LogUtil($serviceCode, $this->logPath);
			}
		}

		function comm($data) {
			$socket;
			
			$refTime = round(microtime(true)*1000); //milliseconds

			try{
				$socket = new BgSocket($this->ip, $this->port, $this->timeout);
			}catch(Exception $e) {
				if (self::REAL_MODE == $this->mode) {
					if ($this->logger != null) {
						$this->logger->println("RETRY CONNECTION IP : [".$this->backupIp."] PORT : [".$this->port."] MODE : [".$this->mode."]");
					}

					$socket = new BgSocket($this->backupIp, $this->port, $this->timeout);
				}else{
					throw $e;
				}
			}

			if ($this->logger != null) {
				$this->logger->println("SERVER CONNECTION SUCCESS!!");
			}

			try{
				$socket->writeMessage($data->getData());

				$time = $this->timeout - (int)( round(microtime(true)*1000) - $refTime);

				if ($time <= 0) {
					throw new Exception("02"); //Read Time Out
				}

				$length = (int)implode("", $socket->readMessage(Message::TOTAL_LENGTH_LENGTH));
				$d = implode("", $socket->readMessage($length));

				if ($socket != null) {
					@$socket->close();
				}

				return $d;

			}catch(Exception $e) {
				if ($socket != null) {
					@$socket->close();
				}
				throw $e;
			}
		}

		function requestBatch($data) {
			$socket;
			
			try{
				$socket = new BgSocket($this->ip, $this->port, $this->timeout);
			}catch(Exception $e) {
				if (self::REAL_MODE == $this->mode) {
					if ($this->logger != null) {
						$this->logger->println("RETRY CONNECTION IP : [".$this->backupIp."] PORT : [".$this->port."] MODE : [".$this->mode."]");
					}

					$socket = new BgSocket($this->backupIp, $this->port, $this->timeout);
				}else{
					throw $e;
				}
			}

			if ($this->logger != null) {
				$this->logger->println("SERVER CONNECTION SUCCESS!!");
			}

			try{
				$reqMsg = new Message($data->charset);

				$reqMsg->setVersion($data->getVersion());
				$reqMsg->setMerchantId($data->getMerchantId());
				$reqMsg->setServiceCode($data->getServiceCode());
				$reqMsg->setCommand($data->getCommand());
				$reqMsg->setOrderId($data->getOrderId());
				$reqMsg->setOrderDate($data->getOrderDate());
				$reqMsg->setCipher($data->getCipher());

				$fileName = $data->get(self::TAG_FILE_NAME);

				if ( file_exists($fileName) == false || is_file($fileName) == false ) {
					throw new Exception("04");
				}

				$fileSize = @(int)filesize($fileName);
				$pos = strrpos($fileName, "/");

				if ($pos === false) {
					$pos = 0;
				}else{
					$pos = $pos + 1;
				}
		
				$reqMsg->put(self::TAG_FILE_NAME, substr($fileName, $pos));
				$reqMsg->put(self::TAG_FILE_SIZE, (string)$fileSize);

				$socket->writeMessage($reqMsg->getData());

				//파일전송
				$file = fopen($fileName, "r");
				while(!feof($file)) {
					$buf = fread($file, 1024);
					$socket->writeMessage($buf);
				}
				@fclose($file);

				$length = (int)implode("", $socket->readMessage(Message::TOTAL_LENGTH_LENGTH));
				$d = implode("", $socket->readMessage($length));

				if ($socket != null) {
					@$socket->close();
				}

				return $d;

			}catch(Exception $e) {
				if ($socket != null) {
					@$socket->close();
				}
				throw $e;
			}
		}

		function responseBatch($data) {
			$socket;
			$fo;
			
			try{
				$socket = new BgSocket($this->ip, $this->port, $this->timeout);
			}catch(Exception $e) {
				if (self::REAL_MODE == $this->mode) {
					if ($this->logger != null) {
						$this->logger->println("RETRY CONNECTION IP : [".$this->backupIp."] PORT : [".$this->port."] MODE : [".$this->mode."]");
					}

					$socket = new BgSocket($this->backupIp, $this->port, $this->timeout);
				}else{
					throw $e;
				}
			}

			if ($this->logger != null) {
				$this->logger->println("SERVER CONNECTION SUCCESS!!");
			}

			try{
				$socket->writeMessage($data->getData());

				$length = (int)implode("", $socket->readMessage(Message::TOTAL_LENGTH_LENGTH));
				$d = implode("", $socket->readMessage($length));

				$resMsg = new Message($data->charset);
				$resMsg->setData($d, $data->getCipher());

				if ( $resMsg->get(self::TAG_RESPONSE_CODE) != "0000" ) {
					return $d;
				}

				$fileName = $resMsg->get(self::TAG_FILE_NAME);
				$fileSize = $resMsg->get(self::TAG_FILE_SIZE);
				$intFileSize = (int)$fileSize;

				$fileName = $data->get(self::TAG_FILE_PATH)."/".$fileName;
				$fo = @fopen($fileName, "w");

				if (!$fo) {
					throw new Exception("04");
				}

				$quo = $intFileSize / 1024;
				$rem = $intFileSize % 1024;

				for ($i=0 ; $i < (int)$quo; $i++) {
					$w = implode("", $socket->readMessage(1024));
					@fwrite($fo, $w);
				}

				if ((int)$rem != 0) {
					$w = implode("", $socket->readMessage((int)$rem));
					@fwrite($fo, $w);
				}

				$length = (int)implode("", $socket->readMessage(Message::TOTAL_LENGTH_LENGTH));
				$d = implode("", $socket->readMessage($length));

				if ($socket != null) {
					@$socket->close();
				}
				if ($fo != null) {
					@fclose($fo);
				}
				return $d;

			}catch(Exception $e) {
				if ($socket != null) {
					@$socket->close();
				}
				if ($fo != null) {
					@fclose($fo);
				}

				throw $e;
			}

		}

		function invoke($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			try{
				if ($this->logger != null) {
					$this->logger->println("IP : [".$this->ip."] PORT : [".$this->port."] MODE : [".$this->mode."]");
					$this->logger->println("REQUEST MESSAGE : ".$reqMsg->getLogString());
				}

				if ( $reqMsg->getCommand() == self::TAG_CASHRECEIPT_BATCH_REQUEST ) {
					$d = $this->requestBatch($reqMsg);
				}else if ( $reqMsg->getCommand() == self::TAG_CASHRECEIPT_RESULT_REQUEST ) {
					$d = $this->responseBatch($reqMsg);
				}else{
					$d = $this->comm($reqMsg);
				}

				$resMsg->setRemoveLogTag($reqMsg->getRemoveLogTag());
				$resMsg->setData($d, $reqMsg->getCipher());

				if ($this->logger != null) {
					$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
				}
			}catch(Exception $e) {
				$exMsg = $e->getMessage();
				if ( $exMsg == "01" ) {
					$resMsg = $this->getSocketConnectExceptionMessage($reqMsg, $charset);
				}else if ( $exMsg == "02" ) {
					$resMsg = $this->getSocketTimeoutExceptionMessage($reqMsg, $charset);
				}else if ( $exMsg == "03" ) {
					$resMsg = $this->getDataLengthExceptionMessage($reqMsg, $charset);
				}else if ( $exMsg == "04" ) {
					$resMsg = $this->getFileIOExceptionMessage($reqMsg, $charset);
				}else{
					$resMsg = $this->getExceptionMessage($reqMsg, $charset);
				}	
			}

			if ($this->logger != null) {
				@$this->logger->close();
			}

			return $resMsg;
		}
		
		function getSocketConnectExceptionMessage($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			$resMsg->setCipher($reqMsg->getCipher());
			$resMsg->setVersion($reqMsg->getVersion());
			$resMsg->setServiceCode($reqMsg->getServiceCode());
			$resMsg->setOrderDate($reqMsg->getOrderDate());
			$resMsg->setOrderId($reqMsg->getOrderId());

			$resMsg->put("1002", substr(self::SOCKET_CONNECT_ERROR, 0, 4) );
			$resMsg->put("1003", "통신 에러" );
			$resMsg->put("1009", substr(self::SOCKET_CONNECT_ERROR, 4) );
			$resMsg->put("1010", "Connection timeout 오류" );

			if ($this->logger != null) {
				$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
			}

			return $resMsg;
		}

		function getSocketTimeoutExceptionMessage($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			$resMsg->setCipher($reqMsg->getCipher());
			$resMsg->setVersion($reqMsg->getVersion());
			$resMsg->setServiceCode($reqMsg->getServiceCode());
			$resMsg->setOrderDate($reqMsg->getOrderDate());
			$resMsg->setOrderId($reqMsg->getOrderId());

			$resMsg->put("1002", substr(self::SOCKET_TIMEOUT_ERROR, 0, 4) );
			$resMsg->put("1003", "통신 에러" );
			$resMsg->put("1009", substr(self::SOCKET_TIMEOUT_ERROR, 4) );
			$resMsg->put("1010", "Read timeout 오류" );

			if ($this->logger != null) {
				$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
			}

			return $resMsg;
		}

		function getDataLengthExceptionMessage($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			$resMsg->setCipher($reqMsg->getCipher());
			$resMsg->setVersion($reqMsg->getVersion());
			$resMsg->setServiceCode($reqMsg->getServiceCode());
			$resMsg->setOrderDate($reqMsg->getOrderDate());
			$resMsg->setOrderId($reqMsg->getOrderId());

			$resMsg->put("1002", substr(self::DATA_LENGTH_ERROR, 0, 4) );
			$resMsg->put("1003", "알수 없는 에러" );
			$resMsg->put("1009", substr(self::DATA_LENGTH_ERROR, 4) );
			$resMsg->put("1010", "가맹점에 문의 하세요." );

			if ($this->logger != null) {
				$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
			}

			return $resMsg;
		}

		function getFileIOExceptionMessage($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			$resMsg->setCipher($reqMsg->getCipher());
			$resMsg->setVersion($reqMsg->getVersion());
			$resMsg->setServiceCode($reqMsg->getServiceCode());
			$resMsg->setOrderDate($reqMsg->getOrderDate());
			$resMsg->setOrderId($reqMsg->getOrderId());

			$resMsg->put("1002", substr(self::FILE_IO_ERROR, 0, 4) );
			$resMsg->put("1003", "파일 에러" );
			$resMsg->put("1009", substr(self::FILE_IO_ERROR, 4) );
			$resMsg->put("1010", "파일 접근 에러" );

			if ($this->logger != null) {
				$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
			}

			return $resMsg;
		}

		function getExceptionMessage($reqMsg, $charset = "") {
			$resMsg = new Message($charset);

			$resMsg->setCipher($reqMsg->getCipher());
			$resMsg->setVersion($reqMsg->getVersion());
			$resMsg->setServiceCode($reqMsg->getServiceCode());
			$resMsg->setOrderDate($reqMsg->getOrderDate());
			$resMsg->setOrderId($reqMsg->getOrderId());

			$resMsg->put("1002", substr(self::UNKNOWN_ERROR, 0, 4) );
			$resMsg->put("1003", "알수 없는 에러" );
			$resMsg->put("1009", substr(self::UNKNOWN_ERROR, 4) );
			$resMsg->put("1010", "가맹점에 문의 하세요." );

			if ($this->logger != null) {
				$this->logger->println("RESPONSE MESSAGE : ".$resMsg->getLogString());
			}

			return $resMsg;
		}
	}

	class Message {
	
		const TOTAL_LENGTH_LENGTH      = 4;
		const VERSION_LENGTH           = 10;
		const MERCHANT_ID_LENGTH       = 20;
		const SERVICE_CODE_LENGTH      = 4;
		const COMMAND_LENGTH           = 4;
		const ORDER_ID_LENGTH          = 64;
		const ORDER_DATE_LENGTH        = 14;
		const NUMBER_OF_RECORD_LENGTH  = 4;

		const TAG_LENGTH               = 4;
		const COUNT_LENGTH             = 4;
		const VALUE_LENGTH             = 4;

		const VERSION_INDEX            = 0;
		const MERCHANT_ID_INDEX        = 10;
		const SERVICE_CODE_INDEX       = 30;

		const COMMAND_INDEX            = 0;
		const ORDER_ID_INDEX           = 4;
		const ORDER_DATE_INDEX         = 68;
		const NUMBER_OF_RECORD_INDEX   = 82;
		const DATA_INDEX               = 86;

		//----------------------------
		//Define Header valiable
		//----------------------------
		var $version;
		var $merchantId;
		var $serviceCode;
		var $command;
		var $orderId;
		var $orderDate;
		var $numberOfRecord;
		var $data;
		var $cipher;
		var $charset;
		var $removeLogTag;
		
		function __construct($charset = "") {
			$this->data = array();
			$this->removeLogTag = array();
			$this->charset = $charset;
		}

		function setData($d, $cipher) {
			$this->cipher = $cipher;

			$this->version = trim(substr($d, self::VERSION_INDEX, self::VERSION_LENGTH));
			$this->merchantId = trim(substr($d, self::MERCHANT_ID_INDEX, self::MERCHANT_ID_LENGTH));
			$this->serviceCode = trim(substr($d, self::SERVICE_CODE_INDEX, self::SERVICE_CODE_LENGTH));
			
			$decrypted = substr($d, self::VERSION_LENGTH + self::MERCHANT_ID_LENGTH + self::SERVICE_CODE_LENGTH, strlen($d));
			
			if ($this->cipher != null) {
				$decrypted = $this->cipher->decryptSEED($decrypted, $this->charset);
			}

			$this->command = trim(substr($decrypted, self::COMMAND_INDEX, self::COMMAND_LENGTH));
			$this->orderId = trim(substr($decrypted, self::ORDER_ID_INDEX,	self::ORDER_ID_LENGTH));
			$this->orderDate = trim(substr($decrypted, self::ORDER_DATE_INDEX, self::ORDER_DATE_LENGTH));
			$this->numberOfRecord = (int)trim(substr($decrypted, self::NUMBER_OF_RECORD_INDEX, self::NUMBER_OF_RECORD_LENGTH));
			
			$bodyStr =substr($decrypted, self::DATA_INDEX, strlen($decrypted));
			
			if (strlen($bodyStr) <= 0) {
				throw new Exception("03"); //Data Length Exception
			}
			
			$this->parseData($bodyStr);
		}
		
		function setMessages($messages) {
			try{
				$messages = substr($messages, self::TOTAL_LENGTH_LENGTH);
				$this->setData($messages, $this->getCipher());
			}catch(Exception $e) {
			}
		}

		function setVersion($version) {
			$this->version = $version;
		}
		
		function setMerchantId($merchantId) {
			$this->merchantId = $merchantId;
		}

		function setServiceCode($serviceCode) {
			$this->serviceCode = $serviceCode;
		}

		function setCommand($command) {
			$this->command = $command;
		}

		function setOrderId($orderId) {
			$this->orderId = $orderId;
		}

		function setOrderDate($orderDate) {
			$this->orderDate = $orderDate;
		}
		
		function setCipher($cipher) {
			$this->cipher = $cipher;
		}

		function setRemoveLogTag($removeLogTag) {
			$this->removeLogTag = $removeLogTag;
		}

		function getVersion() {
			return $this->version;
		}
		
		function getMerchantId() {
			return $this->merchantId;
		}

		function getServiceCode() {
			return $this->serviceCode;
		}
		
		function getCommand() {
			return $this->command;
		}

		function getOrderId() {
			return $this->orderId;
		}

		function getOrderDate() {
			return $this->orderDate;
		}

		function getCipher() {
			return $this->cipher;
		}

		function getRemoveLogTag() {
			return $this->removeLogTag;
		}
		//----------------------------
		//tag에 해당하는 value를 set하기 위한 method. 같은 tag로 n번 invoke하면 n개의 value가 set된다.
		//----------------------------
		function put($tag, $value) {
			$vt = null;
			if($this->data != null) {
				if(array_key_exists($tag, $this->data)) {
					$vt = $this->data[$tag];
				}
			}
			
			if($vt != null) {
				array_push($vt, $value);
			}
			else {
				$vt = array(0 => $value);
			}
			
			if($this->data != null) {
				$this->data[$tag] = $vt ;
			}else {
				$this->data = array($tag => $vt);
			}

		}

		//----------------------------
		//tag에 해당하는 value를 String으로 return한다. array로 저장된 tag는 index가 0 인 값을
		//return한다. 하나의 tag에 하나의 value를 가지는 경우에 사용한다.
		//----------------------------
		function get($tag) {
			$vt;
			if(array_key_exists($tag, $this->data)) {
				$vt = $this->data[$tag];
			}
			else  {
				return "";
			}

			return $vt[0];
		}

		//----------------------------
		//tag에 해당하는 value를 array 로 return 한다. 하나의 tag에 여러개의 value를 가지는 경우에 사용한다.
		//----------------------------
		function gets($tag) {
			$vt;
			if(array_key_exists($tag, $this->data)) {
				$vt = $this->data[$tag];
			}
			else  {
				return "";
			}	
			
			return $vt;
		}

		function getBody() {
			$vt = null;
			$s = "";
			$v = "";
			$tag = "";
			$body = "";
			
			foreach($this->data as $tag => $vt) {
				
				$s = "";
			
				for($i=0; $i<count($vt); $i++) {
					$v = $vt[$i];
					$s = $s.sprintf("%0".self::VALUE_LENGTH."s", CommonUtil::BgStrLen($v, $this->charset)).$v;
				}

				$body = $body.sprintf("%-".self::TAG_LENGTH."s", $tag)
							 .sprintf("%0".self::COUNT_LENGTH."s", count($vt))
							 .sprintf("%0".self::VALUE_LENGTH."s", CommonUtil::BgStrLen($s, $this->charset))
							 .$s;
			}
			
			return $body;
		}

		function parseData($data) {
			$tag;
			$value;
			$count;
			$length;
			$index = 0;

			for($i=0; $i < $this->numberOfRecord; $i++) {
				$tag = trim(CommonUtil::BgSubStr($data, $index, self::TAG_LENGTH, $this->charset));
				$index += self::TAG_LENGTH;

				$count = (int)trim(CommonUtil::BgSubStr($data, $index, self::COUNT_LENGTH, $this->charset));
				$index += self::COUNT_LENGTH;

				$length = (int)trim(CommonUtil::BgSubStr($data, $index, self::VALUE_LENGTH, $this->charset));
				$index += self::VALUE_LENGTH;

				$tempData = CommonUtil::BgSubStr($data, $index, $length, $this->charset);
				$index +=mb_strlen($tempData, CommonUtil::BgFileEncodingType($this->charset));

				for ($j=0, $k=0; $j < $count; $j++) {
					$length = (int)trim(CommonUtil::BgSubStr($tempData, $k, self::VALUE_LENGTH, $this->charset));
					$k += self::VALUE_LENGTH;

					$value = trim(CommonUtil::BgSubStr($tempData, $k, $length, $this->charset));
					$k += mb_strlen($value, CommonUtil::BgFileEncodingType($this->charset));

					$this->put($tag, $value);
				}
			}
		}

		function toString() {
			$header = sprintf("%-".self::VERSION_LENGTH."s", $this->version)
						.sprintf("%-".self::MERCHANT_ID_LENGTH."s", $this->merchantId)
						.sprintf("%-".self::SERVICE_CODE_LENGTH."s", $this->serviceCode)
						.sprintf("%-".self::COMMAND_LENGTH."s", $this->command)
						.sprintf("%-".self::ORDER_ID_LENGTH."s", $this->orderId)
						.sprintf("%-".self::ORDER_DATE_LENGTH."s", $this->orderDate)
						.sprintf("%0".self::NUMBER_OF_RECORD_LENGTH."s", count($this->data));
			$header = $header.$this->getBody();

			return sprintf("%0".self::TOTAL_LENGTH_LENGTH."s", CommonUtil::BgStrLen($header, $this->charset)).$header;
		}

		function getData() {
			$clear = sprintf("%-".self::VERSION_LENGTH."s", $this->version)
					.sprintf("%-".self::MERCHANT_ID_LENGTH."s", $this->merchantId)
					.sprintf("%-".self::SERVICE_CODE_LENGTH."s", $this->serviceCode);

			$opaque = sprintf("%-".self::COMMAND_LENGTH."s", $this->command)
						.sprintf("%-".self::ORDER_ID_LENGTH."s", $this->orderId)
						.sprintf("%-".self::ORDER_DATE_LENGTH."s", $this->orderDate) 
						.sprintf("%0".self::NUMBER_OF_RECORD_LENGTH."s", count($this->data))
						.$this->getBody();

			$s = "";
			if ($this->cipher != null) {
				$s = $clear.$this->cipher->encryptSEED($opaque, $this->charset);
			}else{
				$s = $clear.$opaque;
			}

			return sprintf("%0".self::TOTAL_LENGTH_LENGTH."s", CommonUtil::BgStrLen($s, $this->charset)).$s;
		}
		

		function getLogString() {
			$vt = null;
			$s = "";
			$v = "";
			$tag = "";
			$header = "";
			$body = "";

			$header = " version[".$this->version."] mid[".$this->merchantId."] serviceCode[".$this->serviceCode."] command[".$this->command."]"
			." orderId[".$this->orderId."] orderDate[".$this->orderDate."] numberOfRecord[".count($this->data)."]";

			foreach($this->data as $tag => $vt) {
				
				$s = "";
			
				for($i=0; $i<count($vt); $i++) {
					$v = $vt[$i];
					$s = $s.$v.",";
				}

				if ( strpos("0009", $tag) !== false || strpos("0069", $tag) !== false || strpos(implode(",",$this->removeLogTag), $tag) !== false ) {
					continue;
				}

				$body = $body." ".$tag."[".$s."]";
			}

			return $header.$body;
		}

		function putRemoveLogTag($tag) {
			if (strlen($tag) > 0) {
				$this->removeLogTag[] = $tag;
			}
		}
	}

	class BgCipher {

		var $userKey;
		var $iv;

		function __construct($configInfo) {

			$this->setKey($configInfo->getKey());
			$this->setIV($configInfo->getIv());
		}

		function setKey($key) {
			$this->userKey = $key;
		}

		function setIV($iv) {
			$this->iv = $iv;
		}

		function encryptSEED($dec, $charset = "") {
			if ($charset != "") {
				$dec = iconv($charset, 'EUC-KR', $dec);
			}

			$planBytes = BytesArrayUtil::String2Hex($dec);
			$keyBytes = BytesArrayUtil::String2Hex($this->userKey);
			$IVBytes = BytesArrayUtil::String2Hex($this->iv);
			
			for($i = 0; $i < 16; $i++) {
				$keyBytes[$i] = hexdec($keyBytes[$i]);
				$IVBytes[$i] = hexdec($IVBytes[$i]);
			}

			for ($i = 0; $i < count($planBytes); $i++) {
				$planBytes[$i] = hexdec($planBytes[$i]);
			}

			if (count($planBytes) == 0) {
				return $dec;
			}

			$ret = null;
			$bszChiperText = null;

			$bszChiperText = KISA_SEED_CBC::SEED_CBC_Encrypt($keyBytes, $IVBytes, $planBytes, 0, count($planBytes));

			$r = count($bszChiperText);

			for($i=0;$i< $r;$i++) {
				$ret[] =  sprintf("%02X", $bszChiperText[$i]);
			}

			return base64_encode(implode("", BytesArrayUtil::Hex2String($ret)));
		}

		function decryptSEED($enc, $charset = "") {
			
			$planBytes = BytesArrayUtil::String2Hex(base64_decode($enc));
			$keyBytes = BytesArrayUtil::String2Hex($this->userKey);
			$IVBytes = BytesArrayUtil::String2Hex($this->iv);
			
			for($i = 0; $i < 16; $i++) {
				$keyBytes[$i] = hexdec($keyBytes[$i]);
				$IVBytes[$i] = hexdec($IVBytes[$i]);
			}

			for ($i = 0; $i < count($planBytes); $i++) {
				$planBytes[$i] = hexdec($planBytes[$i]);
			}

			if (count($planBytes) == 0) {
				return $enc;
			}

			$bszPlainText = null;

			// 방법 1
			$bszPlainText = KISA_SEED_CBC::SEED_CBC_Decrypt($keyBytes, $IVBytes, $planBytes, 0, count($planBytes));
			for($i=0;$i< sizeof($bszPlainText);$i++) {
				$planBytresMessage[] =  sprintf("%02X", $bszPlainText[$i]);
			}

			if ($charset != "") {
				$dec = iconv("EUC-KR", $charset, implode("", BytesArrayUtil::Hex2String($planBytresMessage)));
			}else{
				$dec = implode("", BytesArrayUtil::Hex2String($planBytresMessage));
			}

			return $dec;
		}

	}

	class BgSocket {
		const CONNECTION_TIMEOUT  = 2000;
		const TOTAL_LENGTH_LENGTH = 4;

		var $socket;

		function __construct($hostName, $port, $read_timeout) {
			$sec_conn_timeout = round(self::CONNECTION_TIMEOUT / 1000, 1);
			$sec_read_timeout = round((int)$read_timeout / 1000, 1);

			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

			socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec_conn_timeout, 'usec'=>0));
			socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec_read_timeout, 'usec'=>0));

			@socket_connect($this->socket, $hostName, $port);
			$socket_err = socket_last_error($this->socket);

			if ($socket_err != "0") {
				@$this->close();
				throw new Exception("01"); //Connection Time Out
			}
		}

		function writeMessage($data) {
			socket_write($this->socket, $data, strlen($data));
		}

		function readMessage($length) {
			$buffer = array();
			$readCount = 0;

			if ( $length <= 0 ) {
				throw new Exception("02"); //Read Time Out
			}

			while(true) {
				$data = socket_read($this->socket, 1);

				$buffer[$readCount++] = $data;
				if ($readCount == $length) {
					return $buffer;
				}
			}
		}

		function close() {
			@socket_close($this->socket);
		}
	}
?>