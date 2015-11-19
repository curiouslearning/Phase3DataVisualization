//Globals
var animationDuration = 1000;

var standardheatmapCounter = 0;

var standardHeatmaps = [];
var standardHeatmapsYearMarker = [];
var continuousHeatmaps = [];


var getStandardHeatmap = function(selector, nextSelector, previousSelector, heatmapNumber)
{
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
            var firstYear = getFirstYear(data);

            // generate year buttons for both types of heatmap based on date range of data
            yearButtons(".yearsStandard", getFirstYear(data), getLastYear(data), heatmapNumber);
            monthButtons(".monthsStandard", heatmapNumber, firstYear);

            //////////////////////////////////////////////////
            /////////////***STANDARD HEATMAP***///////////////
            //////////////////////////////////////////////////
            var heatmap = new CalHeatMap();
                heatmap.init({
                    itemSelector: selector,

                    itemName: "file",
                    domain: "month",
                    domainMargin: [10, 0, 10, 0],
                    domainDynamicDimension: false, // all domains have same dimension (based on biggest)
                    label: { // domainLabel position
                        position: "top",
                        align: "center"
                    },
                    subDomain: "day",
                    //display number of itemNames instead of date inside subDomain
                    subDomainTextFormat: function (date, value) {
                        //Reduce number of digits to save space inside cell
                        return (value > 1000) ? (value / 1000).toFixed(1) + "k" : value;
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
                    nextSelector: nextSelector,
                    previousSelector: previousSelector
                });
            standardHeatmaps[heatmapNumber] = heatmap;
        })
};


var idS = "#standardHeatmap"; // instance of standard heatmap
var heatmap;
//Create the heatmap(s)
for(var i = 0; i < 5; i++)
{
    getStandardHeatmap(idS + i, "#nextSelector" + i, "#previousSelector" + i, i);

}

//Create the buttons
// generate year buttons
function yearButtons(container, firstYear, lastYear, heatmapNumber)
{

    for(var i = firstYear; i <= lastYear + 7; i++){
        $('<div/>', {
            class: "yearButton",
            text: i,
            onClick: "jumpYear('" + heatmapNumber + "', '"+ i + "');",
            //id: id,
        }).appendTo(container + heatmapNumber);
    }

}

// generate month buttons
function monthButtons(container, heatmapNumber, firstYear)
{
    standardHeatmapsYearMarker[heatmapNumber] = firstYear;
    for(var i = 1; i <= 12; i++){
        $('<div/>', {
            class: "monthButton",
            onClick: "jumpMonth('" + heatmapNumber + "', '"+ i + "');",
            text: i,
            //id: id
        }).appendTo(container + heatmapNumber);
    }
}

var idC = "#Continuous1"; // instance of continuous heatmap

// get first year in data range
function getFirstYear(data){
    var firstDateUnix = Object.keys(data)[0];
    var firstDate =  new Date(firstDateUnix*1000);
    var firstYear = firstDate.getFullYear();

    return firstYear;
}

// get last year in data range
function getLastYear(data){
    var lastPos = Object.keys(data).length - 1;
    var lastDateUnix = Object.keys(data)[lastPos];
    var lastDate = new Date(lastDateUnix*1000);
    var lastYear = lastDate.getFullYear();

    return lastYear;
}



// gets values from json data
function getInputValues(data){
    var input = new Array;

    for(var key in data) {
        var value = data[key];
        input.push(value);
    }
    return input;
}

//function parseId(id){
//    var toParse = id;
//    var type = id[1];
//    var number = id.substring(2);
//    //console.log(type);
//    //console.log(number);
//    //console.log("heatmap" + type + "[" + number + "]");
//    return "heatmap" + type + "[" + number + "]";
//}

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

//Jump to a the specified month
function jumpYear(heatmapNumber, jumpYear)
{
    standardHeatmapsYearMarker[heatmapNumber] = jumpYear;
    standardHeatmaps[heatmapNumber].jumpTo(new Date(jumpYear, 0), true);
}

// jumped to clicked month of current year
function jumpMonth(heatmapNumber, month)
{
    standardHeatmaps[heatmapNumber].jumpTo(new Date(standardHeatmapsYearMarker[heatmapNumber], month-1), true);
}


    //parseId(idS);

    //
    //
    //
    //// get data from php page
    //$.get("getprocessedfilecount.php")
    //    .error(function()
    //    {
    //        alert("The request could not be completed.")
    //    })
    //    .success(function( data ) {
    //        // array of values from json data to use when setting legend
    //        var inputValues = getInputValues(data);
    //
    //        // keep track of which year is being viewed
    //        var yearCurrent = getFirstYear(data);
    //
    //        // generate year buttons for both types of heatmap based on date range of data
    //        yearButtons(idS, ".yearsStandard",  getFirstYear(data), getLastYear(data));
    //        yearButtons(idC, ".yearsContinuous", getFirstYear(data), getLastYear(data));
    //
    //        //////////////////////////////////////////////////
    //        /////////////***STANDARD HEATMAP***///////////////
    //        //////////////////////////////////////////////////
    //        heatmapsS[num] = new CalHeatMap();
    //        console.log(heatmapsS[num]);
    //
    //        // draw heatmap
    //        heatmapsS[num].init({
    //            itemSelector: idS,
    //
    //            itemName: "file",
    //            domain: "month",
    //            domainMargin: [10, 0, 10, 0],
    //            domainDynamicDimension: false, // all domains have same dimension (based on biggest)
    //            label: { // domainLabel position
    //                position: "top",
    //                align: "center",
    //            },
    //            subDomain: "day",
    //            //display number of itemNames instead of date inside subDomain
    //            subDomainTextFormat: function(date ,value) {
    //                //Reduce number of digits to save space inside cell
    //                return (value > 1000) ? (value/1000).toFixed(1) + "k" : value;
    //            },
    //            start: new Date(2015, 0, 1),
    //            data: data, // json data from php file
    //            range: 6, // how many domain instances are displayed
    //            animationDuration: animationDuration,
    //            cellSize: 23,
    //            cellRadius: 1,
    //            tooltip: true,
    //            displayLegend: true,
    //            legend: setLegend(inputValues), // customizes legend based on input values of itemNames
    //            legendCellSize: 17,
    //            legendVerticalPosition: "bottom",
    //            legendHorizontalPosition: "center",
    //            legendOrientation: "horizontal",
    //            legendColors: ["#efefef", "steelblue"],
    //            legendCellPadding: 1.2,
    //            legendMargin: [10, 0, 10, 0],
    //
    //            // defines buttons that scroll through cal
    //            nextSelector: "#next",
    //            previousSelector: "#previous",
    //
    //        });
    //
    //        //////////////////////////////////////////////////
    //        /////////////***CONTINUOUS HEATMAP***/////////////
    //        //////////////////////////////////////////////////
    //        var Continuous1 = new CalHeatMap();
    //
    //        //draw heatmap
    //        Continuous1.init({
    //            itemSelector: "#continuousSelector1",
    //
    //            itemName: "file",
    //            domain: "year",
    //            domainMargin: [10, 0, 10, 0],
    //            domainDynamicDimension: false, // all domains have same dimension (based on biggest)
    //            label: { // domainLabel position
    //                position: "top",
    //                align: "right"
    //            },
    //            subDomain: "day",
    //            start: new Date(2015, 0, 1),
    //            data: data, // json data from php file
    //            range: 1, // how many domain instances are displayed
    //            animationDuration: animationDuration,
    //            cellSize: 14,
    //            cellRadius: 1,
    //            tooltip: true,
    //            displayLegend: true,
    //            legend: setLegend(inputValues), // customizes legend based on input values of itemNames
    //            legendCellSize: 12,
    //            legendVerticalPosition: "bottom",
    //            legendHorizontalPosition: "center",
    //            legendOrientation: "horizontal",
    //            legendColors: ["#f4decd", "#ad001d"],
    //            legendCellPadding: 1,
    //            legendMargin: [0, 0, 5, 0],
    //
    //            // defines buttons that scroll through cal
    //            nextSelector: "#n",
    //            previousSelector: "#prev",
    //
    //            onClick: function(date, nb) {
    //                $("#onClick-placeholder").html("<b>" +
    //                    (nb === null ? "unknown" : nb)+ "</b> files"
    //                );
    //            }
    //
    //        });


            //////////////////////////////////////////////////////////
            //////////////////***HELPER FUNCTIONS***//////////////////
            //////////////////////////////////////////////////////////



            //// jump to clicked year
            //$(".yearButton").on("click", function(event) {
            //
            //    var heatmapToJump = parseId($(this).attr("id"));
            //    console.log(heatmapToJump);
            //    heatmapsS[num].jumpTo(new Date($(this).text(), 0), true);
            //    // heatmapToJump.jumpTo(new Date($(this).text(), 0), true); // doesn't work bc ___.jumpTo needs to be exactly same as name of cal
            //
            //    yearCurrent = $(this).text();
            //});

            // jumped to clicked month of current year
        //    $(".monthButton").on("click", function(event) {
        //        heatmapsS[num].jumpTo(new Date(yearCurrent, $(this).text() - 1), true);
        //    });
        //});



