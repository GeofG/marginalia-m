<?php // handles annotation actions

require_once( "../../config.php" );
require_once( 'config.php' );
require_once( 'marginalia-php/Annotation.php' );
require_once( 'marginalia-php/AnnotationService.php' );
require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'annotation_globals.php' );
require_once( 'annotation_summary_query.php' );

if ( $CFG->forcelogin || ANNOTATION_REQUIRE_USER )
   require_login();

 
class moodle_annotation extends Annotation
{
	function is_action_valid( $action )
	{
		return null === $action || '' === $action;
	}
	
	function is_access_valid( $access )
	{
		return ! $access || 'public' == $access || 'private' == $access
			|| 'author' == $access || 'teacher' == $access
			|| 'author teacher' == $access;
	}	
}

class moodle_annotation_service extends AnnotationService
{
	function moodle_annotation_service( $userid )
	{
		global $CFG;

		// Note: Cross-site request forgery protection requires cookies, so it will not be
		// activated if $CFG->usesid=true
		$csrfprotect = ! empty( $CFG->usesid ) && $CFG->usesid;
		
		AnnotationService::AnnotationService( 
			annotation_globals::get_host(),
			annotation_globals::get_service_path(),
			annotation_globals::get_install_date(),
			$userid,
			array(
				'baseUrl' => $CFG->wwwroot,
				'csrfCookie' => $csrfprotect ? null : 'MoodleSessionTest'.$CFG->sessioncookie,
				'csrfCookieValue' => $csrfprotect ? null : $_SESSION['SESSION']->session_test )
			);
		$this->tablePrefix = $CFG->prefix;
	}
	
	function doListAnnotations( $url, $username, $block, $all )
	{
		$handler = annotation_summary_query::handler_for_url( $url );
		$user = get_record( 'user', 'username', $username );
		$summary = new annotation_summary_query( $url, $handler, null, $user, null, false, $all );
		if ( $summary->error )  {
			$this->httpError( 400, 'Bad Request', 'Bad URL 1' );
			return null;
		}
		elseif ( !isloggedin( ) && ANNOTATION_REQUIRE_USER )  {
			$this->httpError( 403, 'Forbidden', 'Anonymous listing not allowed' );
			return null;
		}
		else
		{
			$querysql = $summary->sql( 'section_type, section_name, quote_title, start_block, start_line, start_word, start_char, end_block, end_line, end_word, end_char' );
			$annotation_set = get_records_sql( $querysql );
			$annotations = Array( );
			if ( $annotation_set )  {
				$i = 0;
				foreach ( $annotation_set as $r )
					$annotations[ $i++ ] = annotation_globals::record_to_annotation( $r );
			}
			$format = $this->getQueryParam( 'format', 'atom' );
			$logurl = 'annotate.php?format='.$format.($user ? '&user='.$user->id : '').'&url='.$url;
			add_to_log( $summary->handler->courseid, 'annotation', 'list', $logurl );
			return $annotations;
		}
	}
	
	function doGetAnnotation( $id )
	{
		global $CFG;
	
		// Check whether the range column exists (for backwards compatibility)
		$range = '';
/*		if ( column_type( $this->tablePrefix.'annotation', 'range' ) )
			$range = ', a.range AS range ';
*/		
		// Caller should ensure that id is numeric
		$query = "SELECT a.id, a.userid, u.username as username, a.url,
			  a.start_block, a.start_xpath, a.start_line, a.start_word, a.start_char,
			  a.end_block, a.end_xpath, a.end_line, a.end_word, a.end_char,
			  a.note, a.access_perms, a.quote, a.quote_title, a.quote_author_id,
			  qu.username as quote_author_username,
			  a.link, a.link_title, a.action,
			  a.created, a.modified $range
			  FROM {$this->tablePrefix}".AN_DBTABLE." a
			  JOIN {$this->tablePrefix}user u ON u.id=a.userid
			  JOIN {$this->tablePrefix}user qu ON qu.id=a.quote_author_id
			WHERE a.id = $id";
		$resultset = get_record_sql( $query );
		if ( $resultset && count( $resultset ) != 0 )  {
			$annotation = annotation_globals::record_to_annotation( $resultset );
			return $annotation;
		}
		else
			return null;
	}
	
	function doCreateAnnotation( $annotation )
	{
		if ( strlen( $annotation->getNote( ) ) > MAX_NOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Note too long' );
		elseif ( strlen( $annotation->getQuote( ) ) > MAX_QUOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Quote too long' );
		else
		{
			$time = time( );
			$annotation->setCreated( $time );
			$annotation->setModified( $time );
			$record = annotation_globals::annotation_to_record( $annotation );
			
			// Figure out the object type and ID from the url
			// Doing this here avoids infecting the caller with application-specific mumbo-jumbo
			// The cost of doing it here is low because annotations are created one-by one.  In essence,
			// this is really caching derived fields in the database to make queries easier.  (If only
			// MySQL had added views before v5).
			if ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $annotation->getUrl( ), $matches ) )  {
				$record->object_type = AN_OTYPE_POST;
				$record->object_id = (int) $matches[ 1 ];
			}
	
			// must preprocess fields
			$id = insert_record( AN_DBTABLE, $record, true );
			
			if ( $id )  {
				// TODO: fill in queryStr for the log
				$urlquerystr = '';
				$logurl = 'annotate.php' . ( $urlquerystr ? '?'.$urlquerystr : '' );
				add_to_log( null, 'annotation', 'create', $logurl, "$id" );
				return $id;
			}
		}
		return 0;
	}
	
	function doUpdateAnnotation( $annotation )
	{
		$urlquerystr = '';
		$annotation->setModified( time( ) );
		$record = annotation_globals::annotation_to_record( $annotation );
		$logurl = 'annotate.php' . ( $urlquerystr ? '?'.$urlquerystr : '' );
		add_to_log( null, 'annotation', 'update', $logurl, "{$annotation->id}" );
		return update_record( AN_DBTABLE, $record );
	}
	
	function doBulkUpdate( $oldnote, $newnote )
	{
		global $CFG, $USER;
		
		$where = "userid='".addslashes($USER->id)."' AND note='".addslashes($oldnote)."'";

		// Count how many replacements will be made
		$query = 'SELECT count(id) AS n FROM '.$CFG->prefix.AN_DBTABLE." WHERE $where";
		$result = get_record_sql( $query );
		$n = (int)$result->n;
		
		if ( $n )  {
			// Do the replacements
			$query = 'UPDATE '.$CFG->prefix.AN_DBTABLE
				." set note='".addslashes($newnote)."',"
				." modified=".time( )
				." WHERE $where";
			execute_sql( $query, false );
		}
		header( 'Content-type: text/plain' );
		return $n;
	}
	
	function doDeleteAnnotation( $id )
	{
		delete_records( AN_DBTABLE, 'id', $id );
		$logurl = "annotate.php?id=$id";
		add_to_log( null, 'annotation', 'delete', $logurl, "$id" );
		return True;
	}

	function listBodyParams( )
	{
		return MarginaliaHelper::ListBodyParams( true );
	}
}

$service = new moodle_annotation_service( isguest() ? null : $USER->username );
$service->dispatch( );

?>
