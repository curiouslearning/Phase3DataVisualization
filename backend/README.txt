Phase3DataVisualization README
By Jason Krone for Curious Learning

Purpose:
The visualization_endpoint pulls tablet data from the database to be used in visualizations,
and returns that data in the form of a json. This json contains key value pairs, which correspond
to the start_date in seconds since epoch for a specific tablet and the data value for that tablet.  

ex: {'784848482227': 12, '7848484823443': 90}


- How do I query the Visualization Endpoint? 

To query the visualization endpoint specify the type of data you would like to 
recieve as a parameter in your GET request with the value 'true'.

For example if the data type you wanted to recieve was number_of_files your GET request
would be: {'number_of_files', 'true'}


- What are the different data types I can request?

	1. 'number_of_files'
	2. 'number_of_probes'
	3. 'server_data_clean'
	4. 'server_data_uploaded'
	5. 'file_data_clean'
	6. 'file_data_uploaded'


- What types of queries can I ask?

There are four main types of queries you can ask:

	- Standard Query:
	With the standard query you must include the data type you would like to recieve
        as a parameter with the value true.  
 
	You can also include optional arguments in your GET request.  The following are 
	optional arguments are supported with the standard query:

	1. 'start_date' : Returned json will only include data from tablets with the given
	    start_date. The value under the parameter start_date must either be of the 
            form 'Y-m-d' or 'Y-m-d H:m:s'. 

	2. 'end_date' : Returned json will only include data from tablets with the given end_date.
	    The value under this parameter will be of the same form as 'start_date'.

	3. 'tablet_id' : Returned json will only include data from the tablet with the given
	    tablet_id. tablet_id's are positive integers. 

	4. 'max_days_before_now' : Returned json will only include data from tablets with
	    a DEFAULT_DATE_RANGE_FIELD between the current date and the date max_days_before_now
            ago. 

	    ex: {'server_data_clean' : 'true', 'max_days_before_now' : 5} will return
	    the value for server_data_clean for each tablet that has a DEFAULT_DATE_RANGE_FIELD
            that is between now and 5 days ago.  

	Defaults for Standard Query: 
	 	- The DEFAULT_DATE_RANGE_FIELD is currently the created_on field in the database.  
		- If no start_date or end_date is given the returned json will only return 
   		  data that has a date within the current year under the DEFAULT_DATE_RANGE_FIELD. 



	- All Data Query: 
	The all data query returns a json containing values for the given data type for all tablets.
	This type of query does not have a default time range, so you get all the data available 
	under the given field. 

	ex: {'all_data' : 'true', 'file_data_clean' : 'true'} will return a json containing
	the value under the file_data_clean field for every tablet in the database. 



	- Active Deployments Query:
	The json returned will only include data from tablets that are part of active deployments.

	ex: {'all_active_deployments' : 'true', 'file_data_uploaded' : 'true' } will return the
	value for file_data_uploaded for every tablet that is part of an active deployment.

	

	- Single Deployment Query:
	The json returned will only include data from tablets that are part of the deployment
	with the given deployment_id.

	ex: {'deployment_id' : 5, 'server_data_clean' : 'true'} will return a json containing
	clean server data values for every tablet that is part of the deployment with deployment
	id 5. 



What happens if my query returns no results?

If your query returns no results, I call die(error_message) in the back end script.
And no json is returned. 


What testing is in place for the backend script?

There is a testing script called vis_endpoint_tests, which tests the 
four types of queries, as well as the key helper functions that
help to form those queries. 


Technical Notes:
- The visualization endpoint script caches results to queries until midnight
  on the current day using APC.  Please make sure that APC running when you
  query the endpoint. If you are using mamp for testing purposes, APC is built 
  into mamp and you can select APC as the cache to use in the mamp interface
  under the php tab. 


If you have any other questions feel free to email me at jasonkrone @ me.com
