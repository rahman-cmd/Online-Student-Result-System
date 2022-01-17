<?php

//result_action.php

include('srms.php');

$object = new srms();

if(isset($_POST["action"]))
{
	if($_POST["action"] == 'fetch')
	{
		$order_column = array('exam_srms.exam_name', 'class_srms.class_name', 'student_srms.student_name', 'result_srms.result_percentage', 'result_srms.result_status', NULL);

		$output = array();

		$main_query = "
		SELECT * FROM result_srms 
		INNER JOIN exam_srms 
		ON exam_srms.exam_id = result_srms.exam_id 
		INNER JOIN class_srms 
		ON class_srms.class_id = result_srms.class_id 
		INNER JOIN student_srms 
		ON student_srms.student_id = result_srms.student_id 
		";

		$search_query = '';

		if(!$object->is_master_user())
		{
			$search_query .= "
			WHERE result_srms.result_added_by = '".$object->Get_user_name($_SESSION['user_id'])."' 
			";

			if(isset($_POST["search"]["value"]))
			{
				$search_query .= 'AND (exam_srms.exam_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR class_srms.class_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR student_srms.student_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR result_srms.result_percentage LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR result_srms.result_status LIKE "%'.$_POST["search"]["value"].'%") ';
			}
		}
		else
		{
			if(isset($_POST["search"]["value"]))
			{
				$search_query .= 'WHERE exam_srms.exam_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR class_srms.class_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR student_srms.student_name LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR result_srms.result_percentage LIKE "%'.$_POST["search"]["value"].'%" ';
				$search_query .= 'OR result_srms.result_status LIKE "%'.$_POST["search"]["value"].'%" ';
			}
		}
		

		if(isset($_POST["order"]))
		{
			$order_query = 'ORDER BY '.$order_column[$_POST['order']['0']['column']].' '.$_POST['order']['0']['dir'].' ';
		}
		else
		{
			$order_query = 'ORDER BY result_srms.result_id DESC ';
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
			$sub_array[] = html_entity_decode($row["student_name"]);
			$sub_array[] = $row["result_percentage"] . '%';
			if($object->is_master_user())
			{
				$sub_array[] = $row["result_added_by"];
			}
			$status = '';
			if($row["result_status"] == 'Enable')
			{
				$status = '<button type="button" name="status_button" class="btn btn-primary btn-sm status_button" data-id="'.$row["result_id"].'" data-status="'.$row["result_status"].'">Enable</button>';
			}
			else
			{
				$status = '<button type="button" name="status_button" class="btn btn-danger btn-sm status_button" data-id="'.$row["result_id"].'" data-status="'.$row["result_status"].'">Disable</button>';
			}
			$sub_array[] = $status;
			$sub_array[] = '
			<div align="center">
			<button type="button" name="edit_button" class="btn btn-warning btn-circle btn-sm edit_button" data-id="'.$row["result_id"].'"><i class="fas fa-edit"></i></button>
			&nbsp;
			<button type="button" name="delete_button" class="btn btn-danger btn-circle btn-sm delete_button" data-id="'.$row["result_id"].'"><i class="fas fa-times"></i></button>
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

	if($_POST["action"] == 'fetch_details')
	{
		$exam_data = array();
		$student_data = array();
		$subject_data = array();

		$object->query = "
		SELECT exam_id, exam_name FROM exam_srms 
		WHERE class_id = '".$_POST["class_id"]."' 
		AND exam_status = 'Enable' 
		ORDER BY exam_name ASC
		";

		$exam_result = $object->get_result();

		foreach($exam_result as $row)
		{
			$exam_data[] = array(
				'exam_id'		=>	$row['exam_id'],
				'exam_name'		=>	html_entity_decode($row['exam_name'])
			);
		}

		$object->query = "
		SELECT student_id, student_name FROM student_srms 
		WHERE class_id = '".$_POST["class_id"]."' 
		AND student_status = 'Enable' 
		ORDER BY student_name ASC
		";

		$student_result = $object->get_result();

		foreach($student_result as $row)
		{
			$student_data[] = array(
				'student_id'		=>	$row['student_id'],
				'student_name'		=>	html_entity_decode($row['student_name'])
			);
		}

		$object->query = "
		SELECT subject_id, subject_name FROM subject_srms 
		WHERE class_id = '".$_POST["class_id"]."' 
		AND subject_status = 'Enable' 
		ORDER BY subject_name ASC
		";

		$subject_result = $object->get_result();

		foreach($subject_result as $row)
		{
			$subject_data[] = array(
				'subject_id'		=>	$row['subject_id'],
				'subject_name'		=>	html_entity_decode($row['subject_name'])
			);
		}

		$data = array(
			'exam_data'		=>	$exam_data,
			'student_data'	=>	$student_data,
			'subject_data'	=>	$subject_data	
		);

		echo json_encode($data);
	}

	if($_POST["action"] == 'Add')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_id'			=>	$_POST["class_id"],
			':student_id'		=>	$_POST["student_id"],
			':exam_id'			=>	$_POST["exam_id"]
		);

		$object->query = "
		SELECT * FROM result_srms 
		WHERE class_id = :class_id 
		AND student_id = :student_id 
		AND exam_id = :exam_id
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">This Student Result Already Added</div>';
		}
		else
		{
			$data = array(
				':class_id'			=>	$_POST["class_id"],
				':student_id'		=>	$_POST["student_id"],
				':exam_id'			=>	$_POST["exam_id"],
				':result_percentage'=>	'0.00',
				':result_status'	=>	'Enable',
				':result_added_by'	=>	$object->Get_user_name($_SESSION['user_id'])
			);

			$object->query = "
			INSERT INTO result_srms 
			(class_id, student_id, exam_id, result_percentage, result_status, result_added_by) 
			VALUES (:class_id, :student_id, :exam_id, :result_percentage, :result_status, :result_added_by)
			";

			$object->execute($data);

			$result_id = $object->connect->lastInsertId();

			$subject_id = $_POST["subject_id"];
			$marks = $_POST["marks"];

			$count = 0;

			$total = 0;

			for($i = 0; $i < count($subject_id); $i++)
			{
				$marks_data = array(
					':result_id'		=>	$result_id,
					':subject_id'		=>	$subject_id[$i],
					':marks'			=>	$marks[$i]
				);
				$object->query = "
				INSERT INTO marks_srms 
				(result_id, subject_id, marks) 
				VALUES (:result_id, :subject_id, :marks)
				";

				$object->execute($marks_data);
				$count++;
				$total = $total + $marks[$i];
			}

			$percentage = $total/$count;

			$object->query = "
			UPDATE result_srms 
			SET result_percentage = '".$percentage."' 
			WHERE result_id = '".$result_id."'
			";

			$object->execute();

			$success = '<div class="alert alert-success">Result Added Successfully...</div>';
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
		SELECT * FROM result_srms 
		WHERE result_id = '".$_POST["result_id"]."'
		";

		$result = $object->get_result();

		$data = array();

		$exam_data = array();
		$student_data = array();
		$subject_data = array();

		foreach($result as $row)
		{
			$class_id = $row['class_id'];
			$data['class_id'] = $class_id;
			$data['student_id'] = $row['student_id'];
			$data['exam_id'] = $row['exam_id'];

			$object->query = "
			SELECT exam_id, exam_name FROM exam_srms 
			WHERE class_id = '".$class_id."' 
			AND exam_status = 'Enable' 
			ORDER BY exam_name ASC
			";

			$exam_result = $object->get_result();

			foreach($exam_result as $row)
			{
				$exam_data[] = array(
					'exam_id'		=>	$row['exam_id'],
					'exam_name'		=>	html_entity_decode($row['exam_name'])
				);
			}

			$object->query = "
			SELECT student_id, student_name FROM student_srms 
			WHERE class_id = '".$class_id."' 
			AND student_status = 'Enable' 
			ORDER BY student_name ASC
			";

			$student_result = $object->get_result();

			foreach($student_result as $row)
			{
				$student_data[] = array(
					'student_id'		=>	$row['student_id'],
					'student_name'		=>	html_entity_decode($row['student_name'])
				);
			}

			$object->query = "
			SELECT marks_srms.subject_id, marks_srms.marks, marks_srms.marks_id, subject_srms.subject_name FROM marks_srms 
			INNER JOIN subject_srms 
			ON subject_srms.subject_id = marks_srms.subject_id 
			WHERE marks_srms.result_id = '".$_POST["result_id"]."' 
			ORDER BY marks_srms.marks_id ASC
			";

			$subject_result = $object->get_result();

			foreach($subject_result as $row)
			{
				$subject_data[] = array(
					'marks_id'			=>	$row['marks_id'],
					'subject_id'		=>	$row['subject_id'],
					'marks'				=>	$row['marks'],
					'subject_name'		=>	html_entity_decode($row['subject_name'])
				);
			}
			
			$data['exam_data'] = $exam_data;
			$data['student_data'] = $student_data;
			$data['subject_data'] = $subject_data;
		}

		echo json_encode($data);
	}

	if($_POST["action"] == 'Edit')
	{
		$error = '';

		$success = '';

		$data = array(
			':class_id'			=>	$_POST["class_id"],
			':student_id'		=>	$_POST["student_id"],
			':exam_id'			=>	$_POST["exam_id"],
			':result_id'		=>	$_POST['hidden_id']
		);

		$object->query = "
		SELECT * FROM result_srms 
		WHERE class_id = :class_id 
		AND student_id = :student_id 
		AND exam_id = :exam_id 
		AND result_id != :result_id
		";

		$object->execute($data);

		if($object->row_count() > 0)
		{
			$error = '<div class="alert alert-danger">Duplicate Result Entry</div>';
		}
		else
		{
			$subject_id = $_POST["subject_id"];

			$marks = $_POST["marks"];

			$marks_id = $_POST["marks_id"];

			$count = 0;

			$total = 0;

			for($i = 0; $i < count($subject_id); $i++)
			{
				$marks_data = array(
					':marks'			=>	$marks[$i]
				);
				$object->query = "
				UPDATE marks_srms 
				SET marks = :marks 
				WHERE marks_id = '".$marks_id[$i]."'
				";

				$object->execute($marks_data);

				$count++;

				$total = $total + $marks[$i];
			}

			$percentage = $total/$count;

			$data = array(
				':class_id'			=>	$_POST["class_id"],
				':student_id'		=>	$_POST["student_id"],
				':exam_id'			=>	$_POST["exam_id"],
				':result_percentage'=>	$percentage
			);

			$object->query = "
			UPDATE result_srms 
			SET class_id = :class_id, 
			student_id = :student_id, 
			exam_id = :exam_id, 
			result_percentage = :result_percentage 
			WHERE result_id = '".$_POST['hidden_id']."'
			";

			$object->execute($data);

			$success = '<div class="alert alert-success">Result Data Updated</div>';
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
			':result_status'		=>	$_POST['next_status']
		);

		$object->query = "
		UPDATE result_srms 
		SET result_status = :result_status 
		WHERE result_id = '".$_POST["id"]."'
		";

		$object->execute($data);

		echo '<div class="alert alert-success">Result Status change to '.$_POST['next_status'].'</div>';
	}

	if($_POST["action"] == 'delete')
	{
		$object->query = "
		DELETE FROM result_srms 
		WHERE result_id = '".$_POST["id"]."'
		";

		$object->execute();

		$object->query = "
		DELETE FROM marks_srms 
		WHERE result_id = '".$_POST["id"]."'
		";

		$object->execute();

		echo '<div class="alert alert-success">Result Deleted</div>';
	}
}

?>