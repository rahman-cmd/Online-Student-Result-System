<?php

//marks.php

include('srms.php');

$object = new srms();

if(!$object->is_login())
{
    header("location:".$object->base_url."admin");
}

$object->query = "
SELECT * FROM class_srms 
WHERE class_status = 'Enable' 
ORDER BY class_name ASC
";

$result = $object->get_result();

include('header.php');
                
?>

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Result Management</h1>

                    <!-- DataTales Example -->
                    <span id="message"></span>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                        	<div class="row">
                            	<div class="col">
                            		<h6 class="m-0 font-weight-bold text-primary">Result List</h6>
                            	</div>
                            	<div class="col" align="right">
                            		<button type="button" name="add_result" id="add_result" class="btn btn-success btn-circle btn-sm"><i class="fas fa-plus"></i></button>
                            	</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="result_table" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Class</th>
                                            <th>Student</th>
                                            <th>Percentage</th>
                                            <?php
                                            if($object->is_master_user())
                                            {
                                                echo '<th>Added By</th>';
                                            }
                                            ?>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php
                include('footer.php');
                ?>

<div id="resultModal" class="modal fade">
  	<div class="modal-dialog">
    	<form method="post" id="result_form">
      		<div class="modal-content">
        		<div class="modal-header">
          			<h4 class="modal-title" id="modal_title">Add Result Data</h4>
          			<button type="button" class="close" data-dismiss="modal">&times;</button>
        		</div>
        		<div class="modal-body">
        			<span id="form_message"></span>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" id="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php
                            foreach($result as $row)
                            {
                                echo '
                                <option value="'.$row["class_id"].'">'.$row["class_name"].'</option>
                                ';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Exam</label>
                        <select name="exam_id" id="exam_id" class="form-control" required>
                            <option value="">Select Exam</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student Name</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    <div id="subject_area"></div>
        		</div>
        		<div class="modal-footer">
          			<input type="hidden" name="hidden_id" id="hidden_id" />
          			<input type="hidden" name="action" id="action" value="Add" />
          			<input type="submit" name="submit" id="submit_button" class="btn btn-success" value="Add" />
          			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        		</div>
      		</div>
    	</form>
  	</div>
</div>

<script>
$(document).ready(function(){

	var dataTable = $('#result_table').DataTable({
		"processing" : true,
		"serverSide" : true,
		"order" : [],
		"ajax" : {
			url:"result_action.php",
			type:"POST",
			data:{action:'fetch'}
		},
		"columnDefs":[
			{
                <?php
                if($object->is_master_user())
                {
                    echo '"targets":[6],';
                }
                else
                {
                    echo '"targets":[5],';
                }
                ?>
				
				"orderable":false,
			},
		],
	});

	$('#add_result').click(function(){
		
		$('#result_form')[0].reset();

		$('#result_form').parsley().reset();

    	$('#modal_title').text('Add Result Data');

    	$('#action').val('Add');

    	$('#submit_button').val('Add');

    	$('#resultModal').modal('show');

    	$('#form_message').html('');

	});

    $(document).on('change', '#class_id', function(){
        var class_id = $('#class_id').val();
        var action = 'fetch_details';
        $.ajax({
            url:"result_action.php",
            method:"POST",
            data:{class_id:class_id, action:action},
            dataType:"JSON",
            success:function(data)
            {
                if(data.exam_data.length > 0)
                {
                    var exam_html = '<option value="">Select Exam</option>';
                    for(var i = 0; i < data.exam_data.length; i++)
                    {
                        exam_html += '<option value="'+data.exam_data[i].exam_id+'">'+data.exam_data[i].exam_name+'</option>';
                    }
                    $('#exam_id').html(exam_html);
                }
                if(data.student_data.length > 0)
                {
                    var student_html = '<option value="">Select Student</option>';
                    for(var i = 0; i < data.student_data.length; i++)
                    {
                        student_html += '<option value="'+data.student_data[i].student_id+'">'+data.student_data[i].student_name+'</option>';
                    }
                    $('#student_id').html(student_html);
                }
                if(data.subject_data.length > 0)
                {
                    var subject_html = '';
                    for(var i = 0; i < data.subject_data.length; i++)
                    {
                        subject_html += '<div class="form-group">';
                        subject_html += '<label>'+data.subject_data[i].subject_name+'</label>';
                        subject_html += '<input type="text" name="marks[]" class="form-control" required data-parsley-type="integer" data-parsley-trigger="keyup" />';
                        subject_html += '<input type="hidden" name="subject_id[]" value="'+data.subject_data[i].subject_id+'" />';
                        subject_html += '</div>';
                    }
                    $('#subject_area').html(subject_html);
                }
            }
        });
    });

	$('#result_form').parsley();

	$('#result_form').on('submit', function(event){
		event.preventDefault();
		if($('#resultModal').parsley().isValid())
		{		
			$.ajax({
				url:"result_action.php",
				method:"POST",
				data:$(this).serialize(),
				dataType:'json',
				beforeSend:function()
				{
					$('#submit_button').attr('disabled', 'disabled');
					$('#submit_button').val('wait...');
				},
				success:function(data)
				{
					$('#submit_button').attr('disabled', false);
					if(data.error != '')
					{
						$('#form_message').html(data.error);
						$('#submit_button').val('Add');
					}
					else
					{
						$('#resultModal').modal('hide');
						$('#message').html(data.success);
						dataTable.ajax.reload();

						setTimeout(function(){

				            $('#message').html('');

				        }, 5000);
					}
				}
			})
		}
	});

	$(document).on('click', '.edit_button', function(){

		var result_id = $(this).data('id');

		$('#result_form').parsley().reset();

		$('#form_message').html('');

		$.ajax({

	      	url:"result_action.php",

	      	method:"POST",

	      	data:{result_id:result_id, action:'fetch_single'},

	      	dataType:'JSON',

	      	success:function(data)
	      	{
                if(data.exam_data.length > 0)
                {
                    var exam_html = '<option value="">Select Exam</option>';
                    for(var i = 0; i < data.exam_data.length; i++)
                    {
                        exam_html += '<option value="'+data.exam_data[i].exam_id+'">'+data.exam_data[i].exam_name+'</option>';
                    }
                    $('#exam_id').html(exam_html);
                }
                if(data.student_data.length > 0)
                {
                    var student_html = '<option value="">Select Student</option>';
                    for(var i = 0; i < data.student_data.length; i++)
                    {
                        student_html += '<option value="'+data.student_data[i].student_id+'">'+data.student_data[i].student_name+'</option>';
                    }
                    $('#student_id').html(student_html);
                }
                if(data.subject_data.length > 0)
                {
                    var subject_html = '';
                    for(var i = 0; i < data.subject_data.length; i++)
                    {
                        subject_html += '<div class="form-group">';
                        subject_html += '<label>'+data.subject_data[i].subject_name+'</label>';
                        subject_html += '<input type="text" name="marks[]" value="'+data.subject_data[i].marks+'" class="form-control" required data-parsley-type="integer" data-parsley-trigger="keyup" />';
                        subject_html += '<input type="hidden" name="subject_id[]" value="'+data.subject_data[i].subject_id+'" />';
                        subject_html += '<input type="hidden" name="marks_id[]" value="'+data.subject_data[i].marks_id+'" />';
                        subject_html += '</div>';
                    }
                    $('#subject_area').html(subject_html);
                }
                //

                $('#class_id').val(data.class_id);

                $('#exam_id').val(data.exam_id);

                $('#student_id').val(data.student_id);

	        	$('#modal_title').text('Edit Result Data');

	        	$('#action').val('Edit');

	        	$('#submit_button').val('Edit');

	        	$('#resultModal').modal('show');

	        	$('#hidden_id').val(result_id);

	      	}

	    })

	});

	$(document).on('click', '.status_button', function(){
		var id = $(this).data('id');
    	var status = $(this).data('status');
		var next_status = 'Enable';
		if(status == 'Enable')
		{
			next_status = 'Disable';
		}
		if(confirm("Are you sure you want to "+next_status+" it?"))
    	{

      		$.ajax({

        		url:"result_action.php",

        		method:"POST",

        		data:{id:id, action:'change_status', status:status, next_status:next_status},

        		success:function(data)
        		{

          			$('#message').html(data);

          			dataTable.ajax.reload();

          			setTimeout(function(){

            			$('#message').html('');

          			}, 5000);

        		}

      		})

    	}
	});

	$(document).on('click', '.delete_button', function(){

    	var id = $(this).data('id');

    	if(confirm("Are you sure you want to remove it?"))
    	{

      		$.ajax({

        		url:"result_action.php",

        		method:"POST",

        		data:{id:id, action:'delete'},

        		success:function(data)
        		{

          			$('#message').html(data);

          			dataTable.ajax.reload();

          			setTimeout(function(){

            			$('#message').html('');

          			}, 5000);

        		}

      		})

    	}

  	});

});
</script>