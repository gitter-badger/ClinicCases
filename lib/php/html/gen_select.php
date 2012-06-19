<?php
//Generate html for selects using db values

function genSelect($target,$chosen_array,$select_name){

echo "<select name=\"$select_name\" id=\"$select_name\">";

/* arrays of the possible choices in all selects */
$gender = array('M','F','U');
$race =  array('AA','H','W','O','U');
$pl_or_def = array('Plaintiff','Defendant','Other');
$per = array('Year','Month','Week');



	switch($chosen_array){
	case "gender":
	$array = $gender;break;
	case "race":
	$array = $race;break;
	case "pl_or_def":
	$array = $pl_or_def;break;
	case "per":
	$array = $per;break;
	}

		foreach ($array as $v)
			{
			if ($v == $target)
			{echo "<option value = \"$v\" selected=\"selected\">$v</option>";}
			else {echo "<option value=\"$v\">$v</option>";}
			}

echo "</select>";
}


function genStateSelect($target,$select_name)
{
	$state = array('AL'=>"Alabama",
			'AK'=>"Alaska",
			'AZ'=>"Arizona",
			'AR'=>"Arkansas",
			'CA'=>"California",
			'CO'=>"Colorado",
			'CT'=>"Connecticut",
			'DE'=>"Delaware",
			'DC'=>"District Of Columbia",
			'FL'=>"Florida",
			'GA'=>"Georgia",
			'HI'=>"Hawaii",
			'ID'=>"Idaho",
			'IL'=>"Illinois",
			'IN'=>"Indiana",
			'IA'=>"Iowa",
			'KS'=>"Kansas",
			'KY'=>"Kentucky",
			'LA'=>"Louisiana",
			'ME'=>"Maine",
			'MD'=>"Maryland",
			'MA'=>"Massachusetts",
			'MI'=>"Michigan",
			'MN'=>"Minnesota",
			'MS'=>"Mississippi",
			'MO'=>"Missouri",
			'MT'=>"Montana",
			'NE'=>"Nebraska",
			'NV'=>"Nevada",
			'NH'=>"New Hampshire",
			'NJ'=>"New Jersey",
			'NM'=>"New Mexico",
			'NY'=>"New York",
			'NC'=>"North Carolina",
			'ND'=>"North Dakota",
			'OH'=>"Ohio",
			'OK'=>"Oklahoma",
			'OR'=>"Oregon",
			'PA'=>"Pennsylvania",
			'RI'=>"Rhode Island",
			'SC'=>"South Carolina",
			'SD'=>"South Dakota",
			'TN'=>"Tennessee",
			'TX'=>"Texas",
			'UT'=>"Utah",
			'VT'=>"Vermont",
			'VA'=>"Virginia",
			'WA'=>"Washington",
			'WV'=>"West Virginia",
			'WI'=>"Wisconsin",
			'WY'=>"Wyoming");

	echo "<select name=\"$select_name\" id=\"$select_name\">";

	foreach ($state as $key => $v)
			{
			if ($key == $target)
			{echo "<option value = \"$key\" selected=\"selected\">$v</option>";}
			else {echo "<option value=\"$key\">$v</option>";}
			}

echo "</select>";


	}


function generate_time_selector()

{
	if (CC_TIME_UNIT == '5')
	{$minutes = array('0','5','10','15','20','25','30','35','40','45','50','55');}
	else
	{$minutes = array('0','6','12','18','24','30','36','42','48','54');}

	$selects = "<label for 'cn_h'>Hours:</label><select name='csenote_hours' id='cn_h'>";

	for($i = 0; $i <= 8; $i++)
	{$selects .= "<option value='$i'>" . $i . "</option>";}

	$selects .= "</select>";

	$selects .= "<label for 'cn_m'>Minutes: </label><select name='csenote_minutes' id='cn_m'>";

	foreach ($minutes as $val)

	{
		$selects .= "<option value='$val'>$val</option>";
	}

	$selects .= "</select>";

	return $selects;
}



//Generates all open and active cases the user is on for use in a html select
function generate_active_cases_select($dbh,$user)
{

	if ($_SESSION['permissions']['view_all_cases'] == '1')
		{$sql = "SELECT *,cm.id as case_id_val FROM cm WHERE date_close = '' ORDER BY last_name ASC";}
	else
		{$sql = "SELECT *,cm.id as case_id_val
		FROM cm_case_assignees,cm
		WHERE  cm_case_assignees.case_id = cm.id
		AND cm_case_assignees.username = '$user'
		AND cm_case_assignees.status = 'active'
		AND cm.date_close = ''
		ORDER BY cm.last_name ASC";}

	$q = $dbh->prepare($sql);

	$q->execute();

	$cases = $q->fetchAll(PDO::FETCH_ASSOC);

	$options = null;

	foreach ($cases as $case) {

		if (!$case['first_name'] AND !$case['last_name'])
			{$casename = $case['organization'];}
		else
			{$casename = $case['first_name'] . " " . $case['last_name'];}

		//Note: trim for very long case names

		$options .= "<option value='" . $case['case_id_val'] . "'>" . $casename . " </option>";
	}

	return $options;

}

//Generate users on a case
function users_on_case_select($dbh,$case_id)
{
	$get_users = $dbh->prepare("SELECT * FROM cm_case_assignees WHERE case_id = '$case_id'
		AND status = 'active'");

	$get_users->execute();

	$users = $get_users->fetchAll(PDO::FETCH_ASSOC);

	$options = null;

	foreach ($users as $user) {

		$get_name = username_to_fullname($dbh,$user['username']);

		if ($user['username'] == $_SESSION['login'])
			{$options .= "<option selected=selected value = '" . $user['username']  ."'>You</option>";}
		else
			{$options .= "<option value = '" . $user['username']  ."'>" . $get_name   . "</option>";}

	}

	return $options;
}

//Generate a  select of all active users

function all_active_users($dbh)
{
	$q = $dbh->prepare("SELECT * FROM cm_users WHERE status = 'active' ORDER BY last_name ASC");

	$q->execute();

	$users = $q->fetchAll(PDO::FETCH_ASSOC);

	$options = null;

	foreach ($users as $user) {

		$options .= "<option value = '" . $user['username']  . "'>" . $user['first_name'] . " " . $user['last_name'] . "</option>";
	}

	return $options;
}

//Generate a list of all active users and all groups.  Used in messages.
function all_active_users_and_groups($dbh,$case_num)
{
	$options = null;

	//If case, add ability to send to all on the case
	if ($case_num)
	{
		$q = $dbh->prepare("SELECT * FROM cm_case_assignees WHERE `case_id` = '$case_num' AND `status` = 'active'");

		$q->execute();

		$count = $q->rowCount();

		$options .= "<option value='_all_on_case_'>All Users on this Case ($count)</option>";


	}

	//Determine total number of active users
	$q = $dbh->prepare("SELECT * FROM `cm_users` WHERE `status` = 'active'");

	$q->execute();

	$count = $q->rowCount();

	$options .= "<option value='_all_users_'>All Users ($count)</option>";

	//First get all groups defined in cm_groups config
	$q = $dbh->prepare("SELECT group_name, group_title FROM cm_groups ORDER BY group_title ASC");

	$q->execute();

	$groups = $q->fetchAll(PDO::FETCH_ASSOC);

	foreach ($groups as $group) {
		$options .= "<option value='_grp_" . $group['group_name'] . "'>Group: All " . $group['group_title'] . "</option>";
	}

	//Then get every supervisor
	$q = $dbh->prepare("SELECT cm_groups.group_name, cm_groups.supervises, cm_users.grp, cm_users.username
		FROM cm_groups, cm_users
		WHERE cm_groups.supervises =  '1'
		AND cm_users.grp = cm_groups.group_name
		AND cm_users.status =  'active'
		ORDER BY cm_users.username ASC");

	$q->execute();

	$groups = $q->fetchAll(PDO::FETCH_ASSOC);

	foreach ($groups as $group) {
		$options .= "<option value = '_spv_" . $group['username'] . "'>Group: " . username_to_fullname($dbh,$group['username']) . "'s group</option>";
	}

	//Then just get individual users
	$q = $dbh->prepare("SELECT * FROM cm_users WHERE status = 'active' ORDER BY last_name ASC");

	$q->execute();

	$users = $q->fetchAll(PDO::FETCH_ASSOC);

	foreach ($users as $user) {

		$options .= "<option value = '" . $user['username']  . "'>" . $user['first_name'] . " " . $user['last_name'] . "</option>";
	}

	return $options;

}

//Used in user_detail.php
function status_select($status)
{
	$choices = array('active' => 'Active','inactive' => 'Invactive');

	$options = null;

	foreach($choices as $key=>$value){

		if ($key == $status)
			{$selected = "selected=selected";}
		else
			{$selected = "";}

		$options .= "<option value= '$key' $selected>$value</option>";
	}

	return $options;

}

//Also used in user_detail.php
//$supervisor is a string
//$supervisor_name_data is an array
function supervisors_select($supervisors,$supervisor_name_data)
{
	$options = null;
	$sups = explode(',', $supervisors);
	foreach ($supervisor_name_data as $key => $value)
	{
		if (in_array($value, $sups))
		{
			$options .= "<option value='$value' selected=selected>$key</option>";
		}
		else
		{
			$options .= "<option value='$value'>$key</option>";
		}

	}

	return $options;
}

//also used in Users.php.  Get all groups
function group_select($dbh,$val)
{
	$q = $dbh->prepare("SELECT DISTINCT `group_name`, `group_title`  FROM `cm_groups`");

	$q->execute();

	$groups = $q->fetchAll(PDO::FETCH_ASSOC);

	$options = null;

	foreach ($groups as $group) {

		if ($group['group_name'] == $val)
			{
				$options .= '<option name = "'. $group['group_name'] . '" selected=selected>' . $group['group_title'] . '</option>';
			}
		else
			{
				$options .= '<option name = "'. $group['group_name'] . '">' . $group['group_title'] . '</option>';
			}
	}

	return $options;
}

