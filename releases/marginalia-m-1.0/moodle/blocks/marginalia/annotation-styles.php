<?php
header( 'Content-type: text/css' );
/*
//if (!isset($themename)) {
//	$themename = NULL;
//}

$nomoodlecookie = true;
require_once("../config.php");
//$themeurl = style_sheet_setup(filemtime("annotation-styles.php"), 300, $themename);
*/
?>

table td#annotation-controls {
	white-space:  nowrap;
}

#annotation-summary-link,
#annotation-editkeywords-link {
	font-size: smaller;
}

.hentry .notes .splash {
	font-size: smaller;
}

/* Edit | Delete | Reply links at bottom of post */
.forumpostmessage .commands,
.forumpostmessage .commands a {
	text-align: right ;
	font-size: 80% ;
	color: black ;
	font-weight: normal ;
}

.forumpost {
	position: relative;
}

.hentry td.content {
	vertical-align: top;
}

.forumpost .commands button.smartquote {
	display: inline;
	background: none;
	border: none;
	padding: 0;
	margin: 0;
	font-family: inherit;
	font-size: inherit;
	color: blue;
	cursor: pointer;
}

.forumpost .commands button.smartquote:hover span {
	text-decoration: underline;
	color: red;
}

/* hack button to allow for annotation creation, a result of problems with Moz positioning */
/* it has to be a button, otherwise clicking it loses the selection */
td.control-margin {
	width: 1;
	xheight: auto;
}

td.control-margin div {
	display: none;
}

.self-annotated td.control-margin {
	vertical-align: top;
	width: 1em;
	padding: 0 ;
}

.self-annotated td.control-margin div {
	display: block;
	width: 1em;
	height: 100%;
}

/* There's a bug with the button:  its height is set to 100%, but when
 * the annotation notes are added they may increase the height of the table.
 * In that case, the button size does not increase to match.
 */
.self-annotated td.control-margin button {
	height: 100%;
	width: 1em;
	border: none;
	border-left:  #eee 1px dotted;
	border-right:  #eee 1px dotted;
	padding: 0;
	padding-left: 1px;
	margin: 0;
	background: none;
	cursor: pointer;
	display: block;
	z-index: 1;
}

.self-annotated td.control-margin button span {
	visibility: hidden;
}

/* the hover class is because of IE cluelessness */
.self-annotated td.control-margin button:hover span,
.self-annotated td.control-margin button.hover span {
	visibility: inherit;
}

.self-annotated td.control-margin button:hover,
.self-annotated td.control-margin button.hover {
	font-weight: bold;
	background: #fdf377;   /*should be from the theme, but I'm not sure where that's set in 1.5 yet */
}

.hentry .notes li.hover,
.hentry .entry-content em.annotation.hover,
.hentry .entry-content em.annotation.hover ins,
.hentry .entry-content em.annotation.hover del {
	color: red;
}

/* notes in sidebar */
.notes a.annotation-summary {
	bottom: .25em ;
	margin: 0 auto;
	text-align: center;
	font-size: 80%;
	width: 100%;
	display: block;
}

.notes {
	width: 0;
}

/* this rigamarole with both changing the column width *and* hiding the elements within it
 * is because IE is a load of steaming horse manure */
.notes ol,
.notes a.annotation-summary,
.notes a.range-mismatch {
	display: none;
}

.annotated .notes {
	width: 30% ;
	position: relative;
	/* unfortunately the background color has been interfering with the rounded corners
	of the default moodle theme, so for now it's disabled */
	/*background-color:  #f8f8f8; <?PHP echo $THEME->cellcontent2; ?>;*/
}

.annotated .notes ol,
.annotated .notes .annotation-summary {
	display: block;
}

.notes div {
	position: relative;
	padding: 1px;
}

.notes ol {
	margin: 0;
	padding: 0 ;
	right: 0;
	margin-bottom: 1.2em;
}

.notes ol li {
	clear: both;
}

.notes ol li.active {
	color: red ;
}

.notes ol li button {
	background: none ;
	font-size: 12px ;
}

.notes ol textarea {
	vertical-align: top ;
	border: none;
	font-family: inherit;
	width: 94%;
}

/* colors for other users' annotations
.hentry em.annotation { background-color: #77f3fd ; }
.hentry .content em.annotation em.annotation { background: #70d4ec; }
.hentry .content em.annotation em.annotation em.annotation { background: #66c6d8; }
*/

button#hide-all-annotations,
body.annotated button#show-all-annotations {
	display: none ;
}

body.annotated button#hide-all-annotations {
	display: inline;
}

#smartcopy-status {
	position: fixed;
	right: 1em;
	bottom: 1em;
	z-index: 100;
	background: #ffffcc;
	border: #666 1px solid;
	padding: 1ex;
	width: 13em;
	font-size: small;
	opacity: 1;
}

@media print
{
	.forumpost em.annotation {
		text-decoration: underline ;
	}
}


