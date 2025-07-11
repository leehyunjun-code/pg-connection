<?php
/*-------------------------------------------------------------------------------------
 해당 페이지는 빌게이트 결제 테스트를 위한 "인증결과 리턴 및 승인 요청/응답" 페이지 입니다.
	 
	 ※테스트 결제를 원하신다면,
	 1. 결제 정보 (returnUrl, cancelUrl) 가맹점 환경에 맞게 변경
	 2. billgateConfig.php의 BILLGATE_HOME 경로 설정
	 
	 ※실제 상용 테스트를 원하신다면,
	 1. 결제 정보 (serviceId, returnUrl, cancelUrl)를 변경  -> 계약 시 받은 serviceId 정보를 넣으시면 됩니다.
	 2. config.ini 의 Key,Iv 값 변경 (가맹점 관리자 어드민에서 확인 가능)
	 3. config.ini 의 mode = 1(상용)으로 변경 후 실 결제 테스트 하시길 바랍니다.

	 - 상용 테스트는 실제 과금이 이뤄지는 점 유의하시길 바랍니다.
-------------------------------------------------------------------------------------*/
?>
<?php
	header('Content-Type: text/html; charset=euc-kr'); 
	@extract($_REQUEST);

	//---------------------------------------
	// API 클래스 Include
	//---------------------------------------
	include_once ("BillgateConfig.php");
	include_once ("billgateClass/Service.php");
	include_once ('KISA_SHA256.php');
?>
<?php
	//리턴 페이지에서 인증 결과 값을 보여주기 위한 임시 변수설정
	$REQ_RESPONSE_CODE = $RESPONSE_CODE;
	$REQ_RESPONSE_MESSAGE = $RESPONSE_MESSAGE;
	$REQ_DETAIL_RESPONSE_CODE = $DETAIL_RESPONSE_CODE;
	$REQ_DETAIL_RESPONSE_MESSAGE = $DETAIL_RESPONSE_MESSAGE;

	$CP_PRODUCT_AMOUNT = "1000";//가맹점 상품 금액

	//================================================
	// 1. 인증 성공인 경우 결제(승인)요청
	//================================================	
	if(!strcmp($RESPONSE_CODE,"0000") && $SERVICE_CODE != "1800") { //가상계좌 제외

		//HASH_DATA 비교
		$hashTemp = $SERVICE_ID . $ORDER_ID . $ORDER_DATE . $CP_PRODUCT_AMOUNT; //
		$tempHashData = hash('sha256', $hashTemp);

		if("0900" == $SERVICE_CODE || "1100" == $SERVICE_CODE){
			if($tempHashData == $HASH_DATA){
?>	
				<script type="text/javascript">
					alert("HASH_DATA 값 불일치");
					exit;
				</script>
<?php
			}
		}
		
		//=====================
		// 2. 승인 요청 및 응답
		//=====================

		//---------------------------------------
		// API 인스턴스 생성
		//---------------------------------------
		$reqMsg = new Message();       //요청 메시지
		$resMsg = new Message();       //응답 메시지
		$configInfo = new ConfigInfo($INI_FILE, $SERVICE_CODE);

		//set init
		$bgCipher = new BgCipher($configInfo);
		$broker = new ServiceBroker($configInfo, $SERVICE_CODE);

		//Set Header
		$reqMsg->setCipher($bgCipher); //암호화 셋팅

		//Set Body
		$reqMsg->setMessages($MESSAGE); //메시지 셋팅

		//Request
		$resMsg = $broker->invoke($reqMsg); //요청 및 응답

		//휴대폰
		if("1100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_PART_CANCEL_TYPE = $resMsg->get("7049"); //부분취소타입(7049)
			}

		//신용카드
		}else if("0900"==$SERVICE_CODE){
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
				$RES_QUOTA = $resMsg->get("0031"); //할부개월수(0031)
				$RES_CARD_COMPANY_CODE = $resMsg->get("0034"); //발급사코드(0034)
				$RES_PIN_NUMBER = $resMsg->get("0008"); //카드번호(0008)
			}

		//계좌이체
		}else if("1000"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_USING_TYPE = $resMsg->get("0015"); //현금영수증용도(0015) [0:소득공제용, 1:지출증빙용]
				$RES_IDENTIFIER = $resMsg->get("0017"); //현금영수증 승인번호(0017)
				$RES_IDENTIFIER_TYPE = $resMsg->get("0102"); //현금영수증 자진발급제 유무(0102) [Y:자진발급제 적용, 그외:미적용]
				$RES_MIX_TYPE = $resMsg->get("0037"); //거래구분(0037) [0000:일반, 1000:에스크로]
				$RES_INPUT_BANK_CODE = $resMsg->get("0105"); //은행코드(0105)
				$RES_INPUT_ACCOUNT_NAME = $resMsg->get("0107"); //은행명(0107)
			}
	
		//도서상품권
		}else if("0100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}

		//문화상품권
		}else if("0200"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}
	
		//게임문화상품권
		}else if("0300"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}

		//해피 머니 상품권
		}else if("0500"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}
		
		//캐시게이트
		}else if("0700"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_BALANCE = $resMsg->get("1006"); //잔액(1006)
				$RES_DEAL_AMOUNT = $resMsg->get("0012"); //승인금액(0012)
			}

		//에그머니
		}else if("2600"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}
		
		//통합포인트
		}else if("4100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
			}
		
		//티머니
		}else if("1600"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}

		//폰빌
		}else if("1200"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
			}

		//틴캐시
		}else if("2500"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
			
			//승인 성공시
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //승인일시(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //승인금액(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //승인번호(1004)
			}
		}
	}
?>
<html>
<head>
<title></title>
<style>
	body, tr, td {font-size:9pt; font-family:맑은고딕,verdana; }
	div {width: 98%; height:100%; overflow-y: auto; overflow-x:hidden;}
</style>
<meta charset="EUC-KR">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
<title>빌게이트 결제 테스트 샘플페이지</title>
</head>
<body>
	<div>
	<table width="380px" border="0" cellpadding="0"	cellspacing="0">
		<tr> 
			<td height="25" style="padding-left:10px" class="title01"># 현재위치 &gt;&gt; 결제테스트 &gt; <b>가맹점 Return Url</b></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
				<table width="380" border="0" cellpadding="4" cellspacing="1" bgcolor="#B0B0B0">
					<tr>
						<td><b>인증결과</b></td>
					</tr>
					<!--인증결과 시작-->
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>가맹점 아이디</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_ID?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>서비스코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>주문번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_ID?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>주문일시</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_DATE?></b></td>
					</tr>
<?php
	//휴대폰(1100), 폰빌(1200) 서비스타입
	if( ($SERVICE_CODE == "1100" || $SERVICE_CODE == "1200") ) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>서비스타입</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_TYPE?></b></td>
					</tr>
<?php
	}
	//캐시게이트(0700), 신용카드(0900) 거래번호 출력 제외
	if( !($SERVICE_CODE == "0700" || $SERVICE_CODE == "0900") ) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>거래번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//가상계좌(1800) 채번정보
	if($SERVICE_CODE == "1800" && $REQ_RESPONSE_CODE == "0000"){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>가상계좌번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ACCOUNT_NUMBER?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>금액</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $AMOUNT?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>은행코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $BANK_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>거래구분</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $MIX_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>만료일</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $EXPIRE_DATE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>입금마감시간</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $EXPIRE_TIME?></b></td>
					</tr>
<?php
	}
	//틴캐시(2500) 인증구분
	if($SERVICE_CODE == "2500") {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>인증구분</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $CONF_TYPE?></b></td>
					</tr>
<?php
	}
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>응답코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>응답메시지</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>상세응답코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>상세응답메시지</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>예비변수1</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED1?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>예비변수2</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED2?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>예비변수3</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED3?></b></td>
					</tr>
					<!--인증결과 끝-->

					<!--승인결과 시작-->

					<tr>
						<td><b>승인결과</b></td>
					</tr>
<?php
	if ($RES_RESPONSE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>거래번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_TRANSACTION_ID?></b></td>
					</tr>		
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>승인일시</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_DATE?></b></td>
					</tr>
<?php
		//[캐시게이트] 일 경우, 결제금액은 dealAmount로 표시
		if("0700" == $SERVICE_CODE){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>승인금액<b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DEAL_AMOUNT?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>잔액</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_BALANCE?></b></td>
					</tr>
<?php
		}else{ 
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>승인금액</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_AMOUNT?></b></td>
					</tr>
<?php
		}
		//[신용카드] 과세 금액 항목 추가
		if("0900" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>할부개월수</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_QUOTA?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>발급사코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CARD_COMPANY_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>카드번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PIN_NUMBER?></b></td>
					</tr>
<?php
		}
		//신용카드(0900), 계좌이체(0100), 문화상품권(0200), 게임문화상품권(0300), 해피머니상품권(0500), 틴캐시(2500), 에그머니(2600), 승인 응답 파라미터 추가
		if("0900" == $SERVICE_CODE || "0100" == $SERVICE_CODE || "0200" == $SERVICE_CODE || "0300" == $SERVICE_CODE || "0500" == $SERVICE_CODE || "2500" == $SERVICE_CODE || "2600" == $SERVICE_CODE){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>승인번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_NUMBER?></b></td>
					</tr>
<?php
		}
		//[계좌이체]일 경우, 응답 파라미터 추가
		if("1000" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>거래구분</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_MIX_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>현금영수증 용도</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_USING_TYPE?></b></td>
					</tr>	
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>현금영수증 승인번호</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_IDENTIFIER?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>현금영수증 자진발급제유무</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_IDENTIFIER_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>은행 코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_INPUT_BANK_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>은행명</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_INPUT_ACCOUNT_NAME?></b></td>
					</tr>
<?php
		}
		if("1100" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>부분취소타입</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PART_CANCEL_TYPE?></b></td>
					</tr>	
<?php
		}
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>응답코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>응답메시지</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>상세응답코드</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>상세응답메시지</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
<?php
	}else{
?>
					<tr>
						<td width="300" align="center" bgcolor="#F6F6F6" colspan="2"><b>승인 결과 없음</b></td>
					</tr>	
<?php
	}
?>
					<!--승인결과 끝-->
				</table>
			</td>
		</tr>
	</table>
	</div>
	<br>
</body>

</html>