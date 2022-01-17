<?php

//classes_action.php

include('srms.php');

$object = new srms();

if(isset($_POST["action"]))
{
	if($_POST["action"] == 'fetch')
	{
		$order_column = array('class_name', 'class_status');

		$output = array();

		$main_query = "
		SELECT * FROM class_srms ";

		$search_query = '';

		if(isset($_POST["search"]["value"]))
		{
			$search_query .= 'WHERE class_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR class_status LIKE "%'.$_POST["search"]["value"].'%" ';
		}

		if(isset($_POST["order"]))
		{
			$order_query = 'ORDER BY '.$order_column[$_POST['order']['0']['column']].' '.$_POST['order']['0']['dir'].' ';
		}
		else
		{
			$order_query = 'ORDER BY class_id DESC ';
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
			$sub_array[] = html_entity_decode($row["class_name"]);
			$subject_array = $object->Get_Class_subject($row["class_id"]);
			$sub_array[] = implode(", ", $subject_array);
			$sub_array[] = '<button type="button" name="add_subject" data-id="'.$row["class_id"].'" class="btn btn-info btn-sm add_subject"><i class="fas fa-plus"></i> Subject</button>';
			$sub_array[] = '<a href="subject.php?action=view&class='.$row["class_code"].'" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Subject</a>';
			$status = '';
			if($row["class_status"] == 'Enable')
			{
				$status = '<button type="button" name="status_button" class="btn btn-primary btn-sm status_button" data-id="'.$row["class_id"].'" data-status="'.$row["class_status"].'">Enable</button>';
			}
			else
			{
				$status = '<button type="button" name="status_button" class="btn btn-danger btn-sm status_button" data-id="'.$row["class_id"].'" data-status="'.$row["class_status"].'">Disable</button>';
			}
			$sub_array[] = $status;
			$sub_array[] = '
			<div align="center">
			<button type="button" name="edit_button" class="btn btn-warning btn-circle btn-sm edit_button" data-id="'.$row["class_id"].'"><i class="fas fa-edit"></i></button>
			&nbsp;
			<button type="button" name="delete_button" class="btn btn-danger btn-circle btn-sm delete_button" data-id="'.$row["class_id"].'"><i class="fas fa-times"></i></button>
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
			':class_name'	=>	$_POST["class_name"]
		);

		$object->query = "
		SELECT * FROM class_srms 
		WHERE class_name = :class_name
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Class Name Already Exists</div>';
		}
		else
		{
			$data = array(
				':class_name'			=>	$object->clean_input($_POST["class_name"]),
				':class_code'			=>	md5(uniqid()),
				':class_status'			=>	'Enable',
				':class_created_on'		=>	$object->now
			);

			$object->query = "
			INSERT INTO class_srms 
			(class_name, class_code, class_status, class_created_on) 
			VALUES (:class_name, :class_code, :class_status, :class_created_on)
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Class Added</div>';
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
		SELECT * FROM class_srms 
		WHERE class_id = '".$_POST["class_id"]."'
		";

		$result = $object->get_result();

		$data = array();

		foreach($result as $row)
		{
			$data['class_name'] = $row['class_name'];
		}

		echo json_encode($data);
	}

	if($_POST["action"] == 'Edit')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_name'	=>	$_POST["class_name"],
			':class_id'		=>	$_POST['hidden_id']
		);

		$object->query = "
		SELECT * FROM class_srms 
		WHERE class_name = :class_name 
		AND class_id != :class_id
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Class Name Already Exists</div>';
		}
		else
		{

			$data = array(
				':class_name'		=>	$object->clean_input($_POST["class_name"])
			);

			$object->query = "
			UPDATE class_srms 
			SET class_name = :class_name 
			WHERE class_id = '".$_POST['hidden_id']."'
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Class Data Updated</div>';
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
			':class_status'		=>	$_POST['next_status']
		);

		$object->query = "
		UPDATE class_srms 
		SET class_status = :class_status 
		WHERE class_id = '".$_POST["id"]."'
		";

		$object->execute($data);

		echo '<div class="alert alert-success">Class Status change to '.$_POST['next_status'].'</div>';
	}

	if($_POST["action"] == 'delete')
	{
		$object->query = "
		DELETE FROM class_srms 
		WHERE class_id = '".$_POST["id"]."'
		";

		$object->execute();

		echo '<div class="alert alert-success">Class Data Deleted</div>';
	}
}

?>