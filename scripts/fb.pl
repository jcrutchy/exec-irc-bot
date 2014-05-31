#!/usr/bin/perl -w
#########
# A hacky little perl script to pull your facebook news feed and check for messages and notifications.
# Suitable for including in a .conkyrc, use from a terminal, or further scripting.
# You will need to install Facebook::OpenGraph, IO::Null, and Mojo before using it.
# CPAN FTW.

# http://pastebin.com/kHjyDByv

use lib "/home/jared/perl5/lib/perl5";
 
use strict;
use threads;
use Socket;
use Facebook::OpenGraph;
use IPC::Open3;
use IO::Null;
 
##########
# First go to https://developers.facebook.com
# Create a new app. Display name: anything, Category: any, leave the rest alone.
# Fill $appID and $secret in with the values from the page you are redirected to.
# Don't close this page yet.
my $appID = "YOUR_APP_ID";
my $secret = 'YOUR SECRET';
 
##########
# Next hit Settings on your app's page. Then hit Add Platform and Website.
# Fill in the address of your callback server; the box this will be running on.
# ex https://192.168.1.4/, https://hostname/, https://yourhost.no-ip.biz/
#
# Also add the same address without the https:// on the same page under App Domains
#
# Replace YOUR_ADDRESS_HERE with the address you put under App Domains and
# YOUR_PORT_HERE with anything over 2048 that isn't already in use. I use 6666;
my $cbAddress = "debian";
my $cbPort = "6666";
 
##########
# Use the system's default browser for app authorization. Feel free to change this to whatever browser you like.
my $browser_cmd = "epiphany-browser";
 
 
##########
# That should be it. Now name this whatever you like, chmod a+x it, and put it somewhere in $PATH.
# I recomend against a system directory like /usr/bin/ but it's your box.
 
my $redirectUri = "https://$cbAddress:$cbPort/";
my ($expires, $accessToken, $accessCode, $tokenRef) = ('', '', '', '');
my $storage = "$ENV{HOME}/.TMBuzzard";
my ($codeFile, $lockFile, $tokenFile, $expireFile ) = ("$storage/fbauth.code", "$storage/fbauth.lock", "$storage/fbauth.token", "$storage/fbauth.expire");
 
# If we don't have a storage directory, create one at ~/.TMBuzzard
 
unless( -d $storage){mkdir($storage);}
 
# Start baby web server in its own thread.
my $daemon = threads->create('startDaemon', $cbPort);
die "Could not start callback server" unless $daemon;
$daemon->detach();
 
# Create $fb object. No data exchange is done.
my $fb = Facebook::OpenGraph->new(+{
        app_id => $appID,
        secret => $secret,
        redirect_uri => $redirectUri
       
});
 
# Grab the authorization url for later.
my $auth_url = $fb->auth_uri(+{
      display       => 'page',
      response_type => 'code',
      scope         => [qw/manage_notifications read_stream read_mailbox/],
});
 
# Read in our expiry date in seconds from epoch or set to zero.
if( -f $expireFile )
{
        open EXPIRE, "< $expireFile" || die "could not open file: $expireFile. $!\n";
        $expires = <EXPIRE>;
        chomp $expires;
        close EXPIRE;
}
else{$expires = 0;}
 
# Do we already have a token that isn't expired? If so read it. If not get a code to get one.
if( (-f $tokenFile) && (! &isExpired($expires) ) )
{
        open TOKEN, "< $tokenFile" || die "could not open file: $tokenFile. $!\n";
        $accessToken = <TOKEN>;
        close TOKEN;
}
else
{
        # Wait until we're listening with the cbServer.
        while(&notListening()){sleep 1;}
        # Now it's safe to do stuff.
        # Redirect stderr and stdout then launch $browser
        my $in = \*STDIN;
        my $out = IO::Null->new;
        my $pid = open3($in, $out, $out, "$browser_cmd '$auth_url'");
        waitpid($pid, 0);
       
        # Wait until codeFile exists and lockFile does not.
        while(! -f $codeFile)
        {
                sleep 1;
        }
        while( -f $lockFile){sleep 1;}
 
        # Read accessCode from codeFile.
        open CODE, "< $codeFile" || die "could not open file: $codeFile. $!\n";
        $accessCode = <CODE>;
        close CODE;
        unlink $codeFile;
}
 
# If we don't have an access token, take the code from earlier and get one with it.
if($accessToken eq "")
{
        $tokenRef = $fb->get_user_token_by_code($accessCode);
        $accessToken = $tokenRef->{access_token};
        open TOKEN, "> $tokenFile";
        print TOKEN $accessToken || die "could not open file: $tokenFile. $!\n";
        close TOKEN;
        $expires = $tokenRef->{expires} + time;
        open EXPIRE, "> $expireFile" || die "could not open file: $expireFile. $!\n";
        print EXPIRE $expires;
        close EXPIRE;
}
 
# Set the token in the $fb object so we can start doing stuff.
$fb->set_access_token($accessToken);
 
# Do the actual fetching and printing of info.
 
# Notifications
my $notifications = $fb->get('v2.0/me/notifications?fields=id');
my $notItems = $notifications->{data};
my $notCount = scalar @$notItems;
print "You have $notCount unread Notifications\n\n" if $notCount;
 
# Messages
my $messages = $fb->get ('v2.0/me/inbox?fields=unread');
my $mesCount = $messages->{summary}->{unread_count};
print "You have $mesCount unread Messages\n\n" if $mesCount;
 
# News
my $news = $fb->get('v2.0/me/home?limit=50&fields=message,type,from');
my $items = $news->{data};
for my $item (@$items)
{
        next unless $item->{type} eq "status";
        next unless $item->{message};
        print $item->{from}->{name},"\n";
        print $item->{message}, "\n";
        print "\n";
}
 
# Returns 1 if expired 0 otherwise
sub isExpired()
{
        my $expires = shift @_;
        return 1 if $expires < time;
        return 0;
}
 
# Callback server. Takes port number as its only argument
sub startDaemon()
{
        use Mojo::Server::Daemon;
        my $cbPort = shift;
        my $cbServer = Mojo::Server::Daemon->new(listen => ["https://*:$cbPort"]);
        my $storage = "$ENV{HOME}/.TMBuzzard";
        $cbServer->unsubscribe('request');
        $cbServer = $cbServer->silent('1');
        $cbServer->on(request => sub {
                my ($daemon, $tx) = @_;
 
                # We're threaded, so make sure a lock file exists until fbauth.code is safe to touch.
                open LOCKFILE, "> $storage/fbauth.lock" || die "can't open $storage/fbauth.lock: $!\n";
                print LOCKFILE "foo";
                close LOCKFILE;
                # Write the code we received out.
                open CODE, "> $storage/fbauth.code" || die "can't open $storage/fbauth.code: $!\n";
                my $code = $tx->req->url->query;
                chomp($code);
                $code =~ s/^code=//;
                print CODE $code;
                close CODE;
                # Safe now. Kill the lock file.
                unlink "$storage/fbauth.lock";
 
                # Request
                my $method = $tx->req->method;
                my $path   = $tx->req->url->path;
 
                # Response
                $tx->res->code(200);
                $tx->res->headers->content_type('text/plain');
                $tx->res->body("You can go ahead and close this.");
 
                # Resume transaction
                $tx->resume;
        });
        $cbServer->run;
}
 
 
# Returns 1 if the callback server is not already listening and 0 if it is.
sub notListening()
{
        use constant IPPROTO_TCP => 6;
 
        my $address = '127.0.0.1';
 
        my $packed_addr = inet_aton($address);
        my $destination = sockaddr_in($cbPort, $packed_addr);
 
        socket(SOCK, PF_INET, SOCK_STREAM, IPPROTO_TCP) or die " Cant make socket";
        connect(SOCK, $destination) or return 1;
        shutdown(SOCK, 2);
        close(SOCK);
        return 0;
}
