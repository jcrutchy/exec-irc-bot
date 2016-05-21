exec-irc-bot readme
===================

documentation: http://sylnt.us/exec

php irc bot that runs shell commands from aliases

aliases can trigger generic shell commands and send stdout automatically to channel or send only specific output based on simple stdout commands like "/IRC output some text"

can run scripts written in various languages, like php, python, ruby, haskell, perl and bash
can also run compiled programs like "apt-get moo" or curl

start bot from terminal with:
php irc.php

bot code is procedural style, using global variables for settings etc to keep it simple
main bot script requires irc_lib.php

useful commands:
~list
~list-auth
~join (admin)

http://wiki.soylentnews.org/wiki/IRC:exec#Installing_and_running_your_own_.27exec.27_bot
