/*
 * Smartquote functions used in Moodle
 * built on CookieBus
 */

Smartquote = {
	/**
	 * Enable all smartquote buttons on the page
	 * Buttons are found in posts with selector button.smartquote
	 */
	enableSmartquote: function( wwwroot, postPageInfo, skipContent )
	{
		// Use passed-in value for speed if possible
		if ( ! postPageInfo )
			postPageInfo = PostPageInfo.getPostPageInfo( document );

		// Enable smartquote buttons
		var posts = postPageInfo.getAllPosts( );
		for ( var i = 0;  i < posts.length;  ++i )
		{
			var button = domutil.childByTagClass( posts[ i ].getElement( ), 'button', 'smartquote', skipContent );
			if ( button )
				Smartquote.enableSmartquoteButton( button, posts[ i ], wwwroot, skipContent );
		}
	},
	
	/**
	 * Enable a specific smartquote button
	 * Must be a separate function from the loop in enableSmartquote to deal
	 * correctly with Javascript dynamic scoping and closures
	 */
	enableSmartquoteButton: function( button, post, wwwroot, skipContent )
	{
		var content = post.getContentElement( );
		var postId = Smartquote.postIdFromUrl( post.getUrl( ) );
		var f = function( ) { Smartquote.quotePostMicro( content, skipContent, wwwroot, postId ); };
		addEvent( button, 'click', f );
	},
	
	/**
	 * Calculate a post ID based on its URL
	 */
	postIdFromUrl: function( url )
	{
		var matches = url.match( /^.*\/mod\/forum\/permalink\.php\?p=(\d+)/ );
		if ( matches )
			return Number( matches[ 1 ] );
		else
			return 0;
	},
	
	/**
	 * Get a quote (selected text) from a postMicro with a given ID
	 * Returns the quote as HTML with metadata included.  Note, however, that
	 * any HTML tags in the selected text are stripped, and whitespace is
	 * collapsed.
	 */
	getPostMicroQuote: function( content, skipContent, wwwroot, postId )
	{
		// Test for selection support (W3C or IE)
		if ( ( ! window.getSelection || null == window.getSelection().rangeCount )
			&& null == document.selection )
		{
			alert( getLocalized( 'browser support of W3C range required for smartquote' ) );
			return false;
		}
			
		var textRange0 = getPortableSelectionRange();
		if ( null == textRange0 )
		{
			alert( getLocalized( 'select text to quote' ) );
			return false;
		}
		
		// Strip off leading and trailing whitespace and preprocess so that
		// conversion to WordRange will go smoothly.
		var textRange = TextRange.fromW3C( textRange0 );
		
		// Don't need a skip handler unless we're running on a page with Marginalia
		textRange = textRange.shrinkwrap( skipContent );
		if ( ! textRange )
		{
			// this happens if the shrinkwrapped range has no non-whitespace text in it
			alert( getLocalized( 'select text to quote' ) );
			return false;
		}
		
		var quote = getTextRangeContent( textRange, skipContent );
		quote = quote.replace( /(\s|\u00a0)+/g, ' ' );
		
		var postInfo = PostPageInfo.getPostPageInfo( document );
		var post = postInfo.getPostMicro( textRange.startContainer );
		var leadIn = '';
		if ( post )
		{
			leadIn = '<p>' + ( post.getAuthorName( ) ? domutil.htmlEncode( post.getAuthorName( ) ) : 'Someone' )
				+ ( post.getUrl( ) ? ' <a href="' + domutil.htmlEncode( post.getUrl( ) ) + '">wrote</a>' : 'wrote' )
				+ ",</p>";
		}
		return leadIn + '<blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
	},
	
	
	/**
	 * Called when a quote button is clicked on a postMicro.  Extracts the
	 * selected text, builds HTML with metadata, and publishes it on the
	 * CookieBus.
	 */
	quotePostMicro: function( content, skipContent, wwwroot, postId )
	{
//		console.log( 'quote' );
		var pub = Smartquote.getPostMicroQuote( content, skipContent, wwwroot, postId );
		var bus = new CookieBus( 'smartquote' );
		if ( bus.getSubscriberCount( ) > 0 )
		{
//			console.log( 'publish: ' + pub );
			bus.publish( pub );
		}
		else if ( wwwroot && postId )
		{
			// The nbsp below inserts an annoying extra space - but that's better
			// than the editor's default behavior of adding any new text to the previous
			// blockquote.  Moodle needs a new editor (this one was discontinued).
			window.location = wwwroot + '/mod/forum/post.php?reply=' + postId
				+ '&message=' + encodeURIParameter( pub + '&nbsp;<p> ' );
		}
	},
	
	quoteAnnotation: function( annotation, loginUserId, wwwroot, postId )
	{
		var quoteAuthor = annotation.getQuoteAuthorName( );
		var url = annotation.getUrl( );
		var quote = annotation.getQuote( );
		var note = annotation.getNote( );
		var noteAuthor = annotation.getUserName( );
		
		if ( url && 0 != url.indexOf( 'http://' ) && 0 != url.indexOf( 'https://' ) )
			url = wwwroot + url;
		
		quote = quote.replace( /\s/g, ' ' );
		quote = quote.replace( /\u00a0/g, ' ' );

		var pub = '<p>' + ( quoteAuthor ? domutil.htmlEncode( quoteAuthor ) : 'Someone' )
			+ ( url ? ' <a href="' + domutil.htmlEncode( url ) + '">wrote,</a>' : ' wrote' )
			+ '</p><blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
		if ( loginUserId == annotation.getUserId( ) )
		{
			if ( annotation.getNote( ) )
				pub += '<p>' + domutil.htmlEncode( note ) + '</p>';
		}
		else
		{
			note = note.replace( /\s/g, ' ' );
			note = note.replace( /\u00a0/g, ' ' );
			if ( note )
			{
				pub += '<p>Via ' + domutil.htmlEncode( noteAuthor ) + ', who noted,</p>'
					+ '<blockquote><p>' + domutil.htmlEncode( note ) + '</p></blockquote>';
			}
			else
				pub += '<p>(Via an annotation by ' + domutil.htmlEncode( noteAuthor ) + '.)</p>';
		}
		
		var bus = new CookieBus( 'smartquote' );
		if ( bus.getSubscriberCount( ) > 0 )
			bus.publish( pub );
		else if ( wwwroot && postId )
		{
			// The nbsp below inserts an annoying extra space - but that's better
			// than the editor's default behavior of adding any new text to the previous
			// blockquote.  Moodle needs a new editor (this one was discontinued).
			window.location = wwwroot + '/mod/forum/post.php?reply=' + postId
				+ '&message=' + encodeURIParameter( pub + "&nbsp;<p>" );
		}
	},
	

	/**
	 * Subscribe an HTMLArea control to receive smartquote publish events
	 */
	subscribeHtmlArea: function( editor )
	{
		// This code and these tests are very much specific to HTMLArea
		// The test for the range is necessary - otherwise if the user hasn't
		// clicked in the area, everything can blow up.
		var bus = new CookieBus( 'smartquote' );
		bus.subscribe( 2000, function( pub ) {
			var sel = editor._getSelection( );
			var range = editor._createRange( sel );
			// D'oh.  Default range is in HTMLDocument, which of course has
			// no parent (best way I could think to test for that).  HTMLArea
			// blows up when an insert is attempted then.
			if ( HTMLArea.is_ie )
			{
				if ( 'None' == sel.type && false )
				{
					var textRange = editor._doc.body.createTextRange( );
					textRange.select( );
				}
			}
			else
			{
				if ( ! range.startContainer.parentNode )
				{
					var textRange = editor._doc.createRange( );
					textRange.selectNode( editor._doc.body.lastChild );
					var selection = editor._iframe.contentWindow.getSelection();
					selection.addRange( textRange );
					selection.collapseToEnd( );
				}
			}
			editor.insertHTML( pub.value + ' ' + '<br/>');
			
			// Collapse range to the end of the document
			// Otherwise the editor ends up selecting the first paragraph of the
			// last paste, which will be stomped by subsequent pastes
			// Mozilla only (sorry IE - for now)
			if ( ! HTMLArea.is_ie )
			{
				var textRange = editor._doc.createRange( );
				textRange.selectNode( editor._doc.body.lastChild );
				var selection = editor._iframe.contentWindow.getSelection();
				selection.addRange( textRange );
				selection.collapseToEnd( );
			}
		} );
		
		// Don't forget to unsubscribe if the window is unloaded
		addEvent( window, 'unload', function( ) { bus.unsubscribe( ); } );
		
		return bus;
	}
};



