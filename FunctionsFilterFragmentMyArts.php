<?php

	require_once 'DBFilterFragmentMyArts.php';
	require_once 'FunctionsMyArts.php';
	
	class FunctionsFilterFragment {
		private $dbFilterFragment;
		
		public function __construct() {
			$this -> dbFilterFragment = new DBFilterFragment();
			$this -> functions = new Functions();
		}
			
		
		

		
		public function getArtsListFilter($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId, $keywordType, $oldList) {
			$functions = $this -> functions;
			
			if ($keywordType == "artCulture") {
				return $this -> getArtsListCulture($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId);
				
			} else if ($keywordType == "artMedium") {
				return $this -> getArtsListMedium($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId);
				
			} else if ($keywordType == "artTag") {
				return $this -> getArtsListTag($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId);
				
			} else if ($keywordType == "artClassification") {
				if(empty($makerFilter) && empty($centuryFilter)) {
					return $this -> getArtsListClassificationRandom($keywordFilter, $userUniqueId, $oldList);
				} else {
					return $this -> getArtsListClassificationFilter($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId);
				}
				
			} else if (empty($keywordType)) {
				if(empty($makerFilter) && empty($centuryFilter)){
					return $functions -> getArtsList ($pageNumber, $userUniqueId);
				} else {
					return $this -> getArtsListMakerAndCentury ($pageNumber, $userUniqueId, $makerFilter, $centuryFilter);
				}
			}
			
		}
		
		public function getListFilters($keywordFilter, $makerFilter, $centuryFilter, $keywordType, $userUniqueId) {
			$dbFilterFragment = $this -> dbFilterFragment;
			
			$listMakerFilters = array();
			
			if ($keywordType == "artCulture") {
				$listMakerFilters = $dbFilterFragment -> getMakersByCulture($keywordFilter, $centuryFilter);
				$listCenturyFilters = $dbFilterFragment -> getCenturyByCulture($keywordFilter, $makerFilter);
				$listKeywordFilters = $dbFilterFragment -> getKeywordByCulture($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artMedium") {
				$listMakerFilters = $dbFilterFragment -> getMakersByMedium($keywordFilter, $centuryFilter);
				$listCenturyFilters = $dbFilterFragment -> getCenturyByMedium($keywordFilter, $makerFilter);
				$listKeywordFilters = $dbFilterFragment -> getKeywordByMedium($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artTag") {
				$listMakerFilters = $dbFilterFragment -> getMakersByTag($keywordFilter, $centuryFilter);
				$listCenturyFilters = $dbFilterFragment -> getCenturyByTag($keywordFilter, $makerFilter);
				$listKeywordFilters = $dbFilterFragment -> getKeywordByTag($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artClassification") {
				$listMakerFilters = $dbFilterFragment -> getMakersByClassification($keywordFilter, $centuryFilter);
				$listCenturyFilters = $dbFilterFragment -> getCenturyByClassification($keywordFilter, $makerFilter);
				$listKeywordFilters = $dbFilterFragment -> getKeywordByClassification($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if (empty($keywordType)) {
				$listMakerFilters = $dbFilterFragment -> getMakersByCentury($centuryFilter);
				$listCenturyFilters = $dbFilterFragment -> getCenturyByMaker($makerFilter);
				$listKeywordFilters = $dbFilterFragment -> getKeywordByMakerAndCentury($makerFilter, $centuryFilter);
			}
			
			//shuffle($listMakerFilters);
			//$listMakerFilters = array_slice($listMakerFilters, 0, 500);
			//if(!in_array($makerFilter, $listMakerFilters) && strlen($makerFilter) > 0) { array_unshift($listMakerFilters , $makerFilter); }
			
			usort($listKeywordFilters, function($a, $b) { return $a->countArts < $b->countArts ? 1 : -1; });
			//$listKeywordFilters = array_slice($listKeywordFilters, 0, 300);
			$add = true;
			for($i = 0; $i < count($listKeywordFilters); $i++) {
				if (strtoupper($listKeywordFilters[$i]->text) == strtoupper($keywordFilter)) {
					$add = false;
					break;
				}
			}
			if ($add && strlen($keywordFilter) > 0) {
				$keyword = new stdClass();
				$keyword -> text = $keywordFilter;
				$keyword -> countArts = 1;
				$keyword -> type = $keywordType;
				array_unshift($listKeywordFilters , $keyword);
			}
			
			
			if(!$listMakerFilters) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "List of filters";
				$response["listMakerFilters"] = $listMakerFilters;
				$response["listCenturyFilters"] = $listCenturyFilters;
				$response["listKeywordFilters"] = $listKeywordFilters;
				return json_encode($response);
			}
			
		}
		
		public function getArtCountFilterFragment($keywordFilter, $makerFilter, $centuryFilter, $keywordType, $userUniqueId) {
			$dbFilterFragment = $this -> dbFilterFragment;
			
			if ($keywordType == "artCulture") {
				$artCount = $dbFilterFragment -> getArtCountByCulture($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artMedium") {
				$artCount = $dbFilterFragment -> getArtCountByMedium($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artTag") {
				$artCount = $dbFilterFragment -> getArtCountByTag($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if ($keywordType == "artClassification") {
				$artCount = $dbFilterFragment -> getArtCountByClassification($keywordFilter, $makerFilter, $centuryFilter);
				
			} else if (empty($keywordType)) {
				$artCount = $dbFilterFragment -> getArtCountByMakerAndCentury($makerFilter, $centuryFilter);
			}
			
			
			if(!$artCount) {
				$response["result"] = "failure";
				$response["message"] = "Could not get data from DB";
				return json_encode($response);
			} else {
				$response["result"] = "success";
				$response["message"] = "Art Count";
				$response["artCount"] = $artCount;
				return json_encode($response);
			}
			
		}
		
		
		
		
		
		public function getArtsListClassificationRandom($classification, $userUniqueId, $oldList) {
			$functions = $this -> functions;
			
			$pageSize = 30;
			
			$listArts = $functions -> getArts($pageSize, $userUniqueId, $classification);

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
		
		public function getArtsListClassificationFilter($classification, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId) {
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber - 1);
			
			$listArts = array();
			$listArts = $this -> getArtsClassificationFilter($classification, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);

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
		
		public function getArtsClassificationFilter($classification, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			$dbFilterFragment = $this -> dbFilterFragment;
			$functions = $this -> functions;

			$listArtsOut = array();
			$listArts = $dbFilterFragment -> getArtsClassificationFilter($classification, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $functions -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}


		
		
		public function getArtsListCulture($culture, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId) {
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber -1);

			$listArts = array();
			$listArts = $this -> getArtsCulture($culture, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);

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
		
		public function getArtsCulture($culture, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			$dbFilterFragment = $this -> dbFilterFragment;
			$functions = $this -> functions;

			$listArtsOut = array();
			$listArts = $dbFilterFragment -> getArtsCulture($culture, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $functions -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		
		
		public function getArtsListTag($tag, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId) {
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber -1);

			$listArts = array();
			$listArts = $this -> getArtsTag($tag, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			
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
		
		public function getArtsTag($tag, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			$dbFilterFragment = $this -> dbFilterFragment;
			$functions = $this -> functions;

			$listArtsOut = array();
			$listArts = $dbFilterFragment -> getArtsTag($tag, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $functions -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		
		
		public function getArtsListMedium($medium, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId) {
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber -1);

			$listArts = array();
			$listArts = $this -> getArtsMedium($medium, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			
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
		
		public function getArtsMedium($medium, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			$dbFilterFragment = $this -> dbFilterFragment;
			$functions = $this -> functions;

			$listArtsOut = array();
			$listArts = $dbFilterFragment -> getArtsMedium($medium, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $functions -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}

		
		public function getArtsListMakerAndCentury ($pageNumber, $userUniqueId, $makerFilter, $centuryFilter) {
			
			$pageSize = 30;
			$count = $pageSize;
			$offset = $pageSize * ($pageNumber -1);
			
			$string = $makerFilter;
			if (stripos($string, "(") !== false) { $string = substr($string , 0, stripos($string, "(")); }
			if (stripos($string, "[") !== false) { $string = substr($string , 0, stripos($string, "[")); }
			$string = str_replace("Workshop of ", "", $string);
			$string = str_replace("Attributed to ", "", $string);
			$string = str_replace("Style of ", "", $string);
			$string = str_replace(" the Elder", "", $string);
			$string = str_replace("Sir ", "", $string);

			$listArts = array();
			$listArts = $this -> getArtsMakerAndCentury($string, $userUniqueId, $count, $centuryFilter, $offset);
			
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
		
		public function getArtsMakerAndCentury($artMaker, $userUniqueId, $count, $centuryFilter, $offset) {
			$dbFilterFragment = $this -> dbFilterFragment;
			$functions = $this -> functions;

			$listArtsOut = array();
			$listArts = $dbFilterFragment -> getArtsMakerAndCentury($artMaker, $userUniqueId, $count, $centuryFilter, $offset);
			for($i = 0; $i < count($listArts); $i++) {
				$art = $functions -> getArtObject($listArts[$i]);
				$listArtsOut[] = $art;
			}
			return $listArtsOut;
		}
		
		
		

	}

?>