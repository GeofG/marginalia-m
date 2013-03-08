<?php

require_once( $CFG->dirroot.'/blocks/marginalia/config.php' );
require_once( ANNOTATION_DIR.'/annotation_globals.php' );

class moodle_marginalia
{
	/**
	 * Get an annotations preference value;  if the preference doesn't exist, create it
	 * so that the Javascript client will have permission to set it later (to prevent
	 * client creation of random preferences, only existing preferences can be set)
	 */
	public static function get_pref( $name, $default )
	{
		$value = get_user_preferences( $name, null );
		if ( null == $value ) {
			$value = $default;
			set_user_preference( $name, $default );
		}
		return $value;
	}
	
	public static function get_userid( )
	{
		global $USER;
		// Get the users whose annotations are to be shown
		$annotationuser = get_user_preferences( AN_USER_PREF, null );
		if ( null == $annotationuser )  {
			$annotationuser = isguest() ? null : $USER->username;
			set_user_preference( AN_USER_PREF, $annotationuser );
		}
		return $annotationuser;
	}
	
	public static function get_show_annotations_pref( )
	{
		return moodle_marginalia::get_pref( AN_SHOWANNOTATIONS_PREF, 'false' );
	}
	
	
	/**
	 * Return HTML for insertion in the head of a document to include Marginalia Javascript
	 * and initialize Marginalia.  If necessary, also creates relevant user preferences 
	 * (necessary for Marginalia to function correctly).
	 */
	public static function header_html( )
	{
		global $CFG, $USER;
		
		$anscripts = listMarginaliaJavascript( );
		for ( $i = 0;  $i < count( $anscripts );  ++$i )
			require_js( ANNOTATION_PATH.'/marginalia/'.$anscripts[ $i ] );
		require_js( array(
			ANNOTATION_PATH.'/marginalia-config.js',
			ANNOTATION_PATH.'/marginalia-strings.js',
			ANNOTATION_PATH.'/smartquote.js',
			ANNOTATION_PATH.'/MoodleMarginalia.js' ) );
	
		// Bits of YUI
		require_js( array(
			$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js',
	//		$CFG->wwwroot.'/lib/yui/datasource/datasource-min.js',
			$CFG->wwwroot.'/lib/yui/autocomplete/autocomplete-min.js' ) );
		
		$meta = "<link rel='stylesheet' type='text/css' href='".s($CFG->wwwroot)."/lib/yui/autocomplete/assets/skins/sam/autocomplete.css'/>\n"
			."<link rel='stylesheet' type='text/css' href='".s(ANNOTATION_PATH)."/marginalia/marginalia.css'/>\n"
			."<link rel='stylesheet' type='text/css' href='".s(ANNOTATION_PATH)."/annotation-styles.php'/>\n";
/*		Hack for attempt to get this working with the block:
		$meta = "<script type='text/javascript'>\n"
			."  domutil.loadStylesheet( '".s($CFG->wwwroot)."/lib/yui/autocomplete/assets/skins/sam/autocomplete.css');\n"
			."  domutil.loadStylesheet( '".ANNOTATION_PATH.'/marginalia/marginalia.css'."');\n"
			."  domutil.loadStylesheet( '".ANNOTATION_PATH.'/annotation-styles.php'."');\n"
			."</script>\n";
*/			
		return $meta;
	}
	
	/**
	 * Generate the content HTML.  This contains the Javascript necessary to
	 * initialized Marginalia.  It also require_js's a number of Javascript files.
	 */
	public static function init_html( $refurl )
	{
		global $CFG, $USER;
		
		// Get all annotation preferences as an associative array and sets them to defaults
		// in the database if not already present.
		$prefs = array(
			AN_USER_PREF => moodle_marginalia::get_userid( ),
			AN_SHOWANNOTATIONS_PREF => moodle_marginalia::get_pref( AN_SHOWANNOTATIONS_PREF, 'false' ),
			AN_NOTEEDITMODE_PREF => moodle_marginalia::get_pref( AN_NOTEEDITMODE_PREF, 'freeform' ),
			AN_SPLASH_PREF => moodle_marginalia::get_pref( AN_SPLASH_PREF, 'true' )
		);
		
		$showannotationspref = $prefs[ AN_SHOWANNOTATIONS_PREF ];
		$annotationuser = $prefs[ AN_USER_PREF ];
		$showsplashpref = $prefs[ AN_SPLASH_PREF ];
		
		// Build a string of initial preference values for passing to Marginalia
		$first = true;
		$sprefs = '';
		foreach ( array_keys( $prefs ) as $name )
		{
			$value = $prefs[ $name ];
			if ( $first )
				$first = false;
			else
				$sprefs .= "\n, ";
			$sprefs .= "'".s( $name )."': '".s( $prefs[ $name ] )."'";
		}
		$sprefs = '{ '.$sprefs.' }';;
		
		$sitecontext = get_context_instance(CONTEXT_SYSTEM);
		$allowAnyUserPatch = AN_ADMINUPDATE && (
			has_capability( 'moodle/legacy:admin', $sitecontext ) or has_capability( 'moodle/site:doanything', $sitecontext) );
		
		$meta = "<script language='JavaScript' type='text/javascript'>\n"
			."function myOnload() {\n"
			." var moodleRoot = '".s($CFG->wwwroot)."';\n"
			." var annotationPath = '".s(ANNOTATION_PATH)."';\n"
			." var url = '".s($refurl)."';\n"
			.' var userId = \''.s($USER->username)."';\n"
			.' moodleMarginalia = new MoodleMarginalia( annotationPath, url, moodleRoot, userId, '.$sprefs.', {'."\n";
		$meta .= ' useSmartquote: '.s(AN_USESMARTQUOTE)
			.",\n".' allowAnyUserPatch: '.($allowAnyUserPatch ? 'true' : 'false' )
			.",\n smartquoteIcon: '".AN_SMARTQUOTEICON."'"
			.",\n sessionCookie: 'MoodleSessionTest".$CFG->sessioncookie."'";
		if ( $showsplashpref == 'true' )
			$meta .= ',  splash: \''.get_string('splash',ANNOTATION_STRINGS).'\'';
		$meta .= '  } );'."\n"
			." moodleMarginalia.onload();\n"
			."}\n"
			."addEvent(window,'load',myOnload);\n"
			."</script>\n";
		return $meta;
	}
	
	function show_help( )
	{
		global $CFG;
		
		helpbutton( 'annotate', get_string( 'annotation_help', ANNOTATION_STRINGS ), 'block_marginalia' );
		/*
		$helptitle = 'Help with Annotations';
		$linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$helptitle.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
		echo link_to_popup_window ('/help.php?file=annotate.html&amp;forcelang=', 'popup',
										 $linkobject, 400, 500, $helptitle, 'none', true);
		 */
	}
	
	function show_user_dropdown( $refurl )
	{
		global $USER;
		
		$summary = annotation_summary_query::from_url( $refurl );
		$userlist = get_records_sql( $summary->list_users_sql( ) );
		$annotationuserid = moodle_marginalia::get_userid( );
		$showannotationspref = moodle_marginalia::get_show_annotations_pref( ) == 'true';
		
		echo "<select name='anuser' id='anuser' onchange='window.moodleMarginalia.changeAnnotationUser(this,\"$refurl\");'>\n";
		$selected = $showannotationspref ? '' : " selected='selected' ";
		echo " <option $selected value=''>".get_string('hide_annotations',ANNOTATION_STRINGS)."</option>\n";
		if ( ! isguest() )  {
			$selected = ( $showannotationspref && ( $USER->username == $annotationuserid ? "selected='selected' " : '' ) )
				? " selected='selected' " : '';
			echo " <option $selected"
				."value='".s($USER->username)."'>".get_string('my_annotations',ANNOTATION_STRINGS)."</option>\n";
		}
		if ( $userlist )  {
			foreach ( $userlist as $user )  {
				if ( $user->username != $USER->username )  {
					$selected = ( $showannotationspref && ( $user->id == $annotationuserid ? "selected='selected' ":'' ) )
						? " selected='selected' " : '';
					echo " <option $selected"
						."value='".s($user->username)."'>".s($user->firstname.' '.$user->lastname)."</option>\n";
				}
			}
		}
		// Show item for all users
		if ( true )  {
			$selected = ( $showannotationspref && ( '*' == $annotationuserid ? "selected='selected' ":'' ) )
				? " selected='selected' " : '';
			echo " <option $selected value='*'>".get_string('all_annotations',ANNOTATION_STRINGS)."</option>\n";
		}
		echo "</select>\n";	
	}
	
	
	function summary_link_html( $refurl, $userid )
	{
		global $CFG, $course;
		$summaryurl = ANNOTATION_PATH.'/summary.php?user='.urlencode($userid)
			."&url=".urlencode( $refurl );
		return " <a id='annotation-summary-link' href='".s($summaryurl)."'"
			. " title='".get_string('summary_link_title',ANNOTATION_STRINGS)
			."'>".get_string('summary_link',ANNOTATION_STRINGS)."</a>\n"
			
			."<a id='annotation-editkeywords-link' href='".ANNOTATION_PATH.'/tags.php?course='.$course->id."'"
			. " title='".get_string( 'edit_keywords_link', ANNOTATION_STRINGS )
			."'>Tags</a>\n";
	}
	
	
	/**
	 * Deletes all annotations of a specific user
	 * This is here rather than in the annotation code so that not everything will have to
	 * include the annotation code.
	 *
	 * @param int $userid
	 * @return boolean
	 */
	function annotations_delete_user( $userid )
	{
		return delete_records( AN_DBTABLE, 'id', $userid );
	}
}
