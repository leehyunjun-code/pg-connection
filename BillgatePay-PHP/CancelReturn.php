<?php
/*-------------------------------------------------------------------------------------
 �ش� �������� ������Ʈ ���� �׽�Ʈ�� ���� "��Ұ��" ������ �Դϴ�.
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
?>
<?php
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
	$reqMsg->setVersion("0100");	//���� (0100)
	$reqMsg->setMerchantId($SERVICE_ID); //������ ���̵�
	$reqMsg->setServiceCode($SERVICE_CODE); //�����ڵ�
	$reqMsg->setOrderId($ORDER_ID); //�ֹ���ȣ
	$reqMsg->setOrderDate($ORDER_DATE); //�ֹ��Ͻ�(YYYYMMDDHHMMSS)
	$reqMsg->setCipher($bgCipher); //��ȣȭ ����

	//�޴���
	if($SERVICE_CODE == "1100") {
		$reqMsg->setCommand("9000"); 

		//Set Body 
		if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
			$reqMsg->put("1001", $TRANSACTION_ID); //�ŷ���ȣ(1001)

		//�κ� ����� ���
		if($CANCEL_TYPE == "0000") {

			//��ұݾ�(*����* �κ���� �� �ݾ� ���� ��� ��ü ���)
			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("7043", $CANCEL_AMOUNT); //�κ���ұݾ�(7043)
		}
	//������ü
	}else if($SERVICE_CODE == "1000") {
		//�κ� ����� ���
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$reqMsg->setCommand("9300"); //������ü �κ���� Command

			//Set Body 
			if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
				$reqMsg->put("1012", $TRANSACTION_ID); //�ŷ���ȣ(1012)

			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("1033", $CANCEL_AMOUNT); //��ұݾ�(1033)
			
			if($CANCEL_TYPE != NULL || $CANCEL_TYPE != "")
				$reqMsg->put("0015", $CANCEL_TYPE); //��ұ���(0015) [0000:�κ����, 1000:������ ��ü ���]

		//��ü ����� ���
		}else{
			$reqMsg->setCommand("9000"); //������ü ��ü��� Command

			//Set Body 
			if($TRANSACTION_ID != NULL || $TRANSACTION_ID != "")
				$reqMsg->put("1001", $TRANSACTION_ID); //�ŷ���ȣ(1001)
		}

	//�ſ�ī��
	}else if($SERVICE_CODE == "0900") {
		//�κ� ����� ���
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$reqMsg->setCommand("9010"); //�ſ�ī�� �κ���� Command

			//Set Body 
			if($TRANSACTION_ID != NULL)
				$reqMsg->put("1001", $TRANSACTION_ID); //�ŷ���ȣ(1001)
			
			if($CANCEL_AMOUNT != NULL || $CANCEL_AMOUNT != "")
				$reqMsg->put("0012", $CANCEL_AMOUNT); //��ұݾ�(0012) [��ұ����� 1000 �� ��� �ڵ������]

			if($CANCEL_TYPE != NULL || $CANCEL_TYPE != "")
				$reqMsg->put("0082", $CANCEL_TYPE); //��ұ���(0082) [0000:�κ����, 1000:������ ��ü ���]
		//��ü ����� ���
		}else{
			$reqMsg->setCommand("9200"); //�ſ�ī�� ��ü��� Command

			//Set Body 
			if($TRANSACTION_ID != NULL)
				$reqMsg->put("1001", $TRANSACTION_ID); //�ŷ���ȣ(1001)
		}

	}else{
		$reqMsg->setCommand("9000"); //�� �� �������� ���� ��� ��û Command

		//Set Body 
		if($TRANSACTION_ID != NULL)
			$reqMsg->put("1001", $TRANSACTION_ID); //�ŷ���ȣ(1001)
	}


	//Request
	$resMsg = $broker->invoke($reqMsg); //��û �� ����

	//��� ����
	$RES_RESPONSE_CODE = $resMsg->get("1002"); //�����ڵ�(1002)
	$RES_RESPONSE_MESSAGE = $resMsg->get("1003"); //����޽���(1003)
	$RES_DETAIL_RESPONSE_CODE = $resMsg->get("1009"); //�������ڵ�(1009)
	$RES_DETAIL_RESPONSE_MESSAGE = $resMsg->get("1010"); //������޽���(1010)

	
	//�ſ�ī��
	if($SERVICE_CODE == "0900") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)

		//�κ� ����� ���
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$RES_CANCEL_AMOUNT = $resMsg->get("0012"); //�κ���ұݾ� (0012)
			$RES_PARTCANCEL_SEQUENCE = $resMsg->get("5049"); //�κ���� ������(5049)
		//�Ϲ� ����� ���
		}else{
			$RES_CANCEL_AMOUNT = $resMsg->get("1033"); //��ұݾ�(1033)
		}
	//�޴���
	}else if($SERVICE_CODE == "1100") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
		$RES_PART_CANCEL_TYPE = $resMsg->get("7049"); //�κ����Ÿ��(7049)

		//�κ� ����� ���
		if($CANCEL_TYPE == "0000") {
			$RES_CANCEL_AMOUNT = $resMsg->get("7043"); //�κ���ұݾ�(7043)
			$RES_CANCEL_TRANSACTION_ID = $resMsg->get("1032"); //��Ұŷ���ȣ(1032)
			$RES_REAUTH_OLD_TRANSACTION_ID = $resMsg->get("1040"); //����� �����ŷ���ȣ(1040)
			$RES_REAUTH_NEW_TRANSACTION_ID = $resMsg->get("1041"); //����� �ű԰ŷ���ȣ(1041)
		}else{
			$RES_CANCEL_AMOUNT = $resMsg->get("1007"); //��ұݾ�(1007)
		}
	//������ü
	}else if($SERVICE_CODE == "1000") {
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
		$RES_CANCEL_AMOUNT = $resMsg->get("1033"); //��ұݾ�(1033) [��ü���, �κ���� �ݾ�]

		//�κ� ����� ���
		if($CANCEL_TYPE == "0000" || $CANCEL_TYPE == "1000") {
			$RES_TRANSACTION_ID = $resMsg->get("1012"); //�ŷ���ȣ(1012)
			$RES_PARTCANCEL_SEQUENCE = $resMsg->get("0096"); //�κ���� ������(0096)
		}
	//Ƽ�Ӵ�, ĳ�ð���Ʈ
	}else if($SERVICE_CODE == "1600" || $SERVICE_CODE == "0700") {
		$RES_CANCEL_AMOUNT = $resMsg->get("0012"); //��ұݾ�(0012)
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
	}else{
		//0100(������ǰ��), 0200(��ȭ��ǰ��), 2600(���׸Ӵ�), 0300(���ӹ�ȭ��ǰ��), 0500(���ǸӴ�), 4100(��������Ʈ), 1200(����), 2500(ƾĳ��)
		$RES_TRANSACTION_ID = $resMsg->get("1001"); //�ŷ���ȣ(1001)
	}
?>
<html>
<head>
<style>
	body, tr, td {font-size:9pt; font-family:�������,verdana; }
	div {width: 98%; height:100%; overflow-y: auto; overflow-x:hidden;}
</style>
<meta charset="EUC-KR">
<title>������Ʈ ���� �׽�Ʈ ����������</title>
</head>
<body>
	<div>
	<table border="0" cellpadding="0"	cellspacing="0">
		<tr> 
	 		 <td height="25" style="padding-left:10px" class="title01"># ������ġ &gt;&gt; <b>������ ��� ������</b></td>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="center"><!--�������̺� ����--->
				<table border="0" cellpadding="4" cellspacing="1" bgcolor="#B0B0B0">
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>������ ���̵�</b></td>
						<td width="200" align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_ID?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $SERVICE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�ֹ���ȣ</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_ID?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�ֹ��Ͻ�</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $ORDER_DATE?></b></td>
					</tr>			
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�ŷ���ȣ</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_TRANSACTION_ID?></b></td>
					</tr>
<?php
	//�ſ�ī��(��ü/�κ����), �޴���(��ü/�κ����), ������ü(��ü/�κ����)
	if($RES_CANCEL_AMOUNT) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>��ұݾ�</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CANCEL_AMOUNT?></b></td>
					</tr>
<?php
	}
	//�ſ�ī��, ������ü (�κ����)
	if($$RES_PARTCANCEL_SEQUENCE) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�κ���� ������</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PARTCANCEL_SEQUENCE_NUMBER?></b></td>
					</tr>
<?php
	}
	//�޴���
	if($RES_PART_CANCEL_TYPE) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�κ���� Ÿ��</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_PART_CANCEL_TYPE?></b></td>
					</tr>
<?php
	}
	//�޴���
	if($RES_CANCEL_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>��� �ŷ���ȣ</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_CANCEL_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//�޴���
	if($RES_REAUTH_OLD_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>����� �����ŷ���ȣ</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_REAUTH_OLD_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
	//�޴���
	if($RES_REAUTH_NEW_TRANSACTION_ID) {
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>����� �ű԰ŷ���ȣ</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_REAUTH_NEW_TRANSACTION_ID?></b></td>
					</tr>
<?php
	}
?>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�����ڵ�</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>����޽���</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_RESPONSE_MESSAGE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>�������ڵ�</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_CODE?></b></td>
					</tr>
					<tr>
						<td width="150" align="left" bgcolor="#F6F6F6"><b>������޽���</b></td>
						<td align="left" bgcolor="#FFFFFF">&nbsp;<b><?php echo $RES_DETAIL_RESPONSE_MESSAGE?></b></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</div>
</body>
</html>