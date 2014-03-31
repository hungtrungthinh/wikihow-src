<?

// Extend this class for an individual stats wiget
abstract class StandingsIndividual {

	var $mLeaderboardKey = null;
	var $mStats = null;
	var $mContext = null;

	function setContext(IContextSource $context) {
		$this->mContext = $context;
	}

	/**
	 *  Gets the raw table, useful for ajax calls that just want the innards
	 *
	 */
	function getStandingsTable() {
		$this->fetchStats();

		$rank = $this->mStats['standing'];
		if ($rank == 0) {
			$rank = "N/A";
		}


		$today 	= number_format($this->mStats['today'], 0, '.', ",");
		$week 	= number_format($this->mStats['week'], 0, '.', ",");
		$all	= number_format($this->mStats['all'], 0, '.', ",");
		wfLoadExtensionMessages('Standings');

		$table = "<table>
		<tr>
			<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>" . wfMessage('iia_stats_today_label')->text() . "</a></td>
			<td class='stats_count' id='iia_stats_today_{$this->mLeaderboardKey}'>{$today}</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}?period=7'>" . wfMessage('iia_stats_week_label')->text() . "</a></td>
			<td class='stats_count' id='iia_stats_week_{$this->mLeaderboardKey}'>{$week}</td>
		</tr>";
		if ($this->showTotal()) {
			$table .= "<tr>
					<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>" . wfMessage('iia_stats_total_label')->text() . "</a></td>
					<td class='stats_count' id='iia_stats_all_{$this->mLeaderboardKey}'>{$all}</td></tr>";
		}
		$table .= "<tr>
			<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>" . wfMessage('iia_stats_rank_label')->text() . "</a></td>
			<td class='stats_count' id='iia_stats_standing_{$this->mLeaderboardKey}'>{$rank}</td>
		</tr>
		</table>";
		return $table;
	}

	/**
	 *  Sometimes it doesn't make sense to show the total of all time
	 *  such as quick edits because we are only looking at the RC table
	 *  Subclasses can override this.
	 *
	 */
	function showTotal() {
		return true;
	}

	/**
	 * addStatsWidget
	 * add stats widget to right rail
	 **/
	function addStatsWidget() {
		$fname = "StandingsIndividual::addStatsWidget";
		wfProfileIn($fname);

		if (!$this->mContext) {
			wfDeprecated( __METHOD__ . ' without setting context (will fallback to globals)');
			global $wgUser;
			$sk = $wgUser->getSkin();
		} else {
			$sk = $this->mContext->getSkin();
		}

		$display = "<div class='iia_stats'>
		<h3>{$this->getTitle()}</h3>
		<div id='iia_individual_table_{$this->mLeaderboardKey}'>" . $this->getStandingsTable() .
		"</div></div>";

		$sk->addWidget( $display );
		wfProfileOut($fname);
	}

	/**
	 * fetchStats
	 * get the stats in an array
	 **/
	function fetchStats() {
		global $wgUser, $wgMemc, $wgLang;
		$fname = "StandingsIndividual::fetchStats";
		wfProfileIn($fname);

		$dbr = wfGetDB(DB_SLAVE);

		$ts_today = date('Ymd',strtotime('today')) . '000000';
		$ts_week = date('Ymd',strtotime('7 days ago')) . '000000';

		$timecorrection = $wgUser->getOption( 'timecorrection' );
		$ts_today = $wgLang->userAdjust( $ts_today, $timecorrection );
		$ts_week = $wgLang->userAdjust( $ts_week, $timecorrection );

		$tbl = $this->getTable();

		$today 	= $dbr->selectField($tbl, 'count(*)',  $this->getOpts($ts_today), __METHOD__);
		$week 	= $dbr->selectField($tbl, 'count(*)',  $this->getOpts($ts_week), __METHOD__);

		if ($this->showTotal()) {
			$all = $dbr->selectField($tbl, 'count(*)',  $this->getOpts(), __METHOD__);
		}

		$standing = $this->getStanding($wgUser);

		$s_arr = array(
			'today' => $today,
			'week' => $week,
			'all' => $all,
			'standing' => $standing,
		);

		$this->mStats = $s_arr;
		wfProfileOut($fname);
		return $this->mStats;
	}

	function getStanding($user) {
		$group = $this->getGroupStandings();
		return $group->getStanding($user);
	}

	public abstract function getTitle();
	public abstract function getOpts($ts = null);
	public abstract function getGroupStandings();

}

// Extend this class if you a leaderboard type group standings
abstract class StandingsGroup {

	var $mCacheKey = null;

	// how long should the standings array be in the cache? 5min default
	var $mCacheExpiry = 300;
	var $mLeaderboardKey = null;
	var $mContext = null;
	
	function __construct($cacheId) {
		$this->mCacheKey = wfMemcKey($cacheId);
	}

	function setContext(IContextSource $context) {
		$this->mContext = $context;
	}

	/**
	 * getStandingsTable
	 * returns just the raw table for the standings, useful for ajax calls
	 **/
	function getStandingsTable() {
		global $wgUser;
		$fname = "StandingsGroup::getStandingsTable";
		wfProfileIn($fname);

		$display = "<table>";

		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$data = $this->getStandingsFromCache() ;
		$count = 0;
		foreach($data as $key => $value) {
			$u = new User();
			$u->setName($key);
			if (($value > 0) && ($key != '')) {

				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}

				$id = "";
				if ($wgUser->getName() == $u->getName()) {
					$id = "id='iia_stats_group'";
				}
				$display .="<tr><td class='leader_image'>{$img}</td><td class='leader_name'>"
						. Linker::link($u->getUserPage(), $u->getName()) . "</td><td class='leader_count' {$id}>{$value}</td></tr>";
				$count++;
			}
			if ($count > 5) {break;}

		}

		$display .= "
		</table>";
		wfProfileOut($fname);
		return $display;
	}

	/**
	 * 	This returns an array of users, in order for their standings.
	 *	If it's no in the cache, it builds it and puts it in the cache.
	 */
	function getStandingsFromCache() {
		global $wgMemc;
		$fname = "StandingsGroup::getStandingsFromCache";
		wfProfileIn($fname);
		$standings = $wgMemc->get($this->mCacheKey);
		if (!is_array($standings)) {
			$dbr = wfGetDB(DB_SLAVE);
			$ts = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
			$sql = $this->getSQL($ts);
			$res = $dbr->query($sql, __METHOD__);
			$standings = array();
			$field = $this->getField();
			foreach ($res as $row) {
				$standings[$row->$field] = $row->C;
			}
			$wgMemc->set($this->mCacheKey, $standings, $this->mCacheExpiry);
			wfDebug("Standings: didn't get the cache set {$this->mCacheKey} {$this->mCacheExpiry} " . print_r($standings, true) . "\n");
		} else {
			wfDebug("Standings: DID get the cache\n");
		}
		wfProfileOut($fname);
		return $standings;
	}

	/**
	 * 	Returns where a particular users stands in this group
	 *  0 if they aren't in the top X
	 *
	 */
	function getStanding($user) {
		$fname = "StandingsGroup::getStanding";
		wfProfileIn($fname);
		$standings = $this->getStandingsFromCache();
		$index = 1;
		foreach ($standings as $s => $c) {
			if ($s == $user->getName()) {
				wfProfileOut($fname);
				return $index;
			}
			$index++;
		}
		wfProfileOut($fname);
		return 0;
	}

	/**
	 * addStandingsWidget
	 * Generates the actual HTML for the widget, and adds the necessary CSS to the skin
	 *
	 **/
	function addStandingsWidget() {
		global $wgUser, $wgOut;
		$fname = "StandingsGroup::addStandingsWidget";
		wfProfileIn($fname);

		if (!$this->mContext) {
			wfDeprecated( __METHOD__ . ' without setting context (will fallback to globals)');
			global $wgUser;
			$sk = $wgUser->getSkin();
		} else {
			$sk = $this->mContext->getSkin();
		}

		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/Leaderboard.css?rev=') . WH_SITEREV . "'; /*]]>*/</style>");

		$display = "
		<div class='iia_stats'>
		<h3>".$this->getTitle() . "</h3>
		<div id='iia_standings_table'>
		".$this->getStandingsTable()."
		</div> " . $this->getUpdatingMessage() . "
		</div>";

		$sk->addWidget( $display );
		wfProfileOut($fname);
	}


	public abstract function getSQL($ts);
	public abstract function getField();
	public abstract function getTitle();

	/**
	 *
	 * Takes a row number and returns the count for that row.
	 * If there are not enough rows, returns the count for the
	 * last row that exists.
	 * If there are NO rows, returns 0
	 *
	 */
	public function getStandingByIndex($rowNum){
		$standings = $this->getStandingsFromCache();
		$index = 1;
		$c = 0;
		foreach ($standings as $s => $c) {
			if($index == $rowNum)
				return $c;
			$index++;
		}

		return $c;
	}

	/**
	 * You can override this if you don't want your standings widget to update
	 * automatically
	 */
	function getUpdatingMessage() {
		$msg = "<p class='bottom_link' style='text-align:center; padding-top:5px'>
		Updating in <span id='stup'>10</span> minutes
		</p>";
		return $msg;
	}


}

class IntroImageStandingsGroup extends StandingsGroup {

	function __construct() {
		parent::__construct("imageadder_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text, count(*) as C from recentchanges WHERE
			rc_timestamp > '{$ts}' and rc_comment='Edit via [[Special:IntroImageAdder|Image Picker]]: Added an image'
			group by rc_user_text order by C desc limit 25;";
		return $sql;
	}

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMessage('iia_standings_title')->text();
	}
}

class RequestsAnsweredStandingsGroup extends StandingsGroup {

	function __construct() {
		parent::__construct("requestsanswered_standings");
	}

	function getSQL($ts) {
		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if(sizeof($bots) > 0) {
			$dbr = wfGetDB(DB_SLAVE);
			$bot = " AND fe_user NOT IN (" . $dbr->makeList($bots) . ", '0') ";
		}

		$sql = "SELECT page_title,
				fe_user_text as user_text,
				count(*) as C
 				FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title
 				WHERE fe_timestamp >= '{$ts}' AND st_isrequest IS NOT NULL" . $bot .
				" group by user_text order by C limit 25";

		return $sql;
	}

	function getField() {
		return "user_text";
	}

	function getTitle() {
		return wfMessage('ra_standings_title')->text();
	}
}

class ArticleWrittenStandingsGroup extends StandingsGroup {

	function __construct() {
		parent::__construct("articlewritten_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT 'Newpages' AS type,
				count(*) AS C,
				rc_title AS title,
				rc_cur_id AS cur_id,
				rc_user AS \"user\",
				rc_user_text AS user_text
			FROM recentchanges, page
			WHERE rc_cur_id=page_id AND rc_timestamp >= '". $ts ."'
			AND rc_user_text != 'WRM' AND rc_user != '0' AND rc_new = 1 AND rc_namespace = 0 AND page_is_redirect = 0
			group by user_text order by C limit 25";
		return $sql;
	}

	function getField() {
		return "user_text";
	}

	function getTitle() {
		return wfMessage('iia_standings_title')->text();
	}
}

class NABStandingsGroup extends StandingsGroup {

	function __construct() {
		parent::__construct("nab_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT nap_user_ci, count(*) as C from newarticlepatrol WHERE
			nap_timestamp_ci > '{$ts}' and nap_patrolled=1
			group by nap_user_ci order by C desc limit 25;";
		return $sql;
	}

	function getField() {
		return "nap_user_ci";
	}

	function getTitle() {
		return wfMessage('nab_standings_title')->text();
	}
}

class VideoStandingsGroup extends StandingsGroup {

	function __construct() {
		parent::__construct("videoadder_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT va_user_text, count(*) as C from videoadder WHERE
			va_timestamp >= '{$ts}' AND (va_skipped_accepted = '0' OR va_skipped_accepted = '1')
			group by va_user ORDER BY C desc";
		return $sql;
	}

	function getField() {
		return "va_user_text";
	}

	function getTitle() {
		return wfMessage('va_topreviewers')->text();
	}
}


class QCStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("qc_standings");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "
			SELECT user_name, SUM(C) as C FROM
				( (SELECT user_name, count(*) as C from qc_vote left join ".$wgSharedDB.".user on qcv_user=user_id
					WHERE qc_timestamp > '{$ts}' group by qcv_user order by C desc limit 25)
				UNION
				(SELECT user_name, count(*) as C from qc_vote_archive left join ".$wgSharedDB.".user on qcv_user=user_id
					WHERE qc_timestamp > '{$ts}' group by qcv_user order by C desc limit 25) ) t1
			group by user_name order by C desc limit 25";

		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('qc_standings_title')->text();
	}
}

class NFDStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("nfd_standings");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C from nfd_vote left join ".$wgSharedDB.".user on nfdv_user=user_id WHERE
			nfdv_timestamp > '{$ts}'
			group by nfdv_user order by C desc limit 25;";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('nfd_standings_title')->text();
	}
}

class QuickEditStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("quickedit_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text,count(*) as C ".
			"FROM recentchanges ".
			"WHERE rc_comment like 'Quick edit while patrolling' and rc_timestamp >= '$ts' ".
			"GROUP BY rc_user_text ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMessage('rcpatrolstats_leaderboard_title')->text();
	}
}

class RCPatrolStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("rcpatrol_standings");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging FORCE INDEX (times) LEFT JOIN ".$wgSharedDB.".user ON log_user = user_id ".
			"WHERE log_type = 'patrol' AND log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER BY C DESC LIMIT 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('rcpatrolstats_leaderboard_title')->text();
	}
}

class SpellcheckerStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("spellchecker_standings");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging left join ".$wgSharedDB.".user on log_user = user_id ".
			"WHERE log_type = 'spellcheck' and log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('spellcheckerstats_leaderboard_title')->text();
	}
}


class QuickEditStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "rc_quick_edits";
	}

	function getTable() {
		return "recentchanges";
	}

	function showTotal() {
		return false;
	}

	function getTitle() {
		return wfMessage('quickedits_stats')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['rc_user_text'] =$wgUser->getName();
		$opts[] = "rc_comment like 'Quick edit while patrolling' ";
		if ($ts) {
			$opts[]= "rc_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new QuickEditStandingsGroup();
	}

}

class RCPatrolStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "rc_edits";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('rcpatrolstats_currentstats')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='patrol';
		$opts['log_deleted'] = 0;
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new RCPatrolStandingsGroup();
	}

}

class SpellcheckerStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "spellchecked";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('spellcheckerstats_currentstats')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='spellcheck';
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new SpellcheckerStandingsGroup();
	}

}

class IntroImageStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "images_added";
	}

	function getTable() {
		return "image";
	}

	function getTitle() {
		return wfMessage('iia_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['img_user_text'] =$wgUser->getName();
		if ($ts) {
			$opts[]= "img_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new IntroImageStandingsGroup();
	}

}

class TipsPatrolStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "tiptool_indiv1";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('tipspatrol_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getId();
		$opts['log_type'] = "newtips";
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new TipsPatrolStandingsGroup();
	}

}

class WelcomeWagonStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "welcomewagon_indiv1";
	}

	function getTable() {
		return "welcome_wagon_messages";
	}

	function getTitle() {
		wfLoadExtensionMessages('WelcomeWagon');
		return wfMessage('welcomewag_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['ww_from_user_id'] = $wgUser->getId();
		if ($ts) {
			$opts[]= "ww_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new WelcomeWagonStandingsGroup();
	}

}

class MethodGuardianStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "methodguardiantool_indiv1";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('methodguardian_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getId();
		$opts['log_type'] = "methgua";
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new MethodGuardianStandingsGroup();
	}

}

class MethodEditorStandingsIndividual extends StandingsIndividual {

    function __construct() {
        $this->mLeaderboardKey = "methodeditor";
    }

    function getTable() {
        return MethodEditor::LOGGING_TABLE_NAME;
    }

    function getTitle() {
        return wfMessage('methodeditor_stats_title')->text();
    }

    function getOpts($ts = null) {
        global $wgUser;
        $opts = array();
        $opts['mel_user'] =$wgUser->getId();
        if ($ts) {
            $opts[]= "mel_timestamp >'{$ts}'";
        }
        return $opts;
    }

    function getGroupStandings() {
        return new MethodEditorStandingsGroup();
    }

}

class CategorizationStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "articles_categorized";
	}

	function getTable() {
		return "recentchanges";
	}

	function getTitle() {
		return wfMessage('categorization_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['rc_user_text'] =$wgUser->getName();
		$opts[] = "rc_comment like 'categorization'";
		if ($ts) {
			$opts[]= "rc_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new CategorizationStandingsGroup();
	}

}

class VideoStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "videos_reviewed";
	}

	function getTable() {
		return "videoadder";
	}

	function getTitle() {
		return wfMessage('va_yourstats')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['va_user_text'] =$wgUser->getName();
		if ($ts) {
			$opts[]= "va_timestamp >'{$ts}'";
		}
		$opts[] = "(va_skipped_accepted = '0' OR va_skipped_accepted = '1')";
		return $opts;
	}

	function getGroupStandings() {
		return new VideoStandingsGroup();
	}

}

class QCStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "qc";
	}

	function getTable() {
		return "qc_vote";
	}

	function getTitle() {
		wfLoadExtensionMessages('qc');
		return wfMessage('qc_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['qcv_user']=$wgUser->getID();
		if ($ts) {
			$opts[]= "qc_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new QCStandingsGroup();
	}

}

class NFDStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "nfd";
	}

	function getTable() {
		return "nfd_vote";
	}

	function getTitle() {
		wfLoadExtensionMessages('nfdGuardian');
		return wfMessage('nfd_stats_title')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['nfdv_user']=$wgUser->getID();
		if ($ts) {
			$opts[]= "nfdv_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new NFDStandingsGroup();
	}

}

// EditFinder / Article Repair Shop tool
class EditFinderStandingsIndividual extends StandingsIndividual {

	function __construct($type = 'format') {
		$this->mLeaderboardKey = "repair_".$type;
		$this->mEFType = $type;
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('ef_statind_title')->text()." - ".ucfirst(wfMessage('statind_' . $this->mEFType)->text());
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		// Log types can only be 10 chars
		$opts['log_type'] ='EF_' . substr($this->mEFType, 0, 7);
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new EditFinderStandingsGroup($this->mEFType);
	}

}

class EditFinderStandingsGroup extends StandingsGroup  {
	function __construct($type = 'format') {
		global $wgRequest;

		$typeParam = strtolower($wgRequest->getVal('type'));
		if (strlen($typeParam)) {
			$type = $typeParam;
		}
		parent::__construct("editfinder_" . $type . "_standings");
		$this->mEFType = $type;
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging left join ".$wgSharedDB.".user on log_user = user_id ".
			"WHERE log_type = 'EF_" . substr($this->mEFType, 0, 7) . "' and log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('editfinder_leaderboard_title')->text();
	}
}

class TipsPatrolStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("tiptool_standings2");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging left join ".$wgSharedDB.".user on log_user = user_id ".
			"WHERE log_type = 'newtips' and log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('tipspatrol_leaderboard_title')->text();
	}
}

class WelcomeWagonStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("welcomewagon_standings2");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM welcome_wagon_messages left join ".$wgSharedDB.".user on ww_from_user_id = user_id ".
			"WHERE ww_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('welcomewag_leaderboard_title')->text();
	}
}

class MethodGuardianStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("methodguardian_standings2");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging left join ".$wgSharedDB.".user on log_user = user_id ".
			"WHERE log_type = 'methgua' and log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('methodguardian_leaderboard_title')->text();
	}
}

class MethodEditorStandingsGroup extends StandingsGroup  {
    function __construct() {
        parent::__construct("methodeditor_standings2");
    }

    function getSQL($ts) {
		global $wgSharedDB;
        $sql = "SELECT user_name, count(*) as C ".
            "FROM " . MethodEditor::LOGGING_TABLE_NAME . " left join ".$wgSharedDB.".user on mel_user = user_id ".
            "WHERE mel_timestamp >= '$ts' ".
            "GROUP BY user_name ORDER by C desc limit 25";
        return $sql;
    }

    function getField() {
        return "user_name";
    }

    function getTitle() {
        return wfMessage('methodeditor_leaderboard_title')->text();
    }
}

class CategorizationStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("categorization_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text,rc_title, count(*) as C ".
			"FROM recentchanges ".
			"WHERE rc_comment like 'categorization' and rc_timestamp >= '$ts' AND rc_user_text != 'WRM' ".
			"GROUP BY rc_user_text ORDER BY C DESC limit 25" ;
		return $sql;
	}

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMessage('categorization_leaderboard_title')->text();
	}
}

