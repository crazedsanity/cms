<?php
require_once('_app/core.php');

	$channeltitle = 'Taylor for North Dakota';
	$channeldescription = 'Taylor for North Dakota';



$rss='';
$rss.=<<<EORSS
<?xml version="1.0" encoding="ISO-8859-1" ?>
<rss version="2.0">
<channel>
  <title>{$channeltitle}</title>
  <link></link>
  <description>{$channeldescription}</description>
EORSS;
$sql=<<<EOSQL
    SELECT news_id, title, short_description, n.date, DATE_FORMAT(date, '%a, %d %b %Y %T +0000') as formateddate
    FROM news n
EOSQL;
	$news = $db->fetch_array($sql);
    
    foreach($news as $article){
$rss.=<<<EORSS
  <item>
  	<pubDate>{$article['formateddate']}</pubDate>
    <title>{$article['title']}</title>
    <link>http://{$_SERVER['SERVER_NAME']}/news?news_id={$article['news_id']}</link>
    <description>{$article['short_description']}</description>
  </item>
EORSS;
}
$rss.=<<<EORSS
</channel>
</rss>
EORSS;
echo $rss;
?>