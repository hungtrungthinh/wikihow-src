<?
/*
 * TABLE:
 * create table dv_sampledocs (
 * dvs_doc_folder varchar(255),
 * dvs_doc varchar(255),
 * dvs_doc_ext varchar(8)
 * );
 * create index index_1 ON dv_sampledocs (dvs_doc);
 *
 * TABLE:
 * create table dv_links (
 * dvl_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 * dvl_page INT(8),
 * dvl_doc varchar(255)
 * );
 * 
 * TABLE:
 * create table dv_display_names (
 * dvdn_doc varchar(255) PRIMARY KEY,
 * dvdn_display_name varchar(255)
 * );
 */

class DocViewer extends UnlistedSpecialPage {
	
	private static $wgSampleURL = '/Sample/';
	private static $firstRelated = '';
	private static $firstRelatedTitle = '';
	private static $pdf_carrot = '';
	private static $isPdf;
	
	function __construct() {
		parent::__construct( 'DocViewer' );
	}
	
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = true;
		return true;
	}
	
	public static function removeHeadSectionCallback(&$showHeadSection) {
		$showHeadSection = false;
		return true;
	}
	
	public static function getCanonicalUrl(&$this, &$url, $query) {
		$url = self::$wgSampleURL;
		return true;
	}
	
	/**
	 * This is what should go in the <title> tag
	 */
	public function getPageTitleString($doc_name='') {
		if (!$doc_name) $doc_name = self::getSampleFromUrl();
		$text = self::getDisplayName($doc_name);
		return $text;
	}
	
	/**
	 * Sometimes we want to show a different sample name
	 */
	public static function getDisplayName($sample) {
		global $wgMemc;
		$memkey = wfMemcKey('sample_display_name', $sample);
		$name = $wgMemc->get($memkey);
		
		if (!is_string($name)) {
			$dbr = wfGetDB(DB_SLAVE);
			$name = $dbr->selectField('dv_display_names','dvdn_display_name',
					array('dvdn_doc' => $sample),__METHOD__);
					
			if (!$name) {
				//default
				$name = wfMessage('dv-sample-prefix')->text().' '.str_replace('-',' ',$sample);			
			}
			
			//set memcache
			$wgMemc->set($memkey,$name);
		}
		
		return $name;
	}

    public function showStaffStats() {
		global $wgUser, $wgTitle;

		$isLoggedIn = $wgUser->getID() > 0;

        return $isLoggedIn && 
               in_array('staff', $wgUser->getGroups()) && 
               $wgTitle->getNamespace() == NS_SPECIAL && 
               class_exists('Pagestats');
    }

	/**
	 * Display the HTML for this special page
	 */
	public static function displayContainer($doc_name='',$is_mobile) {
		global $wgServer, $wgUploadDirectory, $wgUser;
		$ads = "";
		$ads2 = "";
		$ads3 = "";
			
		$sampleDocsURIbase = '/images/sampledocs';
		$sampleDocsFileBase = $wgUploadDirectory . '/sampledocs';

		if (!$doc_name) $doc_name = self::getSampleFromUrl();
		
		if ($doc_name) {		
			//grab data from the db
			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->select('dv_sampledocs','*',array('dvs_doc' => $doc_name), __METHOD__);
			
			//did we catch anything?
			if (!$res->fetchObject()) return false;
			
			$doc_array = array();
			
			foreach ($res as $row) {
				$doc_hash = preg_replace('@^.*/([^/]+)$@', '$1', $row->dvs_doc_folder);
				$doc_uri_path = "$sampleDocsURIbase/$doc_hash";
				$doc_file_path = "$sampleDocsFileBase/$doc_hash";
				$full_path = $row->dvs_doc_folder.'/'.$row->dvs_doc.'.'.$row->dvs_doc_ext;
				$doc_array[$row->dvs_doc_ext] = $full_path;
			}
			
			//docx, xlsx, doc, xls...we're all cool here
			if ($doc_array['docx']) $doc_array['doc'] = $doc_array['docx'];
			if ($doc_array['xlsx']) $doc_array['xls'] = $doc_array['xlsx'];

			//what are we sending to gdoc?
			if ($doc_array['doc']) {
				$doc_array['gdoc'] = urlencode($wgServer.$doc_array['doc']);
			}
			elseif ($doc_array['xls']) {
				$doc_array['gdoc'] = urlencode($wgServer.$doc_array['xls']);
			}
			
			if(!$is_mobile && wikihowAds::isEligibleForAds()) {
				$ads = wikihowAds::getAdUnitPlaceholder("docviewer");
				
				if ( !self::showPdf($doc_name) ) {
					$ads2 = wikihowAds::getAdunitPlaceholder("docviewer2a");
				}
				
				$ads3 = wikihowAds::getAdUnitPlaceholder("docviewer3");
			}
			
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array(
				'doc_title' => self::getDisplayName($doc_name),
				'header_get' => wfMessage('header_get')->text(),				
				'header_found' => wfMessage('header_found')->text(),
				'header_related' => wfMessage('header_related')->text(),
				'show_staff_stats' => self::showStaffStats(),
				'dv_download' => wfMessage('dv-download')->text(),
				'dv_found' => self::getFoundInArticles($doc_name, $is_mobile),
				'dv_related' => self::getRelatedArticles($doc_name),
				'dv_fallback_img' => self::getFallbackImg($doc_name),
				'dv_ads' => $ads,
				'dv_ads2' => $ads2,
				'dv_ads3' => $ads3,
				'dv_share_buttons' => WikihowShare::getTopShareButtons(),
				'dv_sample_html' => self::getSampleHtml($doc_uri_path, $doc_file_path, $doc_name),
				'dv_pdf_carrot' => self::$pdf_carrot,
				'doc_name' => $doc_name,
				'dv_open_in' => wfMessage('dv-open-in')->text(), 
				'dv_dl_text_pdf' => wfMessage('dv-dl-text-pdf')->text(), 
				'dv_dl_file_pdf' => $doc_array['pdf'], 
				'dv_display_pdf' => 'http://www.wikihow.com/Sample/'.str_replace($doc_array['pdf'],'sampledocs','samplepdfs'), 
				'dv_dl_text_doc' => wfMessage('dv-dl-text-doc')->text(), 
				'dv_dl_file_doc' => $doc_array['doc'], 
				'dv_dl_text_xls' => wfMessage('dv-dl-text-xls')->text(), 
				'dv_dl_file_xls' => $doc_array['xls'], 
				'dv_dl_text_txt' => wfMessage('dv-dl-text-txt')->text(), 
				'dv_dl_file_txt' => $doc_array['txt'], 
				'dv_dl_text_gdoc' => wfMessage('dv-dl-text-gdoc')->text(), 
				'dv_dl_file_gdoc' => $doc_array['gdoc'], 
			));
			
			if ($is_mobile) {
				$tmpl_name = 'docviewer_mobile.tmpl.php';
			}
			else {
				$tmpl_name = 'docviewer.tmpl.php';
			}
			$html = $tmpl->execute($tmpl_name);
			self::addWidgets($tmpl);
		}
		else {
			//no name passed in?
			return false;
		}
		
		//now shows via the wikiHowSkin on the page, so we don't need to ad it in
		//if ($ads) $html = wikihowAds::getSetup() . $html;
		
		return $html;
	}
	
	private static function addWidgets(&$tmpl) {
		global $wgUser, $wgTitle;
		$sk = $wgUser->getSkin();

		// Staff stats
		$html = $tmpl->execute('widget_staff.tmpl.php');
		if (!empty($html)) {
			$sk->addWidget($html);
		}

		// Found in
		$html = $tmpl->execute('widget_foundin.tmpl.php');
		$sk->addWidget($html);

		$html = $tmpl->execute('widget_ads.tmpl.php');
		if (!empty($html)) {
			$sk->addWidget($html, 'sample_ads');
		}

		// Download Links
		$html = $tmpl->execute('widget_dl.tmpl.php');
		$sk->addWidget($html, 'sample_sidebubble');

		// Related Articles 
		$html = $tmpl->execute('widget_related.tmpl.php');
		$sk->addWidget($html);
	}

	private static function getFallbackImg($doc_name) {
		$file = wfFindFile($doc_name.'_sample.png');
		
		//backwards compatibility
		if (!$file || !isset($file)) $file = wfFindFile($doc_name.'.png');
		
		if ($file && isset($file)) {
			$img_url = wfGetPad($file->geturl());
			return $img_url;
		}
		else {
			return false;
		}
	}
	
	private static function showPdf($doc_name) {
		if( !isset(self::$isPdf) ) {
			$val = ConfigStorage::dbGetConfig('sample_pdfs');
			$pageList = preg_split('@[\r\n]+@', $val);
			
			self::$isPdf = in_array($doc_name,$pageList);
		}
		
		return self::$isPdf;
	}
	
	private static function getSampleHtml($doc_uri_path, $doc_file_path, $doc_name) {
		global $wgMemc, $IP;
		
		$doc_title = self::getDisplayName($doc_name);
		
		//check if we want to display the PDF instead
		if ( self::showPdf($doc_name) ) {
			$dv_display_pdf = 'http://www.wikihow.com/'.str_replace('sampledocs','samplepdfs',$doc_uri_path).'/'.$doc_name.'.pdf';
			$dv_fallback_img = self::getFallbackImg($doc_name);
			
			$pdf_code = '<h1>'.$doc_title.'</h1>
						<div class="sample_ribbon pdf_ribbon"></div>
						<div class="sample_container pdf_container">
						<object id="pdfobject" data="http://docs.google.com/gview?url='.$dv_display_pdf.'&embedded=true" width="720" height="600">
							   <!--fallback for IE and other non-PDF-embeddable browsers-->
							   <img src="'.$dv_fallback_img.'" id="fallback_img" alt="'.$doc_title.'" />
						</object></div>';
			
			self::$pdf_carrot = "pdf_carrot";
		
			return $pdf_code;
		}
		
		$memkey = wfMemcKey('sample',$doc_name);		
		$html = $wgMemc->get($memkey);
		
		if (!$html) {
			require_once("$IP/extensions/wikihow/common/composer/vendor/electrolinux/phpquery/phpQuery/phpQuery.php");
			$file = "$doc_file_path/$doc_name.html";
			
			//sanitize
			$file = str_replace("\"","",$file);
			$file = str_replace("`","",$file);
			$file = str_replace("..","",$file);
			$file = str_replace("./","",$file);
			$file = str_replace(":","",$file);
			
			if (file_exists($file)) {
				$html = file_get_contents($file);
				
				// This might be better in the maintenance file
				// since there is other html processing/strippping
				// Putting here for now so we don't have to reprocess all
				// the samples
				$doc = PHPQuery::newDocument($html);
				$styles = pq("style");
				foreach ($styles as $style) {
					$docHtml .= pq($style)->htmlOuter();
				}
				$docHtml .= pq('body')->html();
				$html = $docHtml;
				//toss it into memcache
				$wgMemc->set($memkey,$html);
			}
		}
		
		$html = "<div class='sample_ribbon'></div>

				<div class='sample_container' id='sample_html'>
				<h1 id='sample_title'>$doc_title</h1>
				 $html</div>";
		return $html;
	}
		
	/*
	 * For the Found In section
	 * - standard related wikiHow sidebar format
	 */
	function getFoundInArticles($doc_name, $is_mobile) {
		$html = '';
		
		$dbr = wfGetDB(DB_SLAVE);		
		$res = $dbr->select('dv_links', 'dvl_page', array('dvl_doc' => $doc_name), __METHOD__);
		
		foreach ($res as $row) {
			$t = Title::newFromId($row->dvl_page);
			
			if($t && $t->exists()) {
				global $wgUser;
				
				$sk = $wgUser->getSkin();
				$img = SkinWikihowskin::getGalleryImage($t, 44, 33);

                if($is_mobile)
                    $html .= $sk->featuredArticlesRow($t);
                else
					$html .=  SkinWikihowskin::getArticleThumb($t, 127, 140);

				//save for meta description
				if (!self::$firstRelated) {
					self::$firstRelated = 	' '.wfMessage('dv-meta-article-prefix')->text().
											' '.htmlspecialchars($t->getText());
					self::$firstRelatedTitle = $t;
				}
			}
		}
		
		return $html;
	}
	
	/*
	 * For the Related Articles section
	 * - standard related wikiHow sidebar format
	 */
	function getRelatedArticles($doc_name) {
		global $wgUser, $wgTitle;
		
		//keys off the first related title
		if (!self::$firstRelatedTitle) return '';		
		
		//swap out title
		$tempTitle = $wgTitle;
		$wgTitle = self::$firstRelatedTitle;
		
		//grab related
		$sk = $wgUser->getSkin();
		$html = $sk->getRelatedArticlesBox($this);
		
		//swap title back
		$wgTitle = $tempTitle;
		//format the html
		$html = preg_replace('@<h3>.*</h3>@','',$html);
		$html = preg_replace('@<table>@','<table class="sample_sidelist">',$html);
						
		return $html;
	}	
	
	/*
	 * deal with the link table
	 */
	function updateLinkTable($article, $doc_name, $bAdd = true) {
		$dbr = wfGetDB(DB_SLAVE);
		$dbw = wfGetDB(DB_MASTER);
		
		$samples = array();

		//assemble our db array for docs
		$doc_array = array('dvl_page' => $article->getID());
		if ($doc_name) $doc_array['dvl_doc'] = $doc_name;
		
		//something in there for this?
		$count = $dbr->selectField('dv_links', 'count(*) as count', $doc_array, __METHOD__);
		
		if ($bAdd) { //INSERT
						
			//already in there? um...cool, I guess.
			if ($count > 0) return true;
			
			$res = $dbw->insert('dv_links', 
					array('dvl_page' => $article->getID(), 'dvl_doc' => $doc_name),
					__METHOD__);
		
			$samples[] = $doc_name;
		}
		else { //DELETE
						
			//wait, it's not in the table? ok-ay...
			if ($count == 0) return true;
		
			if (!$doc_name) {
				//we're removing any that are associated with this article
				//grab all the ones we're going to remove...
				$goodbyes = $dbr->select('dv_links','dvl_doc',$doc_array, __METHOD__);
				foreach ($goodbyes as $row) {
					$samples[] = $row->dvl_doc;
				}
			}
			else {
				$samples[] = $doc_name;
			}
			
			$res = $dbw->delete('dv_links', $doc_array, __METHOD__);
		}
		
		//update our status table
		if ($res) {
			foreach ($samples as $sample) {
				self::updateSampleStatus($sample);
			}
		}
		
		return $res;
	}
	
	public static function updateSampleStatus($sample) {
		$dbw = wfGetDB(DB_MASTER);
		$sample_articles = array();
		
		//grab all our articles
		$res = $dbw->select('dv_links', 'dvl_page', array('dvl_doc' => $sample), __METHOD__);
		foreach ($res as $row) {
			$t = Title::newFromId($row->dvl_page);
			
			if ($t && $t->exists()) {
				$sample_articles[] = $t->getFullUrl();
			}
		}
		
		//now add it to our status table
		$dbw->update('dv_sampledocs_status',array('articles' => implode(' | ',$sample_articles), 'reviewed' => 0),array('sample' => $sample));
	}
	
	/**
	 * Function to display the thumb on an article page
	 */
	function GrabDocThumb($doc_name) {
		wfLoadExtensionMessages('DocViewer');
		$html = '<div id="sd_container" class="section_text">';
		$limit = 3;
		
		//figure out how many thumbs we have here
		$doc_array = explode(',',$doc_name);
		foreach ($doc_array as $key => $doc) {
			$sample_class = self::getSampleClass(count($doc_array),($key+1), $limit);
			
			$html .= '<div'.$sample_class.'>'.self::GrabOneDoc($doc).'</div>'."\n";
			
			if ((($key+1) == $limit) || (($key+1) % $limit == 0)) {
				$html .= "\n".'<br class="clearall" />';
			}
		}

		$html .='</div>';
		
		//make sure we have a final clearall
		//Bebeth - taking out as it was causing spacing problem on articles with only sample
		//if (count($doc_array) < $limit) $html .= "\n".'<br class="clearall" />';
		
		return $html;
	}
	
	/**
	 * Function to display the thumb on an article page
	 */
	function GrabOneDoc($doc_name) {
		$html = '';
		
		//spaces to hyphens
		$doc_name_hyphenized = preg_replace('@ @','-',$doc_name);
		
		$file = wfFindFile($doc_name.'_sample.png');
		
		//backwards compatibility
		if (!$file || !isset($file)) $file = wfFindFile($doc_name.'.png');
		
		if ($file && isset($file)) {
			$thumb = $file->getThumbnail(170);
			
			$display_name = self::getDisplayName($doc_name_hyphenized);
		
			$html = '<div class="sd_thumb">'.
					'<a href="/Sample/'.$doc_name_hyphenized.'"><img src="'.wfGetPad($thumb->getUrl()).'" /></a>'.
					'<p><a href="/Sample/'.$doc_name_hyphenized.'">'.$display_name.'</a></p>'.
					'</div>';
		}
		
		return $html;
	}
	
	function getSampleClass($total, $pointer, $limit) {	
		//only one
		if ($total == 1) return '';
		
		//2-3 samples
		if ($total <= $limit) return ' class="sd_multiple_'.$total.'"';
		
		//more than 3 samples, but we're not there yet
		if ($pointer <= $limit) return ' class="sd_multiple_'.$limit.'"';
		
		//more than 3 samples and we're past the breaking point
		if ($total <= ($limit*2)) {
			$mult = $total - $limit;
			if ($mult == 1) {
				return '';
			}
			else {
				return ' class="sd_multiple_'.$mult.'"';
			}
		}
		else {
			//TODO: make this work better for more than 6 samples
			return ' class="sd_multiple_'.$limit.'"';
		}
	}

	/**
	 * Strip out the sample title from the displayed url 
	 **/
	public function getSampleFromUrl() {
		global $wgTitle;
		
		$parts = explode( '/', $wgTitle->getFullUrl() );
		foreach ($parts as $k => $p) {
			if ($p == 'Sample') $sample = $parts[$k+1];
		}
		return $sample;
	}
	 
	
	/**
	 * EXECUTE
	 **/
	function execute($par = '') {
		global $wgOut, $wgRequest, $wgHooks, $wgCanonical, $wgSquidMaxage;
		
		$sample = preg_replace('@-@',' ',$par);
		wfLoadExtensionMessages('DocViewer');
		
		//no side bar
		$wgHooks['ShowSideBar'][] = array('DocViewer::removeSideBarCallback');
		//no head section
		$wgHooks['ShowHeadSection'][] = array('DocViewer::removeHeadSectionCallback');
		
		//make a custom canonical url
		self::$wgSampleURL = wfExpandUrl(self::$wgSampleURL . $par);		
		$wgHooks['GetFullURL'][] = array('DocViewer::getCanonicalUrl');
		
		//page title
		$page_title = $this->getPageTitleString($par);
		$wgOut->setHTMLTitle( wfMessage('pagetitle', $page_title)->text() );
		
		//the guts
		$wgOut->addScript(HtmlSnips::makeUrlTags('css', array('docviewer.css'), 'extensions/wikihow/docviewer', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('docviewer.js'), 'extensions/wikihow/docviewer', false));
		$html = self::displayContainer($par,false);
		
		if (!$html) {
			//nothin'
			$wgOut->setStatusCode(404);
			$html = '<p>'.wfMessage('dv-no-doc-err')->text().'</p>';
		}
		else {
			//http cache headers
			$wgOut->setSquidMaxage($wgSquidMaxage);

			//meta tags
			$wgOut->addMeta('description',"Use our sample '$page_title.' Read it or download it for free. Free help from wikiHow.");
			$wgOut->addMeta('keywords',$sample.', '.wfMessage('sample_meta_keywords_default')->text());
			$wgOut->setRobotPolicy('index,follow');
		}
		
		$wgOut->addHTML($html);
	}
	
}
