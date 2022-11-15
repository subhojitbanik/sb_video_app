<?php
    //use App\Post;

function insert_data_fn()
{

    global $wpdb;
    $request_id     = $_POST['reqid'];
    $meeting_id    = $_POST['meet_id']; //string value use: %s
    $room_name    = 'sb-tw-'.uniqid();
    $meeting_time      = $_POST['meet_time']; //string value use: %s
    $meeting_date   = $_POST['meet_date']; //string value use: %s
    $tutuor_id  = $_POST['tut_id']; //string value use: %s
    $student_id = $_POST['stu_id']; //string value use: %s
    //$meeting_link = $_POST['meeting_link'];
    $table_name = $wpdb->prefix . "sb_video_app_details";
    $wpdb->insert($table_name, array(
        "request_id" => $request_id,
        "meeting_id" => $meeting_id,
        "room_name" => $room_name,
        "meeting_time" => $meeting_time,
        "meeting_date" => $meeting_date,
        "tutuor_id" => $tutuor_id,
        "student_id" => $student_id,
    ));
    ob_start();
?>
    <div class="wrapper" style="padding: 20px;">
        <form action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <label>Request Id</label>
                    <input type="text" name="reqid" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Meeting Id</label>
                    <input type="text" name="meet_id" class="form-control">
                </div>
                
                <div class="col-md-6">
                    <label>Meeting Time</label>
                    <input type="time" name="meet_time" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Meeting Date</label>
                    <input type="date" name="meet_date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Tutour Id</label>
                    <input type="text" name="tut_id" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Student Id</label>
                    <input type="text" name="stu_id" class="form-control">
                </div>
                
                <div class="col-md-6">
                    <input type="hidden" name="action" value="data_form">
                    <input type="submit" class="btn btn-md btn-info" name="submit" value="Insert">
                </div>
            </div>
        </form>
    </div>
    <div class="wrapper" style="padding: 20px; overflow-x: scroll;">
        <table class="table-bordered table-responsive">
            <thead>
                <th>request_id</th>
                <th>meeting_id</th>
                <th>room_name</th>
                <th>meeting_time</th>
                <th>meeting_date</th>
                <th>tutuor_id</th>
                <th>student_id</th>
                <th>tutor_token</th>
                <th>student_token</th>
                <th>room_sid</th>
                <th>tutor_join_status</th>
                <th>student_join_status</th>
                <th>remarks</th>
            </thead>
            <tbody>
                <?php $tablename = $wpdb->prefix . "sb_video_app_details";
                $results = $wpdb->get_results("SELECT * FROM $tablename");
                if (!empty($results)) {
                    // echo '<pre>';
                    // print_r($results);
                    foreach ($results as $row) { ?>
                        <tr>
                            <td><?php echo $row->request_id; ?></td>
                            <td><?php echo $row->meeting_id; ?></td>
                            <td><?php echo $row->room_name; ?></td>
                            <td><?php echo $row->meeting_time; ?></td>
                            <td><?php echo $row->meeting_date; ?></td>
                            <td><?php echo $row->tutuor_id; ?></td>
                            <td><?php echo $row->student_id; ?></td>
                            <td><?php echo $row->tutor_token; ?></td>
                            <td><?php echo $row->student_token; ?></td>
                            <td><?php echo $row->room_sid; ?></td>
                            <td><?php echo $row->tutor_join_status; ?></td>
                            <td><?php echo $row->student_join_status; ?></td>
                            <td><?php echo $row->remarks; ?></td>
                        </tr>
                <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
<!-- stopwatch -->

<?php
    return ob_get_clean();
}
add_shortcode('insert_data', 'insert_data_fn');

add_action( 'after_booking_insert', 'insert_session_data', 1, 2 );
function insert_session_data($booking_id, $booking){
    global $wpdb;
    $table_name = $wpdb->prefix . "sb_video_app_details";
    $wpdb->insert($table_name, array(
        "request_id" => $booking['request_id'],
        "meeting_id" => 'FG'.uniqid().$booking['request_id'],
        "room_name" => 'sb-tw-'.uniqid().$booking['request_id'],
        "meeting_time" => '',
        "meeting_date" => $booking['start_str'],
        "tutuor_id" => get_current_user_id(),
        "student_id" => $booking['student_id'],
    ));
    do_action('after_meeting_insert', $booking_id, $booking);
}


add_shortcode('sb_show_table', 'show_table_fn');
function show_table_fn(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'sb_video_app_details';
    // $prepared_statement = $wpdb->prepare( "SELECT * FROM $table_name ");
    // $values = $wpdb->get_col( $prepared_statement );
    $existing_columns = $wpdb->get_col("DESC {$table_name}", 0);
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    // Implode to a string suitable for inserting into the SQL query
    $sql = implode( ', ', $existing_columns );

    var_dump($sql);

    $i = 0;
    ob_start();
    echo "<table>";
    foreach($results as $values){
        if($i == 0){
            echo "<tr>";
            echo "<th>id</th>";
            echo "<th>request_id</th>";
            echo "<th>tutor_join_status</th>";
            echo "<th>student_join_status</th>";
            echo "<th>remarks</th>";
            echo "</tr>";
        }

        echo "<tr>";
        echo "<td>".$values->id."</td>";
        echo "<td>".$values->request_id."</td>";
        echo "<td>".$values->tutor_join_status."</td>";
        echo "<td>".$values->student_join_status."</td>";
        echo "<td>".$values->remarks."</td>";
        echo "</tr>";

        $i++;
    }
    echo "</table>";
    return ob_get_clean();

}