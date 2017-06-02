import re
import psycopg2
import itertools


class Database:
    """
    Class for database operations.
    """
    def __init__(self):
        """
        Constructor.
        """
        self.conn = psycopg2.connect("dbname='election' user='USER' "
                                     "host='HOST' password='PASSWORD'")
        self.cur = self.conn.cursor()
        self.candidate_ids = {}
        self.hashtag_ids = {}
        self.hashtag_posts_inserted = []
        self.hashtag_pairs_inserted = []

    def reset_tables(self):
        """
        Resets all tables to default state.
        """
        self.cur.execute("""
            DELETE FROM hashtag;
            DELETE FROM hashtag_pair;
            DELETE FROM hashtag_post;
            DELETE FROM post;
            DELETE FROM candidate;
            ALTER SEQUENCE hashtag_h_id_seq RESTART WITH 1;
            ALTER SEQUENCE post_p_id_seq RESTART WITH 1;
            ALTER SEQUENCE candidate_c_id_seq RESTART WITH 1;
        """)
        self.conn.commit()

    def get_candidate_id(self, entry):
        """
        Returns a candidate ID for a specific entry.
        If not existent, it creates the candidate.
        :param entry: Data entry
        :return: Candidate ID for entry
        """
        if entry["candidate"] in self.candidate_ids:
            return self.candidate_ids[entry["candidate"]]

        # select user id from database, if not existent, insert and re-select
        self.cur.execute("""
              SELECT c_id FROM candidate WHERE username = %s
            """, [entry["candidate"]])
        candidate_id = self.cur.fetchone()
        if candidate_id is None:
            self.cur.execute("""
              INSERT INTO candidate (username)
              VALUES (%s) RETURNING c_id
            """, [entry["candidate"]])
            candidate_id = self.cur.fetchone()
            self.conn.commit()

        self.candidate_ids[entry["candidate"]] = candidate_id[0]
        return candidate_id[0]

    def insert_post(self, entry):
        """
        Inserts a post to the database.
        :param entry: Data entry
        :return: Post ID in database
        """
        candidate_id = self.get_candidate_id(entry)
        self.cur.execute("""
              INSERT INTO post (c_id, text, timestamp, favorite_count,
                retweet_count)
              VALUES (%s, %s, TIMESTAMP %s, %s, %s) RETURNING p_id
            """, [candidate_id, entry["text"], entry["time"],
                  entry["favorite_count"], entry["retweet_count"]])
        post_id = self.cur.fetchone()[0]
        self.conn.commit()
        return post_id

    def insert_hashtag(self, entry, post_id):
        """
        Inserts a hashtag, hashtag pair and hashtag post association
        to the database.
        :param post_id: Post ID
        :param entry: Data entry
        :return: Hashtag ID in database
        """
        # handle all hashtags from this entry
        for i in range(len(entry["hashtags"])):
            hashtag = entry["hashtags"][i]

            # if we already have the hashtag ID, increase count
            if hashtag in self.hashtag_ids:
                hashtag_id = self.hashtag_ids[hashtag]
                self.cur.execute("""
                    UPDATE hashtag SET count = count + 1
                    WHERE h_id = %s
                  """, [hashtag_id])
                self.conn.commit()
            else:
                self.cur.execute("""
                    INSERT INTO hashtag (text, count)
                    VALUES (%s, %s) RETURNING h_id
                  """, [hashtag, 1])
                hashtag_id = self.cur.fetchone()[0]
                self.conn.commit()
                self.hashtag_ids[hashtag] = hashtag_id

            # Add hashtag_post row in database or update count (as in
            # rare cases, the same hashtag can appear twice in one post).
            if (hashtag_id, post_id) in self.hashtag_posts_inserted:
                self.cur.execute("""
                    UPDATE hashtag_post SET count = count + 1
                    WHERE h_id = %s AND p_id = %s
                  """, [hashtag_id, post_id])
                self.conn.commit()
            else:
                self.cur.execute("""
                        INSERT INTO hashtag_post (h_id, p_id, count)
                        VALUES (%s, %s, %s)
                      """, [hashtag_id, post_id, 1])
                self.hashtag_posts_inserted.append((hashtag_id, post_id))
                self.conn.commit()

        # handle hashtag pairs
        for hashtag_pair in itertools.combinations(entry["hashtags"], 2):
            sorted_hashtag_ids = sorted(
                [self.hashtag_ids[hashtag_pair[0]],
                 self.hashtag_ids[hashtag_pair[1]]])
            sorted_hashtag_pair = sorted(hashtag_pair)

            # if we already have a hashtag pair inserted, increase count
            if tuple(sorted_hashtag_pair) in self.hashtag_pairs_inserted:
                self.cur.execute("""
                    UPDATE hashtag_pair SET count = count + 1
                    WHERE h1_id = %s AND h2_id = %s
                  """, [sorted_hashtag_ids[0], sorted_hashtag_ids[1]])
                self.conn.commit()
            else:
                self.cur.execute("""
                    INSERT INTO hashtag_pair (h1_id, h2_id, count)
                    VALUES (%s, %s, %s)
                  """, [sorted_hashtag_ids[0], sorted_hashtag_ids[1], 1])
                self.conn.commit()
                self.hashtag_pairs_inserted.append(tuple(sorted_hashtag_pair))


class Importer:
    def __init__(self):
        """
        Constructor for this importer.
        """
        self.entries = []
        self.parse_csv()
        self.query_database()

    def query_database(self):
        """
        Does all required database queries.
        :return:
        """
        num_entries = len(self.entries)
        db = Database()
        db.reset_tables()
        for i in range(num_entries):
            print("\rProgress: {}%".format(round(i / num_entries * 100, 1)),
                  end='')
            post_id = db.insert_post(self.entries[i])
            db.insert_hashtag(self.entries[i], post_id)

        db.conn.commit()
        print("\nProgress complete!")

    @staticmethod
    def parse_hashtags(line):
        """
        Parses all hashtags to a list.
        See: https://stackoverflow.com/a/2527903/3675566
        :param line: Input line
        :return: List of hashtags
        """
        return re.findall(r"#(\w+)", line)

    @staticmethod
    def cleanup_text(text):
        """
        Filters invalid characters from text
        :param text: Text from entry
        :return: Cleaned up text
        """
        re_pattern = re.compile(u'[^\u0000-\uD7FF\uE000-\uFFFF]', re.UNICODE)
        return re_pattern.sub(u'\uFFFD', text)

    def parse_line(self, line):
        """
        Parses a line.
        :param line: Raw text line
        :return: Dictionary of data in line or None if invalid data
        """
        elements = line.split(';')

        # validate length of elements and first entry
        if len(elements) != 11 or elements[0] not in ["HillaryClinton",
                                                      "realDonaldTrump"]:
            return None

        return {
            "candidate": elements[0],
            "text": self.cleanup_text(elements[1]),
            "hashtags": self.parse_hashtags(line),
            "time": str(elements[4]).replace("T", " "),  # ANSI SQL Timestamp
            "retweet_count": elements[7],
            "favorite_count": elements[8]
        }

    def parse_csv(self):
        """
        Parses the CSV file.
        :return:
        """
        with open("american-election-tweets.csv", "r", encoding="cp1252",
                  errors="surrogateescape") as file:
            header = True
            for line in file:
                # if it's the first line (header), we can continue
                if header:
                    header = False
                    continue

                # parse line and append, if we have data
                entry = self.parse_line(line)
                if entry is not None:
                    self.entries.append(entry)

if __name__ == "__main__":
    Importer()
