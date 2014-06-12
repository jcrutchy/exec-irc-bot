<?php

# gpl2
# by crutchy
# 12-june-2014

# /nas/server/git/data/atom.feeds contains a list of urls for scraping

# http://phys.org/rss-feed/

/*
Bytram, 9-june-14
it would be nice if you could get together with Juggs and have his Regurgitator
output not just the raw RSS feed link, but also snag the title, AND follow past
the feed-redirect-crap to get the REAL URL
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

$html=wget("soylentnews.org","/index.atom",80);
$html=strip_headers($html);

$entries=parse_atom($html);
if ($entries===False)
{
  privmsg("error parsing atom feed");
}

term_echo("count: ".count($entries));

#####################################################################################################

/*
ATOM
<entry>
<id>http://soylentnews.org/article.pl?sid=14/06/09/0214225&amp;from=rss</id>
<title>Eye of Sauron Star Image Released by ESO</title>
<link href="http://soylentnews.org/article.pl?sid=14/06/09/0214225&amp;from=rss"/>
<summary><![CDATA[<p class="byline"> <a href="http://soylentnews.org/~Open4D/">Open4D</a> writes:</p><blockquote><div><p>The new <a href="http://www.eso.org/sci/facilities/develop/instruments/sphere.html">SPHERE</a> instrument for the European Southern Observatory's <a href="http://en.wikipedia.org/wiki/Very_Large_Telescope">Very Large Telescope</a> recently achieved 'first light'.  The <a href="http://www.newscientist.com/article/dn25676-eye-of-sauron-star-spotted-by-planethunting-camera.html">New Scientist is reporting on an image they released this week</a> that calls into question whether Frodo really did snuff out the Eye of Sauron.<br> <br>So, how long before the MPAA goes after the <a href="http://en.wikipedia.org/wiki/HR_4796">HR 4796 system</a> for breach of copyright?</p></div> </blockquote><p>On a more serious note, the <a href="http://www.newscientist.com/data/images/ns/cms/dn25676/dn25676-1_1200.jpg">image</a> has amazing detail.  This should make it easier to detect and analyze planets orbiting other stars.</p><p><a href="http://soylentnews.org/article.pl?sid=14/06/09/0214225&amp;from=rss">Read more of this story</a> at SoylentNews.</p>]]></summary>
<updated>2014-06-09T11:55:00+00:00</updated>
<author>
 <name>martyb</name>
</author>
<category term="science"/>
<slash:department>I-see-you-looking-at-me</slash:department>
<slash:section>mainpage</slash:section>
<slash:hit_parade>0,0,0,0,0,0,0</slash:hit_parade>
</entry>
*/

function parse_atom($html)
{
  $parts=explode("<entry>",$html);
  array_shift($parts);
  $entries=array();
  for ($i=0;$i<count($parts);$i++)
  {
    $entry=array();
    $entry["id"]=extract_raw_tag($parts[$i],"id");
    $entry["title"]=extract_raw_tag($parts[$i],"title");
    $entry["summary"]=extract_raw_tag($parts[$i],"summary");
    $entry["updated"]=extract_raw_tag($parts[$i],"updated");
    $entry["link"]=extract_void_tag($parts[$i],"link");
    if ($entry["id"]===False)
    {
      continue;
    }
    $entries[]=$entry;
  }
  return $entries;
}

#####################################################################################################

/*
RSS
 <item>
     <title>El Hierro Volcano helps to improve algorithms used by satellites</title>
   	 <description>Information provided by satellites on the amount of chlorophyll-A and the roughness of the sea following the eruption of the underwater volcano off the island of El Hierro (Spain) did not coincide with the actual data collected in situ by vessels carrying out oceanographic studies. The models have been corrected by researchers at the University of Las Palmas de Gran Canaria, who have for the first time processed very high resolution images of this kind of natural phenomenon captured from space.</description>
     <link>http://phys.org/news321516186.html</link>
	 <category>Earth</category>
	 <pubDate>Mon, 09 Jun 2014 07:03:18 EDT</pubDate>
	 <guid isPermaLink="false">news321516186</guid>
	 <media:thumbnail url="http://cdn.phys.org/newman/gfx/news/tmb/2014/elhierrovolc.jpg" width="90" height="90" />
</item>
*/

/*
RSS
<item rdf:about="http://soylentnews.org/article.pl?sid=14/06/08/1349221&#x26;amp;from=rss">
<title>GM Fires Employees for the &#x22;Switch From Hell&#x27;</title>
<link>http://soylentnews.org/article.pl?sid=14/06/08/1349221&#x26;amp;from=rss</link>
<description><![CDATA[<p class="byline"> <a href="http://poncacityweloveyou.com/">Hugh Pickens</a> writes:</p><p>James R. Healey reports that General Motors has <a href="http://www.usatoday.com/story/money/cars/2014/06/05/gm-barra-report-valukas-failure/9985709/">fired 15 people who either were incompetent or irresponsible in their actions</a> involving fatally flawed ignition switches that are linked to 13 deaths in crashes where airbags failed to inflate. "A disproportionate number of those were in senior roles or executives," said GM CEO Mary Barra. Two high-ranking engineers previously put on paid leave were among them, said Barra adding that five more employees  "one level removed"  were disciplined in unspecified ways because they "simply didn't take action." </p><p>
A far back as 2002, General Motors engineers starting calling it the <a href="http://abcnews.go.com/Business/wireStory/engineers-switch-hell-began-gm-recall-woes-24021644">"switch from hell"</a> but it would <a href="http://www.latimes.com/business/autos/la-fi-gm-recall-findings-20140606-story.html#page=1">take a dozen years, more than 50 crashes and at least 13 deaths for the automaker to recall the ignition switch</a>, used in millions of small cars. GM's own internal investigation  never explains how a lone engineer in a global automaker could approve a less expensive part that failed to meet GM standards. Nor does it illuminate why the same engineer could substitute an improved design without changing the part number, a move critics cite as evidence of a cover-up. After the first cars with the switch went on sale, GM heard complaints from customers, employees and dealers. But "group after group and committee after committee within GM that reviewed the issue failed to take action or acted too slowly," the report said. A unique series of mistakes was made," said Barra. And the problem was misunderstood to be one of owner satisfaction and not safety. GM engineers didn't understand that when the switches failed, they cut power to the airbags.</p><p><a href="http://soylentnews.org/article.pl?sid=14/06/08/1349221&amp;from=rss">Read more of this story</a> at SoylentNews.</p>]]></description>
<dc:creator>n1</dc:creator>
<dc:date>2014-06-08T18:37:00+00:00</dc:date>
<dc:subject>news</dc:subject>
<slash:department>unique-series-of-mistakes</slash:department>
<slash:section>mainpage</slash:section>
<slash:comments>36</slash:comments>
<slash:hit_parade>36,36,31,21,7,2,0</slash:hit_parade>
</item>
*/

function parse_rss($html)
{
  return False;
  return $items;
}

#####################################################################################################

?>
