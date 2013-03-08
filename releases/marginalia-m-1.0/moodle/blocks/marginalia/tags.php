<?php

 // summary.php
 // Part of Marginalia annotation for Moodle
 // See www.geof.net/code/annotation/ for full source and documentation.

 // Display a summary of all annotations for the current user

require_once( "../../config.php" );
require_once( 'config.php' );
require_once( "marginalia-php/MarginaliaHelper.php" );
require_once( 'marginalia-php/Keyword.php' );
require_once( 'annotation_globals.php' );
require_once( 'keywords_db.php' );

global $CFG;

if ($CFG->forcelogin) {
	require_login();
}

/*
// Should probably add logging later
if ($cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
	add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id", $cm->id);
} else {
	add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id");
}

// Should add preference saving if multiple display modes
if ($mode) {
	set_user_preference("forum_displaymode", $mode);
}

$displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);
*/

$urlstring = $_SERVER[ 'REQUEST_URI' ];

if ( $_SERVER[ 'REQUEST_METHOD' ] != 'GET')  {
	header( 'HTTP/1.1 405 Method Not Allowed' );
	header( 'Allow: GET' );
}
else  {
	$errorpage = array_key_exists( 'error', $_GET ) ? $_GET[ 'error' ] : null;
	$courseid = required_param( 'course' );

    if (! $course = get_record('course', 'id', $courseid ) )
        error("Course ID $courseid is incorrect - discussion is faulty");

	$keywords = annotation_keywords_db::list_keywords( $USER->id );
	
	$meta
		= "<link type='text/css' rel='stylesheet' href='tags.css'/>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party/cssQuery.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party/cssQuery-standard.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/log.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia-config.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/domutil.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/rest-annotate.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/rest-keywords.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='tags.js'></script>\n"
		. "<script language='Javascript' type='text/javascript'>\n"
		. " var serviceRoot = '".s(ANNOTATION_PATH)."';\n"
		. " var summaryRoot = 'summary.php?url=".urlencode($CFG->wwwroot.'/course/view.php?id='+$courseid)
			. "&u=".urlencode($USER->username)."&match=exact';\n"
		. " var annotationKeywords = [\n";
	if ( $keywords )
	{
		for ( $i = 0;  $i < count( $keywords );  ++$i )
		{
			$keyword = $keywords[ $i ];
			if ( $i > 0 )
				$meta .= ", ";
			$meta .= "new Keyword('".htmlspecialchars($keyword->name)."', '".htmlspecialchars($keyword->description)."')\n";
		}
	}
	$meta .= "];\n"
		. "addEvent( window, 'load', keywordsOnload );\n"
		. "</script>";
			
	$navtail = get_string( 'edit_keywords_title', ANNOTATION_STRINGS );
	print_header( "$course->shortname: " . get_string( 'edit_keywords_title', ANNOTATION_STRINGS ),
		$course->fullname, "$navtail", "", $meta, true, "", null);
		

	echo "<div id='keyword-display'>\n";
	echo '<p>'.htmlspecialchars(get_string( 'tag_list_prompt', ANNOTATION_STRINGS ))."</p>\n";
	echo "<ul id='keywords'>\n";
		echo "</ul>\n";
	echo "</div>\n";
	
	echo "<fieldset id='replace'>\n";
	echo " <legend>".get_string('note_replace_legend',ANNOTATION_STRINGS)."</legend>\n";
	echo " <label for='old-note'>".get_string('note_replace_old',ANNOTATION_STRINGS).":</label><input id='old-note' type='text'/>\n";
	echo " <label for='new-note'>".get_string('note_replace_new',ANNOTATION_STRINGS).":</label><input id='new-note' type='text'/>\n";
	echo " <button>".get_string('note_replace_button',ANNOTATION_STRINGS)."</button>\n";
	echo " <p id='replace-count-prompt'>".get_string('note_update_count',ANNOTATION_STRINGS)."<span id='replace-count'/></p>\n";
	echo "</fieldset>\n";
	
	print_footer(null);

	$logurl = $_SERVER[ 'REQUEST_URI' ];
	$urlparts = parse_url( $logurl );
	$logurl = array_key_exists( 'query', $urlparts ) ? $urlparts[ 'query' ] : null;
	add_to_log( null, 'annotation', 'summary', 'edit-keywords.php' );
}

?>
