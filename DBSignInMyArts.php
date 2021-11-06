<?php

	require_once 'DBConfig.php';
	
	class DBSignIn extends DBConfig {
		protected $host;
		protected $user;
		protected $db;
		protected $pass;
		protected $conn;
		
		
		public function __construct() {

			$dbConfig = new DBConfig();
	        $this -> host = $dbConfig -> host;
	        $this -> user = $dbConfig -> user;
	        $this -> db = $dbConfig -> db;
	        $this -> pass = $dbConfig -> pass;
	        $dbConfig = NULL;
			
			$this -> conn = new PDO("mysql:host=".$this -> host.";dbname=".$this -> db, $this -> user, $this -> pass);
		}
		

		
		public function checkUserExist($userEmail) {

			$sql = 'SELECT COUNT(*) from MyArtsUsers WHERE userEmail =:userEmail';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array('userEmail' => $userEmail));

			if($query){
				$rowCount = $query -> fetchColumn();
				if ($rowCount == 0){
					return false;
				} else {
					return true;
				}
			} else {
				return false;
			}
		}
		
		
		public function insertDataUser($user, $hasImage) {
			
			$userUniqueId = $user -> userUniqueId;
			$userDisplayName = $user -> userDisplayName;
			$userGivenName = $user -> userGivenName;
			$userFamilyName = $user -> userFamilyName;
			$userEmail = $user -> userEmail;
			$userProviderId = $user -> userProviderId;
			$userAccountProvider = $user -> userAccountProvider;
			//$userImageUrl = $user -> userImageUrl;
			
			$sql= "INSERT MyArtsUsers (userUniqueId, userDisplayName, userGivenName, userFamilyName, userEmail, userProviderId, userAccountProvider, hasImage)
							VALUES (:userUniqueId, :userDisplayName, :userGivenName, :userFamilyName, :userEmail, :userProviderId, :userAccountProvider, :hasImage)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId, ':userDisplayName' => $userDisplayName, ':userGivenName' => $userGivenName, 
									':userFamilyName' => $userFamilyName, ':userEmail' => $userEmail, ':userProviderId' => $userProviderId, 
									':userAccountProvider' => $userAccountProvider, ':hasImage' => $hasImage));
						
			if ($query) {
				return true;
			} else {
				return false;
			}	
		}
		
		
		public function editUser($userGivenName, $userFamilyName, $userUniqueId, $hasImage) {
			
			$sql = 'UPDATE MyArtsUsers SET userGivenName = :userGivenName, userFamilyName = :userFamilyName, hasImage = :hasImage
					WHERE userUniqueId = :userUniqueId';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userGivenName' => $userGivenName, ':userFamilyName' => $userFamilyName, ':hasImage' => $hasImage, ':userUniqueId' => $userUniqueId));
			
			if ($query == 1) {
				return true;
			} else {
				return false;
			}
		}
		
		
		public function getUser($userEmail) {
			
			$sql = 'SELECT * FROM MyArtsUsers WHERE userEmail = :userEmail';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userEmail' => $userEmail));
			$data = $query -> fetchObject();
			
			if (!$data) {
				return false;
			} else {
				$user["userUniqueId"] = $data -> userUniqueId;
				$user["userDisplayName"] = $data -> userDisplayName;
				$user["userGivenName"] = $data -> userGivenName;
				$user["userFamilyName"] = $data -> userFamilyName;
				$user["userEmail"] = $data -> userEmail;
				$user["userProviderId"] = $data -> userProviderId;
				$user["userAccountProvider"] = $data -> userAccountProvider;
					if($data->hasImage) {
						$userImageUrl = "https://bukovskiy.kl.com.ua/MyArts/ImagesUsers/".$data->userUniqueId.".jpg";
					} else {
						$userImageUrl = "";
					}
				$user["userImageUrl"] = $userImageUrl;
				return $user;
			}		
			
		}
		
		
		public function deleteUser($userUniqueId){
			
			$sql= "DELETE FROM MyArtsUsers WHERE userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if ($count > 0) {
				return true;
			} else {
				return false;
			}
		}
		
		
		
		
		



		
	}

?>