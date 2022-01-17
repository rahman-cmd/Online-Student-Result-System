<?php

//subject_action.php

include('srms.php');

$object = new srms();

if(isset($_POST["action"]))
{
	if($_POST["action"] == 'fetch')
	{
		$order_column = array('student_srms.student_roll_no', 'student_srms.student_name', 'class_srms.class_name', 'student_srms.student_email_id', 'student_srms.student_gender', 'student_srms.student_dob', 'student_srms.student_added_on', 'student_srms.student_status');

		$output = array();

		$main_query = "
		SELECT * FROM student_srms 
		INNER JOIN class_srms 
		ON class_srms.class_id = student_srms.class_id 
		";

		$search_query = '';

		if(isset($_POST["search"]["value"]))
		{
			$search_query .= 'WHERE student_srms.student_roll_no LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR class_srms.class_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_email_id LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_gender LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_dob LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_added_on LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR student_srms.student_status LIKE "%'.$_POST["search"]["value"].'%" ';
		}

		if(isset($_POST["order"]))
		{
			$order_query = 'ORDER BY '.$order_column[$_POST['order']['0']['column']].' '.$_POST['order']['0']['dir'].' ';
		}
		else
		{
			$order_query = 'ORDER BY student_srms.student_id DESC ';
		}

		$limit_query = '';

		if($_POST["length"] != -1)
		{
			$limit_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}

		$object->query = $main_query . $search_query . $order_query;

		$object->execute();

		$filtered_rows = $object->row_count();

		$object->query .= $limit_query;

		$result = $object->get_result();

		$object->query = $main_query;

		$object->execute();

		$total_rows = $object->row_count();

		$data = array();

		foreach($result as $row)
		{
			$sub_array = array();
			$sub_array[] = html_entity_decode($row["student_roll_no"]);
			$sub_array[] = html_entity_decode($row["student_name"]);
			$sub_array[] = html_entity_decode($row["class_name"]);
			$sub_array[] = $row["student_email_id"];
			$sub_array[] = $row["student_gender"];
			$sub_array[] = $row["student_dob"];
			$sub_array[] = $row["student_added_on"];
			$status = '';
			if($row["student_status"] == 'Enable')
			{
				$status = '<button type="button" name="status_button" class="btn btn-primary btn-sm status_button" data-id="'.$row["student_id"].'" data-status="'.$row["student_status"].'">Enable</button>';
			}
			else
			{
				$status = '<button type="button" name="status_button" class="btn btn-danger btn-sm status_button" data-id="'.$row["student_id"].'" data-status="'.$row["student_status"].'">Disable</button>';
			}
			$sub_array[] = $status;
			$sub_array[] = '
			<div align="center">
			<button type="button" name="edit_button" class="btn btn-warning btn-circle btn-sm edit_button" data-id="'.$row["student_id"].'"><i class="fas fa-edit"></i></button>
			&nbsp;
			<button type="button" name="delete_button" class="btn btn-danger btn-circle btn-sm delete_button" data-id="'.$row["student_id"].'"><i class="fas fa-times"></i></button>
			</div>
			';
			$data[] = $sub_array;
		}

		$output = array(
			"draw"    			=> 	intval($_POST["draw"]),
			"recordsTotal"  	=>  $total_rows,
			"recordsFiltered" 	=> 	$filtered_rows,
			"data"    			=> 	$data
		);
			
		echo json_encode($output);
	}

	if($_POST["action"] == 'Add')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_id'			=>	$_POST["class_id"],
			':student_roll_no'	=>	$_POST["student_roll_no"]
		);

		$object->query = "
		SELECT * FROM student_srms 
		WHERE class_id = :class_id 
		AND student_roll_no = :student_roll_no
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Student Roll No. Already Exists in <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
		}
		else
		{
			$data = array(
				':class_id'				=>	$object->clean_input($_POST["class_id"]),
				':student_name'			=>	$object->clean_input($_POST["student_name"]),
				':student_roll_no'		=>	$object->clean_input($_POST["student_roll_no"]),
				':student_email_id'		=>	$_POST["student_email_id"],
				':student_gender'		=>	$_POST["student_gender"],
				':student_dob'			=>	$_POST["student_dob"],
				':student_status'		=>	'Enable',
				':student_added_by'		=>	$object->Get_user_name($_SESSION['user_id']),
				':student_added_on'		=>	$object->now
			);

			$object->query = "
			INSERT INTO student_srms 
			(class_id, student_name, student_roll_no, student_email_id, student_gender, student_dob, student_status, student_added_by, student_added_on) 
			VALUES (:class_id, :student_name, :student_roll_no, :student_email_id, :student_gender, :student_dob, :student_status, :student_added_by, :student_added_on)
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Student Added in <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
		}

		$output = array(
			'error'		=>	$error,
			'success'	=>	$success
		);

		echo json_encode($output);

	}

	if($_POST["action"] == 'fetch_single')
	{
		$object->query = "
		SELECT * FROM student_srms 
		WHERE student_id = '".$_POST["student_id"]."'
		";

		$result = $object->get_result();

		$data = array();

		foreach($result as $row)
		{
			$data['class_id'] = $row['class_id'];
			$data['student_name'] = $row['student_name'];
			$data['student_roll_no'] = $row['student_roll_no'];
			$data['student_email_id'] = $row['student_email_id'];
			$data['student_gender'] = $row['student_gender'];
			$data['student_dob'] = $row['student_dob'];
		}

		echo json_encode($data);
	}

	if($_POST["action"] == 'Edit')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_id'			=>	$_POST["class_id"],
			':student_roll_no'	=>	$_POST["student_roll_no"],
			':student_id'		=>	$_POST['hidden_id']
		);

		$object->query = "
		SELECT * FROM student_srms 
		WHERE class_id = :class_id 
		AND student_roll_no = :student_roll_no 
		AND student_id != :student_id
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Student Roll No. Already Exists in <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
		}
		else
		{

			$data = array(
				':class_id'				=>	$object->clean_input($_POST["class_id"]),
				':student_name'			=>	$object->clean_input($_POST["student_name"]),
				':student_roll_no'		=>	$object->clean_input($_POST["student_roll_no"]),
				':student_email_id'		=>	$_POST["student_email_id"],
				':student_gender'		=>	$_POST["student_gender"],
				':student_dob'			=>	$_POST["student_dob"]
			);

			$object->query = "
			UPDATE student_srms 
			SET class_id = :class_id, 
			student_name = :student_name, 
			student_roll_no = :student_roll_no, 
			student_email_id = :student_email_id, 
			student_gender = :student_gender, 
			student_dob = :student_dob 
			WHERE student_id = '".$_POST['hidden_id']."'
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Student Data Updated</div>';
			
		}

		$output = array(
			'error'		=>	$error,
			'success'	=>	$success
		);

		echo json_encode($output);

	}

	if($_POST["action"] == 'change_status')
	{
		$data = array(
			':student_status'		=>	$_POST['next_status']
		);

		$object->query = "
		UPDATE student_srms 
		SET student_status = :student_status 
		WHERE student_id = '".$_POST["id"]."'
		";

		$object->execute($data);

		echo '<div class="alert alert-success">Subject Status change to '.$_POST['next_status'].'</div>';
	}

	if($_POST["action"] == 'delete')
	{
		$object->query = "
		DELETE FROM student_srms 
		WHERE student_id = '".$_POST["id"]."'
		";

		$object->execute();

		echo '<div class="alert alert-success">Subject Data Deleted</div>';
	}

}



?>