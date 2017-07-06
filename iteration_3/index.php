<?php
    include "Database.php";
    $db = new Database();
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script type="text/javascript" src="js/flot/jquery.js"></script>
    <script type="text/javascript" src="js/flot/jquery.flot.js"></script>
    <script type="text/javascript" src="js/flot/jquery.flot.time.js"></script>
    <script type="text/javascript" src="js/flot/jquery.flot.resize.min.js"></script>
    <script type="text/javascript" src="js/sigma/sigma.min.js"></script>
    <script type="text/javascript" src="js/sigma/plugins/sigma.layout.forceAtlas2.min.js"></script>
    <script type="text/javascript" src="js/dbs_helper.js"></script>
    <script type="text/javascript" src="js/dbs_task_01_02_cluster_results.js"></script>
</head>
<body>
    <div class="tab">
        <button class="tablinks active" onclick="openTab(event, 'sigma_container_02_02')">Task 02.02</button>
        <button class="tablinks" onclick="openTab(event, 'sigma_container_02_03')">Task 02.03</button>
        <button class="tablinks" onclick="openTab(event, 'sigma_container_02_04')">Task 02.04</button>
        <button class="tablinks" onclick="openTab(event, 'sigma_container_02_05')">Task 02.05</button>
    </div>
    <div id="sigma_container_02_02" class="sigma-container tabcontent"></div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // init sigma and settings
            var s = new sigma("sigma_container_02_02");
            s.settings({
                labelThreshold: 15,
                maxNodeSize: 6,
                defaultNodeColor: "#ec5148"
            });

            // get pairs and combinations
            var hashtag_pairs = JSON.parse('<?php echo $db->getHashtagPairsJson(); ?>');
            var hashtag_combinations = JSON.parse('<?php echo $db->getHashtagCombinationsJson(); ?>');
            var hashtag_node_ids = [];
            var hashtag_edges_ids = [];
            var i;

            // foreach pair, create a new node (if not existent already)
            for (i = 0; i < hashtag_pairs.length; i++) {
                var hashtag_1 = hashtag_pairs[i][0];
                var hashtag_2 = hashtag_pairs[i][1];

                if (hashtag_node_ids.indexOf(hashtag_1) === -1) {
                    hashtag_node_ids.push(hashtag_1);
                    s.graph.addNode({
                        id: hashtag_1,
                        label: hashtag_1,
                        size: 1
                    })
                }

                if (hashtag_node_ids.indexOf(hashtag_2) === -1) {
                    hashtag_node_ids.push(hashtag_2);
                    s.graph.addNode({
                        id: hashtag_2,
                        label: hashtag_2,
                        size: 1
                    });
                }
            }

            // foreach unique node, set x and y position and create edges for each pair combination
            for (i = 0; i < hashtag_node_ids.length; i++) {
                s.graph.nodes(hashtag_node_ids[i]).x = 2 * Math.cos(Math.PI * 2 * i / hashtag_node_ids.length);
                s.graph.nodes(hashtag_node_ids[i]).y = 2 * Math.sin(Math.PI * 2 * i / hashtag_node_ids.length);

                var combinations = hashtag_combinations[hashtag_node_ids[i]];
                for (var j = 0; j < combinations.length; j++) {
                    var edge_id = hashtag_node_ids[i] + '_' + combinations[j];
                    if (hashtag_edges_ids.indexOf(edge_id) === -1) {
                        hashtag_edges_ids.push(edge_id);
                        s.graph.addEdge({
                            id: edge_id,
                            source: hashtag_node_ids[i],
                            target: combinations[j]
                        });
                    }
                }
            }

            s.refresh();
            s.startForceAtlas2();
        });
    </script>
    <div id="sigma_container_02_03" class="sigma-container tabcontent" style="display: none;"></div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // init sigma and settings
            var s = new sigma("sigma_container_02_03");
            s.settings({
                labelThreshold: 15,
                maxNodeSize: 6
            });

            // for each cluster, create node for each element
            for (var i = 0; i < cluster_results.length; i++) {
                var color = getRandomColor();
                var cluster = cluster_results[i];

                var node_id;
                for (var j = 0; j < cluster.length; j++) {
                    node_id = 'node_' + i + '_' + j;
                    s.graph.addNode({
                        id: node_id,
                        label: cluster[j],
                        x: 2 * Math.cos(Math.PI * 2 * j / cluster.length) + i*6,
                        y: 2 * Math.sin(Math.PI * 2 * j / cluster.length),
                        size: 1,
                        color: color
                    });
                    if (j > 0) {
                        s.graph.addEdge({
                            id: 'edge_' + i + '_' + j,
                            source: 'node_' + i + '_' + (j - 1),
                            target: node_id
                        });
                    }
                }

                s.graph.addEdge({
                    id: 'edge_' + i + '_0',
                    source: node_id,
                    target: 'node_' + i + '_0'
                });
            }
            s.refresh();
            s.startForceAtlas2();
        });
    </script>
    <div id="sigma_container_02_04" class="sigma-container tabcontent" style="display: none;"></div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // get hashtag counts
            var hashtag_counts = JSON.parse('<?php echo $db->getHashtagCountsJson(); ?>');
            appendThousands(hashtag_counts);

            var plotData = [{
                data: hashtag_counts,
                bars: {
                    show: true,
                    barWidth: 20 * 60 * 60 * 1000, // 20h in milliseconds
                    align: 'center'
                },
                label: 'Number of Hashtags'
            }];

            var options = {
                xaxis: {
                    mode: 'time',
                    timeformat: '%d.%m.%Y'
                }
            };

            $('#sigma_container_02_04').plot(plotData, options).data('plot');
        });
    </script>
    <div id="sigma_container_02_05" class="sigma-container tabcontent" style="display: none;"></div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var self = this;
            this.container = $('#sigma_container_02_05');
            this.hashtag_times = JSON.parse('<?php echo $db->getHashtagTimesJson(); ?>');

            this.select_div = document.createElement("div");
            this.select_div.innerHTML = "Select Hashtag to filter: ";
            this.select_div.className = "hashtag-select";
            var select_box = document.createElement("select");
            for (var i = 0; i < Object.keys(this.hashtag_times).length; i++) {
                var option = document.createElement("option");
                option.text = Object.keys(this.hashtag_times)[i];
                select_box.add(option);
            }
            select_box.addEventListener("change", function () {
                var hashtag = this.options[this.selectedIndex].value;
                appendThousands(self.hashtag_times[hashtag]);
                var plotData = [{
                    data: self.hashtag_times[hashtag],
                    bars: {
                        show: true,
                        barWidth: 20*60*60*1000, // 20h in milliseconds
                        align: 'center'
                    },
                    label: 'Appearances of: ' + hashtag
                }];

                var options = {
                    xaxis: {
                        mode: 'time',
                        timeformat: '%d.%m.%Y'
                    }
                };
                self.container.plot(plotData, options).data('plot');
                self.container.append(self.select_div);
            });
            this.select_div.appendChild(select_box);
            this.container.append(this.select_div);
        });
    </script>
</body>
</html>
