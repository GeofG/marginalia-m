<?php

/*
 * I have added this so that messages can have permanent URLs, even when they are
 * "split" (i.e., moved between discussions), which is something I need for annotation.
 * This doesn't actually show the message;  rather, it redirects to the correct
 * discussion.  It's a bit of a hack, but it's minimally invasive to the forum code.
 *
 * Logically this should be called post.php, but that name is already taken by a script
 * that creates or saves changes to posts.
 */

//  Displays a post, and all the posts below it.
//  If no post is given, displays all posts in a discussion

    require_once("../../config.php");
    require_once("lib.php");

    $p = required_param( 'p', PARAM_INT );       // Post ID

	global $DB;

    if (! $post = $DB->get_record("forum_posts", array("id" => $p))) {
        error("Post ID is incorrect or no longer exists");
    }

	// If it's a simple single-discussion forum, redirect to the forum
	$discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion));
	$forum = $DB->get_record('forum', array('id' => $discussion->forum));

	if ('single' == $forum->type)
		$url = 'view.php?id='.$forum->id.'#p'.$p;
	else
		$url = 'discuss.php?d='.$discussion->id.'#p'.$p;
	
	//$url = $anuser ? "discuss.php?d=$d&anuser=$anuser#m$p" : "discuss.php?d=$d#m$p";

	header( 'HTTP/1.1 303 See Other' );
	header( 'Location: '.$url );
?>
