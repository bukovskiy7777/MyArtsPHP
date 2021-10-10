<?php
	
	class DBOperations {
		private $host = "localhost";
		private $user = "bukovskiy";
		private $db = "bukovskiy_1";
		private $pass = "Amn532fx330";
		private $conn;
		
		
		public function __construct(){
			
			$this -> conn = new PDO("mysql:host=".$this -> host.";dbname=".$this -> db, $this -> user, $this -> pass);
		}
		

		public function getArtsList($count, $userUniqueId, $classification) {

			$listSno1 = array();
			$sql = "SELECT DISTINCT(sno) FROM ArtWorks where objectClassification = :classification and providerId = 5 ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':classification' => $classification));
			while ($sno = $query -> fetchColumn()) {
				$listSno1[] = $sno;
			}
			$listSno2 = array();
			$sql = "SELECT DISTINCT(sno) FROM ArtWorks where objectClassification = :classification and providerId <> 5 and providerDepartment <> 'Asian Art' ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':classification' => $classification));
			while ($sno = $query -> fetchColumn()) {
				$listSno2[] = $sno;
			}
			
			$listArts = array();
			$sqlList = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId					
						where ArtWorks.sno = :randomInt";
			$queryList = $this -> conn -> prepare($sqlList);
			for($i = 0; $i < $count/6; $i++) {
				$random = mt_rand(1, count($listSno1));
				$randomInt = $listSno1 [$random];
				$queryList -> execute(array(':userUniqueId' => $userUniqueId, ':randomInt' => $randomInt));

				if ($art = $queryList -> fetchObject()) {
					$listArts[] = $art;
				}
			}
			for($i = 0; $i < $count *5/6; $i++) {
				$random = mt_rand(1, count($listSno2));
				$randomInt = $listSno2 [$random];
				$queryList -> execute(array(':userUniqueId' => $userUniqueId, ':randomInt' => $randomInt));

				if ($art = $queryList -> fetchObject()) {
					$listArts[] = $art;
				}
			}

			return $listArts;
		}
		
		public function getArtsListMuseum($count, $userUniqueId, $classification, $museumId) {

			$listSno = array();
			$sql = "SELECT DISTINCT(sno) FROM ArtWorks where objectClassification = :classification and providerID = :museumId and providerDepartment <> 'Asian Art' limit 20000";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':classification' => $classification, ':museumId' => $museumId));
			while ($sno = $query -> fetchColumn()) {
				$listSno[] = $sno;
			}
			
			$sqlList = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId					
						where ArtWorks.sno = :randomInt";
			$queryList = $this -> conn -> prepare($sqlList);

			$listArts = array();
			for($i = 0; $i < $count; $i++) {
				$random = mt_rand(1, count($listSno));
				$randomInt = $listSno [$random];
				$queryList -> execute(array(':userUniqueId' => $userUniqueId, ':randomInt' => $randomInt));

				if ($art = $queryList -> fetchObject()) {
					$listArts[] = $art;
				}
			}

			return $listArts;
		}

		public function getMuseumInfo($artProviderId) {
			
			$sql = "SELECT * FROM MyArtsProviders where providerId = :artProviderId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId));
			
			$artProvider = new stdClass();
			$artProvider = $query -> fetchObject();
			
			return $artProvider;
		}


		public function insertLogs($operation, $userUniqueId) {
			
			$sql = "INSERT MyArtsLogs (operation, userUniqueId) VALUES (:operation, :userUniqueId)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':operation' => $operation, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}

		}

		
		public function getListMakersByMuseum($artProviderId) {
			
			$listMakers = array();
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			$classification = 'Paintings';
			
			$sql = "SELECT DISTINCT artistName FROM ArtWorks WHERE artistName NOT LIKE :makerName1
						AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5
						AND artistName <>'' AND providerId = :artProviderId AND objectClassification = :classification limit 10000";
			
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, 
									':makerName4' => $makerName4, ':makerName5' => $makerName5, ':classification' => $classification, ':artProviderId' => $artProviderId));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
			
		}

		public function getListFavoritesMakersByMuseum($artProviderId) {
			
			$listMakers = array();
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$sql = "SELECT DISTINCT artMaker FROM MyArtsLikesMakers WHERE UPPER(artMaker) NOT LIKE UPPER(:makerName1) 
						AND UPPER(artMaker) NOT LIKE UPPER(:makerName2) AND UPPER(artMaker) NOT LIKE UPPER(:makerName3) 
						AND UPPER(artMaker) NOT LIKE UPPER(:makerName4) AND UPPER(artMaker) NOT LIKE UPPER(:makerName5) 
						AND artHeaderProviderId = :artProviderId";
			
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, 
									':makerName4' => $makerName4, ':makerName5' => $makerName5, ':artProviderId' => $artProviderId));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
			
		}

		public function isMakerExist($artMaker) {

			$sql = 'SELECT COUNT(*) FROM MyArtsMakersWikiData WHERE artMaker =:artMaker';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array('artMaker' => $artMaker));

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
		
		public function insertMakerWikiData($maker) {
			
			$artMaker = $maker -> artMaker;
			$makerWikiImageUrl = $maker -> makerWikiImageUrl;
			$makerWikiDescription = $maker -> makerWikiDescription;
			$makerWikiPageUrl = $maker -> makerWikiPageUrl;
			
			$sql = "INSERT MyArtsMakersWikiData (artMaker, makerWikiImageUrl, makerWikiDescription, makerWikiPageUrl) 
											VALUES (:artMaker, :makerWikiImageUrl, :makerWikiDescription, :makerWikiPageUrl)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artMaker' => $artMaker, ':makerWikiImageUrl' => $makerWikiImageUrl,
									':makerWikiDescription' => $makerWikiDescription, ':makerWikiPageUrl' => $makerWikiPageUrl));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}
		
		public function getMakerWikiDataLocal($maker) {
			
			$artMaker = $maker -> artMaker;
			
			$sql = "SELECT * FROM MyArtsMakersWikiData where artMaker = :artMaker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artMaker' => $artMaker));
			
			$localWikiData = new stdClass();
			$localWikiData = $query -> fetchObject();
			
			$maker -> makerWikiDescription = $localWikiData -> makerWikiDescription;
			$maker -> makerWikiImageUrl = $localWikiData -> makerWikiImageUrl;
			$maker -> makerWikiPageUrl = $localWikiData -> makerWikiPageUrl;
			
			return $maker;
		}






		public function isLiked($artProviderId, $artId, $userUniqueId, $artObjectType) {
			$sql = "SELECT COUNT(*) FROM MyArtsLikes 
					WHERE artProviderId = :artProviderId and artId = :artId and userUniqueId = :userUniqueId and artObjectType = :artObjectType";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId, ':userUniqueId' => $userUniqueId, ':artObjectType' => $artObjectType));
			
			if($query-> fetchColumn()==0){
				return false;
			} else {
				return true;
			}
		}

		public function like($artProviderId, $artId, $userUniqueId, $artObjectType) {
			$sql = "INSERT MyArtsLikes (artProviderId, artId, userUniqueId, artObjectType) 
									VALUES (:artProviderId, :artId, :userUniqueId, :artObjectType)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId, ':userUniqueId' => $userUniqueId, ':artObjectType' => $artObjectType));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function unLike($artProviderId, $artId, $userUniqueId, $artObjectType) {
			$sql = "DELETE FROM MyArtsLikes 
					WHERE artProviderId = :artProviderId and artId = :artId and userUniqueId = :userUniqueId and artObjectType = :artObjectType";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId, ':userUniqueId' => $userUniqueId, ':artObjectType' => $artObjectType));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function isMakerLiked($artMaker, $userUniqueId) {
			$sql = "SELECT COUNT(*) FROM MyArtsLikesMakers WHERE artMaker = :artMaker and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artMaker' => $artMaker, ':userUniqueId' => $userUniqueId));
			
			if($query-> fetchColumn()==0){
				return false;
			} else {
				return true;
			}
		}

		public function likeMaker($artMaker, $artistBio, $makerWikiImageUrl, $userUniqueId, $artHeaderImageUrl, $artWidth, $artHeight, $artHeaderId, $artHeaderProviderId) {
			$sql = "INSERT MyArtsLikesMakers (artMaker, artistBio, userUniqueId, makerWikiImageUrl, artHeaderImageUrl, artWidth, artHeight, artHeaderId, artHeaderProviderId) 
									VALUES (:artMaker, :artistBio, :userUniqueId, :makerWikiImageUrl, :artHeaderImageUrl, :artWidth, :artHeight, :artHeaderId, :artHeaderProviderId)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artMaker' => $artMaker, ':artistBio' => $artistBio, ':userUniqueId' => $userUniqueId, ':makerWikiImageUrl' => $makerWikiImageUrl,
									':artHeaderImageUrl' => $artHeaderImageUrl, ':artWidth' => $artWidth, ':artHeight' => $artHeight, ':artHeaderId' => $artHeaderId, ':artHeaderProviderId' => $artHeaderProviderId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function unLikeMaker($artMaker, $userUniqueId) {
			$sql = "DELETE FROM MyArtsLikesMakers WHERE artMaker = :artMaker and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artMaker' => $artMaker, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function getMyFavorites($userUniqueId) {

			$sqlFav = "SELECT MyArtsLikes.*, ArtWorks.*, MyArtsProviders.*,
						(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
						AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
							FROM MyArtsLikes
							JOIN ArtWorks ON artId = objectID AND artProviderId = ArtWorks.providerID
							JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerID
							WHERE userUniqueId = :userUniqueId ORDER BY MyArtsLikes.sno DESC";
			$queryFav = $this -> conn -> prepare($sqlFav);
			$queryFav -> execute(array(':userUniqueId' => $userUniqueId));
			
			$listFavorites = array();
			while ($favorite = $queryFav -> fetchObject()) {

				$listFavorites[] = $favorite;
			}
			
			return $listFavorites;
		}
		
		public function getMyFavoritesArtists($userUniqueId) {

			$sqlFav = "SELECT * FROM MyArtsLikesMakers WHERE userUniqueId = :userUniqueId ORDER BY sno DESC";
			$queryFav = $this -> conn -> prepare($sqlFav);
			$queryFav -> execute(array(':userUniqueId' => $userUniqueId));
			
			$listFavorites = array();
			while ($favorite = $queryFav -> fetchObject()) {

				$listFavorites[] = $favorite;
			}
			
			return $listFavorites;
		}
		
		public function isMuseumLiked($providerId, $userUniqueId) {
			$sql = "SELECT COUNT(*) FROM MyArtsLikesMuseums WHERE artProviderId = :providerId and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':providerId' => $providerId, ':userUniqueId' => $userUniqueId));
			
			if($query-> fetchColumn()==0){
				return false;
			} else {
				return true;
			}
		}
		
		public function likeMuseum($providerId, $userUniqueId) {
			$sql = "INSERT MyArtsLikesMuseums (artProviderId, userUniqueId) VALUES (:providerId, :userUniqueId)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':providerId' => $providerId, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function unLikeMuseum($providerId, $userUniqueId) {
			$sql = "DELETE FROM MyArtsLikesMuseums WHERE artProviderId = :providerId and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':providerId' => $providerId, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		
		
		
		
		public function getSingleArt($artId, $userUniqueId, $artProviderId) {
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId					
						where ArtWorks.objectID = :artId and ArtWorks.providerId = :artProviderId ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artId' => $artId, ':artProviderId' => $artProviderId, ':userUniqueId' => $userUniqueId));
			$object = $query -> fetchObject();
			
			return $object;
		}
		



		
		public function artWriteDimens($artId, $artProviderId, $width, $height) {
			
			$sql = "SELECT COUNT(*) FROM MyArtsDimentions WHERE artProviderId = :artProviderId and artId = :artId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId));
			
			if($query-> fetchColumn()==0){
				$sql = "INSERT MyArtsDimentions (artId, artProviderId, width, height) VALUES (:artId, :artProviderId, :width, :height)";
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':artId' => $artId, ':artProviderId' => $artProviderId, ':width' => $width, ':height' => $height));
				$count = $query -> rowCount();
			}
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
			
		}
		
		public function getArtDimens ($artProviderId, $artId) {
			
			$sql = "SELECT COUNT(*) FROM MyArtsDimentions WHERE artProviderId = :artProviderId and artId = :artId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId));
			
			if($query-> fetchColumn()==0){
				
				$artDimens = new stdClass();
				$artDimens -> width = "0";
				$artDimens -> height = "0";
				
				return $artDimens;
			} else {
				
				$sql = "SELECT * FROM MyArtsDimentions WHERE artProviderId = :artProviderId and artId = :artId";
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':artProviderId' => $artProviderId, ':artId' => $artId));
				$object = $query -> fetchObject();
				
				$artDimens = new stdClass();
				$artDimens -> width = $object -> width;
				$artDimens -> height = $object -> height;
				
				return $artDimens;
			}
			
		}


		
		
		public function getArtsListSearch($searchQuery, $userUniqueId, $count, $offset) {
			
			$listArts = array();
			
			//$str = str_replace(" ", "%", '%'.$searchQuery.'%');
			$str = '%'.$searchQuery.'%';
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where artistName like :str or artistCulture like :str or artistPlaceOfOrigin like :str or objectMediums like :str
							or objectTitle like :str or objectTags like :str LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
				
			return $listArts;
		}
		
		public function isSearchQueryInTable($searchQuery, $userUniqueId) {
			$sql = "SELECT COUNT(*) FROM MyArtsSearchQuery WHERE searchQuery = :searchQuery and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':searchQuery' => $searchQuery, ':userUniqueId' => $userUniqueId));
			
			if($query-> fetchColumn()==0){
				return false;
			} else {
				return true;
			}
		}
		
		public function insertSearchQuery($searchQuery, $userUniqueId) {
			$sql = "INSERT MyArtsSearchQuery (searchQuery, userUniqueId) VALUES (:searchQuery, :userUniqueId)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':searchQuery' => $searchQuery, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}
		
		public function updateSearchQuery($searchQuery, $userUniqueId){
			
			$now = date_create()->format('Y-m-d H:i:s');
			
			$sql = 'UPDATE MyArtsSearchQuery SET createdAt = :now WHERE userUniqueId = :userUniqueId AND searchQuery = :searchQuery';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId, ':searchQuery' => $searchQuery, ':now' => $now));
			
			if ($query == 1) {
				return true;
			} else {
				return false;
			}
		}

		
		public function getListSuggest($suggestQuery) {
			
			$listSuggest = array();
			
			$str = '%'.$suggestQuery.'%';
			
			$sql = "SELECT artistName, artistCulture, artistPlaceOfOrigin, objectMediums, objectTitle, objectTags FROM ArtWorks where
					artistName like :str or artistCulture like :str or artistPlaceOfOrigin like :str or objectMediums like :str
					or objectTitle like :str or objectTags like :str LIMIT 2000";
			$queryList = $this -> conn -> prepare($sql);
			$queryList -> execute(array(':str' => $str));
			
			while ($obj = $queryList -> fetchObject()) {
				
				$artistName = $obj -> artistName;
				$artistCulture = $obj -> artistCulture;
				
				if (stripos($artistName, $suggestQuery) !== false) {
					
					$add = true;
					for ($i = 0; $i < count($listSuggest); $i++) {
						if (strtoupper($listSuggest[$i]->suggestStr) == strtoupper($artistName)) {
							$add = false;
							$listSuggest[$i]->countArts = $listSuggest[$i]->countArts + 1;
						}
					}
					if ($add) {
						$suggest = new stdClass();
						$suggest -> suggestStr = $artistName;
						$suggest -> countArts = 1;
						$listSuggest[] = $suggest;
					}
				} else if (stripos($artistCulture, $suggestQuery) !== false) {
					
					$artistCulture = preg_replace('/[^a-zA-Zа-яА-Я0-9-,]/', ' ',$artistCulture);			// оставляем буквы, цифры, дефисы, запятые
					if(strripos($artistCulture, " ") == strlen($artistCulture)-1) {							//если последний символ - пробел, то убираем его
						$artistCulture = substr($artistCulture , 0, strlen($artistCulture)-1);
					}
					$add = true;
					for ($i = 0; $i < count($listSuggest); $i++) {
						if (strtoupper($listSuggest[$i]->suggestStr) == strtoupper($artistCulture)) {
							$add = false;
							$listSuggest[$i]->countArts = $listSuggest[$i]->countArts + 1;
						}
					}
					if ($add) {
						$suggest = new stdClass();
						$suggest -> suggestStr = $artistCulture;
						$suggest -> countArts = 1;
						$listSuggest[] = $suggest;
					}
					
				} else {
					
					$objectMediums = $obj -> objectMediums;
					$objectTitle = $obj -> objectTitle;
					$objectTags = $obj -> objectTags;
					$artistPlaceOfOrigin = $obj -> artistPlaceOfOrigin;
					$objString = implode("|", array_filter([$objectMediums, $objectTitle, $objectTags, $artistPlaceOfOrigin]));

					$pos = stripos($objString, $suggestQuery);                    			//первое вхождение запроса в objString
					$objString = substr($objString , $pos);     							//обрезали строку с начала

					$semicolon = $this -> getLastPositionSuggest ($objString);
					
					if($semicolon > 0) {													//если прямые скобки найдены, обрезаем строку до них
						$objString = substr($objString , 0, $semicolon);
					}

					if(strripos($objString, " ") == strlen($objString)-1) {					//если последний символ - пробел, то убираем его
						$objString = substr($objString , 0, strlen($objString)-1);
					}
					
					while (strripos($objString, " ") > strlen($suggestQuery)) {  			//если последнее вхождение пробела более длинны строки запроса
						$pos_space_last = strripos($objString, " ");
						$objString = substr($objString , 0, $pos_space_last);    			//обрезали строку до последнего пробела
					}
					
					$add = true;
					for ($i = 0; $i < count($listSuggest); $i++) {
						if (strtoupper($listSuggest[$i]->suggestStr) == strtoupper($objString)) {
							$add = false;
							$listSuggest[$i]->countArts = $listSuggest[$i]->countArts + 1;
						}
					}

					if ($add && strlen($objString) > 0) {
						$suggest = new stdClass();
						$suggest -> suggestStr = $objString;
						$suggest -> countArts = 1;
						$listSuggest[] = $suggest;
					}
				}
			
			}
			
			return $listSuggest;
		}
		
		public function getLastPositionSuggest ($objString) {
			
			$array = array();
			
			$semicolon = stripos($objString, ",");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, ".");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, ";");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, " (");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, ")");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, "|");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, "?");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, "]");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, " [");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, ":");
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$semicolon = stripos($objString, '"');
			if ($semicolon !== false) { $array[] = $semicolon; }
			
			$val = min($array);
			if ($val === false) { $val = -1; }
			
			return $val;
			
		}
		
		public function getListUserSuggest($userUniqueId) {
			
			$listSuggest = array();
			
			$sql = "SELECT searchQuery FROM MyArtsSearchQuery where userUniqueId =:userUniqueId ORDER BY createdAt DESC LIMIT 50";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$query -> execute();
			
			while ($obj = $query -> fetchObject()) {
				
				$suggest = new stdClass();
				$suggest -> suggestStr = $obj -> searchQuery;
				$suggest -> countArts = 0;
				$suggest -> isUsedEarlie = true;
				
				$listSuggest[] = $suggest;
			}
			
			return $listSuggest;
			
		}
		
		public function getListDefaultSuggest() {
			
			$listSuggest = array();
			
			$sql = "SELECT defaultSuggest FROM MyArtsDefaultSuggests";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			while ($obj = $query -> fetchObject()) {
				
				$suggest = new stdClass();
				$suggest -> suggestStr = $obj -> defaultSuggest;
				$suggest -> countArts = 0;
				$suggest -> isUsedEarlie = false;
				
				$listSuggest[] = $suggest;
			}
			
			return $listSuggest;
			
		}

		public function deleteSuggest($userUniqueId, $suggestQuery){
			
			$sql= "DELETE FROM MyArtsSearchQuery WHERE userUniqueId = :userUniqueId and searchQuery = :suggestQuery";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId, ':suggestQuery' => $suggestQuery));
			$count = $query -> rowCount();
			
			if ($count == 1) {
				return true;
			} else {
				return false;
			}
		}


		
		

		public function getArtsListMaker($artMaker, $userUniqueId, $count, $offset) {
			
			$listArts = array();
			//$str = str_replace(" ", "%", '%'.$artMaker.'%');
			$str = '%'.$artMaker.'%';
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
						(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
						AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
					FROM ArtWorks
					JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
					where artistName like :str LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
				
			return $listArts;
		}
		
		
		


		public function getExploreList() {
			
			$exploreList = array();
			
			$sql = "SELECT * FROM MyArtsExplore";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			while ($obj = $query -> fetchObject()) {
				
				$explore = new stdClass();
				$explore -> imageUrl = $obj -> imageUrl;
				$explore -> type = $obj -> type;
				$explore -> text = $obj -> text;
				$explore -> itemsCount = 0;
				
				$exploreList[] = $explore;
			}
			
			return $exploreList;
			
		}
		
		public function getTotalMakersCount() {
			
			//$sql = "SELECT max(sno) FROM MyArtsMakers ";
			$sql = "SELECT count(1) FROM MyArtsMakersWikiData WHERE makerWikiPageUrl is not null and makerWikiDescription not like '%refer to%' and makerWikiDescription not like '%refers to%' ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			$nameCount = $query -> fetchColumn();
			return $nameCount;
			
		}

		public function getTotalCultureCount() {
			
			$totalCount = 0;
			
			$first = "%?%";
			$second = "%,%";
			
			$sql = "SELECT COUNT(DISTINCT artistCulture) FROM ArtWorks WHERE artistCulture NOT LIKE :first AND artistCulture NOT LIKE :second";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':first' => $first, ':second' => $second));
			$rowCount = $query-> fetchColumn();
			
			$totalCount = $totalCount + $rowCount;
			
			return $totalCount;
			
		}

		public function getTotalMediumCount() {
			
			$sqlList = "SELECT count(1) FROM MyArtsMediums "; //and is_public_domain = 1
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList -> execute();
			
			$mediumsCount = $queryList -> fetchColumn();
			
			return $mediumsCount;
		}
		
		public function getTotalTagCount() {
			
			$sqlList = "SELECT count(1) FROM MyArtsTags ";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList -> execute();
			
			$tagsCount = $queryList -> fetchColumn();
			
			return $tagsCount;
		}

		public function getTotalClassificationCount() {
			
			//$sql = "SELECT COUNT(DISTINCT classification) FROM MyArtsMetropolitan";
			$sql = "SELECT COUNT(*) FROM MyArtsClassifications";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			$rowCount = $query-> fetchColumn();
			
			return $rowCount;
			
		}





		public function getAttributeCulture($count, $offset) {

			$list = array();
			
			$sql = "SELECT * from ArtWorks Where artistCulture <> '' GROUP BY artistCulture LIMIT :offset, :count";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':count', $count, PDO::PARAM_INT);
			$query->bindParam(':offset', $offset, PDO::PARAM_INT);
			$query -> execute();

			while ($obj = $query -> fetchObject()) {

				$attribute = new stdClass();
			
				$attribute -> text = $obj -> artistCulture;
				$attribute -> type = "artCulture";
				$attribute -> itemsCount = 0;
				$attribute -> artImageUrl = $obj -> imageURL;
				$attribute -> artWidth = $obj -> imageWidth;
				$attribute -> artHeight = $obj -> imageHeight;
				$attribute -> artId = $obj -> objectID;
				$attribute -> artProviderId = $obj -> providerID;

				$list[] = $attribute;

			}
			
			return $list;
			
		}

		public function getTagListAll($offset, $pageSize) {
			
			$sqlList = "SELECT tag FROM MyArtsTags LIMIT :offset, :pageSize";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
			$queryList -> execute();
			
			$tagsList = array();
			while ($tag = $queryList -> fetchColumn()) {
				$tagsList[] = $tag;
			}
			
			return $tagsList;
		}
		
		public function getTagListFilter($filter, $offset, $pageSize) {
			
			$str = $filter.'%';
			
			$sqlList = "SELECT tag FROM MyArtsTags WHERE tag like :str LIMIT :offset, :pageSize";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			$tagsList = array();
			while ($tag = $queryList -> fetchColumn()) {
				$tagsList[] = $tag;
			}
			
			return $tagsList;
		}
		
		public function getAttributeTag($listTags) {
			
			$listAttribute = array();
			
			$sql = "SELECT * FROM ArtWorks WHERE objectTags REGEXP :str LIMIT 1";
			$query = $this -> conn -> prepare($sql);
			
			for ($i = 0; $i < count($listTags); $i++) {
				
				$str = '[[:<:]]'.$listTags[$i].'[[:>:]]';
				$query -> execute(array(':str' => $str));
				
				$attribute = new stdClass();
				if ($obj = $query -> fetchObject()) {
					$attribute -> text = $listTags[$i];
					$attribute -> type = "artTag";
					$attribute -> itemsCount = 0;
					$attribute -> artImageUrl = $obj -> imageURL;
					$attribute -> artWidth = $obj -> imageWidth;
					$attribute -> artHeight = $obj -> imageHeight;
					$attribute -> artId = $obj -> objectID;
					$attribute -> artProviderId = $obj -> providerID;
				} else {
					$attribute -> text = $listTags[$i];
					$attribute -> type = "artTag";
					$attribute -> itemsCount = 0;
					$attribute -> artImageUrl = null;
					$attribute -> artWidth = 0;
					$attribute -> artHeight = 0;
					$attribute -> artId = null;
					$attribute -> artProviderId = "5";
				}
				$listAttribute[] = $attribute;
			}
			
			return $listAttribute;
		}

		public function getMediumList($offset, $pageSize) {
			
			$sqlList = "SELECT medium FROM MyArtsMediums LIMIT :offset, :pageSize";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
			$queryList -> execute();
			
			$mediumsList = array();
			while ($medium = $queryList -> fetchColumn()) {
				$mediumsList[] = $medium;
			}
			
			return $mediumsList;
		}

		public function getAttributeMedium($list) {
			
			$listAttribute = array();
			
			$sql = "SELECT * FROM ArtWorks WHERE objectMediums REGEXP :str LIMIT 1";
			$query = $this -> conn -> prepare($sql);
			
			for ($i = 0; $i < count($list); $i++) {
				
				$str = '[[:<:]]'.$list[$i].'[[:>:]]';
				$query -> execute(array(':str' => $str));
				
				$attribute = new stdClass();
				if ($obj = $query -> fetchObject()) {
					$attribute -> text = $list[$i];
					$attribute -> type = "artMedium";
					$attribute -> itemsCount = 0;
					$attribute -> artImageUrl = $obj -> imageURL;
					$attribute -> artWidth = $obj -> imageWidth;
					$attribute -> artHeight = $obj -> imageHeight;
					$attribute -> artId = $obj -> objectID;
					$attribute -> artProviderId = $obj -> providerID;
				} else {
					$attribute -> text = $list[$i];
					$attribute -> type = "artMedium";
					$attribute -> itemsCount = 0;
					$attribute -> artImageUrl = null;
					$attribute -> artWidth = 0;
					$attribute -> artHeight = 0;
					$attribute -> artId = null;
					$attribute -> artProviderId = "5";
				}
				$listAttribute[] = $attribute;
			}
			
			return $listAttribute;
		}

		public function getClassificationList() {
			
			$list = array();
			
			$sql = "SELECT MyArtsClassifications.*, ArtWorks.* FROM MyArtsClassifications
					JOIN ArtWorks ON MyArtsClassifications.objectID = ArtWorks.objectID and MyArtsClassifications.providerID = ArtWorks.providerID";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			while ($obj = $query -> fetchObject()) {
				$attribute = new stdClass();
				$attribute -> text = $obj -> classification;
				$attribute -> type = "artClassification";
				$attribute -> itemsCount = 0;
					if(!empty($obj -> imageURLSmall)){
						$imageURL = $obj -> imageURLSmall;
					} else {
						$imageURL = $obj -> imageURL;
					}
				$attribute -> artImageUrl = $imageURL;
				$attribute -> artWidth = $obj -> imageWidth;
				$attribute -> artHeight = $obj -> imageHeight;
				$attribute -> artId = $obj -> objectID;
				$attribute -> artProviderId = $obj -> providerId;
				
				$list[] = $attribute;
			}
			
			return $list;
			
		}
		
		public function getArtCountClassification($classification) {
			
			$rowCount = 0;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE objectClassification = :classification";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':classification' => $classification));
			$rowCount = $rowCount + $query-> fetchColumn();
			
			return $rowCount;
		}

		public function getArtCountCulture($culture) {

			//$str = '%'.$culture.'%';
			
			$rowCount = 0;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE artistCulture = :culture";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':culture' => $culture));
			$rowCount = $rowCount + $query-> fetchColumn();
			
			return $rowCount;
		}
		



		public function getMakerByMuseum($makerName, $artProviderId) {
			
			$sqlList = "SELECT * FROM ArtWorks where artistName = :makerName and providerId = :artProviderId LIMIT 1";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList -> execute(array(':makerName' => $makerName, ':artProviderId' => $artProviderId));
			
			$maker = new stdClass();
				
			if ($obj = $queryList -> fetchObject()) {
				
				$maker -> artMaker = $obj -> artistName;
					if (empty($maker -> artMaker)) {
						$maker -> artMaker = "Unknown Artist";
					}
				$maker -> artistBio = $obj -> artistLongBio;
					if(empty($obj -> imageURLSmall)) {
						$maker -> artHeaderImageUrl = $obj -> imageURL;
					} else {
						$maker -> artHeaderImageUrl = $obj -> imageURLSmall;
					}
					if(empty($obj -> imageWidth)) {
						$artDimens = $this -> getArtDimens ($artProviderId, $obj -> objectID);
						$maker -> artWidth = $artDimens -> width;
						$maker -> artHeight = $artDimens -> height;
					} else {
						$maker -> artWidth = $obj -> imageWidth;
						$maker -> artHeight = $obj -> imageHeight;
					}
				$maker -> artHeaderId = $obj -> objectID;
				$maker -> artHeaderProviderId = $artProviderId;
			}
			
			return $maker;
		}
		public function getMaker($makerName) {
			
			$sqlList = "SELECT * FROM ArtWorks where artistName = :makerName LIMIT 1";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList -> execute(array(':makerName' => $makerName));
			
			$maker = new stdClass();
				
			if ($obj = $queryList -> fetchObject()) {
				
				$maker -> artMaker = $obj -> artistName;
					if (empty($maker -> artMaker)) {
						$maker -> artMaker = "Unknown Artist";
					}
				$maker -> artistBio = $obj -> artistLongBio;
					if(empty($obj -> imageURLSmall)) {
						$maker -> artHeaderImageUrl = $obj -> imageURL;
					} else {
						$maker -> artHeaderImageUrl = $obj -> imageURLSmall;
					}
					if(empty($obj -> imageWidth)) {
						$artDimens = $this -> getArtDimens ($obj -> providerId, $obj -> objectID);
						$maker -> artWidth = $artDimens -> width;
						$maker -> artHeight = $artDimens -> height;
					} else {
						$maker -> artWidth = $obj -> imageWidth;
						$maker -> artHeight = $obj -> imageHeight;
					}
				$maker -> artHeaderId = $obj -> objectID;
				$maker -> artHeaderProviderId = $obj -> providerId;
			}
			
			return $maker;
		}




		public function getFilterMakers($filter, $count, $century, $offset) {
			
			$listObjects = array();
			
			if (strlen($filter) > 1) {
				$makerName = '';
			} else {
				//$makerName = '^'.$filter;
				$makerName = '[[:<:]]'.$filter;
			}

			if (is_numeric($century)) {
				$str = '^'.$century;
			} else {
				$str = '';
			}
			
			$sqlList = "SELECT distinct artMaker FROM MyArtsMakersWikiData 
						JOIN ArtWorks on MyArtsMakersWikiData.artMaker = ArtWorks.artistName 
						WHERE makerWikiPageUrl is not null and makerWikiDescription not like '%refer to%' and makerWikiDescription not like '%refers to%'
						and artMaker REGEXP :makerName AND objectCentury REGEXP :str LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList->bindParam(':makerName', $makerName, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList -> execute();
			
			while ($obj = $queryList -> fetchObject()) {
				$object = new stdClass();
				$object -> makerName = $obj -> artMaker;
				//$object -> artProviderId = $obj -> providerId;
				$listObjects[] = $object;
			}
			
			return $listObjects;
		}
		
		
		
		
		
		public function getFolderArts($folderUniqueId, $userUniqueId) {

			$sql = "SELECT MyArtsFoldersItems.*, ArtWorks.*, MyArtsProviders.*,
						(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
						AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
							FROM MyArtsFoldersItems
							JOIN ArtWorks ON MyArtsFoldersItems.artId = ArtWorks.objectID AND MyArtsFoldersItems.artProviderId = ArtWorks.providerID
							JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerID
							WHERE folderUniqueId = :folderUniqueId ORDER BY MyArtsFoldersItems.sno DESC";

			//$sql = "SELECT * FROM MyArtsFoldersItems WHERE folderUniqueId = :folderUniqueId ORDER BY sno DESC";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':folderUniqueId' => $folderUniqueId, ':userUniqueId' => $userUniqueId));
			
			$listArts = array();
			while ($art = $query -> fetchObject()) {

				$listArts[] = $art;
			}
			
			return $listArts;
		}

		public function getFoldersList($userUniqueId) {
			
			$foldersList = array();
			$sql = "SELECT MyArtsFolders.*, COUNT(MyArtsFoldersItems.sno) AS itemsCount 
						FROM MyArtsFolders 
						LEFT JOIN MyArtsFoldersItems USING(folderUniqueId)
						WHERE MyArtsFolders.userUniqueId = :userUniqueId and MyArtsFolders.folderState = 'active' 
						GROUP BY MyArtsFolders.folderUniqueId ORDER BY sno DESC";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId));
			
			while ($obj = $query -> fetchObject()) {
				
				$folder = new stdClass();
				$folder -> title = $obj -> title;
				$folder -> folderUniqueId = $obj -> folderUniqueId;
				$folder -> userUniqueId = $obj -> userUniqueId;
				$folder -> createdAt = $obj -> createdAt;
				$folder -> isPublic = boolval($obj -> isPublic);
				$folder -> folderState = $obj -> folderState;
				$folder -> itemsCount = $obj -> itemsCount;
				
				$foldersList[] = $folder;
			}
			
			return $foldersList;
			
		}
		
		public function getFolderCover($folderUniqueId) {
			
			$sql = "SELECT * FROM MyArtsFoldersItems WHERE folderUniqueId = :folderUniqueId 
						and sno = ( SELECT MAX(sno) FROM MyArtsFoldersItems WHERE folderUniqueId = :folderUniqueId)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':folderUniqueId' => $folderUniqueId));
			$obj = $query -> fetchObject();
			
			return $obj;
		}
		
		public function checkIsFolderExist($folderUniqueId){

			$sql = 'SELECT COUNT(*) from MyArtsFolders WHERE folderUniqueId =:folderUniqueId';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array('folderUniqueId' => $folderUniqueId));

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
		
		public function checkIsArtInFolder($folderUniqueId, $art){

			$artId = $art -> artId;
			$artProviderId = $art -> artProviderId;
			
			$sql = 'SELECT COUNT(*) from MyArtsFoldersItems WHERE folderUniqueId =:folderUniqueId and artId = :artId and artProviderId = :artProviderId';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array('folderUniqueId' => $folderUniqueId, ':artId' => $artId, ':artProviderId' => $artProviderId));

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
		
		public function createFolder($folder) {
			
			$title = $folder -> title;
			$folderUniqueId = $folder -> folderUniqueId;
			$userUniqueId = $folder -> userUniqueId;
			$isPublic = $folder -> isPublic;
			$createdAt = date_create()->format('Y-m-d H:i:s');
			$folderState = "active";
			
			$sql = "INSERT MyArtsFolders (title, folderUniqueId, userUniqueId, createdAt, isPublic, folderState) 
									VALUES (:title, :folderUniqueId, :userUniqueId, :createdAt, :isPublic, :folderState)";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':title' => $title, ':folderUniqueId' => $folderUniqueId, ':userUniqueId' => $userUniqueId, ':createdAt' => $createdAt,
									':isPublic' => $isPublic, ':folderState' => $folderState));
			$count = $query -> rowCount();
			
			if($count == 1){
				
				$sql = "INSERT MyArtsFoldersItems (folderUniqueId, userUniqueId, artId, artProviderId, width, height, addedAt, artImageUrl) 
							VALUES (:folderUniqueId, :userUniqueId, :artId, :artProviderId, :width, :height, :createdAt, :artImageUrl)";
				$query = $this -> conn -> prepare($sql);
				
				$folderListArts = array();
				$folderListArts = $folder -> folderListArts;
				$itemsCount = 0;
				for($i = 0; $i < count($folderListArts); $i++) {
					
					$artId = $folderListArts[$i] -> artId;
					$artProviderId = $folderListArts[$i] -> artProviderId;
					$width = $folderListArts[$i] -> artWidth;
					$height = $folderListArts[$i] -> artHeight;
					if(strlen($folderListArts[$i] -> artImgUrlSmall) > 10) {
						$artImageUrl = $folderListArts[$i] -> artImgUrlSmall;
					} else {
						$artImageUrl = $folderListArts[$i] -> artImgUrl;
					}
					
					$query -> execute(array(':folderUniqueId' => $folderUniqueId, ':userUniqueId' => $userUniqueId, ':artId' => $artId, ':artProviderId' => $artProviderId, 
					':width' => $width, ':height' => $height, ':createdAt' => $createdAt, ':artImageUrl' => $artImageUrl));

					$count = $query -> rowCount();
					if($count == 1) {
						$itemsCount++;
					}
				}
				if($itemsCount == count($folderListArts)) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		public function deleteFolder($userUniqueId, $folder) {
			
			$folderUniqueId = $folder -> folderUniqueId;
			
			$sql = "UPDATE MyArtsFolders SET folderState = 'deleted' WHERE folderUniqueId = :folderUniqueId and userUniqueId = :userUniqueId";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':folderUniqueId' => $folderUniqueId, ':userUniqueId' => $userUniqueId));
			$count = $query -> rowCount();
			
			if($count == 1){
				return true;
			} else {
				return false;
			}
		}

		public function updateFolder($folder){
			
			$title = $folder -> title;
			$isPublic = $folder -> isPublic;
			$createdAt = date_create()->format('Y-m-d H:i:s');
			$folderUniqueId = $folder -> folderUniqueId;
			
			$sql = 'UPDATE MyArtsFolders SET title = :title, isPublic = :isPublic, createdAt = :createdAt WHERE folderUniqueId = :folderUniqueId';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':title' => $title, ':isPublic' => $isPublic, ':createdAt' => $createdAt, ':folderUniqueId' => $folderUniqueId));
			
			if ($query == 1) {
				return true;
			} else {
				return false;
			}
		}

		public function deleteArtsFromFolder($listToDelete, $folderUniqueId) {
			
			$sql = "DELETE FROM MyArtsFoldersItems WHERE folderUniqueId = :folderUniqueId and artId = :artId and artProviderId = :artProviderId";
			$query = $this -> conn -> prepare($sql);
			
			for ($i = 0; $i < count($listToDelete); $i++) {
				
				$artId = $listToDelete[$i] -> artId;
				$artProviderId = $listToDelete[$i] -> artProviderId;
				
				$query -> execute(array(':folderUniqueId' => $folderUniqueId, ':artId' => $artId, ':artProviderId' => $artProviderId));
				$count = $query -> rowCount();
			}
			
			/*if($count > 0) {
				return true;
			} else {
				return false;
			}*/
			return true;
		}
		
		public function addArtsToFolder($listToAdd, $folderUniqueId, $userUniqueId) {
			
			$sql = "INSERT MyArtsFoldersItems (folderUniqueId, userUniqueId, artId, artProviderId, width, height, addedAt, artImageUrl) 
						VALUES (:folderUniqueId, :userUniqueId, :artId, :artProviderId, :width, :height, :createdAt, :artImageUrl)";
			$query = $this -> conn -> prepare($sql);
			
			for ($i = 0; $i < count($listToAdd); $i++) {
				
				$artId = $listToAdd[$i] -> artId;
				$artProviderId = $listToAdd[$i] -> artProviderId;
				$width = $listToAdd[$i] -> artWidth;
				$height = $listToAdd[$i] -> artHeight;
				$createdAt = date_create()->format('Y-m-d H:i:s');
				if(strlen($listToAdd[$i] -> artImgUrlSmall) > 10) {
					$artImageUrl = $listToAdd[$i] -> artImgUrlSmall;
				} else {
					$artImageUrl = $listToAdd[$i] -> artImgUrl;
				}
				
				$query -> execute(array(':folderUniqueId' => $folderUniqueId, ':userUniqueId' => $userUniqueId, ':artId' => $artId, ':artProviderId' => $artProviderId, 
					':width' => $width, ':height' => $height, ':createdAt' => $createdAt, ':artImageUrl' => $artImageUrl));
				$count = $query -> rowCount();
			}
			
			/*if($count > 0) {
				return true;
			} else {
				return false;
			}*/
			return true;
		}

		public function getListFoldersArtAdded ($artId, $artProviderId, $userUniqueId) {
			
			$foldersList = array();
			$sql = "SELECT MyArtsFoldersItems.*, MyArtsFolders.title AS title 
						FROM MyArtsFoldersItems 
						LEFT JOIN MyArtsFolders USING(folderUniqueId)
						WHERE MyArtsFoldersItems.userUniqueId = :userUniqueId and MyArtsFoldersItems.artId = :artId 
							and MyArtsFoldersItems.artProviderId = :artProviderId and MyArtsFolders.folderState = 'active'
						ORDER BY sno DESC";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':userUniqueId' => $userUniqueId, ':artId' => $artId, ':artProviderId' => $artProviderId));
			
			while ($obj = $query -> fetchObject()) {
				
				$title = $obj -> title;
				$foldersList[] = $title;
			}
			
			return $foldersList;
		}


		
		
		
		
		public function getListFavorites($userUniqueId) {

			$sqlFav = "SELECT * FROM MyArtsLikes WHERE userUniqueId = :userUniqueId ORDER BY sno DESC";
			$queryFav = $this -> conn -> prepare($sqlFav);
			$queryFav->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryFav->execute();
			
			$listFavorites = array();
			while ($favorite = $queryFav -> fetchObject()) {

				$listFavorites[] = $favorite;
			}
			
			return $listFavorites;
		}
		
		public function getListFavoritesMakers($userUniqueId) {

			$sqlFav = "SELECT * FROM MyArtsLikesMakers WHERE userUniqueId = :userUniqueId ORDER BY sno DESC";
			$queryFav = $this -> conn -> prepare($sqlFav);
			//$queryFav->bindParam(':count', $count, PDO::PARAM_INT);
			$queryFav->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryFav->execute();
			
			$listFavorites = array();
			while ($favorite = $queryFav -> fetchObject()) {

				$listFavorites[] = $favorite;
			}
			
			return $listFavorites;
		}
		
		public function getListDefaultMakers() {
			
			$listSuggest = array();
			
			$sql = "SELECT defaultSuggest as artMaker, makerWikiImageUrl FROM MyArtsDefaultSuggests Join MyArtsMakersWikiData on defaultSuggest = artMaker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			while ($obj = $query -> fetchObject()) {
				
				$listSuggest[] = $obj;
			}
			
			return $listSuggest;
			
		}
		
		public function getArtListMaker($artMaker) {
			
			$listArts = array();
			
			$maker = '%'.$artMaker.'%';
			$classification = "Paintings";
			
			$sql = "SELECT sno, objectID, providerId FROM ArtWorks where artistName like :maker AND objectClassification = :classification ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':maker' => $maker, ':classification' => $classification));
			while ($obj = $query -> fetchObject()) {
				$art = new stdClass();
				$art -> sno = $obj -> sno;
				$art -> artId = $obj -> objectID;
				$art -> artProviderId = $obj -> providerId;
				$listArts[] = $art;
			}
			
			return $listArts;
		}
		
		
		
		
		
		
		
		
		
		
		
		
		
		



		
	}

?>