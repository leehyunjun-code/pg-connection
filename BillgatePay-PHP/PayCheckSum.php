<?php
	/*
	------------------------------------------------------------------------------------- 
	�ش� �������� ������Ʈ ���� �׽�Ʈ�� ���� "üũ�� ���� "������ �Դϴ�.
	------------------------------------------------------------------------------------- 
	*/	
?>
<?php
	@extract($_REQUEST);
	header('Content-Type: text/html; charset=euc-kr');
	//---------------------------------------
	// API Ŭ���� Include
	//---------------------------------------
	require_once('billgateClass/Util.php');
?>
<?php
	$checkSumUtil = new ChecksumUtil(); //üũ�� Class ����
	
	$checksum = $checkSumUtil->genCheckSum($CheckSum);

	echo $checksum
?>