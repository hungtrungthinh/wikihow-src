<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 * Allows users to post comments directly to discussion pages.'
 * 
 * @addtogroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:PostComment
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */



$wgExtensionCredits['specialpage'][] = array(
    'name' => 'PostComment',
    'author' => 'Travis Derouin',
    'description' => 'Allows users to post comments directly to discussion pages.',
    'url' => 'http://www.mediawiki.org/wiki/Extension:PostComment',
);

$wgExtensionMessagesFiles['PostComment'] = dirname(__FILE__) . '/SpecialPostComment.i18n.php';

$wgSpecialPages['PostComment'] = 'PostComment';
$wgSpecialPages['PostCommentPreview'] = 'PostCommentPreview';
$wgAutoloadClasses['PostComment'] = dirname( __FILE__ ) . '/SpecialPostComment.body.php';
$wgAutoloadClasses['PostCommentPreview'] = dirname( __FILE__ ) . '/SpecialPostComment.body.php';

