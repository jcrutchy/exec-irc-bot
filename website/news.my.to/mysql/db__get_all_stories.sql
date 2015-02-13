SELECT * FROM news_my_to.stories AS p1
LEFT JOIN (SELECT `sid` AS sid_score,SUM(`mod`) AS score FROM news_my_to.story_mods GROUP BY `sid`) AS p2 ON p1.`sid`=p2.sid_score
LEFT JOIN (SELECT `sid` AS sid_comments,COUNT(*) AS comments FROM news_my_to.comments GROUP BY `sid`) AS p3 ON p1.`sid`=p3.sid_comments
ORDER BY p1.`sid` ASC
