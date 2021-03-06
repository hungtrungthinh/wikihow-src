<?php

if (!defined('MEDIAWIKI')) die();

class WikihowShare {
	
	public static function getTopShareButtons($isIndexed = true){
		global $wgLanguageCode, $wgTitle, $wgServer;
		
		$action = self::getAction();

		if(!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || $wgTitle->getText() == "Main-Page")
			return "";

		$url = $wgServer . "/" . urlencode($wgTitle->getPrefixedURL());
		$img = urlencode(self::getPinterestImage($wgTitle));
		$desc = urlencode(wfMessage('Pinterest_text', $wgTitle->getText())->text()); 
				
		$fb = '<div class="like_button"><fb:like href="' . $url . '" send="false" layout="box_count" width="46" show_faces="false"></fb:like></div>';
		if($isIndexed)
			$gp1 = '<div class="gplus1_button"><g:plusone size="tall" callback="plusone_vote"></g:plusone></div>';
		else
			$gp1 = "";
		
		// Reuben added to remove certain images from pinterest sharing where
		// we are having video thumbnail / Google index CTR problems. We
		// override the $img variable to be '' so that the thumbnail image 
		// for the video isn't shared.
		if(ArticleMetaInfo::isImageExclusionArticle()) {
			$img = '';
		}
		$pinterest = '<div id="pinterest"><a href="http://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $img . '&description=' . $desc . '" class="pin-it-button" count-layout="vertical">Pin It</a></div>';

		// German includes "how to " in the title text
		$howto = $wgLanguageCode != 'de' ? wfMessage('howto', htmlspecialchars($wgTitle->getText()))->text() : htmlspecialchars($wgTitle->getText());
		$tb = '<div class="admin_state"><a href="https://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="vertical" data-via="wikiHow" data-text="' . $howto . '" data-related="JackH:Founder of wikiHow">Tweet</a></div>';
		
		if ($wgLanguageCode != 'en') {
			return $gp1 . $tb . $fb;
		}
		else {
			return $gp1 . $fb . $pinterest . $tb;
		}
	}

	public static function getMainPageShareButtons(){
		global $wgTitle;
		
		$action = self::getAction();
		if(!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || $wgTitle->getText() != wfMessage('Mainpage')->text())
			return "";
		
		return wfMessage('mainpage_share_buttons')->text();
		
	}
	
	public static function getBottomShareButtons($isIndexed = true) {
		global $wgLanguageCode, $wgTitle, $wgServer;
		
		$action = self::getAction();
		
		if(!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || self::isMainPage($action))
			return "";
		
		$url = $wgServer . "/" . urlencode($wgTitle->getPrefixedURL());
				
		$fb_share = '<div class="like_button like_tools"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		// German includes "how to " in the title text
		$howto = $wgLanguageCode != 'de' ? wfMessage('Twitter_text', htmlspecialchars($wgTitle->getText()))->text() : htmlspecialchars($wgTitle->getText());
		$tb_share = '<a href="https://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="' . $howto. '" data-related="JackH:Founder of wikiHow">Tweet</a>';

		
		if ($wgLanguageCode != 'en') {
			return $fb_share . $tb_share;
		} else {
			return $fb_share . $tb_share;
		}
	}
	
	public static function getBottomShareButtons_redesign($isIndexed = true) {
		global $wgLanguageCode, $wgTitle, $wgServer;
		
		$action = self::getAction();
		
		if (!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || self::isMainPage($action))
			return "";
		
		$url = $wgServer . "/" . urlencode($wgTitle->getPrefixedURL());
		$img = urlencode(self::getPinterestImage($wgTitle));
		$desc = urlencode(wfMessage('Pinterest_text', $wgTitle->getText())->text()); 
				
		$fb = '<div class="fb-like like_button like_tools"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		// German includes "how to " in the title text
		$howto = $wgLanguageCode != 'de' ? wfMessage('Twitter_text', htmlspecialchars($wgTitle->getText()))->text() : htmlspecialchars($wgTitle->getText());
		$tb = '<a href="https://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="' . $howto. '" data-related="JackH:Founder of wikiHow">Tweet</a>';

		if($isIndexed)
			$gp1 = '<div class="gplus1_button"><g:plusone callback="plusone_vote"></g:plusone></div>';
		else
			$gp1 = "";
		
		// Reuben added to remove certain images from pinterest sharing where
		// we are having video thumbnail / Google index CTR problems. We
		// override the $img variable to be '' so that the thumbnail image 
		// for the video isn't shared.
		if(ArticleMetaInfo::isImageExclusionArticle()) {
			$img = '';
		}
		$pinterest = '<div id="pinterest"><a data-pin-config="beside" href="http://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $img . '&description=' . $desc . '" class="pin-it-button" data-pin-do="buttonPin">Pin It</a></div>';
		
		if ($wgLanguageCode != 'en') {
			return $gp1 . $tb . $fb;
		}
		else {
			return $gp1 . $pinterest . $fb;
		}
	}
	
	function getAction() {
		global $wgRequest;
		
		$action = $wgRequest->getVal("action", "view");
		if ($wgRequest->getVal("diff", "") != "")
			$action = "diff";
		
		return $action;
	}
	
	function isMainPage($action) {
		global $wgTitle;
		
		return $wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() == wfMessage('mainpage')->text()
			&& $action == 'view';
	}
	
	public static function getShareImage(&$title) {
		return self::getPinterestImage($title, false);
	}

	function getPinterestImage($title, $fromPad = true) {
		global $wgLanguageCode, $wgContLang;

		if (in_array($title->getNamespace(), array(NS_MAIN, NS_CATEGORY))) {
			if ($title->getNamespace() == NS_MAIN) {
				
				$file = Wikitext::getTitleImage($title);
				if($file && isset($file)) {
					$url = "/images/" . $file->getRel();
					if ($fromPad) {
						$url = wfGetPad($url);
					}
					return $url;
				}
				
			}

			$catmap = Categoryhelper::getIconMap(); 
			// still here? use default categoryimage

			// if page is a top category itself otherwise get top
			if (isset($catmap[urldecode($title->getPartialURL())])) {
				$cat = urldecode($title->getPartialURL());
			} else {
				$cat = Categoryhelper::getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial URL for getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					if($title != null)
						$cat = $title->getPartialURL();
				}
			}

			if (isset($catmap[$cat])) {
				$image = Title::newFromText($catmap[$cat]);
				$file = wfFindFile($image, false);
				if($file) {
					$url = "/images/" . $file->getRel();
					if ($fromPad) {
						$url = wfGetPad($url);
					}
					if ($url) {
						return $url;
					}
				}
				else {
				  $url = "/skins/WikiHow/images/wikihow_large.jpg";
					if ($fromPad) {
					  $url = wfGetPad($url);
				  }
				  if ($url) {
				    return $url;
					}
				}
			} else {
				$url = "/skins/WikiHow/images/wikihow_large.jpg";
				if ($fromPad) {
					$url = wfGetPad($url);
				}
				if ($url) {
					return $url;
				}
			}
		}
	}
	
	public static function getPinterestTitleInfo($context) {
		if (!$context->canUseWikiPage()) return '';

		$text = $context->getWikiPage()->getText(Revision::RAW);
		$num_steps = 0;
		if (preg_match("/^(.*?)==\s*".wfMessage('tips')->text()."/ms", $text, $sectionmatch)) {
			// has tips, let's assume valid candidate for detailed title
			$num_steps = preg_match_all('/^#[^*]/im', $sectionmatch[1], $matches);
		}

		if ($num_steps >= 5 && $num_steps <= 12) {
				$titleDetail = " in $num_steps Steps";
		} else {
			$titleDetail = '';
		}
		
		return $titleDetail;
	}
	
}

class WikihowShareRest extends UnlistedSpecialPage {
    public function __construct() {
		parent::__construct( 'WikihowShare' );
	}

	public function getPinterestArticles() {
		$pins_of_three = array(
					array('Type-Symbols-Using-the-ALT-Key',
					'Grow-Strawberries',
					'Dry-Nail-Polish-Quickly'),
					array('Remove-a-Splinter-with-Baking-Soda',
					'Make-KFC-Original-Fried-Chicken',
					'Make-Natural-Outdoor-Fly-Repellent-with-Essential-Oils'),
					array('Make-a-New-Bar-of-Soap-from-Used-Bars-of-Soap',
					'Restore-a-Whiteboard',
					'Clean-a-Coffee-Maker'),
					array('Fold-a-Dollar-Into-a-Heart',
					'Make-Mushrooms-in-Beer-Batter',
					'Create-Your-Own-Temporary-Tattoo'),
					array('Color-Code-Your-Keys-Using-Nail-Polish',
					'Make-Croissants',
					'Make-Your-Hair-Grow-Faster'),
					array('Take-a-Detox-Bath',
					'Make-Skittles-Vodka',
					'Fishtail-Braid'),
					array('Make-Plastic-Tubing-Necklaces',
					'Make-a-Vegan-Pina-Vocado',
					'Paint-Your-Carpet'),
					array('Make-Handprint-Art',
					'Create-a-Marble-Nail-Effect-Using-Water',
					'Make-Burlap-Flowers'),
					array('Make-Month-Birthday-Onesies',
					'Create-Decorative-Balloon-Flowers',
					'Make-a-Chocolate-Peanut-Butter-Parfait'),
					array('Make-Almond-Toffee',
					'Make-Glitter-Jar-Candles',
					'Tint-Bottles-and-Jars'),
					array('Make-an-Educational-Word-Slider',
					'Achieve-a-Messy-Hair-Effect',
					'Create-Coconut-Ginger-Hand-Scrub'),
					array('Make-Patterned-Easter-Eggs',
					'Make-a-Fire-Starter-Using-a-Straw',
					'Make-a-Double-Fisherman\'s-Knot-Paracord-Bracelet')
				);
				
		$pin_group_num = rand(0, (count($pins_of_three) - 1));
		$pin_group = $pins_of_three[$pin_group_num];
		
		global $wgServer;
		$html = '';
		foreach ($pin_group as $pin) {
			$pin_img = '<img src="'.wfGetPad('/skins/WikiHow/images/pinterest-test/'.$pin.'.jpg').'" />';
			$pin_title = 'wikiHow to '.str_replace('-',' ',$pin);
			$pin_link = $wgServer.'/'.$pin.'?utm_source='.urlencode($pin).'&utm_medium=Pinterest%2BArticle&utm_campaign=Pinterest_DropDown';
			$html .= '<td valign="top"><div class="pin-head-pin"><a href="'.$pin_link.'">'.$pin_img.'</a><p><a href="'.$pin_link.'">'.$pin_title.'</a></p></div></td>';
		}
		
		return $html;
	}
	
	public function execute($par) {
		global $wgOut, $wgRequest;
		$socialNet = $wgRequest->getVal('soc', '');
		$wgOut->setArticleBodyOnly(true);
		$wgOut->setSquidMaxage(5*60); // 5 minutes in varnish
		if ($socialNet == 'pinterest') {
			$pins = self::getPinterestArticles();
			$pinterest_follow = wfGetPad('/skins/WikiHow/images/pinterest-test/pinterest_follow_box.jpg');
			
			$html = <<<EOHTML
<style>
#pin-head-cta {
	background-color: #FFF;
	width: 1050px;
	margin: 85px auto -72px auto;
	border: 1px solid #e5e5e5;
	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
}

#pin-head-cta td { width: 25%; }

.pin-head-pin {
	padding: 13px;
	border: 1px solid #DCD5CC;
	margin: 7px 7px 7px 7px;
}
.pin-head-pin p { 
	margin: 1em 0 .5em 0; 
	font-size: .9em;
}
.pin-head-pin p a { color: #000; }

#pin-head-cta-follow {
	padding: 12px;
}
</style>
<div id="pin-head-cta">
<table>
<tr>
$pins
<td><a href="http://pinterest.com/wikihow/"><img src="$pinterest_follow" border="0" id="pin-head-cta-follow" /></a></td>
</tr>
</div>
EOHTML;
			$wgOut->addHTML($html);
		}
	}
}

