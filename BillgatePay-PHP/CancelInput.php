<?php
	/*-------------------------------------------------------------------------------------
	해당 페이지는 빌게이트 결제 테스트를 위한 "취소 요청 입력" 페이지 입니다.
		※ 가맹점 환경에 맞게 returnUrl 설정 필요

		※ 상용 전환 시 변경사항
		1. 결제정보(SERVICE_ID) 변경 -> 계약 시 발급 된 SERVICE_ID 정보 입력
		2. config.ini Key, Iv 변경 (가맹점 관리자 어드민에서 확인 가능)
		3. config.ini mode = 1 변경 (상용 모드 설정)

		- 상용 테스트는 실제 취소가 이뤄지는 점 유의하시길 바랍니다.
	-------------------------------------------------------------------------------------*/
?>
<?php
	@extract($_REQUEST);
	header('Content-Type: text/html; charset=euc-kr');
?>
<?php
	//================================================
	// 1. 가맹점 취소 요청 테스트 공통 정보
	//================================================
	$serviceId = "M2103135";          // 테스트 아이디 (일반결제 : M2103135, 자동결제 : M2103139)
	$orderDate = date('YmdHis') ;    // 주문일시
	$orderId = "cancel_".$orderDate ;  // 주문번호
	$cancelUrl = "https://tpay2.billgate.net/BillgatePay-PHP/CancelReturn.php"; // 리턴페이지  2023090115TT149905


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="EUC-KR">
<title>빌게이트 결제 테스트 샘플페이지</title>
<script>
	
	//==========================
	// 결제 취소 요청
	//==========================
	function requestCancel(){
		var HForm = document.cancel;
		var serviceCode = HForm.SERVICE_CODE.options[HForm.SERVICE_CODE.selectedIndex].value;
		var transactionId = HForm.TRANSACTION_ID.value;

		if("" == serviceCode){
			alert("결제수단을 선택해주세요.");
			return;
		}
		
		if("" == transactionId) {
			alert("거래번호를 입력해주세요.");
			return;
		}

		var HForm = document.cancel;
		var option ="width=480,height=600,titlebar=no,fullscreen=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,left=50,top=50";
		var objPopup = window.open("", "cancel", option);
		
		HForm.target="cancel";
		HForm.action= HForm.CANCEL_URL.value;
		HForm.submit();
	}
	

	//==========================
	// 결제 수단 선택
	//==========================
	function paySelect(){

		var HForm = document.cancel;

		var serviceCodeSelect = HForm.SERVICE_CODE;
		var serviceCode = serviceCodeSelect.options[serviceCodeSelect.selectedIndex].value;
		
		var cancelType = HForm.CANCEL_TYPE;

		cancelType[0].selected = true;

		if ( cancelType.options.length > 2 ) {
			cancelType.options[cancelType.options.length -1] = null;
			cancelType.options[cancelType.options.length -1] = null;
		}else if ( cancelType.options.length > 1) {
			cancelType.options[cancelType.options.length -1] = null;
		}


		HForm.ORDER_ID.value = "cancel_" + getStrDate();
		HForm.ORDER_DATE.value = getStrDate();

		document.getElementById("add_view").style.display="none";
		document.getElementById("add_cancel_view1").style.display="none";

		HForm.TRANSACTION_ID.value = "";
		HForm.CANCEL_AMOUNT.value = "";


		//결제창 호출 URL설정
		switch(serviceCode){
			case'0900':	//신용카드
				document.getElementById("add_view").style.display="";
				document.getElementById("add_cancel_view1").style.display="";

				cancelType.options[cancelType.options.length] = new Option("부분취소(0000)","0000");
				cancelType.options[cancelType.options.length] = new Option("나머지 금액 부분취소(1000)","1000");

				break;
			case'1000':	//계좌이체
				cancelType.options[cancelType.options.length] = new Option("부분취소(0000)","0000");
				cancelType.options[cancelType.options.length] = new Option("나머지 금액 부분취소(1000)","1000");

				break;
			case'1100':	//휴대폰
				document.getElementById("add_view").style.display="";
				document.getElementById("add_cancel_view1").style.display="";

				cancelType.options[cancelType.options.length] = new Option("부분취소(0000)","0000");
				
				break;
			default:	//그외
				break;
		}	
	}

	function selectCancelType(){
		var HForm = document.cancel;

		HForm.CANCEL_AMOUNT.value = "";
	}

	//현재 날짜 및 시간 가져오기
	function getStrDate() {
		var date = new Date();
		var strDate = 	(date.getFullYear().toString()) + 
						((date.getMonth() + 1) < 10 ? "0" + (date.getMonth() + 1).toString() : (date.getMonth() + 1).toString()) +
						((date.getDate()) < 10 ? "0" + (date.getDate()).toString() : (date.getDate()).toString()) +
						((date.getHours()) < 10 ? "0" + (date.getHours()).toString() : (date.getHours()).toString()) +
						((date.getMinutes()) < 10 ? "0" + (date.getMinutes()).toString() : (date.getMinutes()).toString()) +
						((date.getSeconds()) < 10 ? "0" + (date.getSeconds()).toString() : (date.getSeconds()).toString());
		return strDate;
	}
</script>	
<style>
	header{position: fixed;	top: 0;	left: 0; right: 0;}	
	body, tr, td {font-size:9pt; font-family:맑은고딕,verdana; }
	table {	border-collapse: collapse;}	
</style>
</head>
<body>
<header>
	<div style="width:100%; heghit:12px; font-size:13px; font-weight:bold; color: #FFFFFF; background:#ff4280;text-align: center;">
		빌게이트 결제 테스트 샘플페이지
	</div>
</header>


	<div  style="padding : 20px 0 20px 0; width:100%; display: block; float:left; ">
		<b style="color:red;"><유의사항></b><br/>
		<b>- 당사에서 제공하는 샘플은 연동에 이해를 돕기위해 단계별로 나열한 것이므로, 동일한 구조를 유지할 필요가 없음을 알려드립니다.</b><br/>
		- 휴대폰, 신용카드, 계좌이체 서비스만 부분취소 기능 제공이 가능합니다. <b style="color:red;">※(담당자 협의 후 제공 가능)</b><br/>
	</div>
	
	<div style="width:100%; display: block; float:left;">
		<form name="cancel" method="post">
			<table border="1px solid" cellpadding="5" cellspacing="1" bgcolor="#B0B0B0">
				<tr>
					<td colspan="2" height="20" align="left" bgcolor="#C0C0C0"><b>취소 정보</b></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">가맹점아이디<br/>(SERVICE_ID)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="SERVICE_ID" size=30 class="input" value="<?php echo $serviceId?>">(일반결제:M2103135, 자동과금결제:M2103139)</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">주문번호<br/>(ORDER_ID)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="ORDER_ID" size=30 class="input" value="<?php echo $orderId?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">주문일시<br/>(ORDER_DATE)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="ORDER_DATE" size=30 class="input" value="<?php echo $orderDate?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">서비스코드<br/>(SERVICE_CODE)</td>
					<td width="150" bgcolor="#FFFFFF">
						<select name="SERVICE_CODE" onChange="paySelect()">
							<option value="" selected>==선택==</option>
							<option value="0100">도서(0100)</option>
							<option value="0200">문화(0200)</option>
							<option value="0300">게임문화(0300)</option>
							<option value="0500">해피머니(0500)</option>
							<option value="0700">캐시게이트(0700)</option>
							<option value="0900">신용카드(0900)</option>
							<option value="1100">휴대폰(1100)</option>
							<option value="1200">폰빌(1200)</option>
							<option value="1600">티머니(1600)</option>
							<option value="1000">계좌이체(1000)</option>
							<option value="2500">틴캐시(2500)</option>
							<option value="2600">에그머니(2600)</option>
							<option value="4100">통합포인트(4100)</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">취소타입<br/>(CANCEL_TYPE)</td>
					<td width="150" bgcolor="#FFFFFF">
						<select name="CANCEL_TYPE" onchange="selectCancelType()">
							<option value="" selected>전체취소(공백)</option>
							<!--option value="0000">부분취소(0000)</option-->
							<!--option value="1000">나머지 금액 부분취소(1000)</option-->
						</select>
					</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">거래번호(TRANSACTION_ID)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="TRANSACTION_ID" size=30 class="input" value="" placeholder="취소요청 할 거래번호를 적어주세요."></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">취소요청 URL(CANCEL_URL)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="CANCEL_URL" size=80 class="input" value="<?php echo $cancelUrl?>"></td>
				</tr>
				<tr id="add_view" style="display:none;">
					<td colspan="4"><b>추가 파라미터</b></td>
				</tr>
				<tr id="add_cancel_view1" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">취소금액(CANCEL_AMOUNT)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="CANCEL_AMOUNT" size=30 class="input" value=""><br/>(<b>취소타입:"나머지 금액 부분취소"</b> 선택인 경우 입력금액과 상관없이 전체 금액 취소 됨.)</td>
				</tr>
			</table>
		</form>

		<div>
			<br/><input type="button" value="취소요청" onclick="javascript:requestCancel();">
			<br/>
		</div>
	</div>

</body>
</html>