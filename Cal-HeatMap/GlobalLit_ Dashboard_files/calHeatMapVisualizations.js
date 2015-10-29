        //Globals
        var animationDuration = 1000;

        // get data from php page
        $.get("getprocessedfilecount.php", {startDate: Date.now(), endDate: "10-30-15"})
        .error(function()
        {
               //Alert user that the request couldn't be completed
        })
        .success(function( data ) {
            // array of values from json data to use when setting legend
            var inputValues = getInputValues(data);

            //////////////////////////////////////////////////
            /////////////***STANDARD HEATMAP***///////////////
            //////////////////////////////////////////////////
            var standardHeatMap = new CalHeatMap();

            // draw heatmap
            standardHeatMap.init({
                itemSelector: "#heatmap_standard",

                itemName: "file",
                domain: "month",
                domainMargin: [10, 5, 10, 5],
                domainDynamicDimension: false, // all domains have same dimension (based on biggest)
                label: { // domainLabel position
                    position: "top",
                    align: "right",
                },
                subDomain: "day",
                //display number of itemNames instead of date inside subDomain
                subDomainTextFormat: function(date ,value) {
                    //Reduce number of digits to save space inside cell
                    return (value > 1000) ? (value/1000).toFixed(1) + "k" : value;
                },
                start: new Date(2015, 0, 1),
                data: data, // json data from php file
                range: 5, // how many domain instances are displayed
                animationDuration: animationDuration,
                cellSize: 24,
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
                legendMargin: [10, 0, 10, 50],

                // defines buttons that move through cal 
                nextSelector: "#next",
                previousSelector: "#previous",

            });
    
            //////////////////////////////////////////////////
            /////////////***CONTINUOUS HEATMAP***/////////////
            //////////////////////////////////////////////////
            var continuousCalHeatMap = new CalHeatMap();

            //draw heatmap
                continuousCalHeatMap.init({
                itemSelector: "#heatmap_continuous",

                itemName: "file",
                domain: "year",
                domainMargin: [10, 0, 10, 0],
                domainDynamicDimension: false, // all domains have same dimension (based on biggest)
                label: { // domainLabel position
                    position: "top",
                    align: "right"
                },
                subDomain: "day",
                //subDomainTextFormat: "%d",
                start: new Date(2015, 0, 1),
                data: data, // json data from php file
                range: 1, // how many domain instances are displayed
                animationDuration: animationDuration,
                cellSize: 12.8,
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
        });