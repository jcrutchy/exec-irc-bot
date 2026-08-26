SELECT * FROM news_my_to.comments AS p1
LEFT JOIN (SELECT `cid` AS cid_score,SUM(`mod`) AS score FROM news_my_to.comment_mods GROUP BY `cid`) AS p2 ON p1.`cid`=p2.cid_score
WHERE `sid`=:sid
