


-- Marginalia for Moodle Readme --


Marginalia for Moodle provides annotation support for Moodle discussion forums. For updates to this release and for a stand-alone demo version of the code, please seemy web site. The stand-alone version includes some utilities and scripts that are not part of the Moodle distribution, such test frameworks and the script that generateshtml-model.js. I highlighy recommend this version for anyone who plans to work with the source code, even within Moodle. The stand-alone version is a much clearer example of how annotation works, and includes additional documentation in the example source code.

The following files contain further documentation on specific topics. In particular, seeINSTALLfor installation istructions. There are two versions of these files: HTML files in thedocdirectory, and raw text versions here.

* CREDITS- a list of people and organizations who assisted in the development of annotation

* LICENSE.txt- the GNU General Public License

* INSTALL- installation instructions

* MANUAL- user instructions

* CHANGES- changes and bugfixes in this release

* DEVEL- instructions for adding annotation support to other parts of Moodle


== License & Funding ==


SeeCREDITSfor details on contributors to this project.

I want to particularly thank BC Campus for funding this effort, and for allowing its release under the GNU General Public License, and to Simon Fraser University through Dr. Andrew Feenberg's Applied Communication and Technology Lab in the School of Communication and through the Learning and Instructional Development Centre for their financial support. Malaspina University-College has also been supportive, as they provided a test site for Marginalia for Moodle.

Open Journal Systems support and other improvements to Marginalia were made possible by Dr. Rick Kopak's "Navigating Information Spaces" project at the University of British Columbia, funded by the Social Sciences and Humanities Research Council of Canada. Chia-ning Chiang and John Willinsky have also been essential in getting this project off the ground.

The UNDESA "Africa i-Parliaments Action Plan" project implemented edit actions, the per-paragraph multiuser support, and a number of other improvements. Thank you to Flavio Zeni, Jean Jordaan, and Ashok Hariharan for all their help and support.

Most of the code is Copyright (c) 2005-2008 Geoffrey Glass and the United Nations.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of theGNU General Public Licensealong with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.


== Limitations and Known Bugs ==


I am aware of several limitations with the software:

* This version has not yet been tested and fixed for Internet Explorer. It may work with IE to an extent, and I plan to make any necessary fixes. Other browsers may display annotations, but they probably won't allow them to be created.

* I have only tried annotation with a MySQL database. It's possible there are incompatibilities with others (e.g. Postgres).

* Smartcopy links are stripped by the HTML editor in Moodle. If the path to a link is in a different directory, the link won't work. I don't want to disable this behavior without understanding exactly why it's there.

* Annotations cannot be edited from the summary page.

* Annotation is incompatible with the Firefox "Auto Copy" extension if the extension is set to automatically clear the selection once it has been copied (this can be modified with the "Auto Copy" preferences).

Internet Explorer support does not (will not) support a number of refinements:

* Smartcopy is not implemented for IE.

* The create annotation button does not highlight when the mouse is over it.

* If an annotation cannot be displayed in the right location because the document has changed (and hence the highlight can't be located), IE does not display the error indicator explaining the problem. However, the full annotation is still visible in the summary page.