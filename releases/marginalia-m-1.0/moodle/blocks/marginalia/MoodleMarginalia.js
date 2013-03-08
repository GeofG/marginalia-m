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
 * Copyright (C) 2005-2007 Geoffrey Glass; the United Nations
 * http://www.geof.net/code/annotation
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
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
 *
 * $Id: MoodleMarginalia.js 257 2007-11-01 22:34:41Z geof.glass $
 */

function MoodleMarginalia( annotationPath, url, moodleRoot, userId, prefs, params )
{
	this.annotationPath = annotationPath;
	this.url = url;
	this.moodleRoot = moodleRoot;
	this.loginUserId = userId;
	this.sessionCookie = params.sessionCookie;
	this.preferences = new Preferences( 
		new RestPreferenceService( this.annotationPath + '/user-preference.php' ),
		prefs );
	this.showAnnotations = prefs[ AN_SHOWANNOTATIONS_PREF ];
	this.showAnnotations = this.showAnnotations == 'true';
	this.splash = prefs[ AN_SPLASH_PREF ] == 'true' ? params[ 'splash' ] : null;
	this.useSmartquote = params.useSmartquote;
	this.allowAnyUserPatch = params.allowAnyUserPatch;
	this.smartquoteIcon = params.smartquoteIcon;
	
	// This is kind of awkward.  If the user changes the display user, then
	// visits another page, then uses the browser back button, we need to use
	// the new display user, not the one set when the page was loaded the first
	// time.  So check whether the user dropdown has a value, and match it.
	this.displayUserId = prefs[ AN_USER_PREF ];
	var userCtrl = document.getElementById( 'anuser' );
	if ( userCtrl )
	{
		if ( userCtrl.selectedIndex >= 0 )
		{
			var userId = userCtrl.options[ userCtrl.selectedIndex ].value;
			if ( userId )
			{
				this.displayUserId = userId;
				this.showAnnotations = true;
			}
			else
				this.showAnnotations = false;
		}
	}
}

MoodleMarginalia.prototype.onload = function( )
{
	//initLogging();

	// Check whether this page should have annotations enabled at all
	// The check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	var actualUrl = '' + window.location;
	if ( this.loginUserId && actualUrl.match( /^.*\/mod\/forum\/discuss\.php\?d=(\d+)/ ) )
	{
		var annotationService = new RestAnnotationService( this.annotationPath + '/annotate.php', {
			csrfCookie: this.sessionCookie } );
		var keywordService = new RestKeywordService( this.annotationPath + '/keywords.php', true);
		keywordService.init( null );
		var moodleMarginalia = this;
		window.marginalia = new Marginalia( annotationService, this.loginUserId, this.displayUserId == '*' ? '' : this.displayUserId, {
			preferences: this.preferences,
			keywordService: keywordService,
			baseUrl:  this.moodleRoot,
			showAccess:  true,
			showBlockMarkers:  false,
			showActions:  false,
			onkeyCreate:  true,
			allowAnyUserPatch: this.allowAnyUserPatch ? true : false,
			displayNote: function(m,a,e,p,i) { moodleMarginalia.displayNote(m,a,e,p,i); },
			editors: {
				link: null,
				'default':  Marginalia.newEditorFunc( YuiAutocompleteNoteEditor )
			},
			onMarginHeight: function( post ) { moodleMarginalia.fixControlMargin( post ); }
		} );
		
		this.cleanUpPostContent( );
		
		// Display annotations
		var url = this.url;
		if ( this.showAnnotations )
			window.marginalia.showAnnotations( url );
		
		// Fix all control margins
		this.fixAllControlMargins( );
		
		Smartquote.enableSmartquote( this.moodleRoot, marginalia.listPosts( ), marginalia.skipContent );
		
//		var marginaliaDirect = new MarginaliaDirect( annotationService );
//		marginaliaDirect.init( );
		
		if ( this.showAnnotations && this.splash )
			this.showSplash( );
	}
};

MoodleMarginalia.prototype.displayNote = function( marginalia, annotation, noteElement, params, isEditing )
{
	var wwwroot = this.moodleRoot;
	buttonParams = this.useSmartquote ?
		{
			className: 'quote',
			title: getLocalized( 'annotation quote button' ),
			content: this.smartquoteIcon,
			onclick: function( ) { 
				Smartquote.quoteAnnotation(
					annotation,
					marginalia.loginUserId,
					wwwroot,
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
	this.hideSplash( );
	delete this.splash;
	window.marginalia.preferences.setPreference( AN_SPLASH_PREF, 'false', null);
	clickCreateAnnotation( event, postId );
};

MoodleMarginalia.prototype.showSplash = function( )
{
	var noteMargins = cssQuery( '.hentry .notes div' );
	if ( noteMargins.length > 0 )
	{
		var margin = noteMargins[ 0 ];
		margin.appendChild( domutil.element( 'p', {
			className: 'splash',
			content: this.splash } ) );
	}
};

MoodleMarginalia.prototype.hideSplash = function( )
{
	var splash = cssQuery( '.hentry .notes div .splash' );
	if ( splash.length > 0 )
		splash[ 0 ].parentNode.removeChild( splash[ 0 ] );
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
	var margin = domutil.childByTagClass( post.getElement( ), 'td', 'control-margin', PostMicro.skipPostContent );
	var button = domutil.childByTagClass( margin, 'button', null );
	button.style.height = '';
	button.style.height = '' + margin.offsetHeight + 'px';
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
				domutil.removeClass( child, PM_POST_CLASS );
				domutil.removeClass( child, PM_CONTENT_CLASS );
				domutil.removeClass( child, AN_NOTES_CLASS );
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
}

MoodleMarginalia.prototype.changeAnnotationUser = function( userControl, url )
{
	var marginalia = window.marginalia;
	var userId = userControl.value;
	this.hideSplash( )
	marginalia.hideAnnotations( );
	if ( null == userId || '' == userId )
		marginalia.preferences.setPreference( AN_SHOWANNOTATIONS_PREF, 'false', null);
	else
	{
		marginalia.displayUserId = userId == '*' ? '' : userId;
		marginalia.showAnnotations( url );
		marginalia.preferences.setPreference( AN_SHOWANNOTATIONS_PREF, 'true', null);
		marginalia.preferences.setPreference( AN_USER_PREF, userId, null );
		if ( this.splash && ( marginalia.loginUserId == marginalia.displayUserId || '' == marginalia.displayUserId ) )
			this.showSplash( );
		this.fixAllControlMargins( );
	}
};

