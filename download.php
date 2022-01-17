<?php

//download.php

include('admin/srms.php');

$object = new srms();

require_once('class/pdf.php');

if(isset($_GET["exam_id"], $_GET["student_roll_no"]))
{
	$html = '';

	$class_id = '';
	$student_id = '';
	$result_id = '';

	$object->query = "
	SELECT * FROM student_srms 
	WHERE student_roll_no = '".$_GET["student_roll_no"]."' 
	AND student_status = 'Enable' 
	";

	$student_result = $object->get_result();

	foreach($student_result as $student_data)
	{
		$html .= '
		<table border="0" cellpadding="5" cellspacing="5" width="100%">
			<tr>
				<td colspan="2" align="center"><h1><u>Online Student Result Management System</u></h1></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2"><b>Roll No. - </b>'.trim($_GET["student_roll_no"]).'</td>
			</tr>
			<tr>
				<td colspan="2"><b>Student Name - </b>'.html_entity_decode($student_data["student_name"]).'</td>
			</tr>
			<tr>
				<td colspan="2"><b>Email ID - </b>'.$student_data["student_email_id"].'</td>
			</tr>
			<tr>
				<td width="50%"><b>Date of Birth - </b>'.$student_data["student_dob"].'</td>
				<td width="50%"><b>Gender - </b>'.$student_data["student_gender"].'</td>
			</tr>
			<tr>
				<td colspan="2"><b>Class Name - </b>'.$object->Get_class_name($student_data["class_id"]).'</td>
			</tr>
		';

		$class_id = $student_data["class_id"];
		$student_id = $student_data["student_id"];
	}

	$object->query = "
	SELECT * FROM exam_srms 
	WHERE exam_id = '".$_GET["exam_id"]."'
	";
	$exam_result = $object->get_result();

	foreach($exam_result as $exam_data)
	{
		$html .='
			<tr>
				<td width="50%"><b>Exam - </b>'.$exam_data["exam_name"].'</td>
				<td width="50%"><b>Date & Time - </b>'.date("Y-m-d H:i:s").'</td>
			</tr>
			';
	}

	$object->query = "
	SELECT * FROM result_srms 
	WHERE class_id = '$class_id' 
	AND student_id = '$student_id' 
	AND exam_id = '".$_GET["exam_id"]."'
	";

	$result_data = $object->get_result();
	foreach($result_data as $result)
	{
		if($result["result_status"] == "Enable")
		{
			$result_id = $result["result_id"];

			$html .= '
			<tr><td colspan="2">
			<table border="1" cellpadding="5" cellspacing="0" width="100%">
				<tr>
					<th>#</th>
					<th>Subject</th>
					<th>Obtain Mark</th>
				</tr>
			';
			$object->query = "
			SELECT subject_srms.subject_name, marks_srms.marks 
			FROM marks_srms 
			INNER JOIN subject_srms 
			ON subject_srms.subject_id = marks_srms.subject_id 
			WHERE marks_srms.result_id = '".$result["result_id"]."'
			";
			$marks_data = $object->get_result();
			$count = 0;
			$total = 0;
			foreach($marks_data as $marks)
			{
				$count++;
				$html .= '
				<tr>
					<td>'.$count.'</td>
					<td>'.$marks["subject_name"].'</td>
					<td>'.$marks["marks"].'</td>
				</tr>
				';
				$total += $marks["marks"];
			}
			$html .= '
				<tr>
					<td colspan="2" align="right"><b>Total</b></td>
					<td>'.$total.'</td>
				</tr>
				<tr>
					<td colspan="2" align="right"><b>Percentage</b></td>
					<td>'.$result["result_percentage"].'%</td>
				</tr>
			</table>
			<td></tr>';
		}
	}

	$html .= '</table>';

	//echo $html;

	$pdf = new Pdf();

	$pdf->loadHtml($html, 'UTF-8');
	$pdf->render();
	$pdf->stream($_GET["student_roll_no"] . '.pdf', array( 'Attachment'=>1 ));
	exit(0);

}

?>