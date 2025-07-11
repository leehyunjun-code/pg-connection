<?php
/*-------------------------------------------------------------------------------------
 해당 페이지는 빌게이트 결제 테스트를 위한 "취소결과" 페이지 입니다.
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
?>
<?php
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
	$reqMsg->setVersion("0100");	//버전 (0100)
	$reqMsg->setMerchantId($SERVICE_ID); //가맹점 아이디
	$reqMsg->setServiceCode($SERVICE_CODE); //서비스코드
	$reqMsg->setOrderId($ORDER_ID); //주문번호
	$reqMsg->setOrderDate($ORDER_DATE); //주문일시(YYYYMMDDHHMMSS)
	$reqMsg->setCipher($bgCipher); //암호화 셋팅

	//휴대폰
	if($SERVICE_CODE == "1100") {
		$reqMsg->setCommand("9000"); 

		//Set Body 
		if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
			$reqMsg->put("1001", $TRANSACTION_ID); //거래번호(1001)

		//부분 취소인 경우
		if($CANCEL_TYPE == "0000") {

			//취소금액(*주의* 부분취소 시 금액 없는 경우 전체 취소)
			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("7043", $CANCEL_AMOUNT); //부분취소금액(7043)
		}
	//계좌이체
	}else if($SERVICE_CODE == "1000") {
		//부분 취소인 경우
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$reqMsg->setCommand("9300"); //계좌이체 부분취소 Command

			//Set Body 
			if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
				$reqMsg->put("1012", $TRANSACTION_ID); //거래번호(1012)

			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("1033", $CANCEL_AMOUNT); //취소금액(1033)
			
			if($CANCEL_TYPE != NULL || $CANCEL_TYPE != "")
				$reqMsg->put("0015", $CANCEL_TYPE); //취소구분(0015) [0000:부분취소, 1000:나머지 전체 취소]

		//전체 취소인 경우
		}else{
			$reqMsg->setCommand("9000"); //계좌이체 전체취소 Command

			//Set Body 
			if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
				$reqMsg->put("1001", $TRANSACTION_ID); //거래번호(1001)
		}

	//신용카드
	}else if($SERVICE_CODE == "0900") {
		//부분 취소인 경우
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$reqMsg->setCommand("9010"); //신용카드 부분취소 Command

			//Set Body 
			if($TRANSACTION_ID != NULL)
				$reqMsg->put("1001", $TRANSACTION_ID); //거래번호(1001)
			
			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("0012", $CANCEL_AMOUNT); //취소금액(0012) [취소구분이 1000 인 경우 자동계산함]

			if($CANCEL_TYPE != NULL || $CANCEL_TYPE != "")
				$reqMsg->put("0082", $CANCEL_TYPE); //취소구분(0082) [0000:부분취소, 1000:나머지 전체 취소]
		//전체 취소인 경우
		}else{
			$reqMsg->setCommand("9200"); //신용카드 전체취소 Command

			//Set Body 
			if($TRANSACTION_ID != NULL)
				$reqMsg->put("1001", $TRANSACTION_ID); //거래번호(1001)
		}

	}else{
		$reqMsg->setCommand("9000"); //그 외 결제수단 승인 취소 요청 Command

		//Set Body 
		if($TRANSACTION_ID != NULL)
			$reqMsg->put("1001", $TRANSACTION_ID); //거래번호(1001)
	}


	//Request
	$resMsg = $broker->invoke($reqMsg); //요청 및 응답

	//취소 응답
	$RES_RESPONSE_CODE = $resMsg->get("1002"); //응답코드(1002)
	$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //응답메시지(1003)
	$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //상세응답코드(1009)
	$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //상세응답메시지(1010)

	
	//신용카드
	if($SERVICE_CODE == "0900") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)

		//부분 취소인 경우
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$RES_CANCEL_AMOUNT = $resMsg->get("0012"); //부분취소금액 (0012)
			$RES_PARTCANCEL_SEQUENCE = $resMsg->get("5049"); //부분취소 시퀀스(5049)
		//일반 취소인 경우
		}else{
			$RES_CANCEL_AMOUNT = $resMsg->get("1033"); //취소금액(1033)
		}
	//휴대폰
	}else if($SERVICE_CODE == "1100") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
		$RES_PART_CANCEL_TYPE = $resMsg->get("7049"); //부분취소타입(7049)

		//부분 취소인 경우
		if($CANCEL_TYPE == "0000") {
			$RES_CANCEL_AMOUNT = $resMsg->get("7043"); //부분취소금액(7043)
			$RES_CANCEL_TRANSACTION_ID = $resMsg->get("1032"); //취소거래번호(1032)
			$RES_REAUTH_OLD_TRANSACTION_ID = $resMsg->get("1040"); //재승인 이전거래번호(1040)
			$RES_REAUTH_NEW_TRANSACTION_ID = $resMsg->get("1041"); //재승인 신규거래번호(1041)
		}else{
			$RES_CANCEL_AMOUNT = $resMsg->get("1007"); //취소금액(1007)
		}
	//계좌이체
	}else if($SERVICE_CODE == "1000") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
		$RES_CANCEL_AMOUNT = $resMsg->get("1033"); //취소금액(1033) [전체취소, 부분취소 금액]

		//부분 취소인 경우
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$RES_TRANSACTION_ID = $resMsg->get("1012"); //거래번호(1012)
			$RES_PARTCANCEL_SEQUENCE = $resMsg->get("0096"); //부분취소 시퀀스(0096)
		}
	//티머니, 캐시게이트
	}else if($SERVICE_CODE == "1600" || $SERVICE_CODE == "0700") {
		$RES_CANCEL_AMOUNT = $resMsg->get("0012"); //취소금액(0012)
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
	}else{
		//0100(도서상품권), 0200(문화상품권), 2600(에그머니), 0300(게임문화상품권), 0500(해피머니), 4100(통합포인트), 1200(폰빌), 2500(틴캐시)
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //거래번호(1001)
	}
?>
<html>
<head>
<style>
	body, tr, td {font-size:9pt; font-family:맑은고딕,verdana; }
	div {width: 98%; height:100%; overflow-y: auto; overflow-x:hidden;}
</style>
<meta charset="EUC-KR">
<title>빌게이트 결제 테스트 샘플페이지</title>
</head>
<body>
	<div>
	<table border="0" cellpadding="0"	cellspacing="0">
		<tr> 
	 		 <td height="25" style="padding-left:10px" class="title01"># 현재위치 &gt;&gt; <b>가맹점 취소 페이지</b></td>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="center"><!--본문테이블 시작--->
				<table border="0" cellpadding="4" cellspacing="1" bgcolor="#B0B0B0">
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>가맹점 아이디</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_ID?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>서비스코드</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>주문번호</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_ID?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>주문일시</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_DATE?></b></td>
					</tr>			
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>거래번호</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_TRANSACTION_ID?></b></td>
					</tr>
<?php
	//신용카드(전체/부분취소), 휴대폰(전체/부분취소), 계좌이체(전체/부분취소)
	if($RES_CANCEL_AMOUNT) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>취소금액</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CANCEL_AMOUNT?></b></td>
					</tr>
<?php
	}
	//신용카드, 계좌이체 (부분취소)
	if($$RES_PARTCANCEL_SEQUENCE) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>부분취소 시퀀스</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PARTCANCEL_SEQUENCE_NUMBER?></b></td>
					</tr>
<?php
	}
	//휴대폰
	if($RES_PART_CANCEL_TYPE) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>부분취소 타입</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PART_CANCEL_TYPE?></b></td>
					</tr>
<?php
	}
	//휴대폰
	if($RES_CANCEL_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>취소 거래번호</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CANCEL_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//휴대폰
	if($RES_REAUTH_OLD_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>재승인 이전거래번호</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_REAUTH_OLD_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//휴대폰
	if($RES_REAUTH_NEW_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>재승인 신규거래번호</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_REAUTH_NEW_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>응답코드</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>응답메시지</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>상세응답코드</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>상세응답메시지</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</div>
</body>
</html>