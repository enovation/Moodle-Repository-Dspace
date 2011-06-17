This code was developed by Enovation Solutions on behalf of the Irish National 
Digital Learning Resource (NDLR) service (www.ndlr.ie) The NDLR  is funded by 
the Irish Higher Education Authority and is a collaborative service that 
provides a platform and support for the development and sharing of reusable
digital learning objects.

DSpace Repository plugin:

This the interface to the DSpace repository for Moodle 2.
This allows the definition of the Dspace that will be searched. You will need the DSpace REST URL
along with a username and password for that site.
Please consult with the DSpace repository for this information.

Then when adding a file you will be able to select the DSpace respository and search for files within it.

Installation:
1) Please make sure that you have http://tracker.moodle.org/browse/MDL-27125 applied to your system as this causes problems with downloading files otherwise.
2) Create a directory dspace in the respository directory
3) Copy all of this code into that directory
4) Go to the Manage repositories and enable the DSpace repository
5) Set the settings required DSpace REST URL, username and password
6) Then within a course add a resource of a file and use the DSpace option to find the required file


