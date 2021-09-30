<?php

	require_once 'DBSignInMyArts.php';
	
	class FunctionsSignIn {
		private $db;
		
		public function __construct() {
			$this -> db = new DBSignIn();
		}
			
			
		
		public function getPolicyPages() {
			
			$response["result"] = "success";
			$response["message"] = "Registered Successfully!";
			$response["privacyPolicyUrl"] = "https://bukovskiy.kl.com.ua/MyArts/docs/privacy-policy.html";
			$response["termsAndConditionsUrl"] = "https://bukovskiy.kl.com.ua/MyArts/docs/terms-and-conditions.html";
			return json_encode($response);
		}
		
		
		public function checkUserExist($userEmail) {
			$db = $this -> db;
			
			if ($db -> checkUserExist($userEmail)) {
				return true;
			} else {
				return false;	
			}
		}
		
		public function registerUser($user) {
			$db = $this -> db;
			
			$userImageUrl = $user -> userImageUrl;
			
			if (!empty($userImageUrl)){
				$imagePath = "ImagesUsers/".$user -> userUniqueId.".jpg";
				$result_uploadImg =file_put_contents($imagePath, file_get_contents($userImageUrl));
				if($result_uploadImg > 0) {
					$hasImage = 1;
				} else {
					$hasImage = 0;
				}
			} else {
				$hasImage = 0;
			}
			
			$result = $db -> insertDataUser($user, $hasImage);
			
			if ($result) {
				
				$user = $db -> getUser($user -> userEmail);
				if(!$user){
					$response["result"] = "failure";
					$response["message"] = "Invaild Register Credentials";
					return json_encode($response);
				}else {
					$response["result"] = "success";
					$response["message"] = "Registered Successfully!";
					$response["user"] = $user;
					return json_encode($response);
				}
				
			} else {
				$response["result"] = "failure";
				$response["message"] = "Registration Failure";
				return json_encode($response);
			}
				
		}
		
		
		public function loginUser($user) {
			$db = $this -> db;

			$user =  $db -> getUser($user -> userEmail);

			if(!$user) {
				$response["result"] = "failure";
				$response["message"] = "Invaild Login Credentials";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "Login Sucessful";
				$response["user"] = $user;
				return json_encode($response);
			}
					
		}
		
		
		public function deleteUser($userUniqueId) {
			$db = $this -> db;

			$result =  $db -> deleteUser($userUniqueId);

			if(!$result) {
				$response["result"] = "failure";
				$response["message"] = "User Delete Failure";
				return json_encode($response);
			} else {
				
				unlink("ImagesUsers/".$userUniqueId.".jpg");
				
				$response["result"] = "success";
				$response["message"] = "User deleted";
				return json_encode($response);
			}
		}
		
		
		public function editUser($user) {
			$db = $this -> db;
			
			$userImageBase64 = $user -> userImageBase64;
			$userGivenName = $user -> userGivenName;
			$userFamilyName = $user -> userFamilyName;
			$userUniqueId = $user -> userUniqueId;
			$userEmail = $user -> userEmail;
			
			if (empty($userImageBase64)) {
				
				$result_uploadImg = unlink("ImagesUsers/".$userUniqueId.".jpg");
				$hasImage = 0;
				
			} else if ($userImageBase64 == "previous") {
				
				$result_uploadImg = 1;
				$hasImage = 1;
				
			} else {
				
				$imagePath = "ImagesUsers/".$userUniqueId.".jpg";
				$result_uploadImg = file_put_contents($imagePath, base64_decode($userImageBase64));
				$hasImage = 1;
			}
			
			if($result_uploadImg > 0) {
				
				$result = $db -> editUser($userGivenName, $userFamilyName, $userUniqueId, $hasImage);
				if ($result) {
					
					$user =  $db -> getUser($userEmail);
					
					if(!$user) {
						$response["result"] = "failure";
						$response["message"] = "Edit Failure";
						return json_encode($response);
					} else {
						$response["result"] = "success";
						$response["message"] = "Account edited Successfuly !";
						$response["user"] = $user;
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "Edit Failure";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "Edit Failure";
				return json_encode($response);
			}
			
					
		}






		

	}

?>