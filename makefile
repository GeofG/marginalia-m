#
# makefile for Marginalia for Moodle
#

# These lib and php dirs used to point to the latest. Making a copy here
# allows me to continue to use an older version, avoid all the minor
# updates to Moodle to make it compatible, and not risk breaking it.
MARGINALIA_PHP_DIR = $(shell pwd)/marginalia-php
MARGINALIA_LIB_DIR = $(shell pwd)/marginalia-lib
include marginalia-lib.mk
include marginalia-php.mk

DATE = $(shell date +'%Y%m%d')

OUTDIR = releases
OUTNAME = marginalia-moodle-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

MOODLE_DIR = moodle
MARGINALIA_MOODLE_DIR = $(MOODLE_DIR)/blocks/marginalia
MARGINALIA_MOODLE_FILES = \
 $(MARGINALIA_MOODLE_DIR)/annotate.php \
 $(MARGINALIA_MOODLE_DIR)/annotation-styles.php \
 $(MARGINALIA_MOODLE_DIR)/annotation_summary_query.php \
 $(MARGINALIA_MOODLE_DIR)/block_marginalia.php \
 $(MARGINALIA_MOODLE_DIR)/config.php \
 $(MARGINALIA_MOODLE_DIR)/help.css \
 $(MARGINALIA_MOODLE_DIR)/help.php \
 $(MARGINALIA_MOODLE_DIR)/keywords.php \
 $(MARGINALIA_MOODLE_DIR)/keywords_db.php \
 $(MARGINALIA_MOODLE_DIR)/lib.php \
 $(MARGINALIA_MOODLE_DIR)/marginalia-config.js \
 $(MARGINALIA_MOODLE_DIR)/MoodleMarginalia.js \
 $(MARGINALIA_MOODLE_DIR)/moodle_marginalia.php \
 $(MARGINALIA_MOODLE_DIR)/smartquote.js \
 $(MARGINALIA_MOODLE_DIR)/summary.js \
 $(MARGINALIA_MOODLE_DIR)/summary.php \
 $(MARGINALIA_MOODLE_DIR)/summary-styles.php \
 $(MARGINALIA_MOODLE_DIR)/user-preference.php
 
VERSION_FILE = $(MARGINALIA_MOODLE_DIR)/version.php

LANG_FILES = $(MARGINALIA_MOODLE_DIR)/lang/en/block_marginalia.php

HELP_FILES = \
 $(MARGINALIA_MOODLE_DIR)/lang/en/help/annotate.html \
 $(MARGINALIA_MOODLE_DIR)/lang/en/help/annotation_summary.html

DB_DIR = $(MARGINALIA_MOODLE_DIR)/db
DB_FILES = \
 $(DB_DIR)/access.php \
 $(DB_DIR)/install.xml \
 $(DB_DIR)/upgrade.php
 
FORUM_FILES = $(MOODLE_DIR)/mod/forum/permalink.php

README_FILES = \
 README.txt \
 LICENSE.txt \
 CREDITS.txt \
 INSTALL.txt

release:  zipcontents
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)
	echo "Please confirm that this is the correct version for this release:"
	grep plugin $(VERSION_FILE)

zipdir:
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/marginalia-php
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/lang/en/help
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/db
	mkdir -p $(ZIPDIR)/moodle/mod/forum

zipcontents: zipdir $(ZIPDIR)/marginalia-m.patch $(DOC_FILES) $(README_FILES) $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES) $(FORUM_FILES) $(LANG_FILES) $(HELP_FILES) $(VERSION_FILE) $(DB_FILES)
	cp $(MARGINALIA_MOODLE_FILES) $(ZIPDIR)/moodle/blocks/marginalia/
	cp $(VERSION_FILE) $(ZIPDIR)/moodle/blocks/marginalia/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia-php/
	cp $(LANG_FILES) $(ZIPDIR)/moodle/blocks/marginalia/lang/en/
	cp $(HELP_FILES) $(ZIPDIR)/moodle/blocks/marginalia/lang/en/help/
	cp $(DB_FILES) $(ZIPDIR)/moodle/blocks/marginalia/db/
	cp $(FORUM_FILES) $(ZIPDIR)/moodle/mod/forum/
	cp $(README_FILES) $(ZIPDIR)

# Do not auto make version file!  It must be confirmed by hand.
#$(VERSION_FILE): zipdir moodle.orig moodle moodle $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES) $(FORUM_FILES) $(LANG_FILES) $(HELP_FILES) $(DB_FILES)
version:
	echo '<?php' >$(VERSION_FILE)
	echo '    $$plugin->version = $(DATE)00;' >>$(VERSION_FILE)
	echo '    $$plugin->requires = 2014111003;' >>$(VERSION_FILE)

# Diff context is 7 lines due to blank or unhelpful lines resulting in patch in
# the wrong place. (GNU diff default is 3.)
$(ZIPDIR)/marginalia-m.patch:  moodle.orig moodle
	mkdir -p $(ZIPDIR)
	-diff -Bbr -u7 -x .svn -x .DS_Store moodle.orig moodle >$(ZIPDIR)/marginalia-m.patch
	
clean-zip:
	rm -r $(ZIPDIR)

