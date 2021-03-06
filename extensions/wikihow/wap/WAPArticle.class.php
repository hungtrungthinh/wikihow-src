<?
abstract class WAPArticle {
	var $dbType = null;
	var $page_id = null;
	var $user_id = null;
	var $user_text = null;
	var $ca = null;
	var $reserved_timestamp = null;
	var $catinfo = null;
	var $tags = null;
	var $completed = null;
	var $completed_timestamp = null;
	var $lang_code = null;

	const STATE_INVALID = 'invalid';
	const STATE_EXCLUDED = 'excluded';
	const STATE_UNASSIGNED = 'unassigned';
	const STATE_ASSIGNED = 'assigned';
	const STATE_COMPLETED = 'completed';
	const STATE_NEW = 'new';

	protected function init(&$row, $dbType) {
		$this->dbType = $dbType;
		$this->page_id = $row->ct_page_id;
		$this->page_title = $row->ct_page_title;
		$this->lang_code = $row->ct_lang_code;
		$this->user_id = $row->ct_user_id;
		$this->user_text = $row->ct_user_text;
		$this->reserved_timestamp = $row->ct_reserved_timestamp;
		$this->catinfo = intVal($row->ct_catinfo);
		$this->categories = $row->ct_categories;
		$this->completed = $row->ct_completed;
		$this->completed_timestamp = $row->ct_completed_timestamp;
		$this->tag_list = $row->ct_tag_list;
	}

	abstract public static function newFromId($aid, $langCode, $dbType);

	abstract public static function newFromDBRow(&$row, $dbType);

	abstract public static function newFromUrl($url, $langCode, $dbType);

	protected static function getDBRow($aid, $langCode,  $dbType) {
		// Use master to prevent replication delay issues
		$dbw = wfGetDB(DB_MASTER);
		$table = WAPDB::getInstance($dbType)->getWAPConfig()->getArticleTableName();
		$res = $dbw->select($table, array('*'), array('ct_page_id' => $aid, 'ct_lang_code' => $langCode), __METHOD__);
		return $dbw->fetchObject($res);
	}

	protected static function getDBRowFromPageTitle($pageTitle, $langCode, $dbType) {
		// Use master to prevent replication delay issues
		$dbw = wfGetDB(DB_MASTER);
		$table = WAPDB::getInstance($dbType)->getWAPConfig()->getArticleTableName();
		$res = $dbw->select($table, array('*'), array('ct_page_title' => $pageTitle, 'ct_lang_code' => $langCode), __METHOD__);
		return $dbw->fetchObject($res);
	}

	public function exists() {
		return intVal($this->page_id) > 0;
	}	

	public function getUserId() {
		return $this->user_id;
	}

	public function getUserText() {
		return $this->user_text;
	}

	public function getUser() {
		if (intVal($this->user_id) == 0) {
			$this->ca = null;
		} else if (empty($this->ca)) {
			$userClass = WAPDB::getInstance($this->dbType)->getWAPConfig()->getUserClassName();
			$this->ca = $userClass::newFromId($this->user_id, $this->dbType);
		}
		return $this->ca;
	}

	public function getPageTitle() {
		return $this->page_title;
	}

	public function getLangCode() {
		return $this->lang_code;
	}

	public function getUrl() {
		return Misc::makeUrl($this->page_title);
	}

	public function isCompleted() {
		return (bool)$this->completed;
	}
	public function getCompletedTimestamp() {
		return $this->completed_timestamp;
	}

	public function getReservedTimestamp() {
		return $this->reserved_timestamp;
	}

	public function getCompletedDate() {
		$ts = $this->completed_timestamp;
		$date = "";
		if (!empty($ts)) {
			$date =  substr($ts, 0, 4) . "-" . substr($ts, 4, 2) . "-" . substr($ts, 6, 2);
		}
		return $date;
	}

	public function getReservedDate() {
		$ts = $this->reserved_timestamp;
		return substr($ts, 0, 4) . "-" . substr($ts, 4, 2) . "-" . substr($ts, 6, 2);
	}

	// JTODO abstract function or aware of config?
	public function getTags() {
		if (is_null($this->tags)) {
			$wa = WAPDB::getInstance($this->dbType)->getArticleTagDB();
			$this->tags = $wa->getTagsOnArticle($this->page_id, $this->lang_code);
		}
		return $this->tags;
	}

	public function getRawTags() {
		$rawTags = array();
		if (!empty($this->tag_list)) {
			$rawTags = explode(",", $this->tag_list);
		}
		return $rawTags;
	}

	public function getTagList() {
		return $this->tag_list;
	}

	public function getTopLevelCategories() {
		return explode(",", $this->categories);
	}

	public function isReservable() {
		return $this->user_id ==  0;
	}

	public function isAssigned() {
		return $this->user_id != 0 && !$this->completed;
	}

	public function getArticleId() {
		return $this->page_id;
	}

	public function getPageId() {
		return $this->page_id;
	}

	public function getCatInfo() {
		return $this->catinfo;
	}

	public function getViewableTags(WAPUser &$user) {
		if ($user->inGroup(WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowAdminGroupName())) {
			return $this->getTags();
		} else {
			$tags = array();
			foreach ($this->getTags() as $aTag) {
				foreach ($user->getTags() as $uTag) {
					if ($aTag['raw_tag'] == $uTag['raw_tag']) {
						$tags[] = $aTag;
					}	
				}
			}
			return $tags;
		}
	}
}
