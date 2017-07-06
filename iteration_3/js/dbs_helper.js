/**
 * Source: https://stackoverflow.com/a/1484514/3675566
 * @returns {string}
 */
function getRandomColor() {
    var letters = '0123456789ABCDEF';
    var color = '#';
    for (var i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

/**
 * Source: https://www.w3schools.com/howto/howto_js_tabs.asp
 * @param evt
 * @param tabId
 */
function openTab(evt, tabId) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(tabId).style.display = "block";
    evt.currentTarget.className += " active";
}

/**
 * Returns timestamp from %d.%m.%Y date string.
 */
function getTimestamp(dateStr) {
    var elements = dateStr.split('-');
    return Date.parse(elements[1] + '/' + elements[0] + '/' + elements[2]);
}

/**
 * Appends "000" suffix to timestamps for flot plotter.
 * @param dates
 */
function appendThousands(dates) {
    for (var i = 0; i < dates.length; i++) {
        dates[i][0] = dates[i][0] * 1000;
    }
}