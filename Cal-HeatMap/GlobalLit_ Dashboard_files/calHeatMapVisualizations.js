    //Globals
    var animationDuration = 1000;

        // get data from php page
        $.get("getprocessedfilecount.php")
        .error(function()
        {
               alert("The request could not be completed.")
        })
        .success(function( data ) {
            // array of values from json data to use when setting legend
            var inputValues = getInputValues(data);

            // keep track of which year is being viewed
            var yearCurrent = getFirstYear(data);

            // generate year buttons for both types of heatmap based on date range of data
            yearButtons(getFirstYear(data), getLastYear(data));

            // generate month buttons for standard heatmap
            monthButtons();

            //////////////////////////////////////////////////
            /////////////***STANDARD HEATMAP***///////////////
            //////////////////////////////////////////////////
            heatmapS = new CalHeatMap();
           // console.log(heatmapsS[num]);

            // draw heatmap
            heatmapS.init({
                itemSelector: "#standard_heatmap",

                itemName: "file",
                domain: "month",
                domainMargin: [10, 0, 10, 0],
                domainDynamicDimension: false, // all domains have same dimension (based on biggest)
                label: { // domainLabel position
                    position: "top",
                    align: "center",
                },
                subDomain: "day",
                //display number of itemNames instead of date inside subDomain
                subDomainTextFormat: function(date ,value) {
                    //Reduce number of digits to save space inside cell
                    return (value > 1000) ? (value/1000).toFixed(1) + "k" : value;
                },
                start: new Date(2015, 0, 1),
                data: data, // json data from php file
                range: 6, // how many domain instances are displayed
                animationDuration: animationDuration,
                cellSize: 23,
                cellRadius: 1,
                tooltip: true,
                displayLegend: true,
                legend: setLegend(inputValues), // customizes legend based on input values of itemNames
                legendCellSize: 17,
                legendVerticalPosition: "bottom",
                legendHorizontalPosition: "center",
                legendOrientation: "horizontal",
                legendColors: ["#efefef", "steelblue"],
                legendCellPadding: 1.2,
                legendMargin: [10, 0, 10, 0],

                // defines buttons that scroll through cal 
                nextSelector: "#next",
                previousSelector: "#previous",

            });
    
            //////////////////////////////////////////////////
            /////////////***CONTINUOUS HEATMAP***/////////////
            //////////////////////////////////////////////////
            var heatmapC = new CalHeatMap();

            //draw heatmap
            heatmapC.init({
                itemSelector: "#continuous_heatmap",

                itemName: "file",
                domain: "year",
                domainMargin: [10, 0, 10, 0],
                domainDynamicDimension: false, // all domains have same dimension (based on biggest)
                label: { // domainLabel position
                    position: "top",
                    align: "right"
                },
                subDomain: "day",
                start: new Date(2015, 0, 1),
                data: data, // json data from php file
                range: 1, // how many domain instances are displayed
                animationDuration: animationDuration,
                cellSize: 14,
                cellRadius: 1,
                tooltip: true,
                displayLegend: true,
                legend: setLegend(inputValues), // customizes legend based on input values of itemNames
                legendCellSize: 12,
                legendVerticalPosition: "bottom",
                legendHorizontalPosition: "center",
                legendOrientation: "horizontal",
                legendColors: ["#f4decd", "#ad001d"],
                legendCellPadding: 1,
                legendMargin: [0, 0, 5, 0],

                // defines buttons that scroll through cal
                nextSelector: "#n",
                previousSelector: "#prev",

                onClick: function(date, nb) {
                    $("#onClick-placeholder").html("<b>" +
                        (nb === null ? "unknown" : nb)+ "</b> files"
                    );
                }

            });

        //////////////////////////////////////////////////////////
        //////////////////***HELPER FUNCTIONS***//////////////////
        //////////////////////////////////////////////////////////

            // gets values from json data
            function getInputValues(object){
                var input = new Array;

                for(var key in data) {
                    var value = data[key];
                    input.push(value);
                }
                return input;
            }

            // returns an array of legend values based on average input
            function setLegend(input){
                var sum = 0;
                var average = 0;
                var steps = 5;
                var legendValues = new Array();

                // find average of input
                avg(input);
                    function avg(numList){
                        for(var i = 0; i < numList.length; i++){
                            sum += numList[i];
                        }
                        average = Math.floor(sum/numList.length);
                    }

                // find how much legend steps up each time
                var stepValue = Math.floor(average/steps);

                // legend steps up by stepValue step*2 times
                legendStep(stepValue);
                    function legendStep(step){
                        for(var i = 0; i <= steps*2; i++){
                            legendValues[i] = (step*i)+step; // to start at stepValue instead of 0
                        }
                    }

                return legendValues;
            }

            // get first year in data range
            function getFirstYear(object){
                var firstDateUnix = Object.keys(data)[0];
                var firstDate =  new Date(firstDateUnix*1000);
                var firstYear = firstDate.getFullYear();

                return firstYear;
            }

            // get last year in data range
            function getLastYear(object){
                var lastPos = Object.keys(data).length - 1;
                var lastDateUnix = Object.keys(data)[lastPos];
                var lastDate = new Date(lastDateUnix*1000);
                var lastYear = lastDate.getFullYear();

                return lastYear;
            }

            // generate year buttons
            function yearButtons(firstYear, lastYear){
                for(var i = firstYear; i <= lastYear + 7; i++){
                    $('<button/>', {
                        class: "yearButton",
                        text: i,
                    })
                    .appendTo(".years");

                }
                changeYear();
            }
    
            // generate month buttons
            function monthButtons(){
                for(var i = 1; i <= 12; i++){
                    $('<button/>', {
                        class: "monthButton",
                        text: i,
                    }).appendTo('#months');
                }
                changeMonth();
            }

            // jump to clicked year
            function changeYear(){
                $(".yearButton").on("click", function(event) {
                    if($(this).parent().parent().parent().attr("id") == "standard"){ // but this is not modular...
                        heatmapS.jumpTo(new Date($(this).text(), 0), true);
                    }
                    else{
                        heatmapC.jumpTo(new Date($(this).text(), 0), true);
                    }
                    yearCurrent = $(this).text();
                });
            }

            // jumped to clicked month of current year
            function changeMonth() {
                $(".monthButton").on("click", function(event) {
                    heatmapS.jumpTo(new Date(yearCurrent, $(this).text() - 1), true);
                });
            }
        });

         /*console.log($(".r1").parent().parent());
            $(".r1").tipsy();*/
        

         /* //gives each heatmap unique id
            function createMapId(num){
                var newId;
                if(type == "standard"){
                    newId = "#s";
                }
                if(type == "continuous"){
                    newId = "#s";
                }
                newId = newId + num;
                return newId;
            }*/

           /* function parseId(id){
                var toParse = id;
                var type = id[1];
                var number = id.substring(2);
                //console.log(type);
                //console.log(number);
                //console.log("heatmap" + type + "[" + number + "]");
                return "heatmap" + type + "[" + number + "]";
            }

            parseId(idS);*/

       /*  // date range picker calendar
        $(function() {
            $('input[name="dateRange"]').daterangepicker({
                "startDate": Date.now(),
                "endDate": "figure this out",
            });
        });

        // get new start and end date from input
        $( "dateRange" ).change(function() {
            var range = $( this ).val();
            var dates = range.split(' - ');
            var start = dates[0];
            var end = dates[1];
            console.log(range);
            console.log(end);
        });*/