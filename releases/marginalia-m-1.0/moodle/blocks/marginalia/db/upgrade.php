<?php

global $CFG;

require_once( $CFG->dirroot.'/blocks/marginalia/config.php' );
require_once( ANNOTATION_DIR.'/annotation_globals.php' );
require_once( ANNOTATION_DIR.'/marginalia-php/Annotation.php' );
require_once( ANNOTATION_DIR.'/marginalia-php/MarginaliaHelper.php' );
require_once( ANNOTATION_DIR.'/marginalia-php/SequenceRange.php' );
require_once( ANNOTATION_DIR.'/marginalia-php/XPathRange.php' );
	
function xmldb_block_marginalia_upgrade( $oldversion )
{
	global $CFG;
	
	$result = true;
	
	if ( $result && $oldversion < 2008121000 )  {
		
		/// Define table annotation to be created
		$table = new XMLDBTable('marginalia');
		
		/// Adding fields to table annotation
		$table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
	//	$table->addFieldInfo('userid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
	//	$table->addFieldInfo('access', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);
		$table->addFieldInfo('access_perms', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('start_block', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('start_xpath', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('start_line', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('start_word', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('start_char', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('end_block', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('end_xpath', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('end_line', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('end_word', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('end_char', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('note', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
		$table->addFieldInfo('quote', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
		$table->addFieldInfo('quote_title', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
	//	$table->addFieldInfo('quote_author', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('quote_author_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('action', XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null);
		$table->addFieldInfo('link', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('link_title', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
		$table->addFieldInfo('created', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('modified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('object_type', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('object_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		
		/// Adding keys to table annotation
		$table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
		
		/// Adding indexes to table annotation
		$table->addIndexInfo('object', XMLDB_INDEX_NOTUNIQUE, array('object_type', 'object_id'));
		
		/// Launch create table for annotation
		$result = $result && create_table($table);
		
		// Check for old annotation table and convert its data over
		if ( $result )  {
			$query = "SELECT a.*, u.id AS uid, qa.id as aid "
				." FROM {$CFG->prefix}annotation a"
				." JOIN {$CFG->prefix}user u ON u.username=a.userid"
				." LEFT JOIN {$CFG->prefix}user qa ON qa.username=a.quote_author";
			$data = get_records_sql( $query );
			if ( $data )  {
				foreach ( $data as $r )  {
/*					// This method and the range classes are clever enough to handle
					// ranges in the old format.
					if ( array_key_exists( 'start_block', $r ) && $r->start_block !== null )  {
						$r->start_block = preg_replace( '/^\//', '', $r->start_block );
						$r->start_block = preg_replace( '/\//', '\.', $r->start_block );
					}
					if ( array_key_exists( 'end_block', $r ) && $r->end_block !== null )  {
						$r->end_block = preg_replace( '/^\//', '', $r->end_block );
						$r->end_block = preg_replace( '/\//', '\.', $r->end_block );
					}
*/
					$r->username = $r->userid;
					$r->userid = 0;
					$r->quote_author_username = $r->quote_author;

					$annotation = annotation_globals::record_to_annotation( $r );
	
					// Will handle 'public' or 'private'
					if ( array_key_exists( 'access', $r) )
						$annotation->setAccess( $r->access );
					
					// Fix backslashes (\' and \") in annotation and note
					if ( AN_DBFIXBACKSLASHES )
					{
						$quote = $annotation->getQuote( );
						$quote = preg_replace( '/\\\\\'/', "'", $quote );
						$quote = preg_replace( '/\\\\"/', '"', $quote );
						$annotation->setQuote( $quote );
						
						$note = $annotation->getNote( );
						$note = preg_replace( '/\\\\\'/', "'", $note );
						$note = preg_replace( '/\\\\"/', '"', $note );
						$annotation->setNote( $note );
					}
					
					$record = annotation_globals::annotation_to_record( $annotation );
					
					// Make sure start_line and end_line are not null
					if ( ! array_key_exists( 'start_line', $r ) )
						$record->start_line = 0;
					if ( ! array_key_exists( 'end_line', $r ) )
						$record->end_line = 0;

					$record->userid = $r->uid;
					$record->quote_author_id = $r->aid;
					$record->object_type = AN_OTYPE_POST;
					$record->object_id = $r->object_id;

					$x = insert_record( AN_DBTABLE, $record, true );
					$result = $result && 0 != $x;
				}
			}
		}
		
		// Should perhaps delete old annotation table?
	}
	
	return $result;
}

