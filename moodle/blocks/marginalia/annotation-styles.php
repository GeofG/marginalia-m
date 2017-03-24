<?php
header( 'Content-type: text/css' );
?>

/* These discussioncontrol definitions are in mod/forum/styles.css, but there
   they apply only to the discuss page.  We need them for the post page too. */
.miacontrols.discussioncontrols {
   	width: 100%;
   	margin: 5px;
   	clear: both;
}
.discussioncontrol {
	width: 33%;
	float: left;
}

table td#annotation-controls {
	white-space:  nowrap;
}

#annotation-summary-link,
#annotation-editkeywords-link {
	font-size: smaller;
}

.mia_margin .splash {
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

.forumpost button.smartquote {
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

.forumpost button.smartquote:hover span {
	text-decoration: underline;
	color: red;
}

.mia_margin li.hover,
.em.mia_annotation.hover,
.em.mia_annotation.hover ins,
.em.mia_annotation.hover del {
	color: red;
}

.forumpost .content {
	position: relative;
}

.mia_margin {
	display: none;
	height: 100%;
	margin: 0;
	padding: 0;
	list-style-type: none;
}

.mia_annotated .mia_margin {
	display: block;
	width: 16em;
	margin: 0 0 1.2em 1ex;
	padding: 1px;
	border: #f8f8f8 1px solid;
	list-style-type: none;
}

.mia_annotated .mia_margin.mia_annotatable {
	cursor: pointer;
}

.mia_margin li {
	list-style-type: none;
}

/* Ack! Moodle is putting padding on divs.  This is bad,
 * because the textarea child has width 100%, so it is then overflowing
 * and inserting a horizontal scroll bar.  Sheesh.  The Moodle
 * selector should be more precise.
 */
.forumpost .content .mia_margin div {
	padding: 0;
}

.mia_margin .mia_tip:hover {
	outline: #aaa 1px dotted;
}

.mia_annotated .mia_margin.hover {
	border: #aaa 1px dotted;
}

/* this rigamarole with both changing the column width *and* hiding the elements within it
 * is because IE is a load of steaming horse manure */
.mia_margin ol,
.mia_margin a.range-mismatch {
	display: none;
}

.mia_annotated .mia_margin {
	position: relative;
	/* unfortunately the background color has been interfering with the rounded corners
	of the default moodle theme, so for now it's disabled */
	/*background-color:  #f8f8f8; [?PHP echo $THEME-]cellcontent2; ?];*/
}

.mia_margin li {
	clear: both;
	position: relative;
	font-size: 90%;
}

.mia_margin li p {
/*	background-color: #f8f8f8; */
	z-index: 10;
}

.mia_margin li.active {
	color: #d00 ;
}

.mia_margin li button {
	background: none;
}

.mia_margin .mia_tip .mia_controls {
	text-align: right;
	float: none;
}

.mia_margin li .mia_controls {
	visibility: hidden;
}

.mia_margin li:hover .mia_controls,
.mia_margin li.mia_hover .mia_controls {
	visibility: visible;
}

.mia_margin textarea {
	vertical-align: top ;
	border: none;
	font-family: inherit;
	width: 94%;
}

/* colors for other users' annotations
.hentry em.mia_annotation { background-color: #77f3fd ; }
.hentry .content em.mia_annotation em.mia_annotation { background: #70d4ec; }
.hentry .content em.mia_annotation em.mia_annotation em.mia_annotation { background: #66c6d8; }
*/

button#hide-all-annotations,
body.mia_annotated button#show-all-annotations {
	display: none ;
}

body.mia_annotated button#hide-all-annotations {
	display: inline;
}

.mia_annotated .essay .answer {
	position: relative;
}
.mia_annotated .essay .answer .qtype_essay_response {
	margin-right: 17.2em;
}
.mia_annotated .essay .answer .mia_margin {
	float: right;
	top: 0;
	right: 0;
	box-sizing: border-box;
	height: auto;	/* change here to avoid touching marginalia.css */
}

.mia_inline-summary {
	margin: 1em 0;
}

.mia_inline-summary table {
	width: 100%;
}

.mia_inline-summary td {
	width: 50%;
	vertical-align: top;
}

@media print
{
	em.mia_annotation {
		border-bottom: solid 1pt #444;
		xtext-decoration-line: underline ;
		xtext-decoration-style: dotted;
	}
	em.mia_annotation em.mia_annotation {
		border-bottom: solid 2pt #333;
		xtext-decoration-style: solid ;
	}
	em.mia_annotation em.mia_annotation em.mia_annotation {
		border-bottom: solid 3pt #222;
		xtext-decoration-style: double;
	}
	em.mia_annotation em.mia_annotation em.mia_annotation em.mia_annotation {
		border-bottom: solid 4pt #111;
		xtext-decoration-style: double;
	}
	em.mia_annotation em.mia_annotation em.mia_annotation em.mia_annotation em.mia_annotation {
		border-bottom: solid 5pt #000;
		xtext-decoration-style: double;
	}
