section 1
This chat appears as a simple and easy way to install.
Using Php-fusion `s builtinfusions installation feature and chat is ready to be used.
To delete the chat, simply defuse it in php fusion infusion admin page. and it would automatically delete all database table

section 2
It is not necessary to insert code in Php-fusion `s code. you just need to link to
Root / infusions / chat / chat.php

section 3
This chat does not have a built-in guest chat aible Only user can access. guests would automatically get a message that they need to be logged in to use the chat.

section 4
Language does not follow the normal standards.
Each language has 2 files [language]. Php and [language] _server.php further you must also insert your language in [chat root] js / lang.js (see file to see how to do it)
If you do not you do it properly it can either lead to strange errors or make chat unusable.
Each updateyou must ensure that the files are updated correctly (do not upload the Javascript language file automatically as it would delete your own input language)

If you would make it easier for people who talk your language using the chat please share it with us.

section 5
The chat comes with two option of contacting server

AJAX is where javascript contacts the server in a certain range. Please note that it can overload the server if there are too many online at the same time. if you use your own server, I would recommend you to use the second solution.
Also note that this solution requires multiple database calls than the other solution.

WebSocket:
This solution is a clear advantage to use (though it is not typically a solution if you have rented space on a web server)
in this way it will not retrieve messages from the database but sends it directly to each user of a given channel.
However, included that you can not use the built-in log function which is included in this tool.
If you remove a ban in the admin section, type in the chat "/ update" and then the server update ban `s (only works with WebSocket in AJAX happens all by itself)
in the same way you do it if you changed some settings.

section 5
API.
So far, follows only 2 functionalities to the API.
Please note that they can be called if you are using WebSocket but they would not do anything.
API functionalities you will find in the file [Chat root] / api.php

section 6
Commands.
I advise you to only use these. if you manage to use one of client commands it can result in the error (eg the commands cause the server sends the response back to the client picks up and answering back)
^/join #([a-zA-Z]*?)$ -> this command gets you into a channel. please note #. all channel must start with # (like IRC)
^/msg ([a-zA-Z]*?) (.*?)$ -> this requires two things first receiver and then the message. This will be only you and the recipient who can view the message.
^/nick ([a-zA-Z]*?)$ -> This command is used to change nickname (The first time you visit the chat uses your username., but when you switch, it would not going out of your user name)
^/title (.*?)$ -> this change channels title. note that only Admin and Super Admin are able to use this command. user will get an error message
^/exit$ -> this makes you leave all channels you are a member in. to get into a new just use the join command
^/kick ([a-zA-Z]*?)$ -> this throws a user out of the channel. Note that the user can join channel again right after.
Only admin and Super admin can use this command
^/leave$ -> this leaves you the channel you are watching.
^/bot (.*?)$ -> with this command Admin and Super Admin get bot to write a message. you could not see who was using this command
^/ban ([a-zA-Z]*?) ([0-9]*?)$ -> this will need 2 things nickname and how long the use must be ban. if the user tries to join again the system would refuse permission for this.
^/unban ([a-zA-Z]*?)$ -> this command would you remove a user's ban.
^/update$ -> effective only if the connection is WebSocket. This command tells the server to reload the server's settings and update who has a ban.
^/clear$ -> This command deletes messages stored in the database. this would lead to deleted messages not access The Chat log.

section 7
Liability disclaimer.
You as a user of the chat can only make the developers of this chat responsibility if the code itself is causing the lost data.
This is not include Hacking.
The responsibility only applies if you use the Chat original code. if you have changed in the chat we would automatically see it as a cause of the problem.
If you have questions about contributions chat etc answer we of course if we have time and energy to it.

section 8
Admin menus do not work if you are using WebSocket.
Ban menu only works if you then use / update the actual chat.
Setting menu only works if you then use / update
Log does not work if you are using WebSocket. you can go in and view the page. But it would not be updated with new message when the user enters a message in the chat.

section 9
I publish the code to chat with each version change. If you want to see the code to chat before you download it and use it, you can see changes etc on the following link
https://github.com/Jugolo/chat/
Please note that no support is available at github.com. it is only to publish the code and make it easily accessible
Further, I also have a website http://jugolo.dk. However, this is in Danish and all the things I write there would also be in Danish.

------------ Change log would be posted here --------
11-07-2014
Version V.0.0.2
New:
adding danish languge
Add lang data to bot message soo it translates to new languge
server.php is not following users lang and not server lang.
bot_message if cid is not found it will call trigger_error
JSON header is now sent if is it AJAX call
Handle ban in admin page for WebSockey (server is not updating data use /update)
/clear use this if you want to delete all message in chat_message table. (it also delete all logs)

Change:
Now it get Lang befor config.
clean up status changes

Bugfix:
If system languge not exists system take English.
Chat languge is not updatet
When loding the page. it will now auto selct the lang name in setting
The text in main option is now fixed. (its is too big before ;))
Status indikator not always work. fixed.
------------ End of readme.txt ---------