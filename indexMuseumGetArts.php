<?php

	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);

	require_once 'FunctionsMyArts.php'; //require_once '../../FunctionsMyArts.php';

	$fun = new Functions();
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		$data = json_decode(file_get_contents("php://input"));


		$fun -> insertLogs($data -> userUniqueId, $data -> operation);

		if(isset($data -> operation)) {
			$operation = $data -> operation;

			if(!empty($operation)) {

				if ($operation == 'getArtsListMuseum') {
					if(isset($data -> oldList) && isset($data -> artProviderId) && !empty($data -> artProviderId) 
						&& isset($data -> pageNumber) && !empty($data -> pageNumber) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$pageNumber = $data -> pageNumber;
						$userUniqueId = $data -> userUniqueId;
						$artProviderId = $data -> artProviderId;
						$oldList = $data -> oldList;
						
						echo $fun -> getArtsListMuseum($pageNumber, $userUniqueId, $artProviderId, $oldList);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else {
					echo $fun -> getMsgInvalidParam();
				}
				
			} else {
				echo $fun -> getMsgParamNotEmpty();
			}
			
		} else {
			echo $fun -> getMsgInvalidParam();
		}
		
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		
	}


?>