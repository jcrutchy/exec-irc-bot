INSERT INTO news_my_to.comments
(`nick`,`sid`,`parent_cid`,`subject`,`content`,`auth_hash`)
VALUES
(:nick,:sid,:parent_cid,:subject,:content,:auth_hash)
