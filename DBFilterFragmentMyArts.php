<?php
	
	require_once 'DBConfig.php';

	class DBFilterFragment extends DBConfig {
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
		

		
		public function getMakersByClassification($keywordFilter, $centuryFilter) {
			
			$listMakers = array();
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$keyword = $keywordFilter;
			$century = '^'.$centuryFilter;

			$sql = "SELECT DISTINCT artistName FROM ArtWorks WHERE objectClassification = :keyword and artistName <> ''
						AND artistName NOT LIKE :makerName1 AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5 AND objectCentury REGEXP :century";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, ':makerName4' => $makerName4,
									':makerName5' => $makerName5, ':keyword' => $keyword, ':century' => $century));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
		}
		
		public function getCenturyByClassification($keywordFilter, $makerFilter) {
			
			$listCentury = array();
			
			$keyword = $keywordFilter;
			$maker = '%'.$makerFilter.'%';

			$sql = "SELECT DISTINCT objectCentury FROM ArtWorks WHERE objectClassification = :keyword and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':keyword' => $keyword, ':maker' => $maker));
			while ($century = $query -> fetchColumn()) {
				if ( !in_array($century, $listCentury) && $century > 2 ) {  $listCentury[] = $century; }
			}
			
			rsort($listCentury);
			return $listCentury;
		}
		
		public function getKeywordByClassification($keywordFilter, $makerFilter, $centuryFilter) {
			
			$listKeyword = array();
			
			$keyword = $keywordFilter;
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			if(empty($makerFilter) && empty($centuryFilter)) { $random = mt_rand(1, 2000); } else { $random = 0; }
			
			$sql = "SELECT artistCulture, objectTitle, objectMediums, objectTags, artistPlaceOfOrigin, objectClassification FROM ArtWorks 
					where objectClassification = :keyword and objectCentury REGEXP :century and artistName LIKE :maker LIMIT :random, 200";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':random', $random, PDO::PARAM_INT);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			while ($obj = $query -> fetchObject()) {
				$objStringMedium .= ", " . implode(", ", array_filter([$obj -> objectMediums]));
				$objStringTag .= ", " . implode(", ", array_filter([$obj -> objectTags, $obj -> artistPlaceOfOrigin, $obj -> objectTitle]));
				$objStringCulture .= ", " . implode(", ", array_filter([$obj -> artistCulture]));
				$objStringClassification .= ", " . implode(", ", array_filter([$obj -> objectClassification]));
			}
			
			$listKeyword = $this -> getListFilterObjectFromString($objStringMedium, "artMedium", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringTag, "artTag", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringCulture, "artCulture", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringClassification, "artClassification", $listKeyword);
			
			return $listKeyword;
		}
		
		
		public function getListFilterObjectFromString($objString, $artType, $listKeyword) {
			
			$keywordArr = str_word_count($objString, 1, " ");
			
			for($i = 0; $i < count($keywordArr); $i++) {
				$keywordStr = $keywordArr[$i];
				if(substr($keywordStr, strlen($keywordStr)-1, 1) == " ") {			// если последний символ - пробел, то убираем его
					$keywordStr = substr($keywordStr , 0, strlen($keywordStr)-1);
				}
				if(substr($keywordStr, 0, 1) == " ") {								// если первый символ - пробел, то убираем его
					$keywordStr = substr($keywordStr , 1, strlen($keywordStr)-1);
				}
				if(substr($keywordStr, strlen($keywordStr)-1, 1) == "'") {			// если последний символ - кавычка, то убираем его
					$keywordStr = substr($keywordStr , 0, strlen($keywordStr)-1);
				}
				if(substr($keywordStr, 0, 1) == "'") {								// если первый символ - кавычка, то убираем его
					$keywordStr = substr($keywordStr , 1, strlen($keywordStr)-1);
				}
				if(substr($keywordStr, strlen($keywordStr)-1, 1) == "-") {			// если последний символ - пробел, то убираем его
					$keywordStr = substr($keywordStr , 0, strlen($keywordStr)-1);
				}
				if(substr($keywordStr, 0, 1) == "-") {								// если первый символ - пробел, то убираем его
					$keywordStr = substr($keywordStr , 1, strlen($keywordStr)-1);
				}
				
				$isBanKeyword = $this -> isBanKeyword($keywordStr);
				if(!$isBanKeyword) {      											// проверка на забанить слово
					$add = true;
					for ($j = 0; $j < count($listKeyword); $j++) {
						if (strtoupper($listKeyword[$j]->text) == strtoupper($keywordStr)) {
							$add = false;
							$listKeyword[$j]->countArts = $listKeyword[$j]->countArts + 1;
						}
					}
					if ($add && strlen($keywordStr) > 0) {
						$keyword = new stdClass();
						$keyword -> text = $keywordStr;
						$keyword -> countArts = 1;
						$keyword -> type = $artType;
						$listKeyword[] = $keyword;
					}
				}
			}
			
			return $listKeyword;
		}
		
		public function isBanKeyword($keywordStr) {
			
			$banKeywords = array("painting", "etc", "print");
			if (in_array($keywordStr, $banKeywords) || strlen($keywordStr) < 3 || substr_count($keywordStr, ' ') > 2) {
				return true;
			} else {
				return false;
			}
		}
		
		
		public function getMakersByCulture($keywordFilter, $centuryFilter) {
			
			$listMakers = array();

			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$century = '^'.$centuryFilter;
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$sql = "SELECT DISTINCT artistName FROM ArtWorks WHERE (artistCulture REGEXP :keyword or artistLongBio REGEXP :keyword) and artistName <> ''
						and artistName NOT LIKE :makerName1 AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5 AND objectCentury REGEXP :century";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, ':makerName4' => $makerName4,
									':makerName5' => $makerName5, ':keyword' => $keyword, ':century' => $century));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
		}
		
		public function getCenturyByCulture($keywordFilter, $makerFilter) {
			
			$listCentury = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';

			$sql = "SELECT DISTINCT objectCentury FROM ArtWorks 
						WHERE (artistCulture REGEXP :keyword or artistLongBio REGEXP :keyword) and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':keyword' => $keyword, ':maker' => $maker));
			while ($century = $query -> fetchColumn()) {
				if ( !in_array($century, $listCentury) && $century > 2 ) {  $listCentury[] = $century; }
			}
			
			rsort($listCentury);
			return $listCentury;
		}
		
		public function getKeywordByCulture($keywordFilter, $makerFilter, $centuryFilter) {
			
			$listKeyword = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT artistCulture, objectTitle, objectMediums, objectTags, artistPlaceOfOrigin, objectClassification FROM ArtWorks 
					where (artistCulture REGEXP :keyword or artistLongBio REGEXP :keyword) and objectCentury REGEXP :century and artistName LIKE :maker LIMIT 200";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			while ($obj = $query -> fetchObject()) {
				$objStringMedium .= ", " . implode(", ", array_filter([$obj -> objectMediums]));
				$objStringTag .= ", " . implode(", ", array_filter([$obj -> objectTags, $obj -> artistPlaceOfOrigin, $obj -> objectTitle]));
				$objStringCulture .= ", " . implode(", ", array_filter([$obj -> artistCulture]));
				$objStringClassification .= ", " . implode(", ", array_filter([$obj -> objectClassification]));
			}
			
			$listKeyword = $this -> getListFilterObjectFromString($objStringMedium, "artMedium", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringTag, "artTag", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringCulture, "artCulture", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringClassification, "artClassification", $listKeyword);
			
			return $listKeyword;
		}
		
		
		public function getMakersByMedium($keywordFilter, $centuryFilter) {
			
			$listMakers = array();

			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$century = '^'.$centuryFilter;
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$sql = "SELECT DISTINCT artistName FROM ArtWorks WHERE objectMediums REGEXP :keyword and artistName <> ''
						and artistName NOT LIKE :makerName1 AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5 AND objectCentury REGEXP :century";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, ':makerName4' => $makerName4,
									':makerName5' => $makerName5, ':keyword' => $keyword, ':century' => $century));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
		}
		
		public function getCenturyByMedium($keywordFilter, $makerFilter) {
			
			$listCentury = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';

			$sql = "SELECT DISTINCT objectCentury FROM ArtWorks WHERE objectMediums REGEXP :keyword and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':keyword' => $keyword, ':maker' => $maker));
			while ($century = $query -> fetchColumn()) {
				if ( !in_array($century, $listCentury) && $century > 2 ) {  $listCentury[] = $century; }
			}
			
			rsort($listCentury);
			return $listCentury;
		}
		
		public function getKeywordByMedium($keywordFilter, $makerFilter, $centuryFilter) {
			
			$listKeyword = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT artistCulture, objectTitle, objectMediums, objectTags, artistPlaceOfOrigin, objectClassification FROM ArtWorks 
					where  objectMediums REGEXP :keyword and objectCentury REGEXP :century and artistName LIKE :maker LIMIT 200";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			while ($obj = $query -> fetchObject()) {
				$objStringMedium .= ", " . implode(", ", array_filter([$obj -> objectMediums]));
				$objStringTag .= ", " . implode(", ", array_filter([$obj -> objectTags, $obj -> artistPlaceOfOrigin, $obj -> objectTitle]));
				$objStringCulture .= ", " . implode(", ", array_filter([$obj -> artistCulture]));
				$objStringClassification .= ", " . implode(", ", array_filter([$obj -> objectClassification]));
			}
			
			$listKeyword = $this -> getListFilterObjectFromString($objStringMedium, "artMedium", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringTag, "artTag", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringCulture, "artCulture", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringClassification, "artClassification", $listKeyword);
			
			return $listKeyword;
		}
		
		
		public function getMakersByTag($keywordFilter, $centuryFilter) {
			
			$listMakers = array();

			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$century = '^'.$centuryFilter;
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$sql = "SELECT DISTINCT artistName FROM ArtWorks 
						WHERE artistName <> '' and (objectTitle REGEXP :keyword OR objectTags REGEXP :keyword OR artistPlaceOfOrigin REGEXP :keyword)
						and artistName NOT LIKE :makerName1 AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5 AND objectCentury REGEXP :century";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':makerName1' => $makerName1, ':makerName2' => $makerName2, ':makerName3' => $makerName3, ':makerName4' => $makerName4,
									':makerName5' => $makerName5, ':keyword' => $keyword, ':century' => $century));
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
		}
		
		public function getCenturyByTag($keywordFilter, $makerFilter) {
			
			$listCentury = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';

			$sql = "SELECT DISTINCT objectCentury FROM ArtWorks 
					WHERE (objectTitle REGEXP :keyword OR objectTags REGEXP :keyword OR artistPlaceOfOrigin REGEXP :keyword) and artistName LIKE :maker ";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':keyword' => $keyword, ':maker' => $maker));
			while ($century = $query -> fetchColumn()) {
				if ( !in_array($century, $listCentury) && $century > 2 ) {  $listCentury[] = $century; }
			}
			
			rsort($listCentury);
			return $listCentury;
		}
		
		public function getKeywordByTag($keywordFilter, $makerFilter, $centuryFilter) {
			
			$listKeyword = array();
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT artistCulture, objectTitle, objectMediums, objectTags, artistPlaceOfOrigin, objectClassification FROM ArtWorks 
					where (objectTitle REGEXP :keyword OR objectTags REGEXP :keyword OR artistPlaceOfOrigin REGEXP :keyword) 
					and objectCentury REGEXP :century and artistName LIKE :maker LIMIT 200";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			while ($obj = $query -> fetchObject()) {
				$objStringMedium .= ", " . implode(", ", array_filter([$obj -> objectMediums]));
				$objStringTag .= ", " . implode(", ", array_filter([$obj -> objectTags, $obj -> artistPlaceOfOrigin, $obj -> objectTitle]));
				$objStringCulture .= ", " . implode(", ", array_filter([$obj -> artistCulture]));
				$objStringClassification .= ", " . implode(", ", array_filter([$obj -> objectClassification]));
			}
			
			$listKeyword = $this -> getListFilterObjectFromString($objStringMedium, "artMedium", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringTag, "artTag", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringCulture, "artCulture", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringClassification, "artClassification", $listKeyword);
			
			return $listKeyword;
		}
		
		
		public function getMakersByCentury($centuryFilter) {
			
			$listMakers = array();
			
			$makerName1 = '%After %';
			$makerName2 = '%Painter%';
			$makerName3 = '%Workshop%';
			$makerName4 = '%Attributed%';
			$makerName5 = '%?%';
			
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT DISTINCT artistName FROM ArtWorks WHERE artistName <> '' 
						and artistName NOT LIKE :makerName1 AND artistName NOT LIKE :makerName2 AND artistName NOT LIKE :makerName3 
						AND artistName NOT LIKE :makerName4 AND artistName NOT LIKE :makerName5 AND objectCentury REGEXP :century ";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':makerName1', $makerName1, PDO::PARAM_STR);
			$query->bindParam(':makerName2', $makerName2, PDO::PARAM_STR);
			$query->bindParam(':makerName3', $makerName3, PDO::PARAM_STR);
			$query->bindParam(':makerName4', $makerName4, PDO::PARAM_STR);
			$query->bindParam(':makerName5', $makerName5, PDO::PARAM_STR);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query -> execute();
			while ($name = $query -> fetchColumn()) {
				$listMakers[] = $name;
			}
			
			return $listMakers;
		}
		
		public function getCenturyByMaker($makerFilter) {
			
			$listCentury = array();
			
			$maker = '%'.$makerFilter.'%';

			$sql = "SELECT DISTINCT objectCentury FROM ArtWorks WHERE artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':maker' => $maker));
			while ($century = $query -> fetchColumn()) {
				if ( !in_array($century, $listCentury) && $century > 2 ) {  $listCentury[] = $century; }
			}
			
			rsort($listCentury);
			return $listCentury;
		}
		
		public function getKeywordByMakerAndCentury($makerFilter, $centuryFilter) {
			
			$listKeyword = array();
			
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			if(empty($makerFilter) && empty($centuryFilter)) { $random = mt_rand(1, 2000); } else { $random = 0; }
			
			$sql = "SELECT artistCulture, objectTitle, objectMediums, objectTags, artistPlaceOfOrigin, objectClassification FROM ArtWorks 
					where objectCentury REGEXP :century and artistName LIKE :maker LIMIT :random, 200";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':random', $random, PDO::PARAM_INT);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			while ($obj = $query -> fetchObject()) {
				$objStringMedium .= ", " . implode(", ", array_filter([$obj -> objectMediums]));
				$objStringTag .= ", " . implode(", ", array_filter([$obj -> objectTags, $obj -> artistPlaceOfOrigin, $obj -> objectTitle]));
				$objStringCulture .= ", " . implode(", ", array_filter([$obj -> artistCulture]));
				$objStringClassification .= ", " . implode(", ", array_filter([$obj -> objectClassification]));
			}
			
			$listKeyword = $this -> getListFilterObjectFromString($objStringMedium, "artMedium", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringTag, "artTag", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringCulture, "artCulture", $listKeyword);
			$listKeyword = $this -> getListFilterObjectFromString($objStringClassification, "artClassification", $listKeyword);
			
			return $listKeyword;
		}
		
		
		
		
		
		
		
		public function getArtsClassificationFilter($classification, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			
			$makerName = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$listArts = array();
			$sqlList = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where objectClassification = :classification and artistName like :makerName
						AND objectCentury REGEXP :century LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sqlList);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':makerName', $makerName, PDO::PARAM_STR);
			$queryList->bindParam(':century', $century, PDO::PARAM_STR);
			$queryList->bindParam(':classification', $classification, PDO::PARAM_STR);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList -> execute();
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;  
			} 
			return $listArts;
		}

		public function getArtsCulture($culture, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			
			$listArts = array();
			$str = '%'.$culture.'%';
			$makerName = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where (artistCulture = :culture or artistLongBio like :str) AND artistName like :makerName AND objectCentury REGEXP :century 
						LIMIT :offset, :count ";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':makerName', $makerName, PDO::PARAM_STR);
			$queryList->bindParam(':century', $century, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList->bindParam(':culture', $culture, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
				
			return $listArts;
		}

		public function getArtsTag($tag, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			
			$listArts = array();
			$str = '[[:<:]]'.$tag.'[[:>:]]';
			$makerName = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where (objectTitle REGEXP :str OR artistPlaceOfOrigin REGEXP :str OR objectTags REGEXP :str ) AND artistName like :makerName AND objectCentury REGEXP :century 
						ORDER BY sno ASC LIMIT :offset, :count ";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':makerName', $makerName, PDO::PARAM_STR);
			$queryList->bindParam(':century', $century, PDO::PARAM_STR);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
				
			return $listArts;
		}

		public function getArtsMedium($medium, $makerFilter, $centuryFilter, $userUniqueId, $count, $offset) {
			
			$listArts = array();
			//$str = '%'.$medium.'%';
			$str = '[[:<:]]'.$medium.'[[:>:]]';
			$makerName = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where objectMediums REGEXP :str and artistName like :makerName AND objectCentury REGEXP :century LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':makerName', $makerName, PDO::PARAM_STR);
			$queryList->bindParam(':century', $century, PDO::PARAM_STR);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
				
			return $listArts;
		}

		public function getArtsMakerAndCentury($artMaker, $userUniqueId, $count, $centuryFilter, $offset) {
			
			$listArts = array();
			
			$str = '%'.$artMaker.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT ArtWorks.*, MyArtsProviders.*,
							(SELECT COUNT(*) FROM MyArtsLikes WHERE MyArtsLikes.artId = ArtWorks.objectID 
							AND MyArtsLikes.artProviderId = ArtWorks.providerID AND MyArtsLikes.userUniqueId = :userUniqueId) AS likes
						FROM ArtWorks
						JOIN MyArtsProviders ON ArtWorks.providerID = MyArtsProviders.providerId
						where artistName like :str AND objectCentury REGEXP :century LIMIT :offset, :count";
			$queryList = $this -> conn -> prepare($sql);
			$queryList->bindParam(':count', $count, PDO::PARAM_INT);
			$queryList->bindParam(':offset', $offset, PDO::PARAM_INT);
			$queryList->bindParam(':century', $century, PDO::PARAM_STR);
			$queryList->bindParam(':userUniqueId', $userUniqueId, PDO::PARAM_STR);
			$queryList->bindParam(':str', $str, PDO::PARAM_STR);
			$queryList -> execute();
			
			while ($art = $queryList -> fetchObject()) {
				$listArts[] = $art;	
			}
			return $listArts;
		}





		public function getArtCountByClassification($keywordFilter, $makerFilter, $centuryFilter) {
			
			$artCount = 0;
			
			$keyword = $keywordFilter;
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE objectClassification = :keyword and objectCentury REGEXP :century and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			$artCount = $query-> fetchColumn();
			
			return $artCount;
		}
		
		public function getArtCountByTag($keywordFilter, $makerFilter, $centuryFilter) {
			
			$artCount = 0;
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE (objectTitle REGEXP :keyword OR artistPlaceOfOrigin REGEXP :keyword OR objectTags REGEXP :keyword ) 
						and objectCentury REGEXP :century and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			$artCount = $query-> fetchColumn();
			
			return $artCount;
		}

		public function getArtCountByMedium($keywordFilter, $makerFilter, $centuryFilter) {
			
			$artCount = 0;
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE objectMediums REGEXP :keyword and objectCentury REGEXP :century and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			$artCount = $query-> fetchColumn();
			
			return $artCount;
		}
		
		public function getArtCountByCulture($keywordFilter, $makerFilter, $centuryFilter) {
			
			$artCount = 0;
			
			$keyword = '[[:<:]]'.$keywordFilter.'[[:>:]]';
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE (artistCulture REGEXP :keyword or artistLongBio REGEXP :keyword) 
						and objectCentury REGEXP :century and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':keyword', $keyword, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			$artCount = $query-> fetchColumn();
			
			return $artCount;
		}
		
		public function getArtCountByMakerAndCentury($makerFilter, $centuryFilter) {
			
			$artCount = 0;
			
			$maker = '%'.$makerFilter.'%';
			$century = '^'.$centuryFilter;
			
			$sql = "SELECT COUNT(1) FROM ArtWorks WHERE objectCentury REGEXP :century and artistName LIKE :maker";
			$query = $this -> conn -> prepare($sql);
			$query->bindParam(':century', $century, PDO::PARAM_STR);
			$query->bindParam(':maker', $maker, PDO::PARAM_STR);
			$query -> execute();
			$artCount = $query-> fetchColumn();
			
			return $artCount;
		}






		

		
	}

?>