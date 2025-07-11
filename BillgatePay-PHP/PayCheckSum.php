<?php
	/*
	------------------------------------------------------------------------------------- 
	해당 페이지는 빌게이트 결제 테스트를 위한 "체크썸 생성 "페이지 입니다.
	------------------------------------------------------------------------------------- 
	*/	
?>
<?php
	@extract($_REQUEST);
	header('Content-Type: text/html; charset=euc-kr');
	//---------------------------------------
	// API 클래스 Include
	//---------------------------------------
	require_once('billgateClass/Util.php');
?>
<?php
	$checkSumUtil = new ChecksumUtil(); //체크섬 Class 생성
	
	$checksum = $checkSumUtil->genCheckSum($CheckSum);

	echo $checksum
?>