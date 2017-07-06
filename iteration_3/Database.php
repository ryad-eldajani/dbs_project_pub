<?php
/**
 * Class Database.
 */
class Database {
    /**
     * Database constructor.
     */
    function __construct() {
        $connection = "host=HOST port=5432 dbname=DB user=USER password=PASS";
        $this->connection = pg_connect($connection);
        $this->hashtagPairs = null;
        $this->hashtagCounts = null;
        $this->hashtagTimes = null;
        $this->dates = [];
        $this->datesByHashtag = [];
        $this->first_date = null;
        $this->last_date = null;
        $this->getHashtagPairsJson();
        $this->getHashtagCountsJson();
        $this->getHashtagTimesJson();
    }

    /**
     * Returns all hashtag pairs as hashtag texts.
     * @return array
     */
    function getHashtagPairs() {
        if ($this->hashtagPairs == null) {
            $result = pg_query($this->connection,
                "
                SELECT lower(ht1.text) AS h1_text, lower(ht2.text) AS h2_text
                FROM hashtag_pair hp, hashtag ht1, hashtag ht2
                WHERE hp.h1_id = ht1.h_id AND hp.h2_id = ht2.h_id;
            ");
            $this->hashtagPairs = pg_fetch_all($result);
        }

        return $this->hashtagPairs;
    }

    /**
     * Returns an array hashtag -> timestamp
     * @return array
     */
    function getHashtagTimes() {
        $result = pg_query($this->connection,
            "
            SELECT lower(ht.text) AS text, date(p.timestamp) AS date
            FROM hashtag ht, hashtag_post hp, post p
            WHERE ht.h_id = hp.h_id AND hp.p_id = p.p_id;
        ");
        return pg_fetch_all($result);
    }

    /**
     * Returns an array date -> count (hashtags)
     * @return array
     */
    function getHashtagCountsByDate() {
        $result = pg_query($this->connection,
            "
            SELECT DATE(p.timestamp) AS date, count(ht.text)
            FROM hashtag ht, hashtag_post hp, post p
            WHERE ht.h_id = hp.h_id AND hp.p_id = p.p_id
            GROUP BY date
            ORDER BY date;
        ");
        return pg_fetch_all($result);
    }

    /**
     * Returns hashtag pairs as JSON string.
     * @return string JSON string
     */
    function getHashtagPairsJson() {
        $list = [];
        foreach ($this->getHashtagPairs() as $hashtagPair) {
            $list[] = [$hashtagPair["h1_text"], $hashtagPair["h2_text"]];
        }

        return json_encode($list);
    }

    /**
     * Returns hashtag pair combinations as JSON string.
     * @return string JSON string
     */
    function getHashtagCombinationsJson() {
        $hashtagPairs = $this->getHashtagPairs();
        $dict = [];
        foreach ($hashtagPairs as $hashtagPair) {
            $hashtag1 = $hashtagPair["h1_text"];
            $hashtag2 = $hashtagPair["h2_text"];

            $dict[$hashtag1][] = $hashtag2;
            $dict[$hashtag2][] = $hashtag1;

            foreach ($hashtagPairs as $hashtagPair2) {
                if ($hashtag1 == $hashtagPair2["h1_text"]
                    && !in_array($hashtagPair2["h2_text"], $dict[$hashtag1])) {
                    $dict[$hashtag1][] = $hashtagPair2["h2_text"];
                } else if ($hashtag1 == $hashtagPair2["h2_text"]
                    && !in_array($hashtagPair2["h1_text"], $dict[$hashtag1])) {
                    $dict[$hashtag1][] = $hashtagPair2["h1_text"];
                } else if ($hashtag2 == $hashtagPair2["h1_text"]
                    && !in_array($hashtagPair2["h2_text"], $dict[$hashtag2])) {
                    $dict[$hashtag2][] = $hashtagPair2["h2_text"];
                } else if ($hashtag2 == $hashtagPair2["h2_text"]
                    && !in_array($hashtagPair2["h1_text"], $dict[$hashtag2])) {
                    $dict[$hashtag2][] = $hashtagPair2["h1_text"];
                }
            }
        }

        return json_encode($dict);
    }

    /**
     * Returns hashtag times as JSON string.
     * @return string JSON string
     */
    function getHashtagTimesJson() {
        if ($this->hashtagTimes == null) {
            $this->hashtagTimes = [];
            foreach ($this->getHashtagTimes() as $hashtagTime) {
                $this->hashtagTimes[$hashtagTime["text"]][] = [strtotime($hashtagTime["date"]), 1];
            }
            foreach ($this->hashtagTimes as $hashtag=>$hashtagTime) {
                $this->fillMissingDates($this->hashtagTimes[$hashtag], 0);
            }
        }

        return json_encode($this->hashtagTimes);
    }

    /**
     * Returns hashtag counts as JSON string.
     * @return string JSON string
     */
    function getHashtagCountsJson() {
        if ($this->hashtagCounts == null) {
            $this->hashtagCounts = [];
            foreach ($this->getHashtagCountsByDate() as $hashtagCount) {
                $this->dates[] = $hashtagCount["date"];
                $this->hashtagCounts[] = [strtotime($hashtagCount["date"]), (int)$hashtagCount["count"]];
            }

            // fill dates for no hashtags
            $this->first_date = $this->hashtagCounts[0][0];
            $this->last_date = $this->hashtagCounts[count($this->hashtagCounts)-1][0];
            $this->fillMissingDates($this->hashtagCounts, 0);

            //print_r($this->hashtagCounts);
        }

        return json_encode($this->hashtagCounts);
    }

    /**
     * Sets missing dates for list.
     * @param $list array list to set
     * @param $value string to set
     */
    function fillMissingDates(&$list, $value) {
        for ($i = $this->first_date; $i <= $this->last_date; $i = $i + 86400) {
            $this_date = date('Y-m-d', $i);

            if (!in_array($this_date, $list)) {
                $list[] = [strtotime($this_date), $value];
            }
        }
    }
}

