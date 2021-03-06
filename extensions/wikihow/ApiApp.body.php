<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/extensions/wikihow/mobile/JSLikeHTMLElement.php");
require_once("$IP/extensions/wikihow/common/composer/vendor/electrolinux/phpquery/phpQuery/phpQuery.php");

class ApiApp extends ApiBase {
	private static $SubCommands = array (
		'article',
		'credits',
		'featured',
		'search',
		'psearch',
		'langs'
	);

	public function __construct($main, $action) {
		parent::__construct($main, $action);
		$this->mSubCommands = self::$SubCommands;
	}

	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();
		$command = $params['subcmd'];

		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';
		// try to catch fatal errors to not break app
		ini_set('display_errors', 0);
		register_shutdown_function(function () {
			$error = error_get_last();
			if ($error !== null && ($error['type'] == E_ERROR || $error['type'] == E_CORE_ERROR)) {
				header('Content-Type: application/json');
				$result = array('app' => array('error' => print_r($error, true)));
				print json_encode($result);
				exit;
			}
		});

		switch ($command) {
		case 'article':
			$id = $params['id'];
			$name = $params['name'];
			$random = in_array( strtolower($params['random']), array('1', 't', 'y', 'true') );
			$title = null;
			if ($random) {
				$title = Randomizer::getRandomTitle();
			} elseif ($id) {
				$title = Title::newFromID($id);
			} elseif ($name) {
				$title = Title::newFromURL($name);
			}
			if (!$title || !$title->exists()) {
				$error = 'Title not found';
			} else {
				$revid = !$random ? $params['oldid'] : 0;
				if (!$revid) {
					$title = WikihowArticleEditor::resolveRedirects($title);
					if (!$title) {
						$error = 'Could not find redirect';
					}
				}
				if (!$error) {
					$articleResult = AppDataFormatter::parseArticle($title, $revid);
					$creditsResult = AppDataFormatter::getArticleCredits($title, &$articleResult);
					$articleResult['random'] = $random;

//print_r($articleResult);exit;
					$result->addValue( null, $module, $articleResult );
					$result->addValue( null, $module, $creditsResult );
				}
			}
			break;
		case 'credits':
			$id = $params['id'];
			$name = $params['name'];
			$title = null;
			if ($id) {
				$title = Title::newFromID($id);
			} elseif ($name) {
				$title = Title::newFromURL($name);
			}
			if (!$title || !$title->exists()) {
				$error = 'Title not found';
			} else {
				$creditsResult = AppDataFormatter::getArticleCredits($title);
				$result->addValue( null, $module, $creditsResult );
			}
			break;
		case 'featured':
			$num = $params['num'];
			$results = AppDataFormatter::featuredArticles($num);
			$result->addValue( null, $module, array('articles' => $results) );
			break;
		case 'search':
			$first = $params['first'];
			$num = $params['num'];
			$q = $params['q'];
			$qp = $params['qp'];
			$noCache = $params['nocache'];
			$results = null;
			if ($q) {
				$results = AppDataFormatter::search($q, $first, $num);
			} elseif ($qp) {
				$results = AppDataFormatter::searchPartial($qp, $num, $noCache);
			} else {
				$error = 'Either q (query) or qp (query prefix) param is required';
			}
			if ($results) {
				$result->addValue( null, $module, array('articles' => $results) );
			}
			break;
		case 'psearch':
			$q = $params['q'];
			$wt = $params['wt'];
			$rows = $params['rows']?:10;

			$contents = self::websolrSearch($q, $wt, $rows);
			echo $contents;
			exit();
			break;

		case 'langs':
			$langs = AppDataFormatter::getLangEndpoints();
			$result->addValue( null, $module, array('langs' => $langs) );
			break;
		default:
			$error = 'no subcmd specified';
			break;
		}

		if ($error) {
			$result->addValue( null, $module, array('error' => $error) );
		}

		global $wgUseSquid, $wgSquidMaxage;
		if ($wgUseSquid) {
			$this->getMain()->setCacheMode("anon-public-user-private");
			$this->getMain()->setCacheMaxAge($wgSquidMaxage);
			$this->getMain()->setCacheControl(array("must-revalidate"=>true));
		}

		return true;
	}

	function websolrSearch($q, $wt, $rows) {
		global $wgServer;
		$q = urlencode($q);
		$wsUrl = 'http://ec2-west.websolr.com/solr/d4901f648d5/select?sort=page_counter+desc&defType=edismax&fl=id,title,image_58x58&wt='.$wt.'&q='.$q.'&rows='.$rows;

		if ($wgServer == "http://www.wikihow.com") {
			$wsUrl = 'http://index.websolr.com/solr/1040955300c/select?defType=edismax&mm=100%25&fl=id,title,image_58x58&bf=scale(map(page_counter,0,0,5),1,2)&wt='.$wt.'&q='.$q.'&rows='.$rows;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $wsUrl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$contents = curl_exec($ch);
		curl_close($ch);
		return $contents;
	}

	function getAllowedParams() {
		return array(
			'subcmd' => array (
				ApiBase :: PARAM_TYPE => $this->mSubCommands
			),

			'id' => 0, // for article
			'name' => '', // for article
			'oldid' => 0, // for article
			'random' => '', // for article

			'q' => '', // for search
			'qp' => '', // for prefix search
			'first' => 0, // for search
			'num' => array (
				ApiBase :: PARAM_DFLT => 10,
				ApiBase :: PARAM_TYPE => 'integer'
			),
			'rows' => 10, // for psearch
			'wt' => 'json', // for psearch
			'_' => '',
			'nocache' => '', // for psearch
			);
	}
	public function getDescription() {
		return array ( 'This module is used to get json repr of articles and do searches for articles.');
	}

	public function getParamDescription() {
		return array (
			'subcmd' => 'The subcommand you are performing',
			'id' => 'id of the article. overrides name',
			'name' => 'name of the article',
			'oldid' => 'You can specify this for article subcmd',
			'random' => 'get random article. overrides id and name',
			'q' => 'for search subcmd',
			'qp' => 'for prefix searching',
			'first' => 'for search and featured',
			'num' => 'for search and featured',
			'rows' => 'for psearch',
			'wt' => 'writer for psearch',
			'_' => 'for random -- this param is thrown away, but used to make the url unique'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=app&subcmd=article&format=jsonfm&name=Kiss'
		);
	}

	function getVersion() {
		return '0.9.0';
	}

}

class AppDataFormatter {

	const SEARCH_THUMB_WIDTH = 58;
	const SEARCH_THUMB_HEIGHT = 58;

	static function search($q, $first, $num) {
		global $wgMemc;
		$cachekey = wfMemcKey('apisearch', $q);
		$res = $wgMemc->get($cachekey);
		if (is_array($res)) {
			$cached_num = $res['num'];
			if ($cached_num >= $num) {
				unset($res['num']);
				$time += microtime(true);
				return array_slice($res, 0, $num);
			}
		}

		// for now the search is just partial search results formatted
		$pResults = self::searchPartial($q, $num);
		$results = array();
		foreach($pResults as $res) {
			$results[] = Title::newFromID($res['id']);
		}
		$results = self::formatResults($results);

		// add num to results for use in caching only
		$results['num'] = $num;
		$wgMemc->set($cachekey, $results, 3600);
		unset($results['num']);
		return $results;
	}

	static function searchPartial($qp, $num, $noCache = false) {
		global $wgMemc;

		$howto = strtolower(wfMsg('howto', ''));
		$qp = trim(preg_replace('@(^' . preg_quote($howto) . '|howto)@i', '', strtolower($qp)));

		$cachekey = wfMemcKey('searchpartial', $qp);
		if (!$noCache) {
			$res = $wgMemc->get($cachekey);
		}
		if (is_array($res)) {
			$cached_num = $res['num'];
			if ($cached_num >= $num) {
				unset($res['num']);
				$time += microtime(true);
				return array_slice($res, 0, $num);
			}
		}

		$res = ApiApp::websolrSearch($qp, "json", $num);
		$res = json_decode($res, true);
		$res = $res['response']['docs'];

		// add num to results for use in caching only
		$res['num'] = $num;
		$wgMemc->set($cachekey, $res, 3600);
		unset($res['num']);

		return $res;
	}

	static function featuredArticles($num) {
		$titles = array();
		$fas = FeaturedArticles::getTitles($num);
		foreach ($fas as $fa) {
			$titles[] = $fa['title'];
		}
		return self::formatResults($titles);
	}

	private static function getAbstract($title) {
		$abstract = '';

		$ami = new ArticleMetaInfo($title);
		if ($ami) {
			// meta description
			$abstract = $ami->getFacebookDescription();
		}

		if (!$abstract) {
			$rev = Revision::newFromTitle($title);
			if ($rev) {
				$wikitext = $rev->getText();
				$abstract = Article::getSection($wikitext, 0);
			}
		}

		return $abstract;
	}

	private static function formatResults($results) {
		$ret = array();
		if ($results) {
			foreach ($results as $title) {
				$image = self::getArticleImage($title);
				$abstract = self::getAbstract($title);
				$rev = GoodRevision::newFromTitle($title, $title->getArticleId());
				if ($rev) {
					$revid = $rev->latestGood();
				}
				if ($image['obj']) {
					// Required to make a cropped square image
					$imageObj = $image['obj'];
					$srcWidth = $imageObj->getWidth();
					$srcHeight = $imageObj->getHeight();
					$heightPreference = $srcWidth > $srcHeight;
					$thumb = WatermarkSupport::getUnwatermarkedThumbnail($imageObj, self::SEARCH_THUMB_WIDTH, self::SEARCH_THUMB_HEIGHT, true, true, $heightPreference);
					if (!($thumb instanceof MediaTransformError)) {
						$thumburl = ArticleHTMLParser::uriencode(wfGetPad($thumb->getUrl()));
					}
				}
				unset($image['obj']);
				$ret[] = array(
					'id' => intval($title->getArticleId()),
					'revision_id' => $revid,
					'title' => $title->getText(),
					'fulltitle' => wfMsg('howto', $title->getText()),
					'url' => self::makeFullURL( $title->getPartialUrl() ),
					'image' => $image,
					'image_58x58' => $thumburl,
					'abstract' => $abstract,
				);
			}
		}
		return $ret;
	}

	static function getArticleCredits($title, $parsed=null) {
		$result = array();
		if (!$parsed) {
			$parsed = self::parseArticle($title, 0);
		}
		if ($parsed && is_array($parsed['sections'])) {
			// Format Sources and Citations
			foreach ($parsed['sections'] as $key=>$section) {
				if ($section['type'] == 'sources') {
					$result['article_sources'] = array(
						'list' => isset($section['list']) ? $section['list'] : array(),
						'numbered' => isset($section['numbered']) ? $section['numbered'] : array(),
					);
					// remove the sources section for the original here because we are adding it to the article_sources section
					unset($parsed['sections'][$key]);
				}
			}
			// reset the array that may have had its keys messed with
			$parsed['sections'] = array_values($parsed['sections']);

			// Include image categories etc
			$result['categories'] = self::getCategories($title);
			$result['authors'] = self::getAuthors($title);

			// Gather the image credits
			$credits = array('uploaders' => array(), 'licenses' => array());
			self::gatherImageCredits($parsed, $credits);
			$result['image_sources'] = array(
				'uploaders' => array_values(array_unique($credits['uploaders'])),
				'licenses' => array_values(array_unique($credits['licenses'])));
		}

		return $result;
	}

	static function getAuthors($title) {
		$authorsKeys = ArticleAuthors::getAuthors( $title->getArticleID() );
		$authors = array_keys($authorsKeys);
		//$authors = array_slice($authors, 0, min($authors, 100));
		return $authors;
	}

	static function getCategories($title) {
		$removePrefixes = function (&$arr) use (&$removePrefixes) {
			if (is_array($arr)) {
				$newArr = array();
				foreach ($arr as $k => $v) {
					$title = Title::newFromURL($k);
					$newk = $title->getText();
					$newArr[$newk] = $v;
					if ($v) {
						$removePrefixes($newArr[$newk]);
					}
				}
				$arr = $newArr;
			}
		};
		$hasWikihowLeaf = function($arr) use (&$hasWikihowLeaf) {
			foreach ($arr as $k => $v) {
				if ($k == 'WikiHow') return true;
				if (is_array($v) && $v && $hasWikihowLeaf($v)) return true;
			}
			return false;
		};
		$flattenTree = function ($arr) use (&$flattenTree) {
			$out = array();
			foreach ($arr as $k => $v) {
				$out[] = $k;
				if (is_array($v) && $v) {
					$out = array_merge($out, $flattenTree($v));
				}
			}
			return $out;
		};

		$parents = $title->getParentCategoryTree();
		$removePrefixes($parents);
		$out = array();
		foreach ($parents as $parent => $subTree) {
			if ($hasWikihowLeaf($subTree)) {
				continue;
			}
			$out[] = $parent;
			$out = array_merge($out, $flattenTree($subTree));
		}
		return $out;
	}

	static function gatherImageCredits($parsed, &$credits) {
		foreach ($parsed as $k => $v) {
			if(!is_array($v) && ($k == "url" || $k == "html")) {
				if (strpos(strtolower($v), 'jpg') === FALSE && strpos(strtolower($v), 'png') === FALSE) {
					continue;
				}
				if ($k == "html") {
					$gotImage = true;
					$pqDoc = PHPQuery::newDocument($v);
					$v = pq("img")->attr("src");
				} elseif ($k == "url") {
					$gotImage = true;
				}
			}
			if ($gotImage) {
				$imageName = explode("/",$v);
				$imageName = $imageName[count($imageName) - 2];
				$titleName = "Image:".$imageName;
				$title = Title::newFromText($imageName, NS_IMAGE);
				if ($title && $title->isKnown()) {
					$article = new Article($title);
					if ($article) {
						$user = $article->getUserText();
						if ($user) $user .= ' (wikiHow)';
						$wikitext = $article->getContent();
						if (preg_match('@{{(cc-by[^}]+)}}@', $wikitext, $m)) {
							$license = $m[1];
							// From http://creativecommons.org/licenses/
							$licenseTexts = array(
								'cc-by-sa-nc-2.5-self' => 'Creative Commons Share-Alike Non-Commercial Attribution',
							);
							if (isset($licenseTexts[ $license ])) {
								$license = $licenseTexts[$license];
							}
						} elseif (preg_match('@{{flickr[^}]+\|([^|}]+)}}@', $wikitext, $m)) {
							$user = $m[1] . ' (Flickr)';
						}
					}
				}
				if ($user) $credits['uploaders'][] = $user;
				if ($license) $credits['licenses'][] = $license;
			} elseif (is_array($v)) {
				self::gatherImageCredits($v, $credits);
			}
		}
	}

	static function loadTitleRevision($title, $revid = 0) {
		if (!$revid) {
			$good = GoodRevision::newFromTitle($title, $title->getArticleId());
			if ($good) {
				$revid = $good->latestGood();
			}
		}
		// TODO these two are to be uncommented only for debugging
		//$revision = Revision::newFromTitle($title);
		//$revid = $revision->getId();
		$article = new Article($title, $revid);
		if (!$article) return null;

		$article->loadContent();
		$rev = $article->mRevision;
		return $rev ? $rev : null;
	}

	// does just what it says
	static function addImageToIntro(&$parsed, $title) {
		if (!($parsed && $parsed['sections'])) {
			return;
		}

		// todo resuse the title image from somewhere else
		// so we don't process this twice.. although it is
		// probably pretty fast since the rev is loaded already
		$image = Wikitext::getTitleImage($title, true);
		if ($image) {
			$image = ArticleHTMLParser::getImageDetails($image);
		}

		if (!$image || !$image['url']) {
			// try any image on the page
			$image = AppDataFormatter::findAnyImage($parsed['sections']);
		}

		if (!$image) {
			return;
		}

		foreach($parsed['sections'] as &$section) {
			if ($section["type"] =="intro") {
				$section['image'] = $image;
			}
		}
	}


	// Remove some unnecessary (from perspective of "article" API subcmd) 
	// elements/nodes from parse tree
	static function cleanParsedForView(&$parsed, $contextKey) {
		// Remove image objects
		foreach ($parsed as $k => &$v) {
			if ($contextKey == 'image' && $k == 'obj') {
				unset($parsed[$k]);
			} elseif (is_array($v)) {
				self::cleanParsedForView($v, $k);
			}
		}
	}

	static function findAnyImage($sections) {
		foreach ($sections as $section) {
			if ($section['type'] != 'steps') continue;
			for ($j = count($section['methods']) - 1; $j >= 0; $j--) {
				$method = $section['methods'][$j];
				if (!isset($method['steps'])) continue;
				for ($i = count($method['steps']) - 1; $i >= 0; $i--) {
					$step = $method['steps'][$i];
					if (isset($step['image'])) {
						return $step['image'];
					}
				}
			}
		}
		return null;
	}
	// TODO this was taken from WikiHowSkin so could refactor it back into getGalleryImage
	static function getCategoryImageFile($title) {
		$catmap = Categoryhelper::getIconMap();

		// if page is a top category itself otherwise get top
		if (isset($catmap[urldecode($title->getPartialURL())])) {
			$cat = urldecode($title->getPartialURL());
		} else {
			$cat = Categoryhelper::getTopCategory($title);

			//INTL: Get the partial URL for the top category if it exists
			// For some reason only the english site returns the partial
			// URL for getTopCategory
			if (isset($cat) && $wgLanguageCode != 'en') {
				$title = Title::newFromText($cat);
				if ($title) {
					$cat = $title->getPartialURL();
				}
			}
		}

		if (isset($catmap[$cat])) {
			$image = Title::newFromText($catmap[$cat]);
			$file = wfFindFile($image, false);
		} else {
			$image = Title::makeTitle(NS_IMAGE, "Book_266.png");
			$file = wfFindFile($image, false);
			if(!$file) {
				$file = wfFindFile("Book_266.png");
			}
		}
		return $file;
	}

	static function getArticleImage($title, $sections=array()) {
		// calling get title image with skip parser = true is faster even though comments indicate otherwise
		$image = Wikitext::getTitleImage($title, true);
		if ($image) {
			$image = ArticleHTMLParser::getImageDetails($image);
		}

		if (!$image || !$image['url']) {
			// try any image on the page
			$image = self::findAnyImage($sections);
		}

		if (!$image || !$image['url']) {
			// still no image? get category image
			$image = self::getCategoryImageFile($title);
			if ($image) {
				$image = ArticleHTMLParser::getImageDetails($image);
			}
		}

		return $image?: array('obj' => '', 'url' => '', 'large' => '');
	}

	static function parseArticle($title, $revid) {
		$rev = self::loadTitleRevision($title, $revid);

		$sectionParser = new ApiSectionParser($title, $rev);
		$sections = $sectionParser->parse();

		$abstract = self::getAbstract($title);

		$result = array(
			'id' => intval($title->getArticleID()),
			'revision_id' => $rev->getId(),
			'title' => $title->getText(),
			'fulltitle' => wfMsg('howto', $title->getText()),
			'url' => self::makeFullURL( $title->getPartialUrl() ),
			'image' => self::getArticleImage($title, $sections),
			'abstract' => $abstract,
			'sections' => $sections,
		);

		AppDataFormatter::addImageToIntro($result, $title);
		AppDataFormatter::cleanParsedForView($result, '');

		return $result;
	}

	private static function makeFullURL($partialURL) {
		global $wgLanguageCode;
		$baseURL = Misc::getLangBaseURL($wgLanguageCode);
		return $baseURL . '/' . $partialURL;
	}

	public static function getLangEndpoints() {
		global $wgActiveLanguages;
		$langs = array();
		foreach (array_merge($wgActiveLanguages, array('en')) as $langCode) {
			$langs[$langCode] = array(
				'code' => $langCode,
				'endpoint' => Misc::getLangBaseURL($langCode) . '/api.php');
		}
		return $langs;
	}

}

class ApiSectionParser {
	
	var $title, $rev, $html;
	var $device, $imageNsText;

	function __construct($title, $rev) {
		$this->title = $title;
		$this->rev = $rev;
	}

	private function loadHtml() {
		global $wgOut, $wgParser;

		$pOpts = $wgOut->parserOptions();
		$pOpts->setTidy(true);
		$pOut = $wgParser->parse($this->rev->getText(), $this->title, $pOpts, true, true, $this->rev->getId());
		$html = $pOut->mText;
		$pOpts->setTidy(false);

		// munge steps first
		$opts = array('no-ads' => true);
		$this->html = WikihowArticleHTML::postProcess($html, $opts);
	}

	function parse() {
		global $wgContLang;

		$this->loadHtml();

		$platforms = MobileWikihow::getPlatformConfigs();
		$this->device = $platforms['iphoneapp'];

		$this->imageNsText = $wgContLang->getNsText(NS_IMAGE);

		$sections = $this->parseArticleHtml($this->html);
		return $sections;
	}

	// Copied (shamefully) and adapted from MobileHtmlBuilder::parseNonMobileArticle()
	private function parseArticleHtml(&$articleHtml) {

		$sectionMap = array(
			wfMsg('Intro') => 'intro',
			wfMsg('Ingredients') => 'ingredients',
			wfMsg('Steps') => 'steps',
			wfMsg('Video') => 'video',
			wfMsg('Tips') => 'tips',
			wfMsg('Warnings') => 'warnings',
			wfMsg('relatedwikihows') => 'relatedwikihows',
			wfMsg('sourcescitations') => 'sources',
			wfMsg('thingsyoullneed') => 'thingsyoullneed',
			wfMsg('article_info') => 'article_info',
		);

		$doc = self::htmlToDoc($articleHtml);
		$xpath = new DOMXPath($doc);

		// Delete #featurestar node
		$node = $doc->getElementById('featurestar');
		if (!empty($node)) {
			$node->parentNode->removeChild($node);
		}

		// Remove #newaltmethod node
		$node = $doc->getElementById('newaltmethod');
		if( !empty($node)) {
			   $node->parentNode->removeChild($node);
		}
		
		// Remove all "Edit" links
		$nodes = $xpath->query('//a[@id = "gatEditSection"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// Resize youtube video
		$nodes = $xpath->query('//embed');
		foreach ($nodes as $node) {
			// Get youtube link
			$src = $node->attributes->getNamedItem('src')->nodeValue;
			if (!$this->device['show-youtube'] || stripos($src, 'youtube.com') === false) {
				$youtubeLink = '';
			} else {
				$youtubeLink = $src;
			}

			// Delete video section node
			$parent = $node->parentNode;
			$grandParent = $parent->parentNode;
			if ($grandParent && $parent) {
				$grandParent->removeChild($parent);
			}
		}

		// Remove templates from intro so that they don't muck up
		// the text and images we extract
		$nodes = $xpath->query('//div[@class = "template_top"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// create a php query doc to operate on in pq() calls
		$pqDoc = PHPQuery::newDocument($doc);

		// remove table of contents
		if(pq('table#toc')->length) {
			$toc = pq('table#toc');
			$toc->remove();
		}

		// creates sections array to be filled up
		$sections = array();
		// get intro data and remove it
		$intro = ArticleHTMLParser::processIntro($this->imageNsText);
		$intro['type'] = "intro";
		$introDoc = $this->processGeneric($intro['html']);
		$intro['html'] = $introDoc->html();
		$sections[] = $intro;

		// Get rid of the <span> element to standardize the html for the
		// next dom query
		$nodes = $xpath->query('//div/span/a[@class = "image"]');
		foreach ($nodes as $a) {
			$parent = $a->parentNode;
			$grandParent = $parent->parentNode;
			$grandParent->replaceChild($a, $parent);
		}

		// Change the width attribute from any tables with a width set.
		// This often happen around video elements.
		$nodes = $xpath->query('//table/@width');
		foreach ($nodes as $node) {
			$width = preg_replace('@px\s*$@', '', $node->nodeValue);
			if ($width > $this->device['screen-width'] - 20) {
				$node->nodeValue = $this->device['screen-width'] - 20;
			}
		}

		// Surround step content in its own div. We do this to support other features like checkmarks
		$nodes = $xpath->query('//div[@id="steps"]/ol/li');
		foreach ($nodes as $node) {
			$node->innerHTML = '<div class="step_content">' . $node->innerHTML . '</div>';
		}

		
		// Remove quiz
		$nodes = $xpath->query('//div[@class = "quiz_cta_2"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		$nodes = $xpath->query('//div[@class = "quiz_cta"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// Remove quiz header
		$nodes = $xpath->query('//h3/span[text()="Quiz"]');
		foreach ($nodes as $node) {
			$parentNode = $node->parentNode;
			$parentNode->parentNode->removeChild($parentNode);
		}

		$html = self::docToHtml($doc);

		$sectionsHtml = explode('<h2>', $html);
		unset($sectionsHtml[0]); // remove leftovers from intro section
		foreach ($sectionsHtml as $i => &$html) {
			$html = '<h2>' . $html;
			$pqDoc = phpQuery::newDocumentHTML($html);
			$headingElement = pq(".mw-headline:first");
			$heading = $headingElement->html();
			if ($heading) {
				$section = array();
				$type = null;
				foreach($sectionMap as $key=>$value) {
					if (strpos($heading, $key) !== FALSE) {
						$type = $value;
						break;
					}
				}
				// remove the section header since we got the info from it we needed
				pq(".mw-headline:first")->parent()->remove();
				$html = pq('')->html();

				$section = array( 'heading' => $heading, 'type' => $type);
				$doc = $this->processGeneric($html);
				if ($type == 'steps') {
					$section['methods'] = $this->processSteps($doc);
				} elseif ($type == 'video') {
					if ($youtubeLink) {
						$section['video'] = $youtubeLink;
						$vid = pq('#video td');
						$section['html'] = trim($vid->html());
					} else {
						continue;
					}
				} elseif (in_array($type, array('thingsyoullneed', 'ingredients'))) {
					$list = $this->processListWithHeaders($doc);
					$section = array_merge( $section, $list );
				} elseif (in_array($type, array('tips', 'warnings'))) {
					$list = $this->processListWithHeaders($doc);
					$section = array_merge( $section, $list );
				} elseif ($type == 'relatedwikihows') {
					$list = $this->processList($doc);
					$section = array_merge( $section, $list );
					if (is_array($section['list'])) {
						$articles = $this->processRelatedWikihows($section['list']);
						if ($articles) {
							unset($section['html']);
							unset($section['list']);
							$section['articles'] = $articles;
						}
					}
				} elseif ($type == 'sources') {
					$list = $this->processListHTML($doc);
					$section = array_merge( $section, $list );
				} else {
					$text = trim($doc->text());
					$section['html'] = $doc->html();
					if (empty($text) || empty($section['html'])) {
						continue;
					}
					if (empty($section['type'])) {
						unset($section['type']);
					}
				}
				$sections[] = $section;
			}
		}
		return $sections;
	}

	private function flattenSpaces($html) {
		return preg_replace('@(\s|\n)+@', ' ', $html);
	}

	private function processGeneric($html) {
		// remove extraneous whitespace and convert "\n" to " "
		$html = self::flattenSpaces($html);

		$doc = phpQuery::newDocumentHTML($html);

		// convert all <br>s to "\n"
		foreach (pq('br') as $node) {
			pq($node)->replaceWith("\n");
		}

		// remove "class" attribute from bold tags
		foreach (pq('b') as $node) {
			pq($node)->removeAttr('class');
		}

		// replace all reference html with <cite> nodes
		foreach (pq('sup.reference') as $node) {
			$pq = pq($node);
			$text = $pq->text();
			$num = intval( preg_replace('@^\[(\d+)\]$@', '$1', $text) );
			if ($num > 0) {
				$citeHTML = '<cite num="' . $num . '">' . $text . '</cite>';
				$pq->replaceWith($citeHTML);
			}
		}

		// transform all links to add articleid="1234" if they
		// are internal, keep the href if they are external, and remove 
		// altogether if outside those sets
		foreach (pq('a') as $node) {
			$pq = pq($node);

			$subImg = pq('img', $node);
			if ($subImg->length) {
				continue;
			}

			$href = $pq->attr('href');
			$title = $pq->attr('title');
			$parentClass = $pq->parent()->attr('class');
			if ((!$href || !$title) && ($parentClass != 'reference-text')) {
				$pq->remove();
				continue;
			}

			$removeAttrs = array('class', 'style', 'name', 'id', 'rel');
			if (!preg_match('@^https?://@', $href)) {
				$removeAttrs[] = 'href';
				$titleAttr = $pq->attr('title');
				$id = 0;
				if ($titleAttr) {
					$titleObj = Title::newFromText($titleAttr);
					if ($titleObj) {
						$id = $titleObj->getArticleID();
					}
				}
				if ($id > 0) {
					$pq->attr('articleid', $id);
				} else {
					$removeAttrs[] = 'title';
				}
			} else {
				$removeAttrs[] = 'title';
			}

			foreach ($removeAttrs as $attr) {
				$pq->removeAttr($attr);
			}
		}

		// Remove all captions
		pq('.caption')->remove();

		return $doc;
	}

	private function pullOutWikiVideo(&$whvid) {
		$ret = null;
		if ($whvid->length) {
			$lrgimg = ArticleHTMLParser::uriencode(trim( pq('div[id^=lrgimgurl]', $whvid)->text() ));
			$smlimg = ArticleHTMLParser::uriencode(trim( pq('div[id^=smlimgurl]', $whvid)->text() ));
			$vid = ArticleHTMLParser::uriencode(trim( pq('div[id^=vidurl-whvid]', $whvid)->text() ));
			if ($lrgimg && $smlimg && $vid) {
				$ret = array('lrgimg' => $lrgimg, 'smlimg' => $smlimg, 'vid' => $vid);
			}
			$whvid->remove();
		}
		return $ret;
	}

	function modifyImageTags($stepNode) {
		// Pull out any modify the tags of any remaining images
		foreach (pq('.mwimg', $stepNode) as $node) {
			$img = pq('img', $node);
			$a = pq('a.image:first', $node);

			if ($img->length && $a->length) {
				$img = ArticleHTMLParser::pullOutImage($a, $this->imageNsText, false);

				$newTag = '<img src="' . htmlspecialchars($img['url']).
					'" width="' . htmlspecialchars($img['width']).
					'" height="' . htmlspecialchars($img['height']);

				if ($img['large']) {
					$newTag .= '" lrgsrc="' . htmlspecialchars($img['large']).
					'" large_width="' . htmlspecialchars($img['large_width']).
					'" large_height="' . htmlspecialchars($img['large_height']);
				}

				if ($img['original']) {
					$newTag .= '" originalsrc="' . htmlspecialchars($img['original']).
						'" original_width="' . htmlspecialchars($img['original_width']).
						'" original_height="' . htmlspecialchars($img['original_height']);
				}

				$newTag .= '" />';

				pq($node)->replaceWith($newTag);
			}
		}
	}

	private function processStepContent($node) {
		$steps = array();
		foreach (pq('div.step_content', $node) as $stepNode) {
			$step = array();

			// Pull out step number
			$numNode = pq('div.step_num:first', $stepNode);
			if ($numNode->length) {
				$step['num'] = $numNode->text();
				$numNode->remove();
			}

			$imgNode = null;
			// pull out any top level images and videos to be the 'hero'
			foreach (pq($stepNode)->children() as $children) {
				$pqNode = pq($children);

				if ($pqNode->is('.mwimg')) {
					$imgNode = pq('a.image:first', $pqNode);
				} else if ($pqNode->is("b")) {
					//special case- the first image might end up inside a bold tag if the
					// first sentence wasn't punctuated correctly
					if ($pqNode->children()->is('.mwimg')) {
						$imgNode = pq('a.image:first', $pqNode);
					}
				}
				if (!$imgNode && $pqNode->attr('class') =='whvid_cont') {
					$vid = $this->pullOutWikiVideo($pqNode);
					if ($vid) {
						$step['whvid'] = $vid;
					}
				}
			}

			if ($imgNode->length) {
				$image = ArticleHTMLParser::pullOutImage($imgNode, $this->imageNsText);
				if ($image) {
					$step['image'] = $image;
				}
			}

			$this->modifyImageTags($stepNode);

			// Pull out any modify the tags of any remaining videos
			foreach (pq('.whvid_cont', $stepNode) as $node) {
				$node = pq($node);
				if ($node->length) {
					$vid = trim( pq('div[id^=vidurl-whvid]', $node)->text() );
				}
				pq($node)->replaceWith('<video><source src="'.$vid.'"</></video>');
			}

			// Change all <br> nodes to be newlines
			foreach (pq('br', $stepNode) as $node) {
				pq($node)->replaceWith("\n");
			}

			// Remove all "empty" nodes
			ArticleHTMLParser::removeEmptyNodes(&$stepNode);

			$divStep = trim($this->divFirstSentence(pq($stepNode)->html()));
			pq($stepNode)->html($divStep);
			$step['summary'] = trim(strip_tags(pq('#firstSentence')->html(), "<a>"));
			pq('#firstSentence')->remove();
			$step['html'] = trim(pq($stepNode)->html());

			$steps[] = $step;
		}
		return $steps;

	}

	static function divFirstSentence($htmlText) {
		$punct = "!\.\?\:"; # valid ways of ending a sentence for bolding

		$htmlparts = preg_split("@(<[^>]*>)@im", $htmlText,
			0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$dummy = 0;
		$incaption = false;
		$apply_div = false;
		$the_big_step = $next;
		$p = '<div id="firstSentence">';
		while ($x = array_shift($htmlparts)) {
			# if it's a tag, just append it and keep going
			if (strpos($x, '<') === 0) {
				//tag
				$p .= $x;
				if ($x == '<span class="caption">') {
					$incaption = true;
				} elseif ($x == "</span>" && $incaption) {
					$incaption = false;
				}
				continue;
			}
			# put the closing </div> in if we hit the end of the sentence
			if (!$incaption) {
				if (!$apply_div && trim($x) != "") {
					$apply_div = true;
				}
				if ($apply_div) {
					$x = preg_replace("@([{$punct}])@im", '$1</div>', $x, 1, $closecount);
				}
			}

			$p .= $x;

			if ($closecount > 0) {
				break;
			}
			$dummy++;
		}

		# get anything left over
		$p .= implode("", $htmlparts);
		return $p;
	}
	private function processSteps($doc) {
		$steps = pq('#steps');

		$methods = array();
		if (!$steps->length) {
			return array('html' => $html);
		}

		$newDoc = phpQuery::newDocument('<div id="steps" />');
		$newSteps = pq('#steps');

		$name = '';
		$method = pq('<div class="method" />');
		$newSteps->append($method);
		$methods[] = array('name' => $name, 'method' => $method);
		foreach ($steps->children() as $node) {
			$pqNode = pq($node);
			if ($pqNode->is('h3')) {
				$name = pq('span', $pqNode)->html();
				$method = pq('<div class="method" />');
				$newSteps->append($method);
				$methods[] = array('name' => $name, 'method' => $method);
			} else {
				$method->append($pqNode);
			}
		}

		// Remove first "method" if it has an empty name and
		// little text
		if (count($methods)) {
			$text = trim($methods[0]['method']->text());
			if (!$methods[0]['name'] && strlen($text) < 10) {
				// remove this mostly empty un-named first method
				$methods[0]['method']->remove();
				unset($methods[0]);
				// reset array indexes
				$methods = array_values($methods);
			}
		}

		$parts = 0;
		foreach ($methods as $key=>&$method) {
			$ret = WikihowArticleEditor::removeMethodNamePrefix($method['name']);
			if ($ret['has_parts']) $parts++;

			// Remove all samples
			foreach (pq('*', $method['method']) as $node) {
				$pq = pq($node);
				$class = $pq->attr('class');

				// class name starts with sd_ ?
				if (strpos($class, 'sd_') === 0) {
					unset($methods[$key]);
				}
			}
		}

		$methods = array_values($methods);
		$methodType = $parts > (count($methods) - $parts) ? 'part' : 'method';

		foreach ($methods as &$method) {
			$method['type'] = $methodType;

			$steps = array();

			$methodParent = $method['method'];
			foreach (pq($method['method'])->children() as $node) {
				$pq = pq($node);

				if ($pq->is('ol')) {
					$steps = array_merge($steps, $this->processStepContent($pq));
				}
				else if ($pq->is('p') || $pq->is('ul')) {
					ArticleHTMLParser::removeEmptyNodes($pq);
					$html = trim($pq->html());
					if ($html) {
						$steps[] = array("html"=>$html);
					}
				}
				else if ($pq->is('h4')) {
					ArticleHTMLParser::removeEmptyNodes($pq);
					$html = trim(strip_tags($pq->html(), "<a>"));
					if ($html) {
						$steps[] = array("heading"=>$html);
					}
				}
				else {
					//$class = $pq->attr('class');
					//$id = $pq->attr('id');
					//$tag = $node->tagName;
					// not sure what to do with any leftovers here..just ignore for now
					//decho("class", $class);
					//decho("id", $id);
					//decho("tag", $tag);
				}
			}

			if ($steps) {
				$method['steps'] = $steps;
			} else {
				$method['html'] = trim($method['method']->html());
			}
			unset($method['method']);
		}

		return $methods;
	}

	private function processRelatedWikihows($list) {
		$relateds = array();
		$howto = wfMsg('howto', '');
		foreach ($list as $item) {
			$doc = phpQuery::newDocumentHTML($item['html']);
			$a = $doc['a'];
			if ($a->length) {
				$text = $a->html();
				$text = preg_replace('@^' . $howto . '@', '', $text);
				$title = Title::newFromText($text);
				if ($title && $title->exists()) {
					$articleID = intval($title->getArticleID());
					$image = AppDataFormatter::getArticleImage($title);
					if ($image) unset($image['obj']);
					$relateds[] = array(
						'id' => $articleID,
						'title' => wfMsg('howto', $title->getText()),
						'image' => $image);
				}
			}
		}
		return $relateds;
	}

	private function processListHTML($html) {
		$doc = phpQuery::newDocumentHTML($html);
		return $this->processList($doc);
	}

	/**
	*  Processes a list that might have h3 headers and takes all the li out
	*  even if they are separated into different uls. if there are videos it just leaves them in the html
	*  if any li's have images it takes those out
	**/
	private function processListWithHeaders($doc) {
		$list = array("list"=>array());
		foreach ($doc->children()->children() as $child) {
			if (pq($child)->is('h3') || pq($child)->is('p')) {
				$text = trim(strip_tags(pq($child)->html()), "<a>");
				if ($text) {
					$list["list"][]=array("heading"=>trim($text));
				}
			}
			$processList = false;
			if (pq($child)->is('ul') || pq($child)->is('ol')) {
				$processList = true;
			}
			if ($processList) {
				foreach (pq($child)->children() as $ulChild) {
					ArticleHTMLParser::removeEmptyNodes(&$ulChild);

					if (pq($ulChild)->is('li')) {
						$item = array();

						//modify any image tags
						$this->modifyImageTags($ulChild);
						$text = trim(pq($ulChild)->html());
						if ($text) {
							$item["html"] = $text;
						}
						if (count($item) > 0) {
							$list["list"][] = $item;
						}
					}
					pq($ulChild)->remove();
				}
			}
		}
		return $list;
	}

	private function processList($doc) {
		$result = array();

		$listType = 'ol';
		$resultName = 'numbered';
		$itemNodes = $doc[$listType];

		if ($itemNodes->length == 0) {
			// try looking for unordered list
			$listType = 'ul';
			$itemNodes = $doc[$listType];
			if ($itemNodes->length) {
				$resultName = 'list';
			}
		}

		if ($itemNodes->length == 0) {
			// If there is real value to the leftover html, we keep it
			$html = trim($doc->html());
			if ($html) {
				$result['html'] = $html;
			}
			return $result;
		}

		$list = $itemNodes->filter($listType);
		foreach (pq('> li', $list) as $li) {
			$item = array();
			$pq = pq($li);

			// pull out wikiVideo html
			$whvid = pq('.whvid_cont:first', $li);
			$vid = $this->pullOutWikiVideo($whvid);
			if ($vid) {
				$item['whvid'] = $vid;
			}

			//modify remaining image tags
			$this->modifyImageTags($li);

			$item['html'] = trim($pq->html());
			$items[] = $item;
		}
		$result[$resultName] = $items;

		// Remove <p><a name="foo"></a></p> element
		$p = $list->next()->filter('p:last');
		if ($p->length) {
			$c = $p->children();
			if ($c->length == 1 && $c->is('a') && $c->attr('name') && !$c->attr('href')) {
				$p->remove();
			}
		}

		// Remove <ul>, <ol> and <div> elements if they exist
		$list->remove();
		if ($listType == 'ol') {
			$refDiv = pq('div.references-small');
			if ($refDiv->length && !trim($refDiv->html())) {
				$refDiv->remove();
			}
		}

		$div = pq('div.article_inner');
		if ($div->length && $div->attr('id') && !trim($div->text())) {
			$div->remove();
		}

		// If there is real value to the leftover html, we keep it
		$html = trim( $doc->html() );
		if ($html) {
			$result['html'] = $html;
		}

		return $result;
	}

	private static function htmlToDoc($articleHtml) {
		global $wgLanguageCode;

		// Make doc correctly formed
$articleText = <<<DONE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="$wgLanguageCode" lang="$wgLanguageCode">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset='utf-8'" />
</head>
<body>
$articleHtml
</body>
</html>
DONE;
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
		$doc->strictErrorChecking = false;
		$doc->recover = true;
		//$doc->preserveWhiteSpace = false;
		//$wgOut->setarticlebodyonly(true);
		@$doc->loadHTML($articleText);
		$doc->normalizeDocument();
		//echo $doc->saveHtml();exit;
		return $doc;
	}

	private static function docToHtml($doc) {
		//self::walkTree($doc->documentElement, 1);
		$html = $doc->saveXML();

		// Remove </body></html> from html
		$html = preg_replace('@</body>(\s|\n)*</html>(\s|\n)*$@', '', $html);

		return $html;
	}

}

