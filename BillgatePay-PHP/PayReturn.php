<?php
/*-------------------------------------------------------------------------------------
 �ش� �������� ������Ʈ ���� �׽�Ʈ�� ���� "������� ���� �� ���� ��û/����" ������ �Դϴ�.
	 
	 ���׽�Ʈ ������ ���ϽŴٸ�,
	 1. ���� ���� (returnUrl, cancelUrl) ������ ȯ�濡 �°� ����
	 2. billgateConfig.php�� BILLGATE_HOME ��� ����
	 
	 �ؽ��� ��� �׽�Ʈ�� ���ϽŴٸ�,
	 1. ���� ���� (serviceId, returnUrl, cancelUrl)�� ����  -> ��� �� ���� serviceId ������ �����ø� �˴ϴ�.
	 2. config.ini �� Key,Iv �� ���� (������ ������ ���ο��� Ȯ�� ����)
	 3. config.ini �� mode = 1(���)���� ���� �� �� ���� �׽�Ʈ �Ͻñ� �ٶ��ϴ�.

	 - ��� �׽�Ʈ�� ���� ������ �̷����� �� �����Ͻñ� �ٶ��ϴ�.
-------------------------------------------------------------------------------------*/
?>
<?php
	header('Content-Type: text/html; charset=euc-kr'); 
	@extract($_REQUEST);

	//---------------------------------------
	// API Ŭ���� Include
	//---------------------------------------
	include_once ("BillgateConfig.php");
	include_once ("billgateClass/Service.php");
	include_once ('KISA_SHA256.php');
?>
<?php
	//���� ���������� ���� ��� ���� �����ֱ� ���� �ӽ� ��������
	$REQ_RESPONSE_CODE = $RESPONSE_CODE;
	$REQ_RESPONSE_MESSAGE = $RESPONSE_MESSAGE;
	$REQ_DETAIL_RESPONSE_CODE = $DETAIL_RESPONSE_CODE;
	$REQ_DETAIL_RESPONSE_MESSAGE = $DETAIL_RESPONSE_MESSAGE;

	$CP_PRODUCT_AMOUNT = "1000";//������ ��ǰ �ݾ�

	//================================================
	// 1. ���� ������ ��� ����(����)��û
	//================================================	
	if(!strcmp($RESPONSE_CODE,"0000") && $SERVICE_CODE != "1800") { //������� ����

		//HASH_DATA ��
		$hashTemp = $SERVICE_ID . $ORDER_ID . $ORDER_DATE . $CP_PRODUCT_AMOUNT; //
		$tempHashData = hash('sha256', $hashTemp);

		if("0900" == $SERVICE_CODE || "1100" == $SERVICE_CODE){
			if($tempHashData == $HASH_DATA){
?>	
				<script type="text/javascript">
					alert("HASH_DATA �� ����ġ");
					exit;
				</script>
<?php
			}
		}
		
		//=====================
		// 2. ���� ��û �� ����
		//=====================

		//---------------------------------------
		// API �ν��Ͻ� ����
		//---------------------------------------
		$reqMsg = new Message();       //��û �޽���
		$resMsg = new Message();       //���� �޽���
		$configInfo = new ConfigInfo($INI_FILE, $SERVICE_CODE);

		//set init
		$bgCipher = new BgCipher($configInfo);
		$broker = new ServiceBroker($configInfo, $SERVICE_CODE);

		//Set Header
		$reqMsg->setCipher($bgCipher); //��ȣȭ ����

		//Set Body
		$reqMsg->setMessages($MESSAGE); //�޽��� ����

		//Request
		$resMsg = $broker->invoke($reqMsg); //��û �� ����

		//�޴���
		if("1100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_PART_CANCEL_TYPE = $resMsg->get("7049"); //�κ����Ÿ��(7049)
			}

		//�ſ�ī��
		}else if("0900"==$SERVICE_CODE){
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
				$RES_QUOTA = $resMsg->get("0031"); //�Һΰ�����(0031)
				$RES_CARD_COMPANY_CODE = $resMsg->get("0034"); //�߱޻��ڵ�(0034)
				$RES_PIN_NUMBER = $resMsg->get("0008"); //ī���ȣ(0008)
			}

		//������ü
		}else if("1000"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_USING_TYPE = $resMsg->get("0015"); //���ݿ������뵵(0015) [0:�ҵ������, 1:����������]
				$RES_IDENTIFIER = $resMsg->get("0017"); //���ݿ����� ���ι�ȣ(0017)
				$RES_IDENTIFIER_TYPE = $resMsg->get("0102"); //���ݿ����� �����߱��� ����(0102) [Y:�����߱��� ����, �׿�:������]
				$RES_MIX_TYPE = $resMsg->get("0037"); //�ŷ�����(0037) [0000:�Ϲ�, 1000:����ũ��]
				$RES_INPUT_BANK_CODE = $resMsg->get("0105"); //�����ڵ�(0105)
				$RES_INPUT_ACCOUNT_NAME = $resMsg->get("0107"); //�����(0107)
			}
	
		//������ǰ��
		}else if("0100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}

		//��ȭ��ǰ��
		}else if("0200"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}
	
		//���ӹ�ȭ��ǰ��
		}else if("0300"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}

		//���� �Ӵ� ��ǰ��
		}else if("0500"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}
		
		//ĳ�ð���Ʈ
		}else if("0700"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_BALANCE = $resMsg->get("1006"); //�ܾ�(1006)
				$RES_DEAL_AMOUNT = $resMsg->get("0012"); //���αݾ�(0012)
			}

		//���׸Ӵ�
		}else if("2600"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}
		
		//��������Ʈ
		}else if("4100"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
			}
		
		//Ƽ�Ӵ�
		}else if("1600"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}

		//����
		}else if("1200"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
			}

		//ƾĳ��
		}else if("2500"==$SERVICE_CODE) {
			//Response
			$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
			$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
			$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
			$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)
			$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
			
			//���� ������
			if(!strcmp($RES_RESPONSE_CODE, "0000")) {
				$RES_AUTH_DATE = $resMsg->get("1005"); //�����Ͻ�(1005)
				$RES_AUTH_AMOUNT = $resMsg->get("1007"); //���αݾ�(1007)
				$RES_AUTH_NUMBER = $resMsg->get("1004"); //���ι�ȣ(1004)
			}
		}
	}
?>
<html>
<head>
<title></title>
<style>
	body, tr, td {font-size:9pt; font-family:�������,verdana; }
	div {width: 98%; height:100%; overflow-y: auto; overflow-x:hidden;}
</style>
<meta charset="EUC-KR">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
<title>������Ʈ ���� �׽�Ʈ ����������</title>
</head>
<body>
	<div>
	<table width="380px" border="0" cellpadding="0"	cellspacing="0">
		<tr> 
			<td height="25" style="padding-left:10px" class="title01"># ������ġ &gt;&gt; �����׽�Ʈ &gt; <b>������ Return Url</b></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
				<table width="380" border="0" cellpadding="4" cellspacing="1" bgcolor="#B0B0B0">
					<tr>
						<td><b>�������</b></td>
					</tr>
					<!--������� ����-->
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>������ ���̵�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_ID?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ֹ���ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_ID?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ֹ��Ͻ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_DATE?></b></td>
					</tr>
<?php
	//�޴���(1100), ����(1200) ����Ÿ��
	if( ($SERVICE_CODE == "1100" || $SERVICE_CODE == "1200") ) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>����Ÿ��</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_TYPE?></b></td>
					</tr>
<?php
	}
	//ĳ�ð���Ʈ(0700), �ſ�ī��(0900) �ŷ���ȣ ��� ����
	if( !($SERVICE_CODE == "0700" || $SERVICE_CODE == "0900") ) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ŷ���ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//�������(1800) ä������
	if($SERVICE_CODE == "1800" && $REQ_RESPONSE_CODE == "0000"){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>������¹�ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ACCOUNT_NUMBER?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ݾ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $AMOUNT?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $BANK_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ŷ�����</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $MIX_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>������</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $EXPIRE_DATE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�Աݸ����ð�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $EXPIRE_TIME?></b></td>
					</tr>
<?php
	}
	//ƾĳ��(2500) ��������
	if($SERVICE_CODE == "2500") {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>��������</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $CONF_TYPE?></b></td>
					</tr>
<?php
	}
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>����޽���</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�������ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>������޽���</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $REQ_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���񺯼�1</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED1?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���񺯼�2</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED2?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���񺯼�3</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RESERVED3?></b></td>
					</tr>
					<!--������� ��-->

					<!--���ΰ�� ����-->

					<tr>
						<td><b>���ΰ��</b></td>
					</tr>
<?php
	if ($RES_RESPONSE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ŷ���ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_TRANSACTION_ID?></b></td>
					</tr>		
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����Ͻ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_DATE?></b></td>
					</tr>
<?php
		//[ĳ�ð���Ʈ] �� ���, �����ݾ��� dealAmount�� ǥ��
		if("0700" == $SERVICE_CODE){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���αݾ�<b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DEAL_AMOUNT?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ܾ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_BALANCE?></b></td>
					</tr>
<?php
		}else{ 
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���αݾ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_AMOUNT?></b></td>
					</tr>
<?php
		}
		//[�ſ�ī��] ���� �ݾ� �׸� �߰�
		if("0900" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�Һΰ�����</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_QUOTA?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�߱޻��ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CARD_COMPANY_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>ī���ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PIN_NUMBER?></b></td>
					</tr>
<?php
		}
		//�ſ�ī��(0900), ������ü(0100), ��ȭ��ǰ��(0200), ���ӹ�ȭ��ǰ��(0300), ���ǸӴϻ�ǰ��(0500), ƾĳ��(2500), ���׸Ӵ�(2600), ���� ���� �Ķ���� �߰�
		if("0900" == $SERVICE_CODE || "0100" == $SERVICE_CODE || "0200" == $SERVICE_CODE || "0300" == $SERVICE_CODE || "0500" == $SERVICE_CODE || "2500" == $SERVICE_CODE || "2600" == $SERVICE_CODE){
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���ι�ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_AUTH_NUMBER?></b></td>
					</tr>
<?php
		}
		//[������ü]�� ���, ���� �Ķ���� �߰�
		if("1000" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�ŷ�����</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_MIX_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���ݿ����� �뵵</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_USING_TYPE?></b></td>
					</tr>	
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���ݿ����� ���ι�ȣ</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_IDENTIFIER?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���ݿ����� �����߱�������</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_IDENTIFIER_TYPE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>���� �ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_INPUT_BANK_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_INPUT_ACCOUNT_NAME?></b></td>
					</tr>
<?php
		}
		if("1100" == $SERVICE_CODE) {
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�κ����Ÿ��</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PART_CANCEL_TYPE?></b></td>
					</tr>	
<?php
		}
?>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>����޽���</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>�������ڵ�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="100" align="left" bgcolor="#F6F6F6"><b>������޽���</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
<?php
	}else{
?>
					<tr>
						<td width="300" align="center" bgcolor="#F6F6F6" colspan="2"><b>���� ��� ����</b></td>
					</tr>	
<?php
	}
?>
					<!--���ΰ�� ��-->
				</table>
			</td>
		</tr>
	</table>
	</div>
	<br>
</body>

</html>