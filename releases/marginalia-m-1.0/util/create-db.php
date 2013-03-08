<?php

/***********************************************************
* Annotation Tool Database Creator
* Author: Eric Schewe (schewee@mala.bc.ca)
* Additional Contact Info: 
*	
*	Version: 0.1
* Released: 08/10/2005
*
*	Description: This page is used to create the annotation
*							 tools database automatically using the current
*							 moodle instances configuration.
*
* Variable Key: s = String
*								
************************************************************/

include("../config.php"); //Includes the Moodle Configuration file for this instance.

$sMySQLCommand = "create table {$CFG->prefix}annotation ("
	.'id int primary key auto_increment'
	.', userid varchar(255) not null'
	.', access varchar(32) null'
	.', url varchar(255) not null'
	.', start_block varchar(255) not null'
	.', start_xpath varchar(255) not null'
	.', start_word int not null'
	.', start_char int not null'
	.', end_block varchar(255) not null'
	.', end_xpath varchar(255) not null'
	.', end_word int not null'
	.', end_char int not null'
	.', note varchar(255) null'
	.', created datetime not null'
	.', modified timestamp not null'
	.', quote text null'
	.', quote_title varchar(255) null'
	.', quote_author varchar(255) null'
	.', link varchar(255) null'
	.', link_title varchar(255) null'
	.', action varchar(30) null'
	.', version int null'
	.', object_type varchar(16) null'
	.', object_id int null)';
mysql_query($sMySQLCommand) or die(mysql_error());

$sMySQLCommand = "create table {$CFG->prefix}annotation_keywords ("
	.'userid bigint(10) unsigned not null'
	.',name varchar(255) not null'
	.',description text null)';
mysql_query($sMySQLCommand) or die(mysql_error());

echo "Database created successfully";

?>