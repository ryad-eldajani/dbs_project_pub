CREATE SEQUENCE hashtag_h_id_seq
  START WITH 1
  INCREMENT BY 1
  NO MINVALUE
  NO MAXVALUE
  CACHE 1;

CREATE SEQUENCE post_p_id_seq
  START WITH 1
  INCREMENT BY 1
  NO MINVALUE
  NO MAXVALUE
  CACHE 1;

CREATE SEQUENCE candidate_c_id_seq
  START WITH 1
  INCREMENT BY 1
  NO MINVALUE
  NO MAXVALUE
  CACHE 1;


CREATE TABLE candidate
(
  c_id INTEGER DEFAULT nextval('candidate_c_id_seq'::regclass) PRIMARY KEY NOT NULL,
  username VARCHAR(255)
);

CREATE TABLE post
(
  p_id INTEGER DEFAULT nextval('post_p_id_seq'::regclass) PRIMARY KEY NOT NULL,
  c_id INTEGER NOT NULL,
  text VARCHAR(255),
  timestamp TIMESTAMP,
  favorite_count INTEGER,
  retweet_count INTEGER,
  CONSTRAINT fk_c_id FOREIGN KEY (c_id) REFERENCES candidate (c_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE hashtag
(
  h_id INTEGER DEFAULT nextval('hashtag_h_id_seq'::regclass) PRIMARY KEY NOT NULL,
  text VARCHAR(255),
  count INTEGER
);

CREATE TABLE hashtag_pair
(
  count INTEGER,
  h1_id INTEGER NOT NULL,
  h2_id INTEGER NOT NULL,
  CONSTRAINT pk_hashtag_pair PRIMARY KEY (h1_id, h2_id),
  CONSTRAINT fk_hashtag_pair_h1_id FOREIGN KEY (h1_id) REFERENCES hashtag (h_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_hashtag_pair_h2_id FOREIGN KEY (h2_id) REFERENCES hashtag (h_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE hashtag_post
(
  count INTEGER,
  h_id INTEGER NOT NULL,
  p_id INTEGER NOT NULL,
  CONSTRAINT pk_hashtag_post PRIMARY KEY (h_id, p_id),
  CONSTRAINT fk_h_id FOREIGN KEY (h_id) REFERENCES hashtag (h_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_p_id FOREIGN KEY (p_id) REFERENCES post (p_id) ON DELETE CASCADE ON UPDATE CASCADE
);
