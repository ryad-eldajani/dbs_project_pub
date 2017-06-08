import bs4
import requests
import csv
import re
import operator


def get_page(url):
    """
    Returns a BeautifulSoup object from an URL request
    :param url: URL
    :return: BeautifulSoup object
    """
    r = requests.get(url)
    data = r.text
    return bs4.BeautifulSoup(data, "lxml")


def main():
    """
    Web-Scraper for heise.de HTTPS topics.
    """
    file_obj = open('heise-data.csv', 'w')
    csv_writer = csv.writer(file_obj, delimiter=';')

    words = {}
    heise_url = "https://www.heise.de/thema/https"
    link_pages = get_page(heise_url).find_all("span", {"class", "pagination"}) \
        [0].find_all("a")

    # scrape all sub-pages of topic HTTPS
    for link in link_pages:
        page = get_page("https://www.heise.de" + link["href"])

        headlines = page.find_all("div", {"class": "keywordliste"})[0] \
            .find_all("nav")[0].find_all("header")

        for headline in headlines:
            # split words in headline, filter some chars like ";"
            headline_words = re.findall(r'[^\"()\-,;:\s]+', headline.string)

            # set/update counter in words dictionary
            for word in headline_words:
                if word in words:
                    words[word] += 1
                else:
                    words[word] = 1

    # sort words dictionary by count value
    sorted_words = sorted(words.items(), key=operator.itemgetter(1),
                          reverse=True)

    # write result in CSV file
    for element in sorted_words:
        csv_writer.writerow(element)

    file_obj.close()
    print("Scraping complete, top 3 words: {}".format(sorted_words[:3]))

if __name__ == '__main__':
    main()