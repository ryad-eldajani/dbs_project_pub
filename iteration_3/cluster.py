import psycopg2
import nltk
import Pycluster as PC


class Database:
    """
    Class for database operations.
    """
    def __init__(self):
        """
        Constructor.
        """
        self.conn = psycopg2.connect("dbname='DB' user='USER' "
                                     "host='HOST' password='PASSWORD'")
        self.cur = self.conn.cursor()

    def get_hashtags(self):
        """
        Reads all hashtags.
        :return: List of hashtags
        """
        self.cur.execute("""
            SELECT DISTINCT lower(text) FROM hashtag;
        """)
        return self.cur.fetchall()


class ClusterAnalysis:
    def __init__(self):
        """
        Constructor
        Source: https://stackoverflow.com/a/6293804/3675566
        """
        self.db = Database()
        hashtags_unclean = self.db.get_hashtags()
        hashtags = []
        for ht in hashtags_unclean:
            hashtags.append(ht[0])

        dist = [nltk.edit_distance(hashtags[i], hashtags[j])
                for i in range(1, len(hashtags))
                for j in range(0, i)]

        labels, error, nfound = PC.kmedoids(dist, nclusters=100)
        cluster = dict()
        for word, label in zip(hashtags, labels):
            cluster.setdefault(label, []).append(word)
        for label, grp in cluster.items():
            print(grp)


if __name__ == "__main__":
    ClusterAnalysis()
