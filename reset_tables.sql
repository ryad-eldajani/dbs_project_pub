DELETE FROM hashtag_pair;
DELETE FROM hashtag_post;
DELETE FROM hashtag;
DELETE FROM post;
DELETE FROM candidate;
ALTER SEQUENCE hashtag_h_id_seq RESTART WITH 1;
ALTER SEQUENCE post_p_id_seq RESTART WITH 1;
ALTER SEQUENCE candidate_c_id_seq RESTART WITH 1;