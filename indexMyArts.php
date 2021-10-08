<?php

	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);

	require_once 'FunctionsMyArts.php';
	require_once 'FunctionsFilterFragmentMyArts.php';
	require_once 'FunctionsSignInMyArts.php';
	$fun = new Functions();
	$funFilterFragment = new FunctionsFilterFragment();
	$funSignIn = new FunctionsSignIn();
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		$data = json_decode(file_get_contents("php://input"));


		$fun -> insertLogs($data -> userUniqueId, $data -> operation);

		if(isset($data -> operation)) {
			$operation = $data -> operation;

			if(!empty($operation)) {

				if ($operation == 'getArtsList') {
					
					$pageNumber = $data -> pageNumber;
					$userUniqueId = $data -> userUniqueId;

					echo $fun -> getArtsList($pageNumber, $userUniqueId);
						
				} else if ($operation == 'getRecommendations') {
					
					$userUniqueId = $data -> userUniqueId;

					echo $fun -> getRecommendations($userUniqueId);
						
				} else if ($operation == 'artLike') {
					if(isset($data -> art ) && !empty($data -> art) 
						&& isset($data -> userUniqueId) && !empty($data -> userUniqueId) ){
						
						$art = $data -> art;
						$userUniqueId = $data -> userUniqueId;
						$artObjectType = "item";
						
						echo $fun -> isLiked($art, $userUniqueId, $artObjectType);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMyFavorites') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getMyFavorites($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'makerLike') {
					if(isset($data -> maker ) && !empty($data -> maker) 
						&& isset($data -> userUniqueId) && !empty($data -> userUniqueId) ){
						
						$maker = $data -> maker;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> isMakerLiked($maker, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMyFavoritesArtists') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getMyFavoritesArtists($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'museumLike') {
					if(isset($data -> artProvider ) && !empty($data -> artProvider) 
						&& isset($data -> userUniqueId) && !empty($data -> userUniqueId) ){
						
						$artProvider = $data -> artProvider;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> isArtProviderLiked($artProvider, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'artWriteDimens') {
					if(isset($data -> art) && !empty($data -> art)){
						
						$art = $data -> art;
						
						echo $fun -> artWriteDimens($art);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getSuggest') {
					if(isset($data -> suggestQuery) && !empty($data -> suggestQuery) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$suggestQuery = $data -> suggestQuery;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getSuggest($suggestQuery, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getInitialSuggests') {
					if(isset($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getInitialSuggests($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'deleteSuggestQuery') {
					if(isset($data -> suggestQuery) && !empty($data -> suggestQuery)
							&& isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						$suggestQuery = $data -> suggestQuery;
						
						echo $fun -> deleteSuggest($userUniqueId, $suggestQuery);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtsListSearch') {
					if(isset($data -> searchQuery) && !empty($data -> searchQuery)
								&& isset($data -> pageNumber) && !empty($data -> pageNumber)
										&& isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$searchQuery = $data -> searchQuery;
						$pageNumber = $data -> pageNumber;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getArtsListSearch($searchQuery, $pageNumber, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtsListMaker') {
					if(isset($data -> makerFilter) && !empty($data -> makerFilter)
								&& isset($data -> pageNumber) && !empty($data -> pageNumber)
										&& isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$artMaker = $data -> makerFilter;
						$pageNumber = $data -> pageNumber;
						$userUniqueId = $data -> userUniqueId;
						
						$keywordFilter = $data -> keywordFilter;
						$keywordType = $data -> keywordType;
						
						if(!empty($keywordFilter) && !empty($keywordType )){
							echo $funFilterFragment -> getArtsListFilter($keywordFilter, $artMaker, "", $pageNumber, $userUniqueId, $keywordType, null);
						} else {
							echo $fun -> getArtsListMaker($artMaker, $pageNumber, $userUniqueId);
						}

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMakerObject') {
					if(isset($data -> maker ) && !empty($data -> maker) 
						&& isset($data -> userUniqueId) && !empty($data -> userUniqueId) ){
						
						$maker = $data -> maker;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getMakerObject($maker, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtCountMakerByKeyword') {
					if(isset($data -> keywordFilter) && isset($data -> makerFilter)  && isset($data -> keywordType) ){
						
						$keywordFilter = $data -> keywordFilter;
						$makerFilter = $data -> makerFilter;
						$keywordType = $data -> keywordType;
						
						echo $funFilterFragment -> getArtCountFilterFragment($keywordFilter, $makerFilter, "", $keywordType, "");

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtsListFilter') {
					if(isset($data -> keywordFilter) && isset($data -> makerFilter) && isset($data -> centuryFilter)&& isset($data -> keywordType) 
								&& isset($data -> pageNumber) && !empty($data -> pageNumber) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						//&& isset($data -> oldList)
						$pageNumber = $data -> pageNumber;
						$userUniqueId = $data -> userUniqueId;
						
						$keywordFilter = $data -> keywordFilter;
						$makerFilter = $data -> makerFilter;
						$centuryFilter = $data -> centuryFilter;
						
						$keywordType = $data -> keywordType;
						$oldList = $data -> oldList;
						
						echo $funFilterFragment -> getArtsListFilter($keywordFilter, $makerFilter, $centuryFilter, $pageNumber, $userUniqueId, $keywordType, $oldList);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getListFilters') {
					if(isset($data -> keywordFilter) && isset($data -> makerFilter) && isset($data -> centuryFilter) 
								&& isset($data -> keywordType) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						$keywordFilter = $data -> keywordFilter;
						$makerFilter = $data -> makerFilter;
						$centuryFilter = $data -> centuryFilter;
						$keywordType = $data -> keywordType;
						
						echo $funFilterFragment -> getListFilters($keywordFilter, $makerFilter, $centuryFilter, $keywordType, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtCountFilterFragment') {
					if(isset($data -> keywordFilter) && isset($data -> makerFilter) && isset($data -> centuryFilter) 
								&& isset($data -> keywordType) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						$keywordFilter = $data -> keywordFilter;
						$makerFilter = $data -> makerFilter;
						$centuryFilter = $data -> centuryFilter;
						$keywordType = $data -> keywordType;
						
						echo $funFilterFragment -> getArtCountFilterFragment($keywordFilter, $makerFilter, $centuryFilter, $keywordType, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getArtsListMuseum') {
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
					
				} else if ($operation == 'getMuseumInfo') {
					if(isset($data -> artProviderId) && !empty($data -> artProviderId) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						$artProviderId = $data -> artProviderId;
						
						echo $fun -> getMuseumInfo($userUniqueId, $artProviderId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMuseumMakersList') {
					if(isset($data -> artProviderId) && !empty($data -> artProviderId) && isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						$artProviderId = $data -> artProviderId;
						
						echo $fun -> getMuseumMakersList($userUniqueId, $artProviderId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getExploreList') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getExploreList($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getFoldersList') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getFoldersList($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMyFavoritesToCreateFolder') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> getMyFavoritesToCreateFolder($userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'createFolder') {
					if(isset($data -> folder) && !empty($data -> folder)){
						
						$folder = $data -> folder;
						
						echo $fun -> checkIsFolderExist($folder);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'saveArtToFolder') {
					if(isset($data -> folder) && !empty($data -> folder) && isset($data -> art) && !empty($data -> art)){
						
						$folder = $data -> folder;
						$art = $data -> art;
						$userUniqueId = $data -> userUniqueId;
						
						echo $fun -> saveArtToFolder($folder, $art, $userUniqueId);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'deleteFolder') {
					if(isset($data -> folder) && !empty($data -> folder)
							&& isset($data -> userUniqueId) && !empty($data -> userUniqueId)){
						
						$userUniqueId = $data -> userUniqueId;
						$folder = $data -> folder;
						
						echo $fun -> deleteFolder($userUniqueId, $folder);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getFolderArts') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)
						&& isset($data -> folder) && !empty($data -> folder)){
						
						$userUniqueId = $data -> userUniqueId;
						$folder = $data -> folder;
						
						echo $fun -> getFolderArts($userUniqueId, $folder);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getFolderForEdit') {
					if(isset($data -> userUniqueId) && !empty($data -> userUniqueId)
						&& isset($data -> folder) && !empty($data -> folder)){
						
						$userUniqueId = $data -> userUniqueId;
						$folder = $data -> folder;
						
						echo $fun -> getFolderForEdit($userUniqueId, $folder);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getFilterMakers') {
					if(isset($data -> charFilter) && !empty($data -> charFilter)
							&& isset($data -> pageNumber) && !empty($data -> pageNumber)
								&& isset($data -> userUniqueId) && !empty($data -> userUniqueId)
									&& isset($data -> centuryFilter) && !empty($data -> centuryFilter)){
						
						$filter = $data -> charFilter;
						$pageNumber = $data -> pageNumber;
						$userUniqueId = $data -> userUniqueId;
						$century = $data -> centuryFilter;
						
						echo $fun -> getFilterMakers($filter, $pageNumber, $userUniqueId, $century);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getCultureList') {
					if(isset($data -> pageNumber) && !empty($data -> pageNumber)){
						
						$pageNumber = $data -> pageNumber;
						
						echo $fun -> getCultureList($pageNumber);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getTagList') {
					if(isset($data -> pageNumber) && !empty($data -> pageNumber)
						&& isset($data -> charFilter) && !empty($data -> charFilter)){
						
						$pageNumber = $data -> pageNumber;
						$filter = $data -> charFilter;
						
						echo $fun -> getTagList($pageNumber, $filter);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getMediumList') {
					if(isset($data -> pageNumber) && !empty($data -> pageNumber)){
						
						$pageNumber = $data -> pageNumber;
						
						echo $fun -> getMediumList($pageNumber);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if ($operation == 'getClassificationList') {
					if(isset($data -> pageNumber) && !empty($data -> pageNumber)){
						
						$pageNumber = $data -> pageNumber;
						
						echo $fun -> getClassificationList($pageNumber);

					} else {
						echo $fun -> getMsgInvalidParam();
					}
					
				} else if($operation == 'signInUser'){
					if(isset($data -> user ) && !empty($data -> user)){
						$user = $data -> user;
						$userEmail = $user -> userEmail;
						
						if ($funSignIn -> checkUserExist($userEmail)) {
							echo $funSignIn -> loginUser($user);
						} else {
							echo $funSignIn -> registerUser($user);
						}
					} else {
						echo $fun -> getMsgInvalidParam();
					}

				} else if($operation == 'deleteUserAccount'){
					if(isset($data -> userUniqueId ) && !empty($data -> userUniqueId)){
						$userUniqueId = $data -> userUniqueId;
						
						echo $funSignIn -> deleteUser($userUniqueId);
					} else {
						echo $fun -> getMsgInvalidParam();
					}

				} else if($operation == 'editUserAccount'){
					if(isset($data -> user ) && !empty($data -> user)){
						$user = $data -> user;
						
						echo $funSignIn -> editUser($user);
					} else {
						echo $fun -> getMsgInvalidParam();
					}

				} else if($operation == 'getPolicyPages'){
					echo $funSignIn -> getPolicyPages();

				}
				
			} else {
				echo $fun -> getMsgParamNotEmpty();
			}
			
		} else {
			echo $fun -> getMsgInvalidParam();
		}
		
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		//echo "MyArts API". "<br />";
		
		//echo $funFilterFragment -> getArtsListFilter("Paintings", "", 19, 1, "asdhff", "artClassification", null);
		//echo $fun -> getListFilters("cachenez", "", "", "artTag", "asdhff");
		
		//echo $fun -> insertHarvardArts();
		
		//echo $fun -> insertFrenchArts();
		//echo $fun -> checkImgUrl();
		//echo $fun -> getExploreList("");
		//echo $fun -> updateClassificationChicago();
		//echo $fun -> getRecommendations("");
		//echo $fun -> getArtsList($pageNumber, $userUniqueId);
		
		//echo $fun -> insertArts();
		//echo $fun -> insertTags();
		
		//echo $fun -> getTagList(1, 'All');
		//echo $fun -> getArtsList(1, '');
		//echo $fun -> getFilterMakers('All', 1, '', 'All');
		//echo $fun -> insertMakers();
		//echo $fun -> getArtsListMaker("Rembrandt", 1, "gggggh");
		
		
	}


?>