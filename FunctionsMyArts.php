<?php

	require_once 'DBOperationsMyArts.php';
	require_once 'DBFilterFragmentMyArts.php';
	
	class Functions {
		private $db;
		
		public function __construct() {
			$this -> db = new DBOperations();
			$this -> dbFilterFragment = new DBFilterFragment();
		}
			
		
		public function getMsgParamNotEmpty(){
			$response["result"] = "failure";
			$response["message"] = "Parameters should not be empty !";
			return json_encode($response);
		}
		
		public function getMsgInvalidParam(){
			$response["result"] = "failure";
			$response["message"] = "Invalid Parameters";
			return json_encode($response);
		}





		public function getArtsList ($pageNumber, $userUniqueId) {
			$db = $this -> db;

			$pageSize = 30;
			//$count = $pageSize * $pageNumber;
			$count = $pageSize;

			$listArts = array();
			$listArts = $this -> getArts($count, $userUniqueId, "Paintings");
			shuffle($listArts);

			if(!$listArts) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}

		}
		
		public function getArts($count, $userUniqueId, $classification) {
			$db = $this -> db;

			$listArtsOut = array();
			$listArts = $db -> getArtsList($count, $userUniqueId, $classification);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $this -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		



		public function getArtsListMuseum ($pageNumber, $userUniqueId, $artProviderId, $oldList) {
			$db = $this -> db;

			$pageSize = 30;
			//$count = $pageSize * $pageNumber;
			$count = $pageSize;

			$listArts = array();
			$listArts = $this -> getArtsMuseum($count, $userUniqueId, $artProviderId);

			if(!$listArts) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}

		}

		public function getArtsMuseum($count, $userUniqueId, $museumId) {
			$db = $this -> db;

			$listArtsOut = array();
			
			$listArts = array();
			$list = $db -> getArtsListMuseum($count, $userUniqueId, "Paintings", $museumId);
			$listArts = array_merge($listArts, $list);
			$list = $db -> getArtsListMuseum($count/2, $userUniqueId, "Prints", $museumId);
			$listArts = array_merge($listArts, $list);
			$list = $db -> getArtsListMuseum($count/2, $userUniqueId, "Drawings", $museumId);
			$listArts = array_merge($listArts, $list);
			shuffle($listArts);
			
			for($i = 0; $i < $count; $i++) {
				$art = $this -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}

		public function getMuseumInfo($userUniqueId, $artProviderId) {
			$db = $this -> db;
			
			$artProvider = $db -> getMuseumInfo($artProviderId);
			$artProvider -> isLiked = $db -> isMuseumLiked($artProviderId, $userUniqueId);
			
			$response["result"] = "success";
			$response["message"] = "Museum Data Retrieved";
			$response["artProvider"] = $artProvider;
			return json_encode($response);
			
		}

		
		public function insertLogs($userUniqueId, $operation) {
			$db = $this -> db;

			$logs = $db -> insertLogs($operation, $userUniqueId);
			
		}

		
		public function getMuseumMakersList($userUniqueId, $artProviderId) {
			$db = $this -> db;
			
			$listMakers = array();
			
			$list = $db -> getListMakersByMuseum($artProviderId);
			shuffle($list);
			$list = array_slice($list, 0, 7);
			
			$listFavorites = $db -> getListFavoritesMakersByMuseum($artProviderId);
			$listFavorites = array_slice($listFavorites, 0, 8);
			$output = array_unique(array_merge($listFavorites, $list), SORT_REGULAR);
			shuffle($output);
			
			for($i = 0; $i < count($output); $i++) {
				
				$maker = $db -> getMakerByMuseum ($output[$i], $artProviderId);
				
				$isMakerExist =  $db -> isMakerExist($maker -> artMaker);
				if(!$isMakerExist) {
					$maker = $this -> getMakerWikiData($maker);
					$result = $db -> insertMakerWikiData($maker);
				} else {
					$maker = $db -> getMakerWikiDataLocal($maker);
				}
				
				$listMakers[] = $maker;
			}
			
			$response["result"] = "success";
			$response["message"] = "List Makers Retrieved";
			$response["listMakers"] = $listMakers;
			return json_encode($response);
			
		}
		





		public function isLiked($art, $userUniqueId, $artObjectType) {
			$db = $this -> db;
			
			$artId = $art -> artId;
			$artProviderId = $art -> artProviderId;
			$isLiked =  $db -> isLiked($artProviderId, $artId, $userUniqueId, $artObjectType);
			
			if(!$isLiked) {
				$like =  $db -> like($artProviderId, $artId, $userUniqueId, $artObjectType);
				if($like){
					$art -> isLiked = true;
					$response["result"] = "success";
					$response["message"] = "Art Liked Successfully";
					$response["art"] = $art;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Liked Error";
					return json_encode($response);
				}
			}else {
				$unLike =  $db -> unLike($artProviderId, $artId, $userUniqueId, $artObjectType);
				if($unLike){
					$art -> isLiked = false;
					$response["result"] = "success";
					$response["message"] = "Art Unliked Successfully";
					$response["art"] = $art;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Unliked Error";
					return json_encode($response);
				}
			}
		}

		public function getMyFavorites($userUniqueId) {
			$db = $this -> db;
			
			$listFavorites = array();
			$listFavorites =  $db -> getMyFavorites($userUniqueId);
			
			$listArts = array();
			for ($i = 0; $i < count($listFavorites); $i++) {
				$art = $this -> getArtObject($listFavorites[$i]);
				$listArts[] = $art;
			}

			if(!$listFavorites) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
		}
		
		public function getMyFavoritesArtists($userUniqueId) {
			$db = $this -> db;
			
			$listFavorites = array();
			$listFavorites =  $db -> getMyFavoritesArtists($userUniqueId);
			
			$listMakers = array();
			for ($i = 0; $i < count($listFavorites); $i++) {
				
				$maker = new stdClass();
				$maker -> artMaker = $listFavorites [$i] -> artMaker;
				$maker -> artistBio = $listFavorites [$i] -> artistBio;
				//$maker -> artCount = $this -> getArtCountMaker($listFavorites [$i] -> artMaker);
				$maker -> artCount = 0;
				$maker -> isLiked = true;
				$maker -> artHeaderImageUrl = $listFavorites [$i] -> artHeaderImageUrl;
				$maker -> artWidth = $listFavorites [$i] -> artWidth;
				$maker -> artHeight = $listFavorites [$i] -> artHeight;
				$maker -> makerWikiImageUrl = $listFavorites [$i] -> makerWikiImageUrl;
				$maker -> artHeaderId = $listFavorites [$i] -> artHeaderId;
				$maker -> artHeaderProviderId = $listFavorites [$i] -> artHeaderProviderId;
				if ($maker -> artWidth == 0) {
					$artDimens = $db -> getArtDimens ($maker -> artHeaderProviderId, $maker -> artHeaderId);
					$maker -> artWidth = $artDimens -> width;
					$maker -> artHeight = $artDimens -> height;
				}
				
				$listMakers[] = $maker;
				
			}

			if(!$listFavorites) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listMakers"] = $listMakers;
				return json_encode($response);
			}
		}
		
		public function isMakerLiked($maker, $userUniqueId) {
			$db = $this -> db;
			
			$artMaker = $maker -> artMaker;
			$isLiked =  $db -> isMakerLiked($artMaker, $userUniqueId);
			
			$artistBio = $maker -> artistBio;
			$makerWikiImageUrl = $maker -> makerWikiImageUrl;
			$artHeaderImageUrl = $maker -> artHeaderImageUrl;
			$artWidth = $maker -> artWidth;
			$artHeight = $maker -> artHeight;
			$artHeaderId = $maker -> artHeaderId;
			$artHeaderProviderId = $maker -> artHeaderProviderId;
			if(!$isLiked) {
				$like =  $db -> likeMaker($artMaker, $artistBio, $makerWikiImageUrl, $userUniqueId, $artHeaderImageUrl, $artWidth, $artHeight, $artHeaderId, $artHeaderProviderId);
				if($like){
					$maker -> isLiked = true;
					$response["result"] = "success";
					$response["message"] = "Maker Liked Successfully";
					$response["artMaker"] = $maker;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Liked Error";
					return json_encode($response);
				}
			}else {
				$unLike =  $db -> unLikeMaker($artMaker, $userUniqueId);
				if($unLike){
					$maker -> isLiked = false;
					$response["result"] = "success";
					$response["message"] = "Maker Unliked Successfully";
					$response["artMaker"] = $maker;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Unliked Error";
					return json_encode($response);
				}
			}
		}
		
		public function getMakerWikiData($maker) {
			$db = $this -> db;
			
			$string = $maker -> artMaker;
			if (stripos($string, "(") !== false) { $string = substr($string , 0, stripos($string, "(")); }
			if (stripos($string, "[") !== false) { $string = substr($string , 0, stripos($string, "[")); }
			if (stripos($string, ",") !== false) { $string = substr($string , 0, stripos($string, ",")); }
			$string = str_replace("Workshop of ", "", $string);
			$string = str_replace("Attributed to ", "", $string);
			$string = str_replace("Style of ", "", $string);
			$string = str_replace(" the Elder", "", $string);
			$string = str_replace("Sir ", "", $string);
			
			$string = str_replace("ō", "o", $string);
			$string = str_replace("ô", "o", $string);
			$string = str_replace("é", "e", $string);
			$string = str_replace("ë", "e", $string);
			$string = str_replace("è", "e", $string);
			$string = str_replace("ü", "u", $string);
			$string = str_replace("ū", "u", $string);
			$string = str_replace("ç", "c", $string);
			$string = str_replace("á", "a", $string);
			$string = str_replace("í", "i", $string);
			$string = str_replace("î", "i", $string);
			
			$string = preg_replace('/\s+/', '_', $string);
			
			$url = "https://en.wikipedia.org/w/api.php?action=query&format=json&titles=".$string."&prop=extracts&exintro&explaintext&redirects=1&indexpageids";
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); // get the data returned
			curl_setopt($ch,CURLOPT_URL, $url);
			$curlResponse = curl_exec($ch);
			$obj = json_decode($curlResponse);
			curl_close($ch);
			$query = $obj -> query;
			$pageids = $query -> pageids;
			$id = $pageids[0];
			$pages = $query -> pages;
			$object = $pages -> $id;
			$description = $object -> extract;
			//echo $description. "<br />";
			
			$url = "https://en.wikipedia.org/w/api.php?action=query&prop=info&pageids=".$id."&inprop=url&format=json";
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); // get the data returned
			curl_setopt($ch,CURLOPT_URL, $url);
			$curlResponse = curl_exec($ch);
			$obj = json_decode($curlResponse);
			curl_close($ch);
			$query = $obj -> query;
			$pages = $query -> pages;
			$object = $pages -> $id;
			$fullurl = $object -> fullurl;
			//echo $fullurl. "<br />";
			
			$url = "https://en.wikipedia.org/w/api.php?action=query&titles=".$string."&prop=pageimages&format=json&pithumbsize=800";
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); // get the data returned
			curl_setopt($ch,CURLOPT_URL, $url);
			$curlResponse = curl_exec($ch);
			$obj = json_decode($curlResponse);
			curl_close($ch);
			$query = $obj -> query;
			$pages = $query -> pages;
			$object = $pages -> $id;
			$thumbnail = $object -> thumbnail;
			$imageUrl = $thumbnail -> source;
			//echo $imageUrl. "<br />";
			
			$maker -> makerWikiDescription = $description;
			$maker -> makerWikiImageUrl = $imageUrl;
			$maker -> makerWikiPageUrl = $fullurl;
			
			return $maker;
		}
		
		public function getMakerObject($maker, $userUniqueId) {
			$db = $this -> db;
			$dbFilterFragment = $this -> dbFilterFragment;
			
			$isMakerExist =  $db -> isMakerExist($maker -> artMaker);
			if(!$isMakerExist) {
				$maker = $this -> getMakerWikiData($maker);
				$result = $db -> insertMakerWikiData($maker);
			} else {
				$maker = $db -> getMakerWikiDataLocal($maker);
			}
			
			$maker -> isLiked = $db -> isMakerLiked($maker -> artMaker, $userUniqueId);
			
			$string = $maker -> artMaker;
			if (stripos($string, "(") !== false) { $string = substr($string , 0, stripos($string, "(")); }
			if (stripos($string, "[") !== false) { $string = substr($string , 0, stripos($string, "[")); }
			if (stripos($string, ",") !== false) { $string = substr($string , 0, stripos($string, ",")); }
			$string = str_replace("Workshop of ", "", $string);
			$string = str_replace("Attributed to ", "", $string);
			$string = str_replace("Style of ", "", $string);
			$string = str_replace(" the Elder", "", $string);
			$string = str_replace("Sir ", "", $string);
			//$maker -> artCount = $this -> getArtCountMaker($string);
			//$maker -> artCount = 0;
			$maker -> artCount = $dbFilterFragment -> getArtCountByMakerAndCentury($string, "");
			
			$listKeywordFilters = array();
			$listKeywordFilters = $dbFilterFragment -> getKeywordByMakerAndCentury($string, "");
			usort($listKeywordFilters, function($a, $b) { return $a->countArts < $b->countArts ? 1 : -1; });
			$listKeywordFilters = array_slice($listKeywordFilters, 0, 30);
			
			$response["result"] = "success";
			$response["message"] = "Maker Data Retrieved";
			$response["artMaker"] = $maker;
			$response["listKeywordFilters"] = $listKeywordFilters;
			return json_encode($response);
			
		}
		
		public function isArtProviderLiked($artProvider, $userUniqueId) {
			$db = $this -> db;
			
			$providerId = $artProvider -> providerId;
			$isLiked =  $db -> isMuseumLiked($providerId, $userUniqueId);
			
			if(!$isLiked) {
				$like =  $db -> likeMuseum($providerId, $userUniqueId);
				if($like){
					$artProvider -> isLiked = true;
					$response["result"] = "success";
					$response["message"] = "Maker Liked Successfully";
					$response["artProvider"] = $artProvider;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Liked Error";
					return json_encode($response);
				}
			}else {
				$unLike =  $db -> unLikeMuseum($providerId, $userUniqueId);
				if($unLike){
					$artProvider -> isLiked = false;
					$response["result"] = "success";
					$response["message"] = "Maker Unliked Successfully";
					$response["artProvider"] = $artProvider;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					$response["message"] = "Unliked Error";
					return json_encode($response);
				}
			}
		}
		
		
		
		
		
		public function artWriteDimens($art) {
			$db = $this -> db;
			
			$artId = $art -> artId;
			$artProviderId = $art -> artProviderId;
			$width = $art -> artWidth;
			$height = $art -> artHeight;
			
			$result =  $db -> artWriteDimens($artId, $artProviderId, $width, $height);

			if(!$result) {
				$response["result"] = "failure";
				$response["message"] = "Could not write data to DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "Dimens wrote to DB";
				return json_encode($response);
			}
		}
		



		
		public function getArtsListSearch($searchQuery, $pageNumber, $userUniqueId) {
			$db = $this -> db;
			
			$result = $this -> writeSearchQuery($searchQuery, $userUniqueId);
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber - 1);

			$listArts = array();
			$listArts = $this -> getListSearch($searchQuery, $userUniqueId, $count, $offset);

			if(!$listArts) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
		}
		
		public function getListSearch($searchQuery, $userUniqueId, $count, $offset) {
			$db = $this -> db;

			$listArtsOut = array();
			$listArts = $db -> getArtsListSearch($searchQuery, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $this -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		
		public function writeSearchQuery($searchQuery, $userUniqueId) {
			$db = $this -> db;
			
			$isSearchQueryInTable =  $db -> isSearchQueryInTable($searchQuery, $userUniqueId);
			
			if(!$isSearchQueryInTable) {
				
				$result =  $db -> insertSearchQuery($searchQuery, $userUniqueId);
				
			} else {
				
				$result =  $db -> updateSearchQuery($searchQuery, $userUniqueId);
				
			}
			
		}
		
		
		public function getSuggest($suggestQuery, $userUniqueId) {
			$db = $this -> db;
			
			$listSuggests = array();
			$listSuggests = $db -> getListSuggest($suggestQuery);

			usort($listSuggests, function($a, $b) {
				return $a->countArts < $b->countArts ? 1 : -1;
			});
			
			$listUserSuggest = $db -> getListUserSuggest($userUniqueId);
			for ($i = 0; $i < count($listSuggests); $i++) {
				
				$isUsedEarlie = false;
				for ($j = 0; $j < count($listUserSuggest); $j++) {
					if (strtoupper($listSuggests[$i]->suggestStr) == strtoupper($listUserSuggest[$j]->suggestStr)) {
						$isUsedEarlie = true;
					}
				}
				$listSuggests[$i]->isUsedEarlie = $isUsedEarlie;
			}
			
			if(!$listSuggests) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				$response["suggestQuery"] = $suggestQuery;
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of suggests";
				$response["listSuggests"] = $listSuggests;
				$response["suggestQuery"] = $suggestQuery;
				return json_encode($response);
			}
		}
		
		public function getInitialSuggests($userUniqueId) {
			$db = $this -> db;
			
			
			$listSuggests = array();
			
			$listUserSuggest = $db -> getListUserSuggest($userUniqueId);
			for ($i = 0; $i < count($listUserSuggest); $i++) {
				$listSuggests[] = $listUserSuggest[$i];
			}
			
			$listDefaultSuggest = $db -> getListDefaultSuggest();
			for ($i = 0; $i < count($listDefaultSuggest); $i++) {
				
				$add = true;
				for ($j = 0; $j < count($listSuggests); $j++) {
					if (strtoupper($listDefaultSuggest[$i]->suggestStr) == strtoupper($listSuggests[$j]->suggestStr)) {
						$add = false;
					}
				}
				if ($add) {
					$listSuggests[] = $listDefaultSuggest[$i];
				}
			}
			
			if(!$listSuggests) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				$response["suggestQuery"] = "";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of suggests";
				$response["listSuggests"] = $listSuggests;
				$response["suggestQuery"] = "";
				return json_encode($response);
			}
			
		}

		public function deleteSuggest($userUniqueId, $suggestQuery) {
			$db = $this -> db;
			
			$result = $db -> deleteSuggest($userUniqueId, $suggestQuery);
			
			$listSuggests = array();
			
			$listUserSuggest = $db -> getListUserSuggest($userUniqueId);
			for ($i = 0; $i < count($listUserSuggest); $i++) {
				$listSuggests[] = $listUserSuggest[$i];
			}
			
			$listDefaultSuggest = $db -> getListDefaultSuggest();
			for ($i = 0; $i < count($listDefaultSuggest); $i++) {
				
				$add = true;
				for ($j = 0; $j < count($listSuggests); $j++) {
					if (strtoupper($listDefaultSuggest[$i]->suggestStr) == strtoupper($listSuggests[$j]->suggestStr)) {
						$add = false;
					}
				}
				if ($add) {
					$listSuggests[] = $listDefaultSuggest[$i];
				}
			}
			
			if(!$listSuggests) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				$response["suggestQuery"] = "";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of suggests";
				$response["listSuggests"] = $listSuggests;
				$response["suggestQuery"] = "";
				return json_encode($response);
			}
			
		}


		
		
		
		public function getArtsListMaker($artMaker, $pageNumber, $userUniqueId) {
			$db = $this -> db;
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber - 1);
			
			$string = $artMaker;
			if (stripos($string, "(") !== false) { $string = substr($string , 0, stripos($string, "(")); }
			if (stripos($string, "[") !== false) { $string = substr($string , 0, stripos($string, "[")); }
			if (stripos($string, ",") !== false) { $string = substr($string , 0, stripos($string, ",")); }
			$string = str_replace("Workshop of ", "", $string);
			$string = str_replace("Attributed to ", "", $string);
			$string = str_replace("Style of ", "", $string);
			$string = str_replace(" the Elder", "", $string);
			$string = str_replace("Sir ", "", $string);

			$listArts = array();
			$listArts = $this -> getListMaker($string, $userUniqueId, $count, $offset);

			if(!$listArts) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
			
		}
		
		public function getListMaker($artMaker, $userUniqueId, $count, $offset) {
			$db = $this -> db;

			$listArtsOut = array();
			$listArts = $db -> getArtsListMaker($artMaker, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $this -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		
		
		
		
		public function getArtObject ($object) {
			$db = $this -> db;
			
			$art = new stdClass();
			$art -> artLink = $object -> objectLink;
			$art -> artId = $object -> objectID;
			$art -> artTitle = $object -> objectTitle;
			$art -> artMaker = $object -> artistName;
				if (empty($art -> artMaker)) { $art -> artMaker = "Unknown Artist"; }
				$title = $object -> objectTitle;
				$objectDate = $object -> objectDateStart;
				$nameAuthor = $object -> artistName;
			$art -> artLongTitle = implode(", ", array_filter([$title, $objectDate, $nameAuthor])) ;
			$art -> artImgUrl = $object -> imageURL;
			$art -> artImgUrlSmall = $object -> imageURLSmall;
			$art -> artWidth = $object -> imageWidth;
			$art -> artHeight = $object -> imageHeight;
				if($object -> imageWidth == 0) {
					$artDimens = $db -> getArtDimens ($object -> providerId, $art -> artId);
					$art -> artWidth = $artDimens -> width;
					$art -> artHeight = $artDimens -> height;
				}
			$art -> artistBio = $object -> artistLongBio;
			$art -> artProviderId = $object -> providerId;
			$art -> artProvider = $object -> artProvider;
			$art -> artProviderCountry = $object -> artProviderCountry;
			$art -> artLogoUrl = $object -> artLogoUrl;
			
			$art -> artClassification = $object -> objectClassification;
				if($object -> likes == 0) { $isLiked = false; } else { $isLiked = true; }
			$art -> isLiked = $isLiked;
			$art -> century = $object -> objectCentury;
				if($object -> objectCentury == 0) { $art -> century = "n.d."; }
			
			return $art;
		}





		public function getExploreList($userUniqueId) {
			$db = $this -> db;
			
			$exploreList = $db -> getExploreList();
			
			for ($i = 0; $i < count($exploreList); $i++) {
				
				if($exploreList[$i] -> type == "artMaker") {
					$exploreList[$i] -> itemsCount = $db -> getTotalMakersCount();
					
				} else if ($exploreList[$i] -> type == "artCulture") {
					$exploreList[$i] -> itemsCount = $db -> getTotalCultureCount();
					
				} else if ($exploreList[$i] -> type == "artMedium") {
					$exploreList[$i] -> itemsCount = $db -> getTotalMediumCount();
					
				} else if ($exploreList[$i] -> type == "artTag") {
					$exploreList[$i] -> itemsCount = $db -> getTotalTagCount();
					
				} else if ($exploreList[$i] -> type == "artClassification") {
					$exploreList[$i] -> itemsCount = $db -> getTotalClassificationCount();
				}
			}
			
			if(!$exploreList) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of explore";
				$response["listExplore"] = $exploreList;
				return json_encode($response);
			}
		}
		
		public function getCultureList($pageNumber) {
			$db = $this -> db;
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber - 1);
			
			$listObjects = $db -> getCultureList($count, $offset);
			sort($listObjects);
			
			$listAttribute = array();
			for ($i = 0; $i < count($listObjects); $i++) {
				$attribute = $db -> getAttributeCulture ($listObjects[$i]);
				//$attribute -> itemsCount = $db -> getArtCountCulture($attribute -> text);
				$listAttribute[] = $attribute;
			}
			
			if(!$listAttribute) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listAttribute"] = $listAttribute;
				return json_encode($response);
			}
		}

		public function getTagList($pageNumber, $filter) {
			$db = $this -> db;
			
			$pageSize = 30;
			$offset = $pageSize * ($pageNumber - 1);
			
			if (strlen($filter) == 1) {
				$listTags = $db -> getTagListFilter($filter, $offset, $pageSize);
			} else {
				$listTags = $db -> getTagListAll($offset, $pageSize);
			}

			$listAttribute = array();
			$listAttribute = $db -> getAttributeTag ($listTags);

			if(!$listAttribute) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listAttribute"] = $listAttribute;
				$response["filter"] = $filter;
				return json_encode($response);
			}
		}
		
		public function getMediumList($pageNumber) {
			$db = $this -> db;
			
			$pageSize = 30;
			$offset = $pageSize * ($pageNumber - 1);
			
			$listObjects = $db -> getMediumList($offset, $pageSize);
			
			$listAttribute = array();
			$listAttribute = $db -> getAttributeMedium ($listObjects);
			
			if(!$listAttribute) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listAttribute"] = $listAttribute;
				return json_encode($response);
			}
		}
		
		public function getClassificationList($pageNumber) {
			$db = $this -> db;
			
			$listAttribute = $db -> getClassificationList();
			for ($i = 0; $i < count($listAttribute); $i++) {
				$listAttribute[$i] -> itemsCount = $db -> getArtCountClassification($listAttribute[$i] -> text);
			}
			
			if(!$listAttribute) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listAttribute"] = $listAttribute;
				return json_encode($response);
			}
		}
		
		
		
		
		
		public function getFilterMakers($filter, $pageNumber, $userUniqueId, $century) {
			$db = $this -> db;
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber - 1);
			
			$listObjects = array();
			$listObjects = $db -> getFilterMakers($filter, $count, $century, $offset);

			$listMakers = array();
			for ($i = 0; $i < count($listObjects); $i++) {
				$maker = $db -> getMaker ($listObjects[$i]->makerName);
				$listMakers[] = $maker;
				
				$isMakerExist =  $db -> isMakerExist($maker -> artMaker);
				if(!$isMakerExist) {
					$maker = $this -> getMakerWikiData($maker);
					$result = $db -> insertMakerWikiData($maker);
				} else {
					$maker = $db -> getMakerWikiDataLocal($maker);
				}
				//$maker -> isLiked = $db -> isMakerLiked($maker -> artMaker, $userUniqueId);
				//$maker -> artCount = $this -> getArtCountMaker($maker -> artMaker);
			}
			
			if(!$listMakers) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of makers";
				$response["filter"] = $filter;
				$response["century"] = $century;
				$response["listMakers"] = $listMakers;
				return json_encode($response);
			}
		}




		public function getFolderArts($userUniqueId, $folder) {
			$db = $this -> db;
			
			$list = array();
			$list =  $db -> getFolderArts($folder -> folderUniqueId, $userUniqueId);
			
			$listArts = array();
			for ($i = 0; $i < count($list); $i++) {
				$art = $this -> getArtObject($list[$i]);
				$listArts[] = $art;
			}

			if(!$list) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
		}

		public function getFolderForEdit($userUniqueId, $folder) {
			$db = $this -> db;
			
			$list = array();
			$list =  $db -> getFolderArts($folder -> folderUniqueId, $userUniqueId);
			
			$listArts = array();
			for ($i = 0; $i < count($list); $i++) {
				$art = $this -> getArtObject($list[$i]);
				$art -> isChosenForAddToFolder = true;
				$art -> listFoldersAdded = $db -> getListFoldersArtAdded ($list[$i] -> objectID, $list[$i] -> providerId, $userUniqueId);
				$listArts[] = $art;
			}
			
			$listToAdd = array();
			$listFavorites =  $db -> getMyFavorites($userUniqueId);
			for ($i = 0; $i < count($listFavorites); $i++) {
				$add = true;
				for($j = 0; $j < count($list); $j++) {
					if($listFavorites[$i]->artId == $list[$j]->artId && $listFavorites[$i]->artProviderId == $list[$j]->artProviderId) {
						$add = false;
					}
				}
				if($add){ $listToAdd[] = $listFavorites[$i]; }
			}
			
			for ($i = 0; $i < count($listToAdd); $i++) {
				$art = $this -> getArtObject($listToAdd[$i]);
				$art -> isChosenForAddToFolder = false;
				$art -> listFoldersAdded = $db -> getListFoldersArtAdded ($listToAdd[$i] -> objectID, $listToAdd[$i] -> providerId, $userUniqueId);
				$listArts[] = $art;
			}
			
			if(!$list) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
		}
		
		public function getMyFavoritesToCreateFolder($userUniqueId) {
			$db = $this -> db;
			
			$listFavorites = array();
			$listFavorites =  $db -> getMyFavorites($userUniqueId);
			
			$listArts = array();
			for ($i = 0; $i < count($listFavorites); $i++) {
				$art = $this -> getArtObject($listFavorites[$i]);
				$art -> isChosenForAddToFolder = false;
				$art -> listFoldersAdded = $db -> getListFoldersArtAdded ($listFavorites[$i] -> objectID, $listFavorites[$i] -> providerId, $userUniqueId);
				$listArts[] = $art;
			}

			if(!$listFavorites) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}
		}

		public function getFoldersList($userUniqueId) {
			$db = $this -> db;
			
			$foldersList = $db -> getFoldersList($userUniqueId);
			for($i = 0; $i < count($foldersList); $i++) {
				$obj = $db -> getFolderCover($foldersList[$i] -> folderUniqueId);
				$foldersList[$i] -> artImageUrl = $obj -> artImageUrl;
				$foldersList[$i] -> artWidth = $obj -> width;
				$foldersList[$i] -> artHeight = $obj -> height;
				$foldersList[$i] -> artId = $obj -> artId;
				$foldersList[$i] -> artProviderId = $obj -> artProviderId;
			}
							
			if(!$foldersList) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of folders";
				$response["listFolders"] = $foldersList;
				return json_encode($response);
			}
		}
		
		public function checkIsFolderExist($folder) {
			$db = $this -> db;
			
			$isFolderExist = $db -> checkIsFolderExist($folder -> folderUniqueId);
			
			if ($isFolderExist) {
				
				$result = $db -> updateFolder($folder);
				
				$listNew =  $folder -> folderListArts;
				$listOld =  $db -> getFolderArts($folder -> folderUniqueId, $folder -> userUniqueId);
				
				
				$listToDelete = array();
				for ($i = 0; $i < count($listOld); $i++) {
					$delete = true;
					for($j = 0; $j < count($listNew); $j++) {
						if($listOld[$i]->artId == $listNew[$j]->artId && $listOld[$i]->artProviderId == $listNew[$j]->artProviderId) {
							$delete = false;
						}
					}
					if($delete){ $listToDelete[] = $listOld[$i]; }
				}
				if(count($listToDelete) > 0) {
					$db -> deleteArtsFromFolder($listToDelete, $folder -> folderUniqueId);
				}
				
				
				$listToAdd = array();
				for ($i = 0; $i < count($listNew); $i++) {
					$add = true;
					for($j = 0; $j < count($listOld); $j++) {
						if($listNew[$i]->artId == $listOld[$j]->artId && $listNew[$i]->artProviderId == $listOld[$j]->artProviderId) {
							$add = false;
						}
					}
					if($add){ $listToAdd[] = $listNew[$i]; }
				}
				if(count($listToAdd) > 0) {
					$db -> addArtsToFolder($listToAdd, $folder -> folderUniqueId, $folder -> userUniqueId);
				}
				
				
			} else {
				$result = $db -> createFolder($folder);
			}
							
			if(!$result) {
				$response["result"] = "failure";
				$response["message"] = "Could not to create a folder";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "Folder created";
				return json_encode($response);
			}
		}
		
		public function saveArtToFolder($folder, $art, $userUniqueId) {
			$db = $this -> db;
			
			$isArtInFolder = $db -> checkIsArtInFolder($folder -> folderUniqueId, $art);
			
			if (!$isArtInFolder) {
				
				$listToAdd = array();
				$listToAdd[] = $art;
				$result = $db -> addArtsToFolder($listToAdd, $folder -> folderUniqueId, $userUniqueId);
								
				if(!$result) {
					$response["result"] = "failure";
					$response["message"] = "Some error occurred";
					return json_encode($response);
				} else {
					$response["result"] = "success";
					$response["message"] = "ArtWork is saved to folder";
					return json_encode($response);
				}
				
			} else {
				$response["result"] = "success";
				$response["message"] = "ArtWork is already saved";
				return json_encode($response);
			}
			
		}
		
		public function deleteFolder($userUniqueId, $folder){
			$db = $this -> db;
			
			$isDeleted = $db -> deleteFolder($userUniqueId, $folder);
			
			if(!$isDeleted) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "Folder deleted";
				return json_encode($response);
			}
		}





		public function getRecommendations ($userUniqueId) {
			$db = $this -> db;

			$count = 30;
			$listArts = array();
			
			$listFavObj = $db -> getListFavorites($userUniqueId);
			
//--------------------------
/*
			if(!empty($listFavObj)) {
				
				shuffle($listFavObj);
				$list = array_slice($listFavObj, 0, $count);
				
				$listFavorites = array();
				for ($i = 0; $i < count($list); $i++) {
					$artProviderId = $list[$i] -> artProviderId;
					$artId = $list[$i] -> artId;
					$object = $db -> getSingleArt ($artId, $userUniqueId, $artProviderId);
					$art = $this -> getArtObject($object);
					$listFavorites[] = $art;
				}
				
				$listMakers = array();
				for ($i = 0; $i < count($listFavorites); $i++) {
					$artMaker = $listFavorites[$i] -> artMaker;
					if (!in_array($artMaker, $listMakers)) {
						$listMakers[] = $artMaker;
						$artsListMaker = $this -> getArtsListMakerRecommend($artMaker, $userUniqueId);
						$listArts = array_merge($listArts, $artsListMaker);
					}
				}
			}
*/
//----------------------------
	
			$listMakers = $db -> getListFavoritesMakers($userUniqueId);
			
			if(empty($listMakers) || count($listMakers) < 3) {
				$listMakers = $db -> getListDefaultMakers();
			} 
			shuffle($listMakers);
			$listMakers = array_slice($listMakers, 0, 5);
			$artsList = array();
			for ($i = 0; $i < count($listMakers); $i++) {
				$artMaker = $listMakers[$i] -> artMaker;
				$artsIds = $db -> getArtListMaker($artMaker);
				shuffle($artsIds);
				$artsIds = array_slice($artsIds, 0, $count);
				$artsList = array_merge($artsList, $artsIds);
			}
			
			shuffle($artsList);
			foreach($artsList as $key => $artId) {
				foreach($listFavObj as $favId) {
					if($favId->artId == $artId->artId && $favId->artProviderId == $artId->artProviderId) {
						unset($artsList[$key]);
						break;
					}
				}
			}
			$artsList = array_slice($artsList, 0, $count);
			
			for ($i = 0; $i < count($artsList); $i++) {
				$artProviderId = $artsList[$i] -> artProviderId;
				$artId = $artsList[$i] -> artId;

				$object = $db -> getSingleArt ($artId, $userUniqueId, $artProviderId);
				$art = $this -> getArtObject($object);
				$listArts[] = $art;
			}
			
			foreach($listArts as $art) {
				foreach($listMakers as $maker) {
					if($maker->artMaker == $art->artMaker) {
						$art-> makerImgUrl = $maker-> makerWikiImageUrl;
						break;
					}
				}
			}

			if(!$listArts) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of arts";
				$response["listArts"] = $listArts;
				return json_encode($response);
			}

		}
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		





		

	}

?>