//Globals
var animationDuration = 1000;

var standardheatmapCounter = 0;

var standardHeatmaps = [];
var standardHeatmapsYearMarker = [];
var continuousHeatmaps = [];


var getStandardHeatmap = function(selector, nextSelector, previousSelector, heatmapNumber, deployment_id)
{
    var payload = {'number_of_files' : 'true', 'deployment_id' : deployment_id};
    $.get("../../backend/visualization_endpoint.php", payload)
        .error(function()
        {
            alert("The request could not be completed.")
        })
        .success(function( data ) {
            // array of values from json data to use when setting legend
            var inputValues = getInputValues(data);

            // keep track of which year is being viewed
            var firstYear = getFirstYear(data);

            // generate year buttons for heatmap based on date range of data
            yearButtons(".yearsStandard", getFirstYear(data), getLastYear(data), heatmapNumber, selector);

            // generates month buttons for standard heatmap
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


var getContinuousHeatmap = function(selector, nextSelector, previousSelector, heatmapNumber, deployment_id)
{
    var payload = {'number_of_probes' : 'true', 'deployment_id' : deployment_id};
    $.get("../../backend/visualization_endpoint.php", payload)
        .error(function()
        {
            alert("The request could not be completed.")
        })
        .success(function( data ) {
            // array of values from json data to use when setting legend
            var inputValues = getInputValues(data);

            // keep track of which year is being viewed
            var firstYear = getFirstYear(data);

            // generate year buttons for heatmap based on date range of data
            yearButtons(".yearsContinuous", getFirstYear(data), getLastYear(data), heatmapNumber, selector);

            //////////////////////////////////////////////////
            /////////////***CONTINUOUS HEATMAP***/////////////
            //////////////////////////////////////////////////
            var heatmap = new CalHeatMap();

            //draw heatmap
            heatmap.init({
                itemSelector: selector,

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
                nextSelector: nextSelector,
                previousSelector: previousSelector,

                onClick: function(date, nb) {
                    $("#onClick-placeholder").html("<b>" +
                        (nb === null ? "unknown" : nb)+ "</b> files"
                    );
                }
            });
            continuousHeatmaps[heatmapNumber] = heatmap;
        })
};

var payload = {'deployment_ids' : 'true'};
$.get( "../../backend/deployments_for_user.php", payload)
    .error(function() {
        alert("Deployments for this user could not be loaded.")
    })
    .success(function( ids ) {
        console.log('ids', ids, 'length', ids.length);
        var length = ids.length;
        var idS = "#standardHeatmap";
        var idC = "#continuousHeatmap";
        var deployment_id = null;
        for (var i = 0; i < length; i++) {
            deployment_id = ids[i];
            getStandardHeatmap(idS + i, "#standardNextSelector" + i, "#standardPreviousSelector" + i, i, deployment_id);
            getContinuousHeatmap(idC + i, "#continuousNextSelector" + i, "#continuousPreviousSelector" + i, i, deployment_id);
        }
    });


//Create the buttons
// generate year buttons
function yearButtons(container, firstYear, lastYear, heatmapNumber, id)
{
    for(var i = firstYear; i <= lastYear; i++){
        $('<div/>', {
            class: "yearButton",
            text: i,
            onClick: "jumpYear('" + heatmapNumber + "', '"+ i + "', '"+ id + "');",
            id: id
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
            text: i
        }).appendTo(container + heatmapNumber);
    }
}

// jump to specified year on click
function jumpYear(heatmapNumber, jumpYear, id)
{
    if(id.indexOf("standard") != -1){ // check whether to move standard or continuous heatmap
        standardHeatmapsYearMarker[heatmapNumber] = jumpYear;
        standardHeatmaps[heatmapNumber].jumpTo(new Date(jumpYear, 0), true);
    }

    else{
        continuousHeatmaps[heatmapNumber].jumpTo(new Date(jumpYear, 0), true);
    }
}

// jump to specified month on click
function jumpMonth(heatmapNumber, month)
{
    standardHeatmaps[heatmapNumber].jumpTo(new Date(standardHeatmapsYearMarker[heatmapNumber], month-1), true);
}

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


// allow tooltips to overflow heatmap container to ensure readability
$(window).load(function (){
    var tooltip = document.getElementsByClassName("ch-tooltip");
    var insert = document.getElementsByClassName("container-fluid");
    var parents = [];
    var divs = [];

    for(var i = 0; i < insert.length; i++){
        divs[i] = document.createElement("div");
        divs[i].className = "tooltip_container";
        parents[i] = insert[i].parentNode;
        parents[i].insertBefore(divs[i], insert[i]);
        divs[i].appendChild(tooltip[i]);
    }
});

