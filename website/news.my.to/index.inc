<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/tfa/(.*)$ /index.php?tfa=$1 [L,QSA]
  RewriteRule ^/img/(.*)$ /index.php?img=$1 [L,QSA]
  RewriteRule ^/op/(.*)$ /index.php?op=$1 [L,QSA]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

#####################################################################################################

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title></title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">\n";
echo "<style type=\"text/css\"></style>\n";
echo "<script type=\"text/javascript\"></script>\n";
echo "</head>\n";
echo "<body style=\"font-family: 'Courier New', Courier, monospace;\">\n";

echo "<p>news.my.to: home | recent | submit | about | help | css / atom</p>\n";

echo "<hr>\n";
echo "<p>08 Feb 15 | <a href=\"/op/mod?dir=dn&amp;tfa=1678\">&lt;&lt;</a> <a href=\"/op/mod?dir=up&amp;tfa=1678\">&gt;&gt;</a> +03 | 050 comments | <a href=\"/tfa/1678\">Google Prepares to Disrupt Wireless Carrier Industry; Verizon Yawns</a></p>\n";

echo "<hr>\n";
echo "<p>08 Feb 15 | <a href=\"/op/mod?dir=dn&amp;tfa=1678\">&lt;&lt;</a> <a href=\"/op/mod?dir=up&amp;tfa=1678\">&gt;&gt;</a> +03 | 050 comments | <a href=\"/tfa/1678\">Pluto's Evaporating Ice Leaves it with a Blank Face</a></p>\n";

echo "<hr>\n";
echo "<p>08 Feb 15 | <a href=\"/op/mod?dir=dn&amp;tfa=1678\">&lt;&lt;</a> <a href=\"/op/mod?dir=up&amp;tfa=1678\">&gt;&gt;</a> +03 | 050 comments | <a href=\"/tfa/1678\">Scientists Explain How Brian Williams' Memory may have Failed Him</a></p>\n";

echo "<hr>\n";
echo "<p>08 Feb 15 | <a href=\"/op/mod?dir=dn&amp;tfa=1678\">&lt;&lt;</a> <a href=\"/op/mod?dir=up&amp;tfa=1678\">&gt;&gt;</a> +03 | 050 comments | <a href=\"/tfa/1678\">Jailbreak your Tractor or Make it Run OSS?</a></p>\n";

echo "</body>\n";
echo "</html>\n";

#####################################################################################################

?>
