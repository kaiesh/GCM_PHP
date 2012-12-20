A quick set of files to provide an easy to copy/move GCM push server based on PHP.

The GCM code here is based on this answer in this thread: 
http://stackoverflow.com/a/11253231

The PHP framework used is from:
https://github.com/kaiesh/PHPLightweightFramework

The guts for the GCM pushing service are encapsulated in one object in:
objects/controllers/GCMPush.php

You can copy/paste these files into your server, and make only two modifications to test it:
- update the API key in /push.php
- configure server details in objects/GCMCore.php
- (don't forget to create the db+table!)

To test:
- Register devices by calling [server]/[dir]/?deviceid=[device push ID from Google]
- Visit: [server]/[dir]/push.php and enter your push message!