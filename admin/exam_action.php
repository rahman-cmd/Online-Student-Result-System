<?php

//exam_action.php

include('srms.php');

$object = new srms();

if(isset($_POST["action"]))
{
	if($_POST["action"] == 'fetch')
	{
		$order_column = array('exam_srms.exam_name', 'class_srms.class_name', 'exam_srms.exam_result_date', 'exam_srms.exam_result_published');

		$output = array();

		$main_query = "
		SELECT * FROM exam_srms 
		INNER JOIN class_srms 
		ON class_srms.class_id = exam_srms.class_id 
		";

		$search_query = '';

		if(isset($_POST["search"]["value"]))
		{
			$search_query .= 'WHERE class_srms.class_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR exam_srms.exam_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR exam_srms.exam_result_date LIKE "%'.$_POST["search"]["value"].'%" ';
			$search_query .= 'OR exam_srms.exam_result_published LIKE "%'.$_POST["search"]["value"].'%" ';
		}

		if(isset($_POST["order"]))
		{
			$order_query = 'ORDER BY '.$order_column[$_POST['order']['0']['column']].' '.$_POST['order']['0']['dir'].' ';
		}
		else
		{
			$order_query = 'ORDER BY exam_srms.exam_id DESC ';
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
			$sub_array[] = html_entity_decode($row["exam_name"]);
			$sub_array[] = html_entity_decode($row["class_name"]);

			$exam_result_date = '';

			if($row['exam_result_date'] == '0000-00-00')
			{
				$exam_result_date = 'Not Publish';
			}
			else
			{
				$exam_result_date = $row['exam_result_date'];
			}

			$sub_array[] = $exam_result_date;

			$result_status = '';

			if($row["exam_result_published"] == 'No')
			{
				$result_status = '<button type="button" name="result_button" class="btn btn-secondary btn-sm result_button" data-id="'.$row["exam_id"].'" data-status="'.$row["exam_result_published"].'">No</button>';
			}
			else
			{
				$result_status = '<button type="button" name="result_button" class="btn btn-success btn-sm result_button" data-id="'.$row["exam_id"].'" data-status="'.$row["exam_result_published"].'">Yes</button>';
			}

			$sub_array[] = $result_status;

			$sub_array[] = $row["exam_added_on"];
			$status = '';
			if($row["exam_status"] == 'Enable')
			{
				$status = '<button type="button" name="status_button" class="btn btn-primary btn-sm status_button" data-id="'.$row["exam_id"].'" data-status="'.$row["exam_status"].'">Enable</button>';
			}
			else
			{
				$status = '<button type="button" name="status_button" class="btn btn-danger btn-sm status_button" data-id="'.$row["exam_id"].'" data-status="'.$row["exam_status"].'">Disable</button>';
			}
			$sub_array[] = $status;
			$sub_array[] = '
			<div align="center">
			<button type="button" name="edit_button" class="btn btn-warning btn-circle btn-sm edit_button" data-id="'.$row["exam_id"].'"><i class="fas fa-edit"></i></button>
			&nbsp;
			<button type="button" name="delete_button" class="btn btn-danger btn-circle btn-sm delete_button" data-id="'.$row["exam_id"].'"><i class="fas fa-times"></i></button>
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
			':exam_name'		=>	$_POST["exam_name"]
		);

		$object->query = "
		SELECT * FROM exam_srms 
		WHERE class_id = :class_id 
		AND exam_name = :exam_name
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Exam Already Exists for <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
		}
		else
		{
			$data = array(
				':class_id'			=>	$_POST["class_id"],
				':exam_name'		=>	$object->clean_input($_POST["exam_name"]),
				':exam_status'		=>	'Enable',
				':exam_added_on'	=>	$object->now,
			);

			$object->query = "
			INSERT INTO exam_srms 
			(class_id, exam_name, exam_status, exam_added_on) 
			VALUES (:class_id, :exam_name, :exam_status, :exam_added_on)
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Exam Added in <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
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
		SELECT * FROM exam_srms 
		WHERE exam_id = '".$_POST["exam_id"]."'
		";

		$result = $object->get_result();

		$data = array();

		foreach($result as $row)
		{
			$data['class_id'] = $row['class_id'];
			$data['exam_name'] = $row['exam_name'];
		}

		echo json_encode($data);
	}

	if($_POST["action"] == 'Edit')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_id'			=>	$_POST["class_id"],
			':exam_name'		=>	$_POST["exam_name"],
			':exam_id'			=>	$_POST['hidden_id']
		);

		$object->query = "
		SELECT * FROM exam_srms 
		WHERE class_id = :class_id 
		AND exam_name = :exam_name
		AND exam_id != :exam_id
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Exam Already Exists in <b>'.$object->Get_class_name($_POST["class_id"]).'</b> Class</div>';
		}
		else
		{
			$data = array(
				':class_id'			=>	$_POST["class_id"],
				':exam_name'		=>	$object->clean_input($_POST["exam_name"])
			);

			$object->query = "
			UPDATE exam_srms 
			SET class_id = :class_id, 
			exam_name = :exam_name  
			WHERE exam_id = '".$_POST['hidden_id']."'
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Exam Updated</div>';
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
			':exam_status'		=>	$_POST['next_status']
		);

		$object->query = "
		UPDATE exam_srms 
		SET exam_status = :exam_status 
		WHERE exam_id = '".$_POST["id"]."'
		";

		$object->execute($data);

		echo '<div class="alert alert-success">Exam Status change to '.$_POST['next_status'].'</div>';
	}

	if($_POST["action"] == 'delete')
	{
		$object->query = "
		DELETE FROM exam_srms 
		WHERE exam_id = '".$_POST["id"]."'
		";

		$object->execute();

		echo '<div class="alert alert-success">Exam Deleted</div>';
	}	

	if($_POST["action"] == 'result_status')
	{
		if($_POST['next_status'] == 'Yes')
		{
			$data = array(
				':exam_result_date'				=>	date('Y-m-d'),
				':exam_result_published'		=>	$_POST['next_status']
			);

			$object->query = "
			UPDATE exam_srms 
			SET exam_result_date = :exam_result_date, exam_result_published = :exam_result_published 
			WHERE exam_id = '".$_POST["id"]."'
			";

			$object->execute($data);

			echo '<div class="alert alert-success">Exam Result has been Lived</div>';
		}
		else
		{
			$data = array(
				':exam_result_published'		=>	$_POST['next_status']
			);

			$object->query = "
			UPDATE exam_srms 
			SET exam_result_published = :exam_result_published 
			WHERE exam_id = '".$_POST["id"]."'
			";

			$object->execute($data);

			echo '<div class="alert alert-success">Exam Result Status change Offline</div>';
		}
		
	}
}

?>