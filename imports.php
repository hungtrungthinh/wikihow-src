<?
#
# wikiHow Extensions
#

# English-specific extensions
if ($wgLanguageCode == 'en') {
	require_once("$IP/extensions/wikihow/FeaturedContributor.php");
	require_once("$IP/extensions/wikihow/IntroImageAdder.php");
	require_once("$IP/extensions/wikihow/rctest/RCTest.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestGrader.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestAdmin.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsUp.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsNotifications.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsEmailNotifications.php");
	require_once("$IP/extensions/wikihow/IheartwikiHow.php");
	require_once("$IP/extensions/wikihow/fblogin/FBLink.php");
	require_once("$IP/extensions/wikihow/HAWelcome/HAWelcome.php");
	require_once("$IP/extensions/wikihow/titus/TitusQueryTool.php");
	require_once("$IP/extensions/wikihow/titus/TitusGraphTool.php");
	require_once("$IP/extensions/wikihow/WikitextDownloader.php");
	require_once("$IP/extensions/wikihow/video/Videoadder.php");
	require_once("$IP/extensions/wikihow/thumbratings/ThumbRatings.php");
	require_once("$IP/extensions/wikihow/wap/WAP.php");
	require_once("$IP/extensions/wikihow/concierge/Concierge.php");
	require_once("$IP/extensions/wikihow/babelfish/Babelfish.php");
	require_once("$IP/extensions/wikihow/editfish/Editfish.php");
	require_once("$IP/extensions/wikihow/textscroller/TextScroller.php");
	require_once("$IP/extensions/wikihow/DupImage.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TipsAndWarnings.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TipsPatrol.php");
	require_once("$IP/extensions/wikihow/nab/Newarticleboost.php");
	require_once("$IP/extensions/wikihow/docviewer/DocViewer.php");
	require_once("$IP/extensions/wikihow/articlecreator/ArticleCreator.php");
	require_once("$IP/extensions/wikihow/imagefeedback/ImageFeedback.php");
	require_once("$IP/extensions/wikihow/altmethodadder/AltMethodAdder.php");
	require_once("$IP/extensions/wikihow/altmethodadder/MethodGuardian.php");
	require_once("$IP/extensions/wikihow/altmethodadder/MethodEditor.php");
	require_once("$IP/extensions/wikihow/altmethodadder/AdminMethodEditor.php");
	require_once("$IP/extensions/wikihow/hydra/Hydra.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TPCoachAdmin.php");
	require_once("$IP/extensions/wikihow/apiappsupport/APIAppAdmin.php");
	require_once("$IP/extensions/EventLogging/EventLogging.php");
	require_once("$IP/extensions/GuidedTour/GuidedTour.php");
	require_once("$IP/extensions/wikihow/EditPageWrapper.php");
	require_once("$IP/extensions/wikihow/AdminImageRemoval.php");
	require_once("$IP/extensions/wikihow/wikihowAds/AdminAdExclusions.php");
	require_once("$IP/extensions/wikihow/mobile/ucipatrol/UCIPatrol.php");
	require_once("$IP/extensions/wikihow/RecommendationPresenter/RecommendationAdmin.php");
	require_once("$IP/extensions/wikihow/RecommendationPresenter/RecommendationPresenter.php");
}
if($wgLanguageCode == "zh") {
	require_once("$IP/extensions/wikihow/chinesevariantselector/ChineseVariantSelector.php");
}

require_once("$IP/extensions/wikihow/wikihowAds/AdminAdExclusions.php");
require_once("$IP/extensions/wikihow/WikihowPreferences/WikihowPreferences.php");
require_once("$IP/extensions/wikihow/whvid/WHVid.php");
require_once("$IP/extensions/wikihow/translateeditor/TranslateEditor.php");
require_once("$IP/extensions/wikihow/QuickEdit.php");
require_once("$IP/extensions/wikihow/Misc.php");
require_once("$IP/extensions/wikihow/video/Importvideo.php");
require_once("$IP/extensions/CheckUser/CheckUser.php");
require_once("$IP/extensions/SpamBlacklist/SpamBlacklist.php");
require_once("$IP/extensions/wikihow/WikihowImagePage/WikihowImagePage.php");
require_once("$IP/extensions/wikihow/WikihowUserPage/WikihowUserPage.php");
$wgSpamBlacklistFiles = array(
	"DB: " . WH_DATABASE_NAME . " Spam-Blacklist",
	"$IP/extensions/SpamBlacklist/wikimedia_blacklist",
	"$IP/extensions/SpamBlacklist/wikihow_custom",
);
require_once("$IP/extensions/Cite/Cite.php");
require_once("$IP/extensions/AntiSpoof/AntiSpoof.php");
require_once("$IP/extensions/UniversalEditButton/UniversalEditButton.php");
require_once("$IP/extensions/Drafts/Drafts.php");
require_once("$IP/extensions/ImageMap/ImageMap.php");
require_once("$IP/extensions/wikihow/EasyTemplate.php");
require_once("$IP/extensions/wikihow/Articlestats.php");
require_once("$IP/extensions/wikihow/Patrolcount.php");
require_once("$IP/extensions/wikihow/PatrolHelper.php");
require_once("$IP/extensions/BlockTitles/BlockTitles.php");
require_once("$IP/extensions/wikihow/LSearch.php");
require_once("$IP/extensions/wikihow/GoogSearch.php");
require_once("$IP/extensions/wikihow/NVGadget.php");
require_once("$IP/extensions/wikihow/GoogGadget.php");
require_once("$IP/extensions/wikihow/Newcontributors.php");
require_once("$IP/extensions/wikihow/TitleSearch.php");
require_once("$IP/extensions/wikihow/ThankAuthors.php");
require_once("$IP/extensions/wikihow/createpage/CreatePage.php");
require_once("$IP/extensions/wikihow/TwitterFeed.php");
require_once("$IP/extensions/wikihow/Standings.php");
require_once("$IP/extensions/wikihow/qc/QC.php");
require_once("$IP/extensions/wikihow/Unguard.php");
require_once("$IP/extensions/wikihow/CheckG.php");
if((!defined('IS_DEV_SITE') || !IS_DEV_SITE) && $wgLanguageCode == 'en') {
	require_once("$IP/extensions/wikihow/Vanilla.php");
}
require_once("$IP/extensions/ProxyConnect/ProxyConnect.php");
require_once("$IP/extensions/wikihow/ImportXML.php");
require_once("$IP/extensions/wikihow/Managepagelist.php");
require_once("$IP/extensions/wikihow/unpatrol/Unpatrol.php");
require_once("$IP/extensions/wikihow/rcpatrol/RCPatrol.php");
require_once("$IP/extensions/wikihow/fblogin/FBLogin.php");
require_once("$IP/extensions/wikihow/GPlusLogin/GPlusLogin.php");
require_once("$IP/extensions/wikihow/WikihowArticle.php");
require_once("$IP/extensions/wikihow/Wikitext.class.php");
require_once("$IP/extensions/wikihow/RobotPolicy.class.php");
require_once("$IP/extensions/wikihow/ConfigStorage.class.php");
require_once("$IP/extensions/wikihow/WikiPhoto.php");
require_once("$IP/extensions/wikihow/FBAppContact.php");
require_once("$IP/extensions/wikihow/Categorylisting.php");
require_once("$IP/extensions/wikihow/Randomizer.php");
require_once("$IP/extensions/wikihow/Generatefeed.php");
require_once("$IP/extensions/wikihow/ToolbarHelper.php");
require_once("$IP/extensions/wikihow/Sitemap.php");
require_once("$IP/extensions/wikihow/EmailLink.php");
require_once("$IP/extensions/wikihow/Suggest.php");
require_once("$IP/extensions/wikihow/MWMessages.php");
require_once("$IP/extensions/wikihow/Rating/Rating.php");
require_once("$IP/extensions/wikihow/SpamDiffTool.php");
require_once("$IP/extensions/wikihow/Bunchpatrol.php");
require_once("$IP/extensions/wikihow/Republish.php");
require_once("$IP/extensions/wikihow/MultipleUpload.php");
require_once("$IP/extensions/wikihow/GenerateJSFeed.php");
require_once("$IP/extensions/FormatEmail/FormatEmail.php");
require_once("$IP/extensions/wikihow/MagicArticlesStarted.php");
require_once("$IP/extensions/wikihow/PostComment/SpecialPostComment.php");
require_once("$IP/extensions/Renameuser/SpecialRenameuser.php");
require_once("$IP/extensions/wikihow/FacebookPage.php");
require_once("$IP/extensions/wikihow/Categoryhelper.php");
require_once("$IP/extensions/wikihow/CheckJS.php");
require_once("$IP/extensions/wikihow/AddRelatedLinks.php");
require_once("$IP/extensions/wikihow/ManageRelated/ManageRelated.php");
require_once("$IP/extensions/wikihow/monitorpages/Monitorpages.php");
require_once("$IP/extensions/wikihow/Changerealname.php");
require_once("$IP/extensions/ConfirmEdit/ConfirmEdit.php");
require_once("$IP/extensions/ConfirmEdit/FancyCaptcha.php");
require_once("$IP/extensions/ParserFunctions/ParserFunctions.php");
require_once("$IP/extensions/wikihow/AutotimestampTemplates.php");
require_once("$IP/extensions/ImportFreeImages/ImportFreeImages.php");
require_once("$IP/extensions/PopBox/PopBox.php");
require_once("$IP/extensions/wikihow/video/EmbedVideo.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearch.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearchUI.php");
require_once("$IP/extensions/wikihow/cattool/Categorizer.php");
require_once("$IP/extensions/wikihow/articledata/ArticleData.php");
require_once("$IP/extensions/wikihow/catsearch/CategoryInterests.php");
require_once("$IP/extensions/wikihow/Mypages.php");
require_once("$IP/extensions/wikihow/hooks/WikihowHooks.php");
require_once("$IP/extensions/wikihow/Wikihow_i18n.class.php");
require_once("$IP/extensions/wikihow/HtmlSnips.class.php");
require_once("$IP/extensions/wikihow/FeaturedArticles.php");
require_once("$IP/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php");
require_once("$IP/extensions/wikihow/Welcome.php");
require_once("$IP/extensions/wikihow/authors/Authorleaderboard.php");
require_once("$IP/extensions/wikihow/authors/AuthorEmailNotification.php");
require_once("$IP/extensions/wikihow/Charityleaderboard.php");
require_once("$IP/extensions/wikihow/avatar/Avatar.php");
require_once("$IP/extensions/wikihow/profilebox/ProfileBox.php");
require_once("$IP/extensions/wikihow/QuickNoteEdit.php");
require_once("$IP/extensions/wikihow/eiu/Easyimageupload.php");
require_once("$IP/extensions/wikihow/Leaderboard.php");
require_once("$IP/extensions/wikihow/mobile/MobileWikihow.php");
require_once("$IP/extensions/wikihow/mqg/MQG.php");
require_once("$IP/extensions/wikihow/FollowWidget.php");

// We create a triaged form of wikiHow if WIKIHOW_LIMITED is defined
// in LocalSettings.php, which requires fewer resources and pings
// our servers less.
if (!defined('WIKIHOW_LIMITED')) {
	if (!defined('DISABLE_RCWIDGET') || !DISABLE_RCWIDGET) {
		require_once("$IP/extensions/wikihow/rcwidget/RCWidget.php");
	}
	require_once("$IP/extensions/wikihow/RCBuddy.php");
	require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
	require_once("$IP/extensions/wikihow/BounceTimeLogger.php");
	require_once("$IP/extensions/wikihow/pagestats/Pagestats.php");
} else {
	if (strpos(@$_SERVER['REQUEST_URI'], '/Special:BounceTimeLogger?') === 0) {
		header('HTTP/1.1 501 Not implemented');
		print "Not available";
		exit;
	}
}

require_once("$IP/extensions/wikihow/WikihowCSSDisplay.php");
require_once("$IP/extensions/wikihow/StatsList.php");
require_once("$IP/extensions/wikihow/AdminResetPassword.php");
require_once("$IP/extensions/wikihow/AdminMarkEmailConfirmed.php");
require_once("$IP/extensions/wikihow/avatar/AdminRemoveAvatar.php");
require_once("$IP/extensions/wikihow/AdminLookupPages.php");
require_once("$IP/extensions/wikihow/AdminRedirects.php");
require_once("$IP/extensions/wikihow/AdminEnlargeImages.php");
require_once("$IP/extensions/wikihow/AdminRatingReasons.php");
require_once("$IP/extensions/wikihow/AdminEditInfo.php");
require_once("$IP/extensions/wikihow/AdminBounceTests.php");
require_once("$IP/extensions/wikihow/AdminSearchResults.php");
require_once("$IP/extensions/wikihow/AdminConfigEditor.php");
require_once("$IP/extensions/wikihow/Bloggers.php");
require_once("$IP/extensions/wikihow/loginreminder/LoginReminder.php");
require_once("$IP/extensions/wikihow/NewHowtoArticles.php");
require_once("$IP/extensions/wikihow/fbnuke/FBNuke.php");
require_once("$IP/extensions/wikihow/editfinder/EditFinder.php");
require_once("$IP/extensions/wikihow/ctalinks/CTALinks.php");
require_once("$IP/extensions/wikihow/dashboard/AdminCommunityDashboard.php");
require_once("$IP/extensions/wikihow/slider/Slider.php");
require_once("$IP/extensions/wikihow/starter/StarterTool.php");
require_once("$IP/extensions/wikihow/ProfileBadges.php");
require_once("$IP/extensions/wikihow/ImageHelper/ImageHelper.php");
require_once("$IP/extensions/wikihow/ImageCaptions.php");
require_once("$IP/extensions/wikihow/nfd/NFDGuardian.php");
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");
require_once("$IP/extensions/wikihow/TitleTests.class.php");
require_once("$IP/extensions/wikihow/GoodRevision.class.php");
require_once("$IP/extensions/wikihow/DailyEdits.php");
require_once("$IP/extensions/wikihow/ArticleWidgets/ArticleWidgets.php");
require_once("$IP/extensions/wikihow/spellchecker/Spellchecker.php");
require_once("$IP/extensions/wikihow/ToolSkip.php");
require_once("$IP/extensions/wikihow/wikihowAds/wikihowAds.class.php");
require_once("$IP/extensions/wikihow/wikihowAds/Radlinks.php");
require_once("$IP/extensions/wikihow/WikihowShare.php");
require_once("$IP/extensions/wikihow/AdminNoIntroImage.php");
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
require_once("$IP/extensions/wikihow/Misc.php");
require_once("$IP/extensions/wikihow/WikihowUser.php");
require_once("$IP/extensions/wikihow/Alien.php");
require_once("$IP/extensions/wikihow/articlerating/ArticleRating.class.php");
require_once("$IP/extensions/wikihow/articlerating/ArticleRating.php");
require_once("$IP/extensions/wikihow/EventLogger.class.php");
require_once("$IP/extensions/wikihow/authors/ArticleAuthors.php");
require_once("$IP/extensions/wikihow/NewlyIndexed.class.php");
require_once("$IP/extensions/wikihow/revisioncount/RevisionCount.php");
require_once("$IP/extensions/wikihow/AdminAnomalies.php");
require_once("$IP/extensions/wikihow/stubs/Hillary.php");
require_once("$IP/extensions/wikihow/TranslationLink.php");
require_once("$IP/extensions/wikihow/translationlinkoverride/TranslationLinkOverride.php");
require_once("$IP/extensions/wikihow/reverttool/RevertTool.php");
require_once("$IP/extensions/wikihow/AdminSamples.php");
require_once("$IP/extensions/wikihow/AdminTitles.php");
require_once("$IP/extensions/wikihow/quizzes/Quizzes.php");
require_once("$IP/extensions/wikihow/UserPagePolicy.php");
require_once("$IP/extensions/wikihow/alfredo/Alfredo.php");
require_once("$IP/extensions/wikihow/microdata/Microdata.php");
require_once("$IP/extensions/wikihow/mobile/ImageUploadHandler.php");
require_once("$IP/extensions/wikihow/AdminUserCompletedImages.php");
require_once("$IP/extensions/wikihow/AdminClearRatings.php");
require_once("$IP/extensions/wikihow/WikihowCategoryPage.php");
require_once("$IP/extensions/wikihow/ApiApp.php");
require_once("$IP/extensions/wikihow/AdminCopyCheck.php");
require_once("$IP/extensions/wikihow/PageStatCheck.php");
require_once("$IP/extensions/wikihow/WelcomeWagon/WelcomeWagon.php");
require_once("$IP/extensions/wikihow/interfaceelements/InterfaceElements.php");
require_once("$IP/extensions/wikihow/ApiMobileArticleDownloader.php");
require_once("$IP/extensions/wikihow/Watermark.php");
require_once("$IP/extensions/wikihow/ArticleHTMLParser.php");
require_once("$IP/extensions/wikihow/WikiError.php");

if(IS_CLOUD_SITE || IS_DEV_SITE) {
	require_once("$IP/extensions/wikihow/titus/ApiTitus.php");
    require_once("$IP/extensions/wikihow/flavius/ApiFlavius.php");
	require_once("$IP/extensions/wikihow/dedup/Dedup.php");
	require_once("$IP/extensions/wikihow/dedup/CommunityExpert.php");
	require_once("$IP/extensions/wikihow/dedup/QueryCat.php");
	require_once("$IP/extensions/wikihow/dedup/ApiDedup.php");
    require_once("$IP/extensions/wikihow/leonard/Leonard.php");
	require_once("$IP/extensions/wikihow/editcontribution/EditContribution.php");
}

#REDESIGN
require_once("$IP/extensions/wikihow/userloginbox/UserLoginBox.php");
require_once("$IP/extensions/wikihow/WikihowCategoryPage.php");
require_once("$IP/extensions/wikihow/homepage/WikihowHomepage.php");
require_once("$IP/extensions/wikihow/homepage/WikihowHomepageAdmin.php");
require_once("$IP/extensions/wikihow/ArticleViewer/WikihowArticleStream.php");
require_once("$IP/extensions/wikihow/ArticleViewer/ArticleViewer.php");
require_once("$IP/extensions/wikihow/Notifications.class.php");
require_once("$IP/extensions/wikihow/ApiCategoryListing.php");
require_once("$IP/extensions/wikihow/userstaffwidget/UserStaffWidget.php");
require_once("$IP/extensions/wikihow/optimizely/OptimizelyPageSelector.php");
require_once("$IP/extensions/wikihow/accountcreationfilter/AccountCreationFilter.php");
require_once("$IP/extensions/wikihow/CategoryNames.php");

#UPGRADE 1.23
require_once("$IP/extensions/wikihow/WikihowLogin.php");
require_once("$IP/extensions/wikihow/MassMessage/MassMessage.php");
require_once("$IP/extensions/Echo/Echo.php");
require_once("$IP/extensions/wikihow/EchoWikihow/EchoWikihow.php");
require_once("$IP/extensions/wikihow/hooks/Email.php");

