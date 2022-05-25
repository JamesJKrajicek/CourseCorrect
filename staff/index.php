<!DOCTYPE html>
<?php
	require_once "../common.php";
	require_staff();
?>
<html lang="en">
<head>
    <title>Staff - CourseCorrect</title>
    <meta charset="utf-8">
	<link rel="icon" href="../favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../libs/bootstrap.min.css">
	<script src="../libs/jquery.slim.min.js"></script>
	<script src="../libs/popper.min.js"></script>
	<script src="../libs/bootstrap.min.js"></script>
	<link rel="stylesheet" href="../common.css">
	<link rel="stylesheet" href="../libs/fontawesome.min.css">
	<script>
		function validateSearch(e){
			//e.preventDefault(); //TODO TEST VALUE. REMOVE BEFORE PULL REQ.
			//Purpose: Parse and Validate the user provided Student ID list and Search Term List and provide a graceful response to invalid entries.
			id_result_bool = idParsingAndValidation();
			search_term_result_bool = searchTermParsingandValidation();
			return (id_result_bool && search_term_result_bool);
		}
		function idParsingAndValidation (){
			id_list = document.getElementById("stu_id_list");
			id_list_rawcontents = id_list.value;
			if (id_list_rawcontents == "")
			{
				return false; //If the id list is empty return false and prevent the form from firing.
			}
			id_list_preprocessed = id_list_rawcontents.replace(/\s/g, '').replace(/,{2,}/g,',').replace(/^,|,$/g,''); // Remove whitespace, remove excess commas, and remove heading and trailing commas.
			id_list.value = id_list_preprocessed; //Overwrite raw input in textarea with pre-processed id list.
			id_list_arr = id_list.value.split(',');// Create an array from the pre-processed string.
			const kuid_regex = /^\d{7}$/;
			const invalid_ids_arr = []; //Create an array to store invalid ids
			invalid_id_detected = false; //Boolean value used to prevent the search from executing if invalid ids are found.
			for (i=0; i<id_list_arr.length; i++){
				if (kuid_regex.test(id_list_arr[i])){
					//Do Nothing
				}
				else{ //Add the invalid id to the invalid id array and set the flag.
					invalid_ids_arr.push(id_list_arr[i]);
					invalid_id_detected = true;
				}
			}
			if (invalid_id_detected){ //Check the invalid id flag
				id_list.classList.add("invalid");
				error_text = document.getElementById("id_error");
				error_text.hidden=false;
				error_text.innerText = "The following IDs are of an invalid type: "+invalid_ids_arr.join(", ");
				id_list.addEventListener("click", function handler() {document.getElementById("stu_id_list").classList.remove("invalid"); this.removeEventListener("click", handler);});
				return false;
			}
			document.getElementById("id_error").hidden=true; //If the input is valid, but previous attempts were not, we remove the error text so if later the user uses the back button he/she doesn't see the error text for the old input.
			return true;
		}
		function searchTermParsingandValidation(){
			search_list = document.getElementById("search_term_list");
			search_list_rawcontents = search_list.value;
			if (search_list_rawcontents != ""){
				search_list_preprocessed = search_list_rawcontents.replace(/^\s+|\s+$/g,'')//Remove leading and trailing whitespace. //.replace(/\s{2,}/g, ' ').replace(/^\s|\s$/g,''); //Remove Duplicate spaces including excess spaces between words as well as heading and trailing whitespace.
				search_list.value = search_list_preprocessed; //Overwrite raw input in textarea with pre-processed search term list.
				const temp_search_list_arr = search_list.value.split(' '); //Create an array of search terms by spliting on the spaces.
				const sorted_search_list_arr = [];
				const quotes_watcher = {isSet:false, startIndex:0};
				start_quote_regex = /^"/;
				end_quote_regex = /"$/;
				for (i=0;i<temp_search_list_arr.length;i++){
					if (!quotes_watcher.isSet){//Is the quotes watcher set?
						if(start_quote_regex.test(temp_search_list_arr[i])){ //If not is there a starting quote present in the entry under evaluation?
							temp_search_list_arr[i] = temp_search_list_arr[i].substr(1);//If it does, trim out the starting quotation mark.
							if (end_quote_regex.test(temp_search_list_arr[i])){//Is this entry a single word surrounded by quotes?
								temp_search_list_arr[i] = temp_search_list_arr[i].slice(0,temp_search_list_arr[i].length-1);//If it is, trim the ending quotation mark...
								sorted_search_list_arr.push(temp_search_list_arr[i]);// ...and push the result to the sorted list.
							}
							else{// If it isn't a single word surround by quotes then set the quote watcher to true and store the starting index.
								quotes_watcher.isSet = true;
								quotes_watcher.startIndex = i;
							}
						}
						else{
							if (end_quote_regex.test(temp_search_list_arr[i])){ //Fault if an end quote is present without a starting quote.
								return searchErrorSet("Mismatched end quote detected!");
							}
							sorted_search_list_arr.push(temp_search_list_arr[i]);//If there are no quotes open and there's no starting quote in the entry then push it to the sorted array.
						}
					}
					else{//If the quotes watcher is set.
						if (start_quote_regex.test(temp_search_list_arr[i])) {return searchErrorSet("Unterminated quote string detected!");}
						if (end_quote_regex.test(temp_search_list_arr[i])){//If there is an ending quote present in the entry under evaluation.
							temp_search_list_arr[i] = temp_search_list_arr[i].slice(0,temp_search_list_arr[i].length-1);//Remove the ending quote.
							sorted_search_list_arr.push(temp_search_list_arr.slice(quotes_watcher.startIndex,i+1).join(' ')); //Slice the array elements from the quote watchers starting index to the present one, join them into a string, and push the result onto the sorted array.
							quotes_watcher.isSet = false; //Reset the quotes watcher.
						}//If the quotes watcher is set but no ending quote was found, keep iterating.
					}
				}
				if (quotes_watcher.isSet){	//Fault if for loop completes and the quotes watcher is still set (we have mismatched quotations marks).
					return searchErrorSet("Unterminated quote string detected!");
				}
				document.getElementById("passed_search_term_list").value = sorted_search_list_arr.join(',');//Take the sorted list and store it in the hidden input object (this prevents the overwritting of the original, unformatted but verified, user input).
				document.getElementById("search_error").hidden=true; //If the input is valid, but previous attempts were not, we remove the error text so if later the user uses the back button he/she doesn't see the error text for the old input.
			}
			//return false; //TEST VALUE, COMMENT OUT BEFORE PUSHING. REMOVE BEFORE PULL REQ.
			return true; //UNCOMMENT BEFORE PUSHING.
		}
		function searchErrorSet(str_to_print){
			search_list = document.getElementById("search_term_list");
			search_list.classList.add("invalid");
			error_text = document.getElementById("search_error");
			error_text.hidden=false;
			error_text.innerText = "Syntax Error: "+str_to_print;
			search_list.addEventListener("click", function handler() {document.getElementById("search_term_list").classList.remove("invalid"); this.removeEventListener("click", handler);});
			return false;
		}
	</script>
</head>
<body>
	<?php display_navbar(true); ?>
    <div class="container">
		<div class="row">
			<div class="col-12">
				<h1>CourseCorrect Staff</h1>
			</div>
			<div class="col-lg-8 col-xl-9">
				<div class="card mb-3">
					<div class="card-body">
						<h2>Lookup student plans</h2>
						<form onsubmit="return validateSearch(event)" method="GET" action="search.php">
							<div class="form-group">
								<label>Student IDs</label>
								<textarea id="stu_id_list" name="stu_id_list" class="form-control" placeholder="3011111, 3022222, 3033333, ..."></textarea>
								<p hidden id="id_error" class="text-danger font-italic"></p>
							</div>
							<div class="form-group">
								<label>Filter plans by name (optional)</label>
								<input id="search_term_list" type="text" class="form-control" placeholder="&quot;EECS 101&quot; SP2022 ...">
								<input id="passed_search_term_list" name="passed_search_term_list" type="hidden">
								<p hidden id="search_error" class="text-danger font-italic"></p>
							</div>
							<button type="submit" class="btn btn-success"><i class="fas fa-search"></i> Search</button>
						</form>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-xl-3">
				<div class="card mb-3">
					<div class="card-body">
						<h2>Create plans</h2>
						<label>The student homepage</label><br>
						<a href="../list"><input type="button" class="btn btn-primary" value="Plan list"></a>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="card mb-3">
					<div class="card-body">
						<h2>Administration</h2>
						<a href="edit-users.php"><button type="button" class="btn btn-danger"><i class="fas fa-user"></i> Edit staff users</button></a>
						<a href="edit-degrees.php"><button type="button" class="btn btn-danger"><i class="fas fa-graduation-cap"></i> Edit degrees</button></a>
						<a href="edit-courses/edit-courses.php"><button type="button" class="btn btn-danger"><i class="fas fa-book"></i> Edit courses</button></a>
						<a href="edit-help.php"><button type="button" class="btn btn-danger"><i class="fas fa-question"></i> Edit text and links</button></a>
						<a href="view-errors.php"><button type="button" class="btn btn-primary"><i class="fas fa-exclamation"></i> View error logs</button></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php display_footer(); ?>
</body>
</html>