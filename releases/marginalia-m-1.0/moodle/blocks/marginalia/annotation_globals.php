<?php

// The smartquote icon symbol(s)
define( 'AN_SMARTQUOTEICON', '\u275d' );	// \u275b\u275c: enclosed single qs, 267a: recycle

// The same thing as entities because - and this stuns the hell out of me every
// single time - PHP 5 *does not have native unicode support*!!!  Geez guys,
// I remember reading about unicode in Byte Magazine in what, the 1980s?
define( 'AN_SMARTQUOTEICON_HTML', '&#10077' ); //'&#10075;&#10076;' );

// Icon for filtering on the summary page
define( 'AN_FILTERICON_HTML', '&#9754;' );  //&#9756;
	
define( 'ANNOTATION_STRINGS', 'block_marginalia' );

define( 'AN_USER_PREF', 'annotations.user' );
define( 'AN_SHOWANNOTATIONS_PREF', 'annotations.show' );
define( 'AN_NOTEEDITMODE_PREF', 'annotations.note-edit-mode' );
define( 'AN_SPLASH_PREF', 'annotations.splash' );
define( 'SMARTCOPY_PREF', 'smartcopy' );

define( 'AN_DBTABLE', 'marginalia' );

define( 'AN_ACCESS_PRIVATE', 0 );
define( 'AN_ACCESS_AUTHOR', 0x1 );
define( 'AN_ACCESS_PUBLIC', 0xffff );

// Object types
define ( 'AN_OTYPE_POST', 1 );

class annotation_globals
{
	function get_host( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		return $urlparts[ 'host' ];
	}
	
	function get_service_path( )
	{
		global $CFG;
		return $CFG->wwwroot . ANNOTATION_PATH . '/annotate.php';
//		return annotation_globals::getMoodlePath( ) . ANNOTATE_SERVICE_PATH;
	}
	
	FUNCTION get_keyword_service_path( )
	{
		global $CFG;
		return $CFG->wwwroot . ANNOTATION_PATH . '/keywords.php';
	}
	
	/** Get the moodle path - that is, the path to moodle from the root of the server.  Typically this is 'moodle/'.
	 * REQUEST_URI starts with this. */
	function get_moodle_path( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		return $urlparts[ 'path' ];
	}
	
	/**
	 * Get the sever part of the moodle path.
	 * This is the absolute path, with the getMoodlePath( ) portion chopped off.
	 * Useful, because appending a REQUEST_URI to it produces an absolute URI. */
	function get_moodle_server( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		if ( $urlparts[ 'path' ] == '/' )
			return $CFG->wwwroot;
		else
			return substr( $CFG->wwwroot, 0, strpos( $CFG->wwwroot, $urlparts[ 'path' ] ) );
	}

	function get_install_date( )
	{
		// Hardcoded because I'm not aware of Moodle recording an install date anywhere
		return strtotime( '2005-07-20' );
	}
	
	function get_feed_tag_uri( )
	{
		return "tag:" . annotation_globals::get_host() . ',' . date( 'Y-m-d', annotation_globals::get_install_date() ) . ":annotation";
	}
	
	/**
	 * Remember: This the Annotation class does not store Moodle user IDs, so
	 * you must be sure to query for username and quote_author_username if you
	 * want userid and quoteAuthorId set.
	 */
	function record_to_annotation( $r )
	{
		$annotation = new Annotation( );
		
		$annotation->setAnnotationId( $r->id );
		
		if ( array_key_exists( 'username', $r ) )
			$annotation->setUserId( $r->username );
		if ( array_key_exists( 'fullname', $r ) )
			$annotation->setUserName( $r->fullname );
		
		if ( array_key_exists( 'access_perms', $r ) )
		{
			if ( $r->access_perms & AN_ACCESS_PUBLIC )
				$annotation->setAccess( 'public' );
			else
				$annotation->setAccess( 'private' );
		}
		if ( array_key_exists( 'url', $r ) )
			$annotation->setUrl( $r->url );
		if ( array_key_exists( 'note', $r ) )
			$annotation->setNote( $r->note );
		if ( array_key_exists( 'quote', $r ) )
			$annotation->setQuote( $r->quote );
		if ( array_key_exists( 'quote_title', $r ) )
			$annotation->setQuoteTitle( $r->quote_title );
		if ( array_key_exists( 'quote_author_username', $r ) )
			$annotation->setQuoteAuthorId( $r->quote_author_username );
		elseif ( array_key_exists( 'quote_author', $r ) )	// to support old mdl_annotation table
			$annotation->setQuoteAuthorId( $r->quote_author );
		if ( array_key_exists( 'quote_author_fullname', $r ) )
			$annotation->setQuoteAuthorName( $r->quote_author_fullname );
		if ( array_key_exists( 'link', $r ) )
			$annotation->setLink( $r->link );
		if ( array_key_exists( 'link_title', $r ) )
			$annotation->setLinkTitle( $r->link_title );
		if ( array_key_exists( 'created', $r ) )
			$annotation->setCreated( (int) $r->created );
		if ( array_key_exists( 'modified', $r ) )
			$annotation->setModified( (int) $r->modified );
		
		$start_line = array_key_exists( 'start_line', $r ) ? $r->start_line : 0;
		$end_line = array_key_exists( 'end_line', $r ) ? $r->end_line : 0;
		// The second and subsequente lines of the test are to catch cases where everything is blank,
		// which can happen if the range is really old and uses the range field
		if ( array_key_exists( 'start_block', $r ) && $r->start_block !== null 
			&& ( ! array_key_exists( 'range', $r )
				|| ( $start_line || $end_line || $r->start_block || $r->end_block || $r->start_word || $r->end_word || $r->start_char || $r->end_char ) ) )
		{
			$range = new SequenceRange( );
			$range->setStart( new SequencePoint( $r->start_block, $start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new SequencePoint( $r->end_block, $end_line, $r->end_word, $r->end_char ) );
			$annotation->setSequenceRange( $range );
		}
		// Older versions used a range string column.  Check and translate that field here:
		else if ( array_key_exists( 'range', $r ) && $r->range !== null )  {
			$range = new SequenceRange( );
			$range->fromString( $r->range );
			$annotation->setSequenceRange( $range );
		}
		
		if ( array_key_exists( 'start_xpath', $r ) && $r->start_xpath !== null )  {
			$range = new XPathRange( );
			$range->setStart( new XPathPoint( $r->start_xpath, $start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new XpathPoint( $r->end_xpath, $end_line, $r->end_word, $r->end_char ) );
			$annotation->setXPathRange( $range );
		}
		
		return $annotation;
	}
		
	function annotation_to_record( $annotation )
	{
		$id = $annotation->getAnnotationId( );
		if ( $id )
			$record->id = $id;
		
		// Map username to id #
		$username = $annotation->getUserId( );
		$user = get_record( 'user', 'username', $username );
		$record->userid = $user ? $user->id : null;

		$access = $annotation->getAccess( );
		$record->access_perms = 'public' == $access ? AN_ACCESS_PUBLIC : AN_ACCESS_PRIVATE;
		$record->url = addslashes( $annotation->getUrl( ) );
		$record->note = addslashes( $annotation->getNote( ) );
		$record->quote = addslashes( $annotation->getQuote( ) );
		$record->quote_title = addslashes( $annotation->getQuoteTitle( ) );
		
		// Map author username to id #
		$username = $annotation->getQuoteAuthorId( );
		$user = get_record( 'user', 'username', $username );
		$record->quote_author_id = $user ? $user->id : null;
		
		$record->link = addslashes( $annotation->getLink( ) );
		$record->link_title = addslashes( $annotation->getLinkTitle( ) );
		$record->created = $annotation->getCreated( );
		$record->modified = $annotation->getModified( );

		$sequenceRange = $annotation->getSequenceRange( );
		$sequenceStart = $sequenceRange->getStart( );
		$sequenceEnd = $sequenceRange->getEnd( );
		$xpathRange = $annotation->getXPathRange( );
		if ( null !== $xpathRange )  {
			$xpathStart = $xpathRange->getStart( );
			$xpathEnd = $xpathRange->getEnd( );
		}
		
		$record->start_block = addslashes( $sequenceStart->getPaddedPathStr( ) );
		$record->start_xpath = null === $xpathRange ? null : addslashes( $xpathStart->getPathStr( ) );
		$record->start_line = $sequenceStart->getLines( );
		$record->start_word = $sequenceStart->getWords( ) ? $sequenceStart->getWords( ) : 0;
		$record->start_char = $sequenceStart->getChars( );
		
		$record->end_block = addslashes( $sequenceEnd->getPaddedPathStr( ) );
		$record->end_xpath = null === $xpathRange ? null : addslashes( $xpathEnd->getPathStr( ) );
		$record->end_line = $sequenceEnd->getLines( );
		$record->end_word = $sequenceEnd->getWords( ) ? $sequenceEnd->getWords( ) : 0;
		$record->end_char = $sequenceEnd->getChars( );
		return $record;
	}
}
