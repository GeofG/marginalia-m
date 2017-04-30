/*
 * MoodleMarginalia.js
 * Annotation functions specific to moodle.php (the generic name is in case
 * non-annotation code ends up in here).
 *
 * Marginalia has been developed with funding and support from
 * BC Campus, Simon Fraser University, and the Government of
 * Canada, the UNDESA Africa i-Parliaments Action Plan, and  
 * units and individuals within those organizations.  Many 
 * thanks to all of them.  See CREDITS.html for details.
 * Copyright (C) 2005-2011 Geoffrey Glass; the United Nations
 * http://www.geof.net/code/annotation
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

function MoodleMarginalia( pageName, annotationPath, url, moodleRoot, userId, prefs, params )
{
	this.pageName = pageName;
	this.annotationPath = annotationPath;
	this.url = url;
	this.moodleRoot = moodleRoot;
	this.loginUserId = userId;
	this.sessionCookie = params.sessionCookie;
	this.preferences = new Preferences( 
		new RestPreferenceService( this.annotationPath + '/user-preference.php' ),
		prefs );
	this.sheet = prefs[ Marginalia.P_SHEET ];
	this.splash = prefs[ Marginalia.P_SPLASH ] == 'true' ? params[ 'splash' ] : null;
	this.useSmartquote = params.useSmartquote;
	this.allowAnyUserPatch = params.allowAnyUserPatch;
	this.smartquoteIcon = params.smartquoteIcon;
	this.handlers = params.handlers;
	this.smartquoteService = params.smartquoteService;
	this.canAnnotate = params.canAnnotate;
	this.canAnnotateData = params.canAnnotateData;
	this.nameDisplay = params.nameDisplay;
	this.enableRecentFlag = params.enableRecentFlag;
	this.enableInlineSummary = params.enableInlineSummary;
	LocalizedAnnotationStrings = params.strings;

	var urlFunc = function( node )
	{
		// strip off leading character
		var id = node.id.substr( 1 );
		
		// Must first figure out which kind of page is being annotated,
		// then return the correct URL for an individual *post*, which may
		// not be the same as the page as a whole (e.g. in the case of the forum).
		var x = window.location.href.indexOf( '#' );
		var base = -1 == x ? window.location.href : window.location.href.substr( 0, x );
		// console.log( 'match URL ' + base );                                  
		var matches = base.match( /\/mod\/forum\/discuss.php\?d=([0-9]+)/ );
		if ( matches )
		{
			return '/mod/forum/permalink.php?p=' + node.id.substr( 1 );
		}
		else
			throw "Annotation not supported for this page URL in MoodleMarginalia.js.";
	};
	
	this.selectors = {
		post: new Selector( '.forumpost', '.forumpost table.forumpost' ),
		post_id: new Selector( function( root ) { return $( root ).prev( 'a' ); }, null, '@id' ),
		post_content: new Selector( '.content .posting', '.content .content .posting' ),
		post_title: new Selector( '.subject', '.content .subject' ),
		post_author: new Selector( '.author a', '.content .author a' ),
		post_authorid: null,
		post_date: null,
		post_url: new Selector( function( root) { return $( root ).prev( 'a' ); }, null, urlFunc ),
		mia_notes: new Selector( '.mia_margin', '.content .posting .mia_margin' )
	};
	// URL is blank!  Should be a function to construct it from window.location + '#' + id
}

/**
 * Get unique URL for a question answer attempt
 *
 * kind of hacky as Moodle might change. Currently, for each question
 * answer in report or review, Moodle spits out something like:
 *   <input name="q8:1_:sequencecheck" value="3" type="hidden"/>
 * where 8 is the question attempt, 1 is the slot, and 3-1 is the step
 * because the value is the number of steps so far.
 * See get_sequence_check_count in Moodle code.
 * (question_usages.id=quiz_attempts.uniqueid, in this example = 8)
 *
 * The returned URL is not a real URL in the UI, as Moodle has
 * no URLs indexed on question_attempt ID. But that's what I
 * need, so that's what I use.
*/
MoodleMarginalia.getQuestionAttemptPostFields = function( root )
{
	var inputs = $( "input[type='hidden']", root );
	for ( var i = 0; i < inputs.length; ++i )
	{
		var node = inputs[ i ];
		var m = node.name.match( /(\d+):(\d+)_:sequencecheck/ );
		if ( m )
		{
			// Always cast to int for security
			var attempt = parseInt( m[ 1 ] );
			var slot = parseInt( m[ 2 ] );
			var step = parseInt( $( node ).val( ) ) - 1;
			return { 'quba_id': attempt, 'slot': slot, 'step': step };
		}
	}
	return null;
}
MoodleMarginalia.getQuestionAttemptUrl = function( root )
{
	var fields = MoodleMarginalia.getQuestionAttemptPostFields( root );
	if ( fields )
	{
		return '/blocks/marginalia/quiz/question_attempt?quba_id=' + fields.quba_id
			+ '&slot=' + fields.slot;
	}
	return null;
}

var MIA_QUBA_URL_PREFIX = '/blocks/marginalia/quiz/question_attempt?';
function parseMiaQubaUrl( url )
{
	if ( ! url.startsWith( MIA_QUBA_URL_PREFIX ) )
		throw "Unknown quba URL: " + url;
	var paramStrs = url.substr( MIA_QUBA_URL_PREFIX.length ).split( '&' );
	var params = { };
	for ( var j = 0; j < paramStrs.length; ++j )
	{
		var pair = paramStrs[ j ].split( '=' );
		params[ pair[ 0 ] ] = pair[ 1 ];
	}
	var step = null;
	if ( params[ 'step' ] )
		step = parseInt( params[ 'step' ] );
	return {
		'quba_id': parseInt( params[ 'quba_id' ] ),
		'slot': parseInt( params[ 'slot' ] ),
		'step': step
	}
}

/**
 * Custom post finder, because an annotation URL can match URLs for earlier
 * steps.
 *
 * An annotation URL matches a post URL if attempt and slot are the same,
 * and the step on the post equal to or greater than the step for the
 * annotation 
 * 
 * Outer function curries inner with a list of information identifying
 * which posts are annotatable.
 */
function QubaPostFinder( canAnnotateData )
{
	// Make a hash listing annotatable posts
	// These may not exist. They are what the server expects may be on the page.
	this.okPosts = {};
	for ( var i = 0; i < canAnnotateData.length; ++i )
	{
		var d = canAnnotateData[ i ];
		var key = d.quba_id + ':' + d.slot;
		this.okPosts[ key ] = d.step ? d.step : 0;
	}
}

QubaPostFinder.prototype.listPosts = function( marginalia, root, selector )
{
	var prefix = '/blocks/marginalia/quiz/question/question_attempt?';
	var possiblePosts = selector.nodes( root );
	var posts = [];
	for ( var i = 0; i < possiblePosts.length; ++i )
	{
		var p = possiblePosts[ i ];
		var fields = MoodleMarginalia.getQuestionAttemptPostFields( p );
		var key = fields.quba_id + ':' + fields.slot;
		if ( this.okPosts[ key ] ) {
			if ( fields.step >= this.okPosts[ key ] ) {
				posts.push( p );
			}
		}
	}
	return posts;
}

QubaPostFinder.prototype.find = function( marginalia, postInfos, url )
{
	return postInfos.getPostByUrl( url, marginalia.baseUrl );
}

/**
 * callback on page load to initialize Marginalia.
 * looks at the page url to determine which URL resource this is and 
 * figure out how to set marginalia up.  If annotation is not supported for
 * this resource nothing happens.  Checking here rather than in the PHP
 * minimizes the number of patches that need to be applied to existing Moodle
 * code.
 */
MoodleMarginalia.prototype.onload = function( )
{
	//console.log('page name: ' + pageName );
	initLogging();

	// check whether this page should have annotations enabled at all
	// the check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	if ( ! this.loginUserId )
		return;
	
	// must first figure out which kind of page is being annotated,
	// then return the correct URL for an individual *post*, which may
	// not be the same as the page as a whole (e.g. in the case of the forum).
	var x = window.location.href.indexOf( '#' );
	var base = -1 == x ? window.location.href : window.location.href.substr( 0, x );
	// first check for forum annotations
	switch ( this.pageName ) {
		case '/mod/forum/discuss':
		case '/mod/forum/post':
		case '/mod/forum/view':
		case '/mod/forum/user':
			var selectors = {
				post: new Selector( '.forumpost', '.forumpost table.forumpost' ),
				post_id: new Selector( function( root ) { return $( root ).prev( 'a' ); }, null, '@id' ),
				post_content: new Selector( '.content .posting', '.content .content .posting' ),
				post_title: new Selector( '.subject', '.content .subject' ),
				post_author: new Selector( '.author a', '.content .author a' ),
				post_authorid: null,
				post_date: null,
				mia_notes: new Selector( '.mia_margin', '.content .posting .mia_margin' ),
				post_url: new Selector( function( root) { return $( root ).prev( 'a' ); }, null,
					function( node ) { return '/mod/forum/permalink.php?p=' + node.id.substr( 1 ); } )
			};
			this.init( selectors );
			break;
		// Currently report and review show answers the same way
		// So that's nice.
		case '/mod/quiz/report':
		case '/mod/quiz/review':
		case '/mod/quiz/reviewquestion':
		case '/mod/quiz/comment':
			var selectors = {
				post: new Selector( '.que.essay' ),
				// post.id is no longer needed, thank goodness: bloody Moodle uses 
				// the same id value multiple times in the document!
				post_id: null,
				post_content: new Selector( '.ablock .qtype_essay_response' ),
				post_title: null,
				post_author: null,
				post_authorid: null,
				post_date: null,
				mia_notes: new Selector( '.mia_margin' ),
				// Selector's first argument must return element list, hence
				// the identity function. Also need to fabricate a URL.
				post_url: new Selector( function( root ) { return [root] }, 
					null, MoodleMarginalia.getQuestionAttemptUrl )
			};
			/*
			 * Sample URLs:
			 * review.php?attempt=9 q10:1_:sequencecheck
			 * reviewquestion.php?attempt=9&Gslot=1 q10:1_:sequencecheck
			 */
			// Add annotation margins - ONLY to posts that match canAnnotateData
			// (otherwise end up with vestigal non-functional margins for non-final steps)
			var qs = $( '.que.essay' );
			var byFields = {};
			for ( var i = 0; i < this.canAnnotateData.length; ++i )
			{
				var c = this.canAnnotateData[ i ];
				var key = c.quba_id + ':' + c.slot + ':' + c.step;
				byFields[ key ] = true;
			}
			for ( i = 0; i < qs.length; i++ )
		  	{
				var q = qs[ i ] ;
				var fields = MoodleMarginalia.getQuestionAttemptPostFields( q );
				var key = fields.quba_id + ':' + fields.slot + ':' + fields.step;
				if ( byFields[ key ] ) {
					$( '.ablock .answer div', q ).before(
						'<ol class="mia_margin"><li class="mia_dummyfirst"></li></ol>');
				}
			}
			$( 'body' ).addClass( 'mia_annotated' );
			this.init(selectors);
			break;
		default:
			console.log( 'Annotation not yet implemented for this page.' );
	}
}

MoodleMarginalia.prototype.init = function( selectors )
{
	var i;
	var annotationService = new RestAnnotationService( this.annotationPath + '/annotate.php', {
		noPutDelete: true,
		csrfCookie: this.sessionCookie } );
	//var keywordService = new RestKeywordService( this.annotationPath + '/keywords.php', true);
	//keywordService.init( null );
	keywordService = null;	// KeywordNoteEditor is not currently implemented
	var moodleMarginalia = this;
	var miaParams = {
		preferences: this.preferences,
		keywordService: keywordService,
		baseUrl:  this.moodleRoot,
		showBlockMarkers:  false,
		showActions:  false,
		onkeyCreate:  true,
		enableRecentFlag: this.enableRecentFlag,
		allowAnyUserPatch: this.allowAnyUserPatch ? true : false,
		canAnnotate: this.canAnnotate,
		nameDisplay: this.nameDisplay,
		displayNote: function(m,a,e,p,i) { moodleMarginalia.displayNote(m,a,e,p,i); },
		editors: {
			link: null
		},
		onMarginHeight: function( post ) { moodleMarginalia.fixControlMargin( post ); },
		selectors: selectors
	};
	if ( this.canAnnotateData )
		miaParams.postFinder = new QubaPostFinder( this.canAnnotateData );
	window.marginalia = new Marginalia( annotationService, this.loginUserId, this.sheet,
		miaParams );
	var mm = this;
	// Only the quiz review page supports inline summary
	if ( this.pageName == '/mod/quiz/review' && this.enableInlineSummary )
	{
		window.marginalia.addEventListener( 'addAnnotation', function ( e ) { mm.onAddAnnotation( e ) } );
		window.marginalia.addEventListener( 'removeAnnotation', function ( e ) { mm.onRemoveAnnotation( e ) } );
		window.marginalia.addEventListener( 'updateAnnotation', function ( e ) { mm.onUpdateAnnotation( e ) } );
	}
	
	// Ensure the sheet drop-down reflects the actual sheet to be shown
	// This relies on preferences being saved correctly.  Otherwise, the user may
	// change the dropdown, visit another page, click back and find that the 
	// sheet control shows the wrong thing.
	var sheetCtrl = document.getElementById( 'ansheet' );
	if ( sheetCtrl )
	{
		for ( i = 0;  i < sheetCtrl.options.length;  ++i )
		{
			if ( sheetCtrl.options[ i ].value == this.sheet )
			{
				sheetCtrl.selectedIndex = i;
				break;
			}
		}
	}
	this.cleanUpPostContent( );
	// The following is actually part of the initialization routine.
	// It enables clicking on the margin to create annotations
	// (clickCreateAnnotation)
	var posts = window.marginalia.listPosts( );
	
	// Display annotations
	var url = this.url;
	/*
	// Special case for report, which is paged: list specific post URLs
	if ( '/mod/quiz/report' == this.pageName ) {
		var url = 
		for ( i = 0; i < posts.length; ++i ) {

		}
	}
	*/
	if ( Marginalia.SHEET_NONE != this.sheet )	
		window.marginalia.showAnnotations( url );

	// Fix all control margins
	this.fixAllControlMargins( );
	
	// Highlight the margin when the mouse is over it - but not one of its children.
	// Lets user know s/he can click to create an annotation.
	if ( this.canAnnotate ) {
		var margin = jQuery( '.mia_margin' );
		margin.addClass( 'mia_annotatable' );
		margin.mouseover( function( e ) { margin.toggleClass( 'hover', e.target == margin[0] ); } );
		margin.mouseleave( function( ) { margin.removeClass( 'hover' ); } );
		
		if ( this.splash && this.sheet != Marginalia.SHEET_NONE )
		{
			var onclose = function() {
				window.marginalia.preferences.setPreference( Marginalia.P_SPLASH, 'false', null);
			};
			window.marginalia.showTip( this.splash, onclose );
		}
	}
};

MoodleMarginalia.prototype.enablePublishQuotes = function( )
{
	this.smartquote = new Smartquote( this.moodleRoot, this.selectors, this.smartquoteService );
	this.smartquote.enable( window.marginalia.listPosts( ), window.marginalia.skipContent );
}

MoodleMarginalia.prototype.enableSubscribeQuotes = function( name )
{
	// object_type and object_id are needed by extservice, whic doesn't  right now
	this.quoteSubscriber = new SmartquoteSubscriber( null );
	this.quoteSubscriber.subscribeMCE( name, null, null );
}

MoodleMarginalia.C_INLINE_SUMMARY = Marginalia.PREFIX + 'inline-summary';
MoodleMarginalia.prototype.getSummaryElement = function( post, autoCreate )
{
	var summary = post.getElement( ).getElementsByClassName( MoodleMarginalia.C_INLINE_SUMMARY );
	if ( summary.length )
		summary = summary[ 0 ];
	else if ( autoCreate )
	{
		summary = jQuery( "<div class='" + MoodleMarginalia.C_INLINE_SUMMARY + "'><table><thead><tr><th class='quote'></th><th class='note'></th><th class='annotator'></th><th class='blank'></th></tr></thead><tbody></tbody></table></div>" )[ 0 ];
		post.getElement( ).getElementsByClassName( 'formulation' )[ 0 ].appendChild( summary );
		$( 'th.quote', summary     ).text( getLocalized( 'inline summary quote' ) );
		$( 'th.note', summary      ).text( getLocalized( 'inline summary note' ) );
		$( 'th.annotator', summary ).text( getLocalized( 'inline summary annotator' ) );
		$( 'th.blank', summary     ).text( getLocalized( 'inline summary blank' ) );
	}
	return summary;
}

MoodleMarginalia.prototype.onAddAnnotation = function ( e )
{
	console.log( 'onAddAnnotation' );
	var summary = this.getSummaryElement( e.post, true );
	var table = summary.getElementsByTagName( 'tbody' )[ 0 ];
	for ( var node = table.lastElementChild; node; node = node.previousElementSibling )
	{
		if ( compareAnnotationRanges( node[ Marginalia.F_ANNOTATION ], e.annotation ) <= 0 )
			break;
	}
	var next = node ? node.nextElementSibling : table.firstElementChild;
	var row = jQuery( "<tr><td class='quote'></td><td class='note'></td><td class='annotator'></td><td class='blank'></td></tr>" )[ 0 ];
	table.insertBefore( row, next );
	$( '.quote', row ).text( e.annotation.getQuote( ) );
	$( '.note', row ).text( e.annotation.getNote( ) );
	$( '.annotator', row).text( e.annotation.getUserName( ) );
	row[ Marginalia.F_ANNOTATION ] = e.annotation;
	row[ Marginalia.F_POST ] = e.post;
}

MoodleMarginalia.prototype.onUpdateAnnotation = function( e )
{
	this.onRemoveAnnotation( e );
	this.onAddAnnotation( e );
}

MoodleMarginalia.prototype.onRemoveAnnotation = function( e )
{
	console.log( 'on Remove annotation' );
	var summary = this.getSummaryElement( e.post, false );
	if ( summary )
	{
		// Linear search. Unlikely to be a problem.
		var rows = jQuery( 'tbody tr', summary );
		for ( var i = 0; i < rows.length; ++i )
		{
			if ( rows[ i ][ Marginalia.F_ANNOTATION ].id == e.annotation.id )
			{
				$( rows[ i ] ).remove( );
				break;
			}
		}
	}
}

/*
MoodleMarginalia.prototype.inlineSummary = function( marginalia, annotation, post )
{
	* Rather than messing with reproducing the quiz review page, this simply
	 * looks through the DOM on that page for annotations and creates an inline
	 * summary beneath each question answer.
	 *
	 * I admit it's clunky. There should be an in-memory representation of
	 * annotations, and an easy way in Marginalia to show a different view of
	 * them. But there isn't, so rather than complicate Marginalia further
	 * I'm localizing this implementation here for now. *

	var answers = $( '.que.essay' );
	for ( var i = 0; i < answers.length; ++i )
	{
		var notes = $( '.mia_margin li', answers[ i ] );
		var table = $( 'table', MoodleMarginalia.C_INLINE_SUMMARY )[ 0 ];
		for ( var j = 0; j < notes.length; ++j )
		{
			var note = notes[ j ];
			var annotation = note[ Marginalia.F_ANNOTATION ];
			if ( annotation ) // ignore dummyfirst
			{
				var post = note[ Marginalia.F_POST ];
				var summary = this.getSummaryElement( post );
				var row = jQuery( "<tr><td class='quote'></td><td class='note'></td></tr>" );
				row[ Marginalia.F_ANNOTATION ] = annotation;
				row[ Marginalia.F_POST ] = post;
				$( 'table', summary ).append( row );
				$( '.quote', row ).text( annotation.getQuote( ) );
				$( '.note', row ).text( annotation.getNote( ) );
			}
		}
	}
}
*/

MoodleMarginalia.prototype.displayNote = function( marginalia, annotation, noteElement, params, isEditing )
{
	var moodleMarginalia = this;
	var wwwroot = this.moodleRoot;
	buttonParams = this.useSmartquote ?
		{
			className: 'quote',
			title: getLocalized( 'annotation quote button' ),
			content: this.smartquoteIcon,
			onclick: function( ) { 
				moodleMarginalia.smartquote.quoteAnnotation(
					annotation,
					marginalia.loginUserId,
					Smartquote.postIdFromUrl( annotation.getUrl( ) ) );
			}
		}
		: { };

	params.customButtons = [
		{
			owner: true,
			others: true,
			params: buttonParams
		}
	];
	return Marginalia.defaultDisplayNote( marginalia, annotation, noteElement, params, isEditing );
};

MoodleMarginalia.prototype.createAnnotation = function( event, postId )
{
	clickCreateAnnotation( event, postId );
};


/*
 * This fixes the height of create annotation buttons.
 *
 * I used to have to do this for IE, and complained bitterly.  Now I have to do it for Firefox
 * and Safari too:  apparently you can't set a table cell child to be the full height of the cell.
 * Probably this is CSS compliant.  Why can't they make sane standards?
 *
 * Note:  if the annotated content changes length (e.g. because of many inserted links or edit
 * actions), the button won't resize to match.  Hmmm.
 */
MoodleMarginalia.prototype.fixControlMargin = function( post )
{
	var margin = jQuery( 'ol.mia_margin', post.getElement( ) );
	var postContent;
	// For quiz answers:
	postContent = jQuery( '.qtype_essay_response', post.getElement( ) );
	// For forum posts:
	if ( ! postContent )
		postContent = jQuery( '.content .posting', post.getElement( ) );
	margin.css( 'min-height', postContent.height( ) );
	postContent.css( 'min-height', margin.height( ) );
/*	margin.height( 'auto' );
	postContent.height( 'auto' );
	if ( margin.height( ) < postContent.height( ) )
		margin.height( postContent.height( ) );
	if ( postContent.height( ) < margin.height( ) )
		postContent.height( margin.height( ) );
		*/
};

MoodleMarginalia.prototype.fixAllControlMargins = function( )
{
	var postInfo = window.marginalia.listPosts( );
	for ( var i = 0;  i < postInfo.posts.length;  ++i )
		this.fixControlMargin( postInfo.posts[ i ] );
}

MoodleMarginalia.prototype.cleanUpPostContent = function( )
{
	var f = function( node ) {
		for ( var child = node.firstChild;  child;  child = child.nextSibling )
		{
			if ( child.nodeType == ELEMENT_NODE )
			{
//				domutil.removeClass( child, PM_POST_CLASS );
//				domutil.removeClass( child, PM_CONTENT_CLASS );
//				domutil.removeClass( child, AN_NOTES_CLASS );
				// #geof# for now, simply clear all class names
				child.removeAttribute( 'class' );
				child.removeAttribute( 'id' );
				if ( child.id )
					delete child.id;
				f( child );
			}
		}
	};
	var posts = marginalia.listPosts( ).getAllPosts( );
	for ( var i = 0;  i < posts.length;  ++i )
	{
		f ( posts[ i ].getContentElement( ) );
	}
};

MoodleMarginalia.prototype.changeSheet = function( sheetControl, url )
{
	var marginalia = window.marginalia;
	var sheet = sheetControl.value;

	// Check to see whether this is a special case with a named handler
	if ( this.handlers[ sheet ] )
		this.handlers[ sheet ]( this, marginalia );
	// This is simply a sheet name: go to that sheet
	else
	{
		marginalia.hideAnnotations( );
		marginalia.preferences.setPreference( Marginalia.P_SHEET, sheet, null );
		if ( Marginalia.SHEET_NONE != sheet )
		{
			marginalia.sheet = sheet;
			marginalia.showAnnotations( url );
			this.fixAllControlMargins( );
		}
	}
};


/*
 * Fetch a localized string
 * This is a function so that it can be replaced with another source of strings if desired
 * (e.g. in a database).  The application uses short English-language strings as keys, so
 * that if the language source is lacking the key can be returned instead.
 */
function getLocalized( s )
{
	var l = LocalizedAnnotationStrings[ s ];
	return l ? l : s;
}

