A quick set of files to provide an easy to copy/move GCM push server based on PHP.

The GCM code here is based on this answer in this thread: 
http://stackoverflow.com/a/11253231

The structure of this repo is broken up as follows:

    GCM_PHP            <-- Holds basic docs and app
     |- site-online    <-- The contents of this dir will go on your server
          |- web       <-- Apache needs access to the files under here
              |- content  <-- Public access needed
              |- objects  <-- Intended not to be available to the public

The guts for the GCM pushing service are encapsulated in one object in:
site-online/web/objects/controllers/GCMPush.php

** Note: This requires the CURL extension to be available in PHP **

You can copy/paste these files into your server, and make only two modifications to test it:
- update all configurations in the site-online/web/objects/Settings.php file
- deploy the necessary database from the site-online/gcm-db.sql file (or update the scripts to your DB tables)
- [OPTIONAL] if you would like to move the "objects" directory somewhere else, then don't forget to update the pages under site-online/web/content

To test:
- Register devices by calling [server]/[dir]/?deviceid=[device push ID from Google]
- Visit: [server]/[dir]/push.php and enter your push message!