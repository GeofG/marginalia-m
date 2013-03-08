<?php
	
/**
 * Objects of this class are immutable.  Use the for_ methods to generate
 * modified version (e.g. for links to related summaries)
 */
class annotation_summary_query
{
	var $url;
	var $text;
	var $user;
	var $ofuser;
	var $exatcmatch;
	var $all;
	var $handler;		// URL handlers (implements much of this class's behavior)
	
	var $sql;			// The result SQL query
	var $error;			// Any error encountered by the constructor
	
	function annotation_summary_query( $url, $handler, $text, $user, $ofuser, $exactmatch, $all )
	{
		$this->url = $url;
		$this->handler = $handler;
		$handler->summary = $this;
		$this->text = $text;
		$this->user = $user;
		$this->ofuser = $ofuser;
		$this->exactmatch = $exactmatch;
		$this->all = $all;
		
		if ( $this->url && ! $this->handler )
			$this->handler = annotation_summary_query::handler_for_url( $url );
	}
	
	// useful if you just want to list users
	static function from_url( $url )
	{
		return new annotation_summary_query( $url, null, null, null, null, false, false );
	}
	
	static function from_params( )
	{
		$url = array_key_exists( 'url', $_GET ) ? $_GET[ 'url' ] : null;
		$text = array_key_exists( 'q', $_GET ) ? $_GET[ 'q' ] : null;
		$username = array_key_exists( 'u', $_GET ) ? $_GET[ 'u' ] : null;
		$ofusername = array_key_exists( 'search-of', $_GET ) ? $_GET[ 'search-of' ] : null;
		$exactmatch = array_key_exists( 'match', $_GET ) ? 'exact' == $_GET[ 'match' ] : false;
		$all = array_key_exists( 'all', $_GET ) ?
			'yes' == $_GET[ 'all' ] || 'true' == $_GET[ 'all' ] : false;
		
		$user = null;
		if ( $username )
			$user = get_record( 'user', 'username', $username );
			
		$ofuser = null;
		if ( $ofusername )
			$ofuser = get_record( 'user', 'username', $ofusername );
		
		$handler = annotation_summary_query::handler_for_url( $url );
		
		return new annotation_summary_query( $url, $handler, $text, $user, $ofuser, $exactmatch, $all );
	}

	static function handler_for_url( $url )
	{
		// A course or something *must* be specified
		if ( ! $url )
			return null;
		
		// All annotations for a course
		elseif ( preg_match( '/^.*\/course\/view\.php\?id=(\d+)/', $url, $matches ) )
			return new course_annotation_url_handler( (int) $matches[ 1 ]);

		// All annotations far a single forum
		elseif ( preg_match( '/^.*\/mod\/forum\/view\.php\?id=(\d+)/', $url, $matches ) )
			return new forum_annotation_url_handler( (int) $matches[ 1 ] );

		// Annotations for a single discussion
		elseif ( preg_match( '/^.*\/mod\/forum\/discuss\.php\?d=(\d+)/', $url, $matches ) )
			return new discussion_annotation_url_handler( (int) $matches[ 1 ] );

		// Annotations for a single post
		elseif ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $url, $matches ) )
			return new post_annotation_url_handler( (int) $matches[ 1 ] );

		else
			return null;
	}
	
	function for_url( $url )
	{
		return new annotation_summary_query( $url, annotation_summary_query::handler_for_url( $url ),
			$this->text, $this->user, $this->ofuser, $this->exactmatch, $this->all );
	}
	
	function for_user( $user )
	{
		return new annotation_summary_query( $this->url, $this->handler, $this->text,
			$user, $this->ofuser, $this->exactmatch, $this->all );
	}
	
	function for_ofuser( $ofuser )
	{
		return new annotation_summary_query( $this->url, $this->handler, $this->text,
			$this->user, $ofuser, $this->exactmatch, $this->all );
	}
	
	function for_parent( )
	{
		$this->handler->fetch_metadata( );
		if ( $this->handler->parenturl )  {
			return new annotation_summary_query( $this->handler->parenturl,
				annotation_summary_query::handler_for_url( $this->handler->parenturl ),
				$this->text, $this->user, $this->ofuser, $this->exactmatch, $this->all );
		}
		else
			return null;
	}
	
	function for_text( $text, $exact=false )
	{
		return new annotation_summary_query( $this->url, $this->handler, $text,
			$this->user, $this->ofuser, $exact, $this->all );
	}
	
	function for_match( $exact=false )
	{
		return new annotation_summary_query( $this->url, $this->handler, $this->text,
			$this->user, $this->ofuser, $exact, $this->all );
	}
	
	function titlehtml( )
	{
		$this->handler->fetch_metadata( );
		return $this->handler->titlehtml;
	}
	
	function fullname( $user )
	{
		return $user->firstname . ' ' . $user->lastname;
	}
	
	/** Produce a natural language description of a query */
	function desc( $titlehtml=null )
	{
		global $USER;
		
		$this->handler->fetch_metadata( );
		
		$a->title = null === $titlehtml ? $this->handler->titlehtml : $titlehtml;
		$a->who = $this->user ? s( $this->fullname( $this->user ) ) : get_string( 'anyone', ANNOTATION_STRINGS );
		$a->author = $this->ofuser ? s( $this->fullname( $this->ofuser ) ) : get_string( 'anyone', ANNOTATION_STRINGS );
		$a->search = s( $this->text );
		$a->match = get_string( $this->exactmatch ? 'matching' : 'containing', ANNOTATION_STRINGS );
		
		if ( null != $this->text && '' != $this->text )
			$s = $this->ofuser ? 'annotation_desc_authorsearch' : 'annotation_desc_search';
		else
			$s = $this->ofuser ? 'annotation_desc_author' : 'annotation_desc';
			
		return get_string( $s, ANNOTATION_STRINGS, $a );
		
		return $desc;
	}
	
	/** A natural language description, with elements as links to more general queries */
	function desc_with_links( $titlehtml=null )
	{
		global $USER;
		
		$this->handler->fetch_metadata( );
		
		$a->title = null === $titlehtml ? $this->handler->titlehtml : $titlehtml;
		
		// Show link to parent search
		$parent_summary = $this->for_parent( );
		if ( $parent_summary ) {
			$a->title = '<a class="opt-link" href="'.s( $parent_summary->summary_url( ) )
				. '" title="'.get_string( 'unzoom_url_hover', ANNOTATION_STRINGS ).'">'
				. '<span class="current">'.$a->title.'</span>'
				. '<span class="alt">'.$parent_summary->titlehtml( ).'</span></a>';
		}
		
		// Unzoom from user to anyone
		if ( $this->user )  {
			$summary_anyone = $this->for_user( null );
			$a->who = '<a class="opt-link" href="'.s( $summary_anyone->summary_url( ) )
				.'" title="'.get_string( 'unzoom_user_hover', ANNOTATION_STRINGS )
				.'"><span class="current">'.s( $this->fullname( $this->user ) ).'</span><span class="alt">'
				.get_string( 'anyone', ANNOTATION_STRINGS ).'</a></a>';
		}
		else
			$a->who = get_string( 'anyone', ANNOTATION_STRINGS );
		
		// Unzoom from of user to of anyone
		if ( $this->ofuser )  {
			$summary_anyone = $this->for_ofuser( null );
			$a->author = '<a class="opt-link" href="'.s( $summary_anyone->summary_url( ) )
				.'" title="'.get_string( 'unzoom_author_hover', ANNOTATION_STRINGS )
				.'"><span class="current">'.s( $this->fullname( $this->ofuser ) ).'</span><span class="alt">'
				.get_string( 'anyone', ANNOTATION_STRINGS ).'</span></a>';
		}
		else
			$a->author = null;
		
		$a->search = $this->text;
		
		// Toggle exact match
		$summary_match = $this->for_match( ! $this->exactmatch );
		$hover = get_string( $this->exactmatch ? 'unzoom_match_hover' : 'zoom_match_hover', ANNOTATION_STRINGS );
		$m1 = get_string( $this->exactmatch ? 'matching' : 'containing', ANNOTATION_STRINGS );
		$m2 = get_string( $this->exactmatch ? 'containing' : 'matching', ANNOTATION_STRINGS );
		$a->match = '<a class="opt-link" href="'.s( $summary_match->summary_url( ) )
			.'" title="'.$hover
			.'"><span class="current">'.$m1
			.'</span><span class="alt">'.$m2.'</span></a>';

		if ( $this->text )
			$s = ( null != $this->ofuser ) ? 'annotation_desc_authorsearch' : 'annotation_desc_search';
		else
			$s = ( null != $this->ofuser ) ? 'annotation_desc_author' : 'annotation_desc';
		
		$desc = get_string( $s, ANNOTATION_STRINGS, $a );

		return $desc;
	}
	
	/**
	 * This takes a list of handlers, each of which corresponds to a particular type of
	 * query (e.g. discussion forum), along with search fields for performing a search.
	 * It returns the SQL query string.
	 *
	 * $searchAccess can be public, private, or empty.  Public annotations are available to
	 *  *everyone*, not just course members or Moodle users.
	 */
	function sql( $orderby )
	{
		global $CFG, $USER;
		
		// The query is a UNION of separate queries, one for each type of annotation
		// This is unfortunate:  with a common table structure, one for parent-child
		// URL relationships, another with URL properties (title and owner would
		// suffice), would forgo UNIONs and simplify this code.
		
		// Users can only see their own annotations or the public annotations of others
		// This is an awfully complex combination of conditions.  I'm wondering if that's
		// a design flaw.
		$accesscond = null;
		$descusers = '';
		
		// this was originally intended to allow more than one handler to respond to a request.
		// That may still be necessary someday, but perhaps a compound handler would be the
		// best way to respond to it.  I eliminated the handler list because YAGNI.
		$handler = $this->handler;

		// Conditions under which someone else's annotation would be visible to this user
		$accessvisible = "a.access_perms & ".AN_ACCESS_PUBLIC;
		if ( array_key_exists( 'username', $USER ) )  {
			$accessvisible .= " OR a.userid=".$USER->id
				. " OR a.access_perms & ".AN_ACCESS_AUTHOR." AND a.quote_author_id=".$USER->id;
		}
		
		// If the all flag is set, see if this is an admin user with permission to
		// export all annotations.
		if ( $this->all )  {
			$sitecontext = get_context_instance( CONTEXT_SYSTEM );
			$all = AN_ADMINVIEWALL && ( has_capability( 'moodle/legacy:admin', $sitecontext )
				or has_capability( 'moodle/site:doanything', $sitecontext) );
			if ( $all )
				$accessvisible = '1=1';
		}
		
		// Filter annotations according to their owners
		
		// Admin only (used especially for research): transcend usual privacy limitations
		if ( null == $this->user )
			$accesscond = " ($accessvisible) ";
		else  {
			if ( ! isloggedin( ) || $USER->id != $this->user->id )
				$accesscond = "($accessvisible)";
			if ( $accesscond )
				$accesscond .= ' AND ';
			$accesscond .= "a.userid=".$this->user->id;
		}

	
		// These are the fields to use for a search;  specific annotations may add more fields
		$stdsearchfields = array( 'a.note', 'a.quote', 'u.firstname', 'u.lastname' );
		
		$prefix = $CFG->prefix;
		
		// Do handler-specific stuff

		// Check whether the range column exists (for backwards compatibility)
		$range = '';

		// These that follow are standard fields, for which no page type exceptions can apply
		$qstdselect = "SELECT a.id AS id, a.url AS url, a.userid AS userid"
		. ", a.start_block, a.start_xpath, a.start_line, a.start_word, a.start_char"
		. ", a.end_block, a.end_xpath, a.end_line, a.end_word, a.end_char"
		. ", a.link AS link, a.link_title AS link_title, a.action AS action"
		. ", a.access_perms AS access_perms, a.created, a.modified $range"
		. ", u.username AS username"
		. ",\n concat(u.firstname, ' ', u.lastname) AS fullname"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',u.id) AS note_author_url"
		. ",\n a.note note, a.quote, a.quote_title AS quote_title"
		. ",\n qu.username AS quote_author_username"
		. ",\n concat(qu.firstname, ' ', qu.lastname) AS quote_author_fullname"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',qu.id) AS quote_author_url";
		
		// Standard tables apply to all (but note the outer join of user, which if gone
		// should not steal the annotation from its owner):
		$qstdfrom = "\nFROM {$prefix}".AN_DBTABLE." a"
			. "\n INNER JOIN {$prefix}user u ON u.id=a.userid"
			. "\n LEFT OUTER JOIN {$prefix}user qu on qu.id=a.quote_author_id";
		
		// This search is always limited by access
		$qstdwhere = "\nWHERE ($accesscond)";

		// Searching limits also;  fields searched are not alone those of the annotation:
		// add to them also those a page of this type might use.
		if ( null != $this->text && '' != $this->text )  {
			if ( $this->exactmatch )
				$qstdwhere .= "\n   AND a.note='".addslashes($this->text)."'";
			else  {
				$searchcond = '';
				$addsearchfields = $handler->get_search_fields( );
				$searchcond = '';
				$querywords = split( ' ', $this->text );
				foreach ( $querywords as $word )
				{
					$sword = addslashes( $word );
					foreach ( $stdsearchfields as $field )
						$searchcond .= ( $searchcond == '' ) ? "$field LIKE '%$sword%'" : " OR $field LIKE '%$sword%'";
					foreach ( $addsearchfields as $field )
						$searchcond .= " OR $field LIKE '%$sword%'";
				}
				$qstdwhere .= "\n   AND ($searchcond)";
			}
		}
		
		// The handler must construct the query, which might be a single SELECT or a UNION of multiple SELECTs
		$q = $handler->get_sql( $this, $qstdselect, $qstdfrom, $qstdwhere, $orderby );
		
		return $q;
	}
	
	/** Get query to list users with public annotations on this discussion */
	function list_users_sql( )
	{
		global $CFG;
		return "SELECT u.id, u.username, u.firstname, u.lastname "
			. "\nFROM {$CFG->prefix}user u "
			. "\nINNER JOIN {$CFG->prefix}".AN_DBTABLE." a ON a.userid=u.id "
			. $this->handler->get_tables( )
			. "\nWHERE a.access_perms & ".AN_ACCESS_PUBLIC;
	}
	
	/** Generate a summary URL corresponding to this query */
	function summary_url( )
	{
		global $CFG;
		$s = ANNOTATION_PATH."/summary.php?url=".urlencode($this->url);
		if ( null != $this->text && '' != $this->text )
			$s .= '&q='.urlencode($this->text);
		if ( null != $this->user )
			$s .= '&u='.urlencode($this->user->username);
		if ( null != $this->ofuser )
			$s .= '&search-of='.urlencode($this->ofuser->username);
		if ( $this->exactmatch )
			$s .= '&match=exact';
		return $s;
	}
	
	/** Generate a feed URL corresponding to this query */
	function get_feed_url( $format='atom' )
	{
		if ( 'atom' == $format )
			return $this->summary_url( ) . '&format=atom';
		else
			return null;
	}
}


class annotation_url_handler
{
	function annotation_url_handler( )
	{ }
	
	// This pulls together the query from the standard portions (which are passed in)
	// and from the handler-specific portions.  Some handlers may override this, e.g. in order
	// to construct a UNION.
	function get_sql( $summary, $qstdselect, $qstdfrom, $qstdwhere, $orderby )
	{
		$q = $qstdselect
			. $this->get_fields( )
			. "\n" . $qstdfrom
			. $this->get_tables( )
			. "\n" . $qstdwhere
			. $this->get_conds( $summary );
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		return $q;
	}
			
	function get_search_fields( )
	{
		return array( );
	}
}


/*
 * Oh, for a language with proper lists...
 */
/*
 A course handler is a nice enough idea, but what does it mean?  Does it retrieve all annotations for
 that course, or shoud there actually be a way to get all discussion annotations for a course?  How
 does it know about all of the sub-level entities that can be annotated (forum posts etc.)?  For now,
 I think adding an optional courseId parameter to ForumAnnotationHandler may be a better option, though
 it will look like a bit of a hack.
*/
class course_annotation_url_handler extends annotation_url_handler
{
	var $courseid;
	var $titlehtml;
	var $parenturl;
	var $parenttitlehtml;
	
	function course_annotation_url_handler( $courseid )
	{
		$this->courseid = $courseid;
		$this->titlehtml = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parenturl, parenttitle, courseid.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
	{
		global $CFG;
		
		if ( null != $this->titlehtml )
			return;
		$query = "SELECT fullname "
			. " FROM {$CFG->prefix}course WHERE id={$this->courseid}";
		$row = get_record_sql( $query );
		if ( False !== $row )
			$this->titlehtml = s( $row->fullname );
		else
			$this->titlehtml = get_string( 'unknown course', ANNOTATION_STRINGS );
		$this->parenturl = null;
		$this->parenttitlehtml = null; 
	}
	
	// Override the default implementation of getSql.  This must construct a UNION of multiple queries.
	
	function get_sql( $summary, $qstdselect, $qstdfrom, $qstdwhere, $orderby )
	{
		global $CFG;
		$q = '';
		
		// Conditions
		$cond = "\n  AND a.object_type=".AN_OTYPE_POST;
		if ( $summary->ofuser )
			$cond .= " AND p.userid=".$summary->ofuser->id;

		// First section:  discussion posts
		$q = $qstdselect
			 . ",\n 'forum' section_type, 'content' row_type"
			 . ",\n f.name section_name"
			 . ",\n concat('{$CFG->wwwroot}/mod/forum/view.php?id=',f.id) section_url"
			. $qstdfrom
			 . "\n INNER JOIN {$CFG->prefix}forum_discussions d ON d.course=".$this->courseid.' '
			 . "\n INNER JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND a.object_type=".AN_OTYPE_POST." AND p.id=a.object_id "
			 . "\n INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
			. $qstdwhere
			. $this->get_conds( $summary );
		
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		
		// If further types of objects can be annotated, additional SELECT statements must be added here
		// as part of a UNION.		
		
		return $q;
	}

	function get_conds( $summary )
	{
		$cond = "\n  AND a.object_type=".AN_OTYPE_POST;
		if ( $summary->ofuser )
			$cond .= " AND a.quote_author_id=".$summary->ofuser->id;
		return $cond;
	}
}


class forum_annotation_url_handler extends annotation_url_handler
{
	var $f;
	var $titlehtml;
	var $parenturl;
	var $parenttitlehtml;
	var $courseid;
	
	function forum_annotation_url_handler( $f )
	{
		$this->f = $f;
		$this->titlehtml = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
	{
		global $CFG;
		
		if ( null != $this->titlehtml )
			return;
		else  {
			$query = "SELECT id, name, course FROM {$CFG->prefix}forum WHERE id={$this->f}";
			$row = get_record_sql( $query );
			if ( False !== $row )
			{
				$a->name = s( $row->name );
				$this->titlehtml = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
				$this->courseid = (int) $row->course;
			}
			else
			{
				$this->titlehtml = get_string( 'unknown_forum', ANNOTATION_STRINGS );
				$this->courseid = null;
			}
			$this->parenturl = '/course/view.php?id='.$this->courseid;
			$this->parenttitlehtml = get_string( 'whole_course', ANNOTATION_STRINGS ); 
		}
	}
	
	function get_fields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function get_tables( )
	{
		global $CFG;
		if ( null == $this->f )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.forum=".addslashes($this->f)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function get_conds( $summary )
	{
		$cond = "\n  AND a.object_type=".AN_OTYPE_POST;
		if ( $summary->ofuser )
			$cond .= " AND a.quote_author_id=".$summary->ofuser->id;
		return $cond;
	}
	
	function get_search_fields( )
	{
		return array( 'd.name' );
	}
}


class discussion_annotation_url_handler extends annotation_url_handler
{
	var $d;
	var $titlehtml;
	var $parenturl;
	var $parenttitlehtml;
	var $courseid;
	var $forumid;
	
	function discussion_annotation_url_handler( $d )
	{
		$this->d = $d;
		$this->titlehtml = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
	{
		global $CFG;
	
		if ( null != $this->titlehtml )
			return;
		elseif ( null == $this->d )  {
			$this->titlehtml = get_string( 'all_discussions', ANNOTATION_STRINGS );
			$this->parenturl = null;
			$this->parenttitlehtml = null;
			$this->courseid = null;
		}
		else  {
			$query = "SELECT d.id AS id, d.name AS name, d.course AS course, d.forum AS forum, f.name AS forum_name"
				. " FROM {$CFG->prefix}forum_discussions d "
				. " INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
				. " WHERE d.id={$this->d}";
			$row = get_record_sql( $query );
			$forumname = 'unknown';
			if ( False !== $row )  {
				$a->name = s( $row->name );
				$this->titlehtml = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
				$this->courseid = (int) $row->course;
				$this->forumid = (int) $row->forum;
				$forumname = $row->forum_name;
			}
			else  {
				$this->titlehtml = get_string( 'unknown_discussion', ANNOTATION_STRINGS );
				$this->courseid = null;
				$this->forumid = null;
			}
			$this->parenturl = '/mod/forum/view.php?id='.$this->forumid;
			$a->name = s( $forumname );
			$this->parenttitlehtml = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
		}
	}
	
	function get_fields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function get_tables( )
	{
		global $CFG;
		if ( null == $this->d )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.id=".addslashes($this->d)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function get_conds( $summary )
	{
		$cond = "\n  AND a.object_type=".AN_OTYPE_POST;
		if ( $summary->ofuser )
			$cond .= " AND a.quote_author_id=".$summary->ofuser->id;
		return $cond;
	}
	
	function get_search_fields( )
	{
		return array( 'd.name' );
	}
}

class post_annotation_url_handler extends annotation_url_handler
{
	var $p;
	var $titlehtml;
	var $parenturl;
	var $parenttitlehtml;
	var $courseid;
	
	function post_annotation_url_handler( $p )
	{
		$this->annotation_url_handler( );
		$this->p = $p;
		$this->title = null;
	}
	
	function fetch_metadata( )
	{
		global $CFG;
		
		if ( null != $this->titlehtml )
			return;
		
		$query = "SELECT p.subject pname, d.id did, d.name dname, d.course course"
			. " FROM {$CFG->prefix}forum_posts AS p"
			. " INNER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion"
			. " WHERE p.id=$p";
		$row = get_record_sql( $query );
		if ( False === $row )  {
			$this->titlehtml = get_string( 'unknown_post', ANNOTATION_STRINGS );
			$this->parenturl = null;
			$this->parenttitlehtml = null;
			$this->courseid = null;
		}
		else  {
			$a->name = s( $row->pname );
			$this->titlehtml = get_string( 'post_name', ANNOTATION_STRINGS, $a );
			$this->parenturl = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$row->did;
			$a->name = s( $row->dname );
			$this->parenttitlehtml = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
			$this->courseid = (int) $row->course;
		}
	}

	function get_fields( )
	{
		global $CFG;
		return ",\n 'post' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url"
			. ",\n 'post' object_type"
			. ",\n p.id object_id";
	}
	
	function get_tables( )
	{
		global $CFG;
		return 	"\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
			. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion";
	}
	
	function get_conds( $summary )
	{
		$cond = "\n AND a.object_type=".AN_OTYPE_POST;
		if ( $summary->ofuser )
			$cond .= " AND a.quote_author_id=".$summary->ofuser->id;
		return $cond;
	}
}

