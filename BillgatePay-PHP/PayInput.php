<?php
	/*-------------------------------------------------------------------------------------
	�ش� �������� ������Ʈ ���� �׽�Ʈ�� ���� "���� ��û �Է�(������û)" ������ �Դϴ�.
		�� ������ ȯ�濡 �°� returnUrl ���� �ʿ�

		�� ��� ��ȯ �� �������
		1. ��������(SERVICE_ID) ���� -> ��� �� �߱� �� SERVICE_ID ���� �Է�
		2. config.ini Key, Iv ���� (������ ������ ���ο��� Ȯ�� ����)
		3. config.ini mode = 1 ���� (��� ��� ����)

		- ��� �׽�Ʈ�� ���� ������ �̷����� �� �����Ͻñ� �ٶ��ϴ�.
	-------------------------------------------------------------------------------------*/
?>
<?php
	@extract($_REQUEST);
	header('Content-Type: text/html; charset=euc-kr');
?>
<?php
	//================================================
	// 1. ������ ���� ��û �׽�Ʈ ���� ����
	//================================================
	$serviceId = "M2103135";          // �׽�Ʈ ���̵� (�Ϲݰ��� : M2103135)
	$orderDate = date('YmdHis') ;    // �ֹ��Ͻ�
	$orderId = "test_".$orderDate ;  // �ֹ���ȣ
	$userId = "user_id";             // ��ID
	$userName = "ȫ�浿";            // ����
	$userEmail = "test@test.com";    // ���̸���
	$itemName = "�׽�Ʈ��ǰ_123";    // ��ǰ��
	$itemCode = "item_code";         // ��ǰ�ڵ�
	$amount = "1004";                // �ݾ�
	$returnUrl = "https://tpay2.billgate.net/BillgatePay-PHP/PayReturn.php"; // ����������
	$cancelFlag = "Y";               // ��Ұ�� ���� ���� (Y, N)
	$reserved1 = "���񺯼�1";        // ���񺯼�1
	$reserved2 = "���񺯼�2";        // ���񺯼�2
	$reserved3 = "���񺯼�3";        // ���񺯼�3

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="EUC-KR">
<title>������Ʈ ���� �׽�Ʈ ����������</title>
<!--�׽�Ʈ ���� js-->
<script type="text/javascript" src="https://tpay.billgate.net/paygate/plugin/gx_web_client.js" />
<!--��� ���� js-->
<!--<script type="text/javascript" src="https://pay.billgate.net/paygate/plugin/gx_web_client.js" /> -->
<script type="text/javascript"></script>
<script>
	
	//==========================
	// ����â ȣ�� 
	//==========================
	function checkSubmit(viewType){
		var HForm = document.payment;
		
		var serviceId = HForm.SERVICE_ID.value;
		var serviceCode = document.getElementById("selectPay").value;

		if("null"==serviceCode||""==serviceCode){
			alert("���������� �������ּ���.");
			return;
		}
		
		if ("null" == viewType || "" == viewType) {
			alert("�� Ÿ���� �Է����ּ���.");
		}
		/*
		GX_pay(
		frmName : ���� form name �Է�, 
		viewType : layerpopup ���̾��˾�, popup : �������˾�, submit : ������ �̵� 
		protocolType : http_tpay(�׽�Ʈ http), https_tpay(�׽�Ʈ https), https_pay(��� https)
		*/	
		GX_pay("payment", viewType ,"https_tpay");
	}
	

	//==========================
	// ���̾� �˾� �ݱ�
	//==========================
	function layer_close(){
		GX_payClose();
	}
	

	//==========================
	// üũ�� �����
	//==========================
	function makeCheckSum(){
		var HForm = document.payment;
		var orderId = "test_" + getStrDate();
		HForm.ORDER_ID.value = orderId;
		HForm.ORDER_DATE.value = getStrDate();

		var CheckSum = HForm.SERVICE_ID.value +orderId + HForm.AMOUNT.value;
		var xhr = new XMLHttpRequest();
		var data = "CheckSum="+CheckSum;

		//Ajax ���
		xhr.onload = function(){
			if(xhr.readyState == 4 && xhr.status == 200){ //��� ���� ��
				console.log(xhr.responseText);
				HForm.CHECK_SUM.value = xhr.responseText.trim();  //������� üũ��
			}
		};
		
		xhr.open("POST","./PayCheckSum.php",true);
		xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		xhr.send(data);
	}

	//==========================
	// ���� ��¥�ð� ��������
	//==========================
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
	

	//==========================
	// ���� ���� ����
	//==========================
	function paySelect(){

		var HForm = document.payment;

		var serviceCodeSelect = document.getElementById("selectPay");
		var serviceCode = serviceCodeSelect.options[serviceCodeSelect.selectedIndex].value;
		
		HForm.SERVICE_CODE.value = serviceCode;
		HForm.ORDER_ID.value = "test_" + getStrDate();
		HForm.ORDER_DATE.value = getStrDate();
		makeCheckSum();

		document.getElementById("add_view").style.display="none";
		document.getElementById("add_card_view1").style.display="none";
		document.getElementById("add_card_view2").style.display="none";
		document.getElementById("add_card_view3").style.display="none";
		document.getElementById("add_common_view1").style.display="none";
		document.getElementById("add_common_view2").style.display="none";
		document.getElementById("add_vaccount_view").style.display="none";
		document.getElementById("add_mobile_view1").style.display="none";
		document.getElementById("add_mobile_view2").style.display="none";
		document.getElementById("add_moneytree_view").style.display="none";


		//����â ȣ�� URL����
		switch(serviceCode){
			case'0900':	//�ſ�ī��
				document.getElementById("add_view").style.display="";
				document.getElementById("add_card_view1").style.display="";
				document.getElementById("add_card_view2").style.display="";
				document.getElementById("add_card_view3").style.display="";

				break;

			case'1800':	//�������
				document.getElementById("add_view").style.display="";
				document.getElementById("add_vaccount_view").style.display="";
				document.getElementById("add_common_view2").style.display="";

				break;

			case'1100':	//�޴���
				document.getElementById("add_view").style.display="";
				document.getElementById("add_mobile_view1").style.display="";
				document.getElementById("add_mobile_view2").style.display="";
				document.getElementById("add_common_view1").style.display="";
				
				(document.getElementsByName("SOCIAL_NUMBER"))[0].disabled = false;
				(document.getElementsByName("SOCIAL_NUMBER"))[1].disabled = true;

				break;

			case'4100':	//��������Ʈ
				document.getElementById("add_view").style.display="";
				document.getElementById("add_moneytree_view").style.display="";
				document.getElementById("add_common_view1").style.display="";
				
				(document.getElementsByName("SOCIAL_NUMBER"))[0].disabled = true;
                (document.getElementsByName("SOCIAL_NUMBER"))[1].disabled = false;
				
				break;

			default:	//�׿�
				break;
		}	
	}

</script>	
<style>
	header{position: fixed;	top: 0;	left: 0; right: 0;}	
	body, tr, td {font-size:9pt; font-family:�������,verdana; }
	table {	border-collapse: collapse;}	
</style>
</head>
<body>
<header>
	<div style="width:100%; heghit:12px; font-size:13px; font-weight:bold; color: #FFFFFF; background:#ff4280;text-align: center;">
		������Ʈ ���� �׽�Ʈ ����������
	</div>
</header>

		
	<div style="padding : 20px 0 20px 0; width:100%; display: block; float:left; ">
		<b style="color:red;"><���ǻ���></b><br/>
		<b>- ��翡�� �����ϴ� ������ ������ ���ظ� �������� �ܰ躰�� ������ ���̹Ƿ�, ������ ������ ������ �ʿ䰡 ������ �˷��帳�ϴ�.</b><br/>
		- SERVICE_ID, AMOUNT, ORDER_ID ���� �� <b>[üũ�� �����]</b> ��ư�� Ŭ���Ͽ� üũ���� ������Ͽ��� ������ �����մϴ�.<br/>
		- �ŷ� �� ������ ���� �ߺ��� �ֹ���ȣ(ORDER_ID)�� ���� ��û�� �������� �ʽ��ϴ�.<br/>	
	</div>
		
	<div style="width:100%; display: block; float:left;">
		<form name="payment" method="post">
			<table border="1px solid" cellpadding="5" cellspacing="1" bgcolor="#B0B0B0">	
				<tr>
					<td colspan="4" height="20" align="left" bgcolor="#C0C0C0"><b>���� ����</b></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">��������</td>
					<td width="150" bgcolor="#FFFFFF" colspan="3">
						<select id="selectPay" onChange="paySelect()">
							<option value="" selected>==����==</option>
							<option value="0100">����(0100)</option>
							<option value="0200">��ȭ(0200)</option>
							<option value="0300">���ӹ�ȭ(0300)</option>
							<option value="0500">���ǸӴ�(0500)</option>
							<option value="0700">ĳ�ð���Ʈ(0700)</option>
							<option value="0900">�ſ�ī��(0900)</option>
							<option value="1100">�޴���(1100)</option>
							<option value="1200">����(1200)</option>
							<option value="1600">Ƽ�Ӵ�(1600)</option>
							<option value="1800">�������(1800)</option>
							<option value="1000">������ü(1000)</option>
							<option value="2500">ƾĳ��(2500)</option>
							<option value="2600">���׸Ӵ�(2600)</option>
							<option value="4100">�Ӵ�Ʈ��(4100)</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">���������̵�<br/>(SERVICE_ID)</td>
					<td  width="150" bgcolor="#FFFFFF"><input type="text" name="SERVICE_ID" id="SERVICE_ID" size=30 class="input" value="<?php echo $serviceId?>"><br/>(�Ϲݰ���:M2103135, �ڵ�����1ȸ������:M2103139)</td>
					<td width="150" align="left" bgcolor="#F6F6F6">����Ÿ��<br/>(SERVICE_TYPE)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="SERVICE_TYPE" size=40 class="input" value="0000"><br/>(�Ϲݰ���:0000)</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">�����ڵ�<br/>(SERVICE_CODE)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="SERVICE_CODE" id="SERVICE_CODE" size=30 class="input" value=""></td>
					<td width="150" align="left" bgcolor="#F6F6F6">���� �ݾ�<br/>(AMOUNT)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="AMOUNT" size=40 class="input" value="<?php echo $amount?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">��ǰ��<br/>(ITEM_NAME)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="ITEM_NAME" size=30 class="input" value="<?php echo $itemName?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">��ǰ�ڵ�<br/>(ITEM_CODE)</td>
					<td bgcolor="#FFFFFF"><input type="text" name="ITEM_CODE" size=40 class="input" value="<?php echo $itemCode?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">�� ���̵�<br/>(USER_ID)</td>
					<td bgcolor="#FFFFFF"><input type="text" name="USER_ID" size=30 class="input" value="<?php echo $userId?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">����<br/>(USER_NAME)</td>
					<td bgcolor="#FFFFFF"><input type="text" name="USER_NAME" size=40 class="input" value="<?php echo $userName?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">�ֹ���ȣ<br/>(ORDER_ID)</td>
					<td bgcolor="#FFFFFF"><input type="text" name="ORDER_ID" size=30 class="input" value="<?php echo $orderId?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">�ֹ��Ͻ�<br/>(ORDER_DATE)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="ORDER_DATE" size=40 class="input" value="<?php echo $orderDate?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">���̸���<br/>(USER_EMAIL)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="USER_EMAIL" size=30 class="input" value="<?php echo $userEmail?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">CheckSum<br/>(CHECK_SUM)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="CHECK_SUM" size=40 class="input" value=""><input type="button" name="" value="üũ�� �����" onclick="javascript:makeCheckSum()"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">���񺯼�1<br/>(RESERVED1)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="RESERVED1" size=30 class="input" value="<?php echo $reserved1?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">���񺯼�2<br/>(RESERVED2)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="RESERVED2" size=40 class="input" value="<?php echo $reserved2?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">���񺯼�3<br/>(RESERVED3)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="RESERVED3" size=30 class="input" value="<?php echo $reserved3?>"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">��Ұ�� ���޿���<br/>(CANCEL_FLAG)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="CANCEL_FLAG" size=40 class="input" value="<?php echo $cancelFlag?>"><br/>(Y:Return ��� ����, N:self.close())</td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">����URL<br/>(RETURN_URL)</td>
					<td bgcolor="#FFFFFF" colspan="3"><input type="text" name="RETURN_URL" size=80 class="input" value="<?php echo $returnUrl?>"></td>
				</tr>
				<tr>
					<td width="150" align="left" bgcolor="#F6F6F6">�ΰ�<br/>(LOGO)</td>
					<td width="150" bgcolor="#FFFFFF" colspan="3"><input type="text" name="LOGO" size=80 class="input" value=""><br/>(�̹��� �ΰ� URL �Է�)</td>
				</tr>
				<tr id="add_view" style="display:none;">
					<td colspan="4"><b>�߰� �Ķ����</b></td>
				</tr>
				
				<!-- �ſ�ī�� ���� -->
				<tr id="add_card_view1" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�Һΰ�����<br/>(INSTALLMENT_PERIOD)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="INSTALLMENT_PERIOD" size=30 class="input" value="0:3:6:9:12"></td>
					<td width="150" align="left" bgcolor="#F6F6F6">ī��缱��<br/>(CARD_TYPE)</td>
					<td bgcolor="#FFFFFF">
						<select name="CARD_TYPE" >
							<option value="0000">---ī��� ����---</option>
								<option value="0052">��ī��(BC card)</option>
								<option value="0050">����ī��(KB card)</option>
								<option value="0073">����ī��(Hyundai card)</option>
								<option value="0054">�Ｚī��(Samsung card)</option>
								<option value="0053">����(LG)ī��(Shinhan(LG) card)</option>
								<option value="0055">�Ե�ī��(Lotte card)</option>
								<option value="0089">��������(savings bank)</option>
								<option value="0051">��ȯī��(Yes card)</option>
								<option value="0076">�ϳ�(Hana card)</option>
								<option value="0079">����(e-jeju bank)</option>
								<option value="0080">����(kjbank)</option>
								<option value="0073">����(����)(cu(Hyundai))</option>
								<option value="0075">����(suhyup)</option>
								<option value="0081">����(jbbank)</option>
								<option value="0078">����(NH card)</option>
								<option value="0084">��Ƽ(Citi card)</option>
						</select>
					</td>
				</tr>
				<tr id="add_card_view2" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">[�ؿ�����]����/�ؿ�ī��<br/>(USING_TYPE)</td>
					<td width="150" bgcolor="#FFFFFF" ><input type="text" name="USING_TYPE" size=30 class="input" value=""><br/>(0001:�ؿ�ī��, �׿�)</td>
					<td width="150" align="left" bgcolor="#F6F6F6">[�ؿ�����]������ȭ����<br/>(CURRENCY)</td>
					<td width="150" bgcolor="#FFFFFF" ><input type="text" name="CURRENCY" size=30 class="input" value=""><br/>(0000:��ȭ����, 0001:�޷�����)</td>
				</tr>
				<tr id="add_card_view3" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">������� ����ȣ��<br/>(DIRECT_USE)</td>
					<td width="150" bgcolor="#FFFFFF"  colspan="3"><input type="text" name="DIRECT_USE" size=30 class="input" value="">(0001:����ȣ��, �׿�:������)</td>
				</tr>
				<!-- �ſ�ī�� ���� -->

				<!-- �޴��� ���� -->
				<tr id="add_mobile_view1" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�ֹι�ȣ ��6�ڸ�<br/>(SOCIAL_NUMBER)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="SOCIAL_NUMBER" size=30 class="input" value="" maxlength="6"><br/>�������(YYMMDD)</td>
					<td width="150" align="left" bgcolor="#F6F6F6">�̵���Ż� �ڵ�<br/>(MOBILE_COMPANY_CODE)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="MOBILE_COMPANY_CODE" size=30 class="input" value="" maxlength="4"><br/>0000:SKT, 0001:KT, 0002:LGU, 0011:CJH, 0010:KCT, 0012:SKL</td>
				</tr>
				<tr id="add_mobile_view2" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�������� �޴�����ȣ ��������<br/>(READONLY_HP)</td>
					<td width="150" bgcolor="#FFFFFF" colspan="3"><input type="text" name="READONLY_HP" size=30 class="input" value=""><br/>(Y:�����Ұ�, N:��������)</td>
				</tr>
				<!-- �޴��� ���� -->

				<!-- ������� ���� -->
				<tr id="add_vaccount_view" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�Աݸ��� ��ȿ��<br/>(QUOTA)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="QUOTA" size=30 class="input" value="" maxlength="2"><br/>(���Է½� ����Ʈ(5), ����������[1 - 30])</td>
					<td width="150" align="left" bgcolor="#F6F6F6">�Աݸ��� ��ȿ�ð�<br/>(EXPIRE_TIME)</td>
					<td width="150" bgcolor="#FFFFFF"><input type="text" name="EXPIRE_TIME" size=30 class="input" value="" maxlength="6"><br/>(���Է½� ����Ʈ(235959) [24HHMMSS])</td>
				</tr>
				<!-- ������� ���� -->

				<!-- �Ӵ�Ʈ�� ���� -->
				<tr id="add_moneytree_view" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�������� �޴�����ȣ ��������<br/>(MODIFY_FLAG)</td>
					<td width="150" bgcolor="#FFFFFF" colspan="3"><input type="text" name="MODIFY_FLAG" size=30 class="input" value="">(Y:�����Ұ�, N:��������)</td>
				</tr>
				<!-- �Ӵ�Ʈ�� ���� -->

				<!-- (�޴���, �Ӵ�Ʈ��) ���� -->
				<tr id="add_common_view1" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">�޴��� ��ȣ<br/>(MOBILE_NUMBER)</td>
					<td width="200" bgcolor="#FFFFFF" colspan="3"><input type="text" name="MOBILE_NUMBER" size=30 class="input" value="" maxlength="11">-(������) ���� �Է�</td>
				</tr>
				<!-- (�޴���, �Ӵ�Ʈ��) ���� -->

				<!-- ������� ���� -->
				<tr id="add_common_view2" style="display:none;">
					<td width="150" align="left" bgcolor="#F6F6F6">����ũ�� ��� ����<br/>(ESCROW_FLAG)</td>
					<td width="150" bgcolor="#FFFFFF" colspan="3"><input type="text" name="ESCROW_FLAG" size=30 class="input" value="">(Y:����ũ�� �ʼ� ����, N:����ũ�� ������)</td>
				</tr>
				<!-- ������� ���� -->
			</table>
		</form>

		<div style="padding : 0px 0 10px 0; width:100%; display: block; float:left; ">
			<br/><input type="button" value="����â ȣ��(layerpopup) PC ����" onclick="javascript:checkSubmit('layerpopup');">
			&nbsp;<input type="button" value="����â ȣ��(submit)" onclick="javascript:checkSubmit('submit');">
			&nbsp;<input type="button" value="����â ȣ��(popup)" onclick="javascript:checkSubmit('popup');">
			<br/>
		</div>
	</div>

</body>
</html>