<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;

function sb_video_get_all_meetings(){

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT * FROM $tablename");

    return $results;

}
function get_user_profile_id($user_id)
{
    $profile_id = get_user_meta( $user_id, 'profile_id', true );
    return $profile_id;
}
    
function get_meeting_link($request_id){

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT room_name,remarks,meeting_date,student_id FROM $tablename WHERE request_id ='$request_id' "); //(tutuor_id = '$tutor_id' OR student_id = '$student_id') AND
    $rmname = $results[0]->room_name;



    if(!empty($rmname) && $results[0]->remarks >= 1){ return ''; }
    return add_query_arg( array(
        'rname' => $rmname,
        'req_id' => $request_id,
    ), home_url( 'join-meeting' ) );

}

function get_adjusted_timestring($tutor_id, $student_id, $start_str){

    global $current_user;
    $user_roles = $current_user->roles;

    $studentTimezone = get_post_meta(get_user_profile_id($student_id), 'student_timezone', true);
    $tutorTimezone = get_post_meta(get_user_profile_id($tutor_id), 'tutor_timezone', true );

    if(!empty($tutorTimezone) && !empty($studentTimezone)){
        if(in_array('student', $user_roles)){
            $start = $start_str;
        }elseif(in_array('tutor', $user_roles)){
            // Get student timezone - Canada/Atlantic
            $sTimezone = new DateTimeZone( $studentTimezone );
            // Set time
            $start_str = new DateTime( $start_str, $sTimezone );
            // Get tutor Timezone - tutor Timezone
            $tTimezone = new DateTimeZone( $tutorTimezone );
            $start_str->setTimeZone( $tTimezone );
            // Convert student timezone to tutor Timezone
            $start = $start_str->format( 'Y-m-d H:i:s' ); // 2020-06-03 03:45:15
        }
    }else{
        $start = $start_str;
    }

    return $start;

}

function get_current_user_timezone(){
    global $current_user;
    $user_roles = $current_user->roles;
    $user_Id = get_current_user_id();

    if(in_array('student', $user_roles)){
        $user_timezone = get_post_meta(get_user_profile_id($user_Id), 'student_timezone', true);
    }elseif(in_array('tutor', $user_roles)){
        $user_timezone = get_post_meta(get_user_profile_id($user_Id), 'tutor_timezone', true );
    }else{
        $user_timezone = 'US/Central';
    }

    return $user_timezone;
}


function get_meeting_status($request_id, $string){

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT remarks,meeting_date,tutuor_id,student_id FROM $tablename WHERE request_id ='$request_id' ");
    $remarks = $results[0]->remarks;

    $start = get_adjusted_timestring($results[0]->tutuor_id, $results[0]->student_id, $results[0]->meeting_date); 
    $meeting_start = new DateTime($start);
    $meeting_start = $meeting_start->format("Y-m-d H:i:s");
    $mStart_string = strtotime($meeting_start);

    // $mEnd = strtotime($start) + 3600; $mEndDate->modify('+ 1 hour');
    $mEnd = new DateTime($start);
    $mEnd = $mEnd->modify('+ 1 hour');
    $mEnd = $mEnd->format('Y-m-d H:i:s');
    $mEnd_string = strtotime($mEnd);

    // $today_now = '2022-09-28 09:30';
    $uTimezone = new DateTimeZone( get_current_user_timezone() );
    $today_now = new DateTime( "now", $uTimezone ); //new DateTime(  $today_now );//
    $today_now = $today_now->format('Y-m-d H:i:s');
    $today_string = strtotime($today_now);
    
    $todayBuffer = new DateTime($today_now);
    $todayBuffer = $todayBuffer->modify('+30 minutes');
    $todayBuffer = $todayBuffer->format('Y-m-d H:i:s');
    $todayBuffer_string = strtotime($todayBuffer);

    
    if($remarks == 0 && $mEnd_string < $today_string){
        $arr = 'Meeting link is not available as time has elapsed';
    }elseif($remarks == 0 && $mStart_string >= $today_string){
        $arr = 'The meeting link will be available at agreed upon session start time. Please refresh your page if you do not see the link.'; 
    }elseif($remarks == 1){
        $arr = 'Tutor failed to join the meeting.';
    }elseif($remarks == 2){
        $arr = 'Student failed to join the meeting.';
    }elseif($remarks == 3){
        $arr = 'Session have conducted successfully.';
    }elseif($remarks == 0 && $mStart_string <= $today_string && $mEnd_string >= $today_string){ //|| $mStart_string <= $todayBuffer_string
        $arr = 'join'; 
    }

    if(!$string){
        return $remarks;
    }

    return $arr;
    
}

function create_meeting_room_form()
{
     //print_r(get_meeting_status(4580));

    if (is_user_logged_in()) {
        // echo 'User ID: ' . get_current_user_id();
         $user_Id = get_current_user_id();
    }
    //echo $user_Id.'<br>';

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT room_name FROM $tablename WHERE (tutuor_id = '$user_Id' OR student_id = '$user_Id') AND request_id ='7' ");
   // echo $rmname = $results[0]->room_name .'<br>';
    // var_dump($results)
    // // Find your Account SID and Auth Token at twilio.com/console
    // // and set the environment variables. See http://twil.io/secure
    $sid = "AC9c033c4d217dbbd6ccbab7cde26f1f82";
    $token = "c5a06e2f29b75e31c30670545e174525";
    $twilio = new Client($sid, $token);
    if (isset($_GET['submit'])) {
        $uni_room_name =  $rmname;
        //echo $uni_room_name;
        $room = $twilio->video->v1->rooms->create(
            [
                "type" => "go",
                "uniqueName" => $uni_room_name,
                "maxParticipantDuration" => 3600,
                "duration" => 3600,
                "unusedRoomTimeout" => 60,
                "emptyRoomTimeout" => 10,
            ]
        );
        $roomID = ($room->sid);
        $_SESSION["roomID"] = $roomID;
    }
    $form_html = '<div class="col-md-12">';
    $form_html .= '<form class="row g-3" id="fcreate" action="' . home_url() . '/join-meeting" method="GET">';
    //$form_html .= '<h2>Join Meeting</h2>';
    $form_html .= '<div class="col-md-12">';
    $form_html .= '<input type="hidden" name="rname" value="' . $rmname . '" class="btn btn-primary">';
    $form_html .= '<input type="submit" name="submit" value="Join Meeting" class="btn btn-primary">';
    $form_html .= '</div>';
    $form_html .= '</form>';
    $form_html .= '</div>';
    return $form_html;
}
add_shortcode('sb-form', 'create_meeting_room_form');
?>
<?php
function sb_join_meeting()
{
    if (is_user_logged_in()) {
        //echo 'User ID: ' . get_current_user_id();
        $user = wp_get_current_user();
        //echo $role = $user->roles[0];
        $user_id = $user->user_email;

    } else {

        ob_start();
            echo do_shortcode( '[elementor-template id="5218"]' );
        return ob_get_clean();
        // $user_id = uniqid();
    }

    //https://fastgrades.net/wp-content/uploads/2022/09/output-onlinepngtools.png

    $meeting_status = get_meeting_status($_GET['req_id'], true);

    if($meeting_status !== 'join'){

        ob_start();
        ?>
            <div class="sb_video_notice_wrapper" style="display: flex;flex-direction: column;align-items: center;">
                <div class="image_wrapper" style="max-width: 420px;">
                    <img src="https://fastgrades.net/wp-content/uploads/2022/09/output-onlinepngtools.png">
                </div>
                <div class="notice_wrapper" style="margin-bottom: 70px;">
                    <h2><?php echo $meeting_status; ?></h2>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }
ob_start();
?>
    <form class="row g-3" action="<?php echo home_url(); ?>/join" method="GET"> 
        <div class="sb_content-wrapper">
            
                <div class="row wid-60">
                    <div class="text-justify session_policy">
                        <h4 style="color: #034279; text-decoration: underline #fcb12b;">Important: Session Termination Policy.</h4>
                        <p>If a student schedules a session and join and the tutor is more than 10 minutes late in joining, the meeting is automatically cancelled and the student will be entitled to a full refund. The same requirement exists for the student. If a student is more than 10 minutes late in joining and the tutor has joined and is waiting, the meeting is automatically cancelled and the tutor will get paid in full and the student would have forfeited their payment.</p>
                    </div>
                    <div class="sb-form-fields">
                        <input type="hidden" name="rname" value="<?php echo $_GET['rname']; ?>">
                        <input type="hidden" id="audio_val" name="audioStatus" value="">
                        <input type="hidden" id="video_val" name="videoStatus" value="">
                        <input type="hidden" id="token" name="token" value="<?php echo $user_id; ?>">
                        <input type="hidden" id="request_id" name="request_id" value="<?php echo $_GET['req_id']; ?>">
                        <input type="submit" name="submit" value="Join" class="btn btn-primary">
                    </div>
                </div>
                <div class="row wid-40">
                    <!-- <form class="row wid-50 g-3" action="<?php //echo home_url(); ?>/join" method="GET">            -->
                        <div class="col-md-6">
                            <div class="localframe">
                                <div id="local-media"></div>
                                <div class="controls sb_controls">
                                    <div class="sb-call-audio" id="disconnect-sb-call-audio">
                                        <span class="material-icons">mic</span>
                                    </div>
                                    <div class="sb-call-audio" id="connect-sb-call-audio" style="display:none">
                                        <span class="material-icons">mic_off</span>
                                    </div>
                                    <div class="sb-call-video" id="disconnect-sb-call-video">
                                        <span class="material-icons">videocam</span>
                                    </div>
                                    <div class="sb-call-video" id="connect-sb-call-video" style="display:none">
                                        <span class="material-icons">videocam_off</span>
                                    </div>
                                </div>
                                  
                            </div>
                        </div>
                    <!-- </form> -->
                </div>
            
        </div>
    </form>
    <script>
        const Video = Twilio.Video;
        Video.createLocalTracks().then(function(localTracks) {
            var localMediaContainer = document.getElementById('local-media');
            localTracks.forEach(function(track) {
                var trackElement = track.attach();
                //console.log(trackElement);
                trackElement.style.transform = 'scale(-1, 1)';
                trackElement.style.width = '100%';
                localMediaContainer.appendChild(trackElement);
                //Show/Hide Video starts
                jQuery(document).ready(function($) {
                    $('#disconnect-sb-call-video').on('click', () => {
                        // alert('triggered');
                        localTracks[1].disable();
                        jQuery('#video_val').val(localTracks[1].isEnabled);
                        jQuery('#disconnect-sb-call-video').hide();
                        jQuery('#connect-sb-call-video').show();
                        console.log(localTracks[1].isEnabled);
                    })
                    $('#connect-sb-call-video').on('click', () => {
                        // alert('triggered');
                        localTracks[1].enable();
                        jQuery('#video_val').val(localTracks[1].isEnabled);
                        jQuery('#connect-sb-call-video').hide();
                        jQuery('#disconnect-sb-call-video').show();
                        console.log(localTracks[1].isEnabled);
                    })
                    //Show/Hide Video ends
                    //mute/unmute audio starts
                    $('#disconnect-sb-call-audio').on('click', () => {
                        // alert('triggered');
                        localTracks[0].disable();
                        jQuery('#audio_val').val(localTracks[0].isEnabled);
                        //console.table(localTracks[0].isEnabled);
                        jQuery('#disconnect-sb-call-audio').hide();
                        jQuery('#connect-sb-call-audio').show();
                    })
                    $('#connect-sb-call-audio').on('click', () => {
                        //    alert('triggered');
                        localTracks[0].enable();
                        jQuery('#audio_val').val(localTracks[0].isEnabled);
                        jQuery('#connect-sb-call-audio').hide();
                        jQuery('#disconnect-sb-call-audio').show();
                    })
                    //mute/unmute audio ends

                });


            });
        });
    </script>
<?php
return ob_get_clean();
}
add_shortcode('sb-join-meeting-form', 'sb_join_meeting');

function generate_access_token()
{
    $twilioAccountSid = 'AC9c033c4d217dbbd6ccbab7cde26f1f82';
    $twilioApiKey = 'SKfc3420064d81057235d9602bea61b7be';
    $twilioApiSecret = 'ZTFrwwhNbCBquqg9oMgugAW4SnXx9E9T';
    // Required for Video grant
    $_SESSION["rname"] = $_GET['rname'];
    $_SESSION["identity"] = $_GET['token'];
    $roomName = $_SESSION["rname"];
    // An identifier for your app - can be anything you'd like
    $identity = $_SESSION["identity"];
    // Create access token, which we will serialize and send to the client
    $token = new AccessToken(
        $twilioAccountSid,
        $twilioApiKey,
        $twilioApiSecret,
        3600,
        $identity
    );
    // Create Video grant
    $videoGrant = new VideoGrant();
    $videoGrant->setRoom($roomName);
    // Add grant to token
    $token->addGrant($videoGrant);
    // render token to string
    //  $up_tok = $token;
    $_SESSION['token'] = $token->toJWT();
    global $wpdb;
    $user = wp_get_current_user();
    $role = $user->roles[0];
    $table_name = $wpdb->prefix . "sb_video_app_details";
    if ($role == 'tutor') {
        $wpdb->update($table_name, array(
            "tutor_token" => $_SESSION["identity"],
            "tutor_join_status" => '1'
        ), array('request_id' => $_GET['request_id']));
    } else if ($role == 'student') {

        $wpdb->update($table_name, array(
            "student_token" => $_SESSION["identity"],
            "student_join_status" => '1',
        ), array('request_id' => $_GET['request_id']));
    }
}

function update_meeting_time($request_id){
   
    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT meeting_date, tutuor_id, student_id, meeting_time FROM $tablename WHERE request_id ='$request_id' ");
    $meeting_time = $results[0]->meeting_time;


        // $today_now = '2022-09-28 09:30';
        //$uTimezone = new DateTimeZone( get_current_user_timezone() );
        
        $student_id = $results[0]->student_id;
        $studentTimezone = get_post_meta(get_user_profile_id($student_id), 'student_timezone', true);
        $sTimezone = new DateTimeZone( $studentTimezone );


        //$today_now = new DateTime( "now", $uTimezone ); //new DateTime(  $today_now );//
       
        $today_now = new DateTime( "now", $sTimezone ); //new DateTime(  $today_now );//
        $today_now = $today_now->format('Y-m-d H:i:s');
        $today_string = strtotime($today_now);

    // date_default_timezone_set(get_current_user_timezone());
    // $sCurrentTime = date("Y-m-d H:i:s");

    $sCurrentTime = $today_now;
    $user = wp_get_current_user();
    $role = $user->roles[0];
    if(empty($meeting_time)){
       
        // if ($role == 'tutor') {
        //     $ajust_curr_time = get_adjusted_timestring($results[0]->tutuor_id, $results[0]->student_id, $sCurrentTime);
        //     $wpdb->update($tablename, array(
        //         "meeting_time" => $ajust_curr_time,
        //     ), array('request_id' => $request_id));
        // }else{
        //     $wpdb->update($tablename, array(
        //         "meeting_time" => $sCurrentTime,
        //     ), array('request_id' => $request_id));
        // }

        $wpdb->update($tablename, array(
            "meeting_time" => $sCurrentTime,
        ), array('request_id' => $request_id));
    }

}


function join_room()
{
    
    //echo $_SESSION['token'];
    $roomName = $_SESSION["rname"];
    $identity = $_SESSION["identity"];

    $_SESSION['req_id'] = $_GET['request_id'];
    $request_id = $_SESSION['req_id'];

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT remarks,meeting_time,tutuor_id,student_id FROM $tablename WHERE request_id ='$request_id' ");
    $remarks = $results[0]->remarks;



    //$start = get_adjusted_timestring($results[0]->tutuor_id, $results[0]->student_id, $results[0]->meeting_time); 
    
    $start = $results[0]->meeting_time; 
    
    // date_default_timezone_set(get_current_user_timezone());
    
    
    $meeting_start = new DateTime($start);
    $m_start = $meeting_start->format("Y-m-d H:i:s");
     //$mStart_string = strtotime($meeting_start);
    
    
    
            // $today_now = '2022-09-28 09:30';
            
            $student_id = $results[0]->student_id;
            $studentTimezone = get_post_meta(get_user_profile_id($student_id), 'student_timezone', true);
            $sTimezone = new DateTimeZone( $studentTimezone );
        
            //echo get_current_user_timezone();

            // $uTimezone = new DateTimeZone( get_current_user_timezone() );
            // $today_now = new DateTime( "now", $uTimezone ); 
            //new DateTime(  $today_now );
            //$sTimezone = new DateTimeZone( $studentTimezone );
            
            $today_now = new DateTime( "now", $sTimezone ); 
            $today_now = $today_now->format('Y-m-d H:i:s');
            $today_string = strtotime($today_now);
            $sCurrentTime = $today_now;
    
    
    
    $sTimeDifferenceMs  = round(abs(strtotime($sCurrentTime) - strtotime($m_start)) * 1000, 0);


    // $mWArning = new DateTime($start);
    // $mWArning = $mWArning->modify('+ 5 minutes');
    // $m_WArning = $mWArning->format('Y-m-d H:i:s');
    // //echo $mWArning;
    //  $mWArning_stringms =round(abs(strtotime($m_WArning) - strtotime($m_start)) * 1000, 0);
     
    //  echo $mWArning_stringms;
    // $sTimeDifferenceMs  = round(abs(strtotime($sCurrentTime) - strtotime($m_start)) * 1000, 0);
    // $alertTime =    $mWArning_stringms - $sTimeDifferenceMs;
    // $mEnd = new DateTime($start);
    // //$mEnd = $mEnd->modify('+ 1 hour');
    // $mEnd = $mEnd->modify('+ 6 minutes');
    // $m_End = $mEnd->format('Y-m-d H:i:s');
    // //echo $mEnd;
    // $mEnd_string = round(abs(strtotime($m_End) - strtotime($m_start)) * 1000, 0);





    $meeting_status = get_meeting_status($_GET['req_id'], true);
      if($remarks != 0){
         ob_start();
         ?>
             <div class="sb_video_notice_wrapper" style="display: flex;flex-direction: column;align-items: center;">
                 <div class="image_wrapper" style="max-width: 420px;">
                     <img src="https://fastgrades.net/wp-content/uploads/2022/09/output-onlinepngtools.png">
                 </div>
                 <div class="notice_wrapper" style="margin-bottom: 70px;">
                     <h2><?php echo 'session has terminated! '; ?></h2>
                 </div>
             </div>
         <?php
         return ob_get_clean();
     }else{
         ob_start();
         generate_access_token();
         update_meeting_time($request_id);
         sb_join_mail_notif_fn($request_id);


?>
    <div class="app-container <?php echo $_GET['request_id'];?>">
        <div class="app-main">
            <div class="video-call-wrapper">
                    <?php 
                        $sb_curr_usr_ID = get_current_user_id();
                        $user_info = get_userdata($sb_curr_usr_ID);
                        $first_name = $user_info->first_name;
                        $last_name = $user_info->last_name;
                        $user = wp_get_current_user();
                        $role = $user->roles[0];
                        if ($role == 'tutor') {
                            $msg = 'waiting for student to join';
                        } else if ($role == 'student') {
                           $msg = 'waiting for tutor to join';
                        }
                    ?>
                <!-- Remoteclient media starts -->
                <div class="video-participant local contain" id="contain">
                    <div class="participant-actions">
                        <!-- <button class="btn-mute"></button>
                        <button class="btn-camera"></button> -->
                        <button class="sb-call-watch" id="watch" style="border-radius: 15px;display: flex;background: #32323200; color: #8b0e0e; border:1px solid;">
                            <span class="material-icons">watch_later</span>

                            <div id="timer" class="demo" style="margin: 0px 5px;">00:00:00</div>

                        </button>

                        

                    </div>
                    <div id="snackbar"><?php _e($msg); ?></div>
                    <script>
                        function sb_snackbar() {
                        var x = document.getElementById("snackbar");
                        x.className = "show";
                        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 5000);
                        }
                    </script>
                    <div class="participant" id="remote-media-div" style="display: flex;width: 100%;height: 100%;">
                    </div>

                </div>
                <!-- Remoteclient media ends -->

                <!-- Localclient media starts -->
                <div class="video-participant client" id="myLoVideo">
                    
                    <div class="participant-details" id="loc-parti-dtls">
                        <a href="#" class="name-tagg"><?php _e($first_name.' '.$last_name); ?></a>
                    </div>
                    <div class="participant-actions" id="loc-parti-actn" style="z-index:9999;">
                        <!-- <button class="btn-mute"></button>
                        <button class="btn-camera"></button> -->
                        <button type="button" id="shrink" class="close"  onclick="shrink()">&times;</button>
                        <button type="button" id="expand" class="close" onclick="expand()" style="display:none;">[ ]</button>
                    </div>
                    <div>
                        <div id="local-media"></div>
                    </div>

                </div>
                <!-- Localclient media ends -->
            </div>
            <div id="icon-blur" class="video-call-actions controls">
                <button class="sb-call-audio" id="disconnect-sb-call-audio">
                    <span class="material-icons">mic</span>
                </button>
                <button class="sb-call-audio" id="connect-sb-call-audio" style="display:none">
                    <span class="material-icons">mic_off</span>
                </button>
                <button class="sb-call-video" id="disconnect-sb-call-video">
                    <span class="material-icons">videocam</span>
                </button>
                <button class="sb-call-video" id="connect-sb-call-video" style="display:none">
                    <span class="material-icons">videocam_off</span>
                </button>
                <button class="sb-call-video material-icons " id="connect-sb-screen-share">
                    screen_share
                </button>
                <!-- <button class="sb-call-video material-icons " id="disconnect-sb-screen-share" style="display:none">
                    stop_screen_share
                </button> -->
                <button class="sb-call-end" id="disconnect">
                    <span class="material-icons">call_end</span>
                </button>
            </div>
        </div>
    </div>
    <script>
        //.style.display = "none"; 
            function shrink(){
                console.log('clicked');
                var myLoVideo = document.getElementById("myLoVideo");
                var shrinkBtn = document.getElementById("shrink");
                var expandBtn = document.getElementById("expand");
                var locPartiDtls = document.getElementById("loc-parti-dtls");
                var currWidth = myLoVideo.clientWidth;
                var currHeight = myLoVideo.clientHeight;
                if(currWidth == 100) return false;
                else{
                    
                    myLoVideo.style.width = (currWidth - 100) + "px";
                    myLoVideo.style.height = (currHeight - 100) + "px";
                    shrinkBtn.style.display = "none";
                    locPartiDtls.style.display = "none";
                    expandBtn.style.display = "block";
                    
                }
            }
            function expand() {
                console.log('clicked');
                var myLoVideo = document.getElementById("myLoVideo");
                var shrinkBtn = document.getElementById("shrink");
                var expandBtn = document.getElementById("expand");
                var locPartiDtls = document.getElementById("loc-parti-dtls");
                var currWidth = myLoVideo.clientWidth;
                var currHeight = myLoVideo.clientHeight;
                if (currWidth == 200) return false;
                else {
                    myLoVideo.style.width = (currWidth + 100) + "px";
                    myLoVideo.style.height = (currHeight + 100) + "px";
                    shrinkBtn.style.display = "block";
                    locPartiDtls.style.display = "block";
                    expandBtn.style.display = "none";
                }
            }
    </script>
    <script>
        jQuery(document).ready(function ($) {
            let idleTimer = null;
            let idleState = false;

            function showFoo(time) {
                clearTimeout(idleTimer);
                if (idleState == true) {
                    //
                    $("#icon-blur").removeClass("inactive");
                    $("#loc-parti-actn").removeClass("inactive");
                }
                idleState = false;
                idleTimer = setTimeout(function() {
                    $("#icon-blur").addClass("inactive");
                    $("#loc-parti-actn").addClass("inactive");
                    idleState = true;
                }, time);
            }

            showFoo(2000);

            $(window).mousemove(function(){
                showFoo(2000);
            });
        });
    </script>
    <script>
        // Getting audio/video status
        var AudioStatus = "<?php echo $_GET['audioStatus'] ?>";
        var VideoStatus = "<?php echo $_GET['videoStatus'] ?>";
        jQuery(document).ready(function($) {
            //console.log(AudioStatus, VideoStatus);
            // Setting constants
            const Video = Twilio.Video;
            // Request audio and video tracks
            Video.createLocalTracks().then(function(localTracks) {
                var localMediaContainer = document.getElementById('local-media');
                localTracks.forEach(function(track) {
                    var trackElement = track.attach();
                    localMediaContainer.appendChild(trackElement);
                });
                return Video.connect('<?php echo $_SESSION['token']; ?>', {
                    name: '<?php echo $roomName; ?>',
                    tracks: localTracks,
                    audio: true,
                    video: {
                        height: 720,
                        frameRate: 24,
                        width: 1280
                    }

                }).then(function(room) {
                        //console.log(`Connected to Room: ${room.name}`);
                        const localParticipant = room.localParticipant;
                        // Assign constant
                        const shareScreen = document.getElementById('connect-sb-screen-share');
                        var screenTrack;
                        shareScreen.addEventListener('click', shareScreenHandler);
                        // Function for share screen
                        function shareScreenHandler() {
                            event.preventDefault();
                            if (!screenTrack) {
                                navigator.mediaDevices.getDisplayMedia({
                                    video: {
                                        frameRate: 24,
                                        zoom: true
                                    }
                                }).then(stream => {
                                    screenTrack = new Video.LocalVideoTrack(stream.getTracks()[0]);
                                    // console.log(tracks);
                                    //screenTrack.classList.add('participantZoomed')
                                    room.localParticipant.publishTrack(screenTrack, {
                                        name: 'screen', // Tracks can be named to easily find them later
                                        priority: 'high', // Priority is set to high by the subscriber when the video track is rendered
                                    });
                                    shareScreen.innerHTML = 'stop_screen_share';
                                    screenTrack.mediaStreamTrack.onended = () => {
                                        shareScreenHandler()
                                    };
                                    room.localParticipant.videoTracks.forEach(publication => {
                                        publication.track.disable();

                                    });


                                }).catch(() => {
                                    alert('Could not share the screen.');
                                });

                            } else {
                                room.localParticipant.unpublishTrack(screenTrack);
                                screenTrack.stop();
                                screenTrack = null;
                                shareScreen.innerHTML = 'screen_share';
                                room.localParticipant.videoTracks.forEach(publication => {
                                    publication.track.enable();
                                });

                            }
                        };

                        //Checking Audio Status After Room Connect.
                        if (AudioStatus == "true") {
                            room.localParticipant.audioTracks.forEach(publication => {
                                publication.track.enable();
                            });
                            jQuery('#connect-sb-call-audio').hide();
                            jQuery('#disconnect-sb-call-audio').show();
                        } else if (AudioStatus == "false") {
                            room.localParticipant.audioTracks.forEach(publication => {
                                publication.track.disable();
                            });
                            jQuery('#disconnect-sb-call-audio').hide();
                            jQuery('#connect-sb-call-audio').show();
                        } else if (AudioStatus == " ") {
                            room.localParticipant.audioTracks.forEach(publication => {
                                publication.track.enable();
                            });
                            jQuery('#connect-sb-call-audio').hide();
                            jQuery('#disconnect-sb-call-audio').show();
                        }
                        //Checking Video Status After Room Connect.
                        if (VideoStatus == "true") {
                            room.localParticipant.videoTracks.forEach(publication => {
                                publication.track.enable();
                            });
                            jQuery('#connect-sb-call-video').hide();
                            jQuery('#disconnect-sb-call-video').show();
                        } else if (VideoStatus == "false") {
                            room.localParticipant.videoTracks.forEach(publication => {
                                publication.track.disable();
                            });
                            jQuery('#disconnect-sb-call-video').hide();
                            jQuery('#connect-sb-call-video').show();
                        } else if (VideoStatus == " ") {
                            room.localParticipant.videoTracks.forEach(publication => {
                                publication.track.enable();
                            });

                            jQuery('#connect-sb-call-video').hide();
                            jQuery('#disconnect-sb-call-video').show();
                        }

                        
                        // watch timer starts
                        sb_snackbar();
                        
                        function fetchjoinstaus(){
                            jQuery.ajax({
                                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                method: "POST",
                                data: {
                                    "action": "fetch_join_status"
                                },
                                success: function(resultData) {
                                    //console.log(resultData);
                                    var res = JSON.parse(resultData);
                                    if(res.res_data == 'exit'){
                                        console.log(res.remarks);
                                        
                                        updateRemarks(res.remarks);
                                        if(res.remarks == 1){
                                            alert('Tutor failed to join the current session!');
                                        }else if(res.remarks == 2){
                                            alert('Student failed to join the current session!'); 
                                        }
                                        room.disconnect();
                                        window.location.href = "<?php echo home_url() ?>";
                                        //
                                    }else{
                                        console.log('continue');
                                        updateRemarks(res.remarks);
                                    }
                                },
                                error: function(e) {
                                    console.log(e);
                                },
                            });
                        }
                        function updateRemarks(rem){
                                var remarks = rem;
                                jQuery.ajax({
                                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                    method: "POST",
                                    data: {
                                        "remarks": remarks,
                                        "action": "update_remarks"
                                    },
                                    success: function(resdata) {
                                        console.log(resdata);
                                        //window.location.href = "<?php echo home_url() ?>";
                                    },
                                    error: function(e) {
                                        console.log(e);
                                    },
                                });
                            }
                        jQuery(document).ready(function($){
                            setTimeout(fetchjoinstaus,10*60*1000);
                        });

                        


                        // Attach the Participant's Media to a <div> element.
                        room.on('participantConnected', participant => {
                            console.log(`Participant "${participant.identity}" connected`);
                            fetchjoinstaus();
                            room.participants.forEach(participant => {
                                //console.log(participant.tracks);
                                participant.tracks.forEach(publication => {
                                    if (publication.isSubscribed) {
                                        const track = publication.track;
                                        var trackElement = track.attach();
                                        // console.log('host Connected participant track publication ' + trackElement);
                                        // trackElement.addEventListener('click', () => {
                                        //     zoomTrack(trackElement)
                                        // });
                                        document.getElementById('remote-media-div').appendChild(trackElement);
                                    }
                                });
                                participant.on('trackSubscribed', track => {
                                    var trackElement = track.attach();
                                    // console.log('host Connected participant track subscribed ' + track);
                                    // trackElement.addEventListener('click', () => {
                                    //     zoomTrack(trackElement)
                                    // });
                                    document.getElementById('remote-media-div').appendChild(trackElement);
                                    if (jQuery("#remote-media-div video:nth-child(3)").is(':visible')) {
                                        jQuery("#remote-media-div video:nth-child(2)").css("display", "none");
                                    } else {
                                        jQuery("#remote-media-div video:nth-child(2)").css("display", "block");
                                    }

                                });
                                participant.on('trackUnsubscribed', (track) => {
                                    track.detach().forEach(element => element.remove());
                                    jQuery("#remote-media-div video:nth-child(2)").css("display", "block");
                                });
                            })

                            jQuery.ajax({
                                url: "<?php echo home_url(); ?>/wp-admin/admin-ajax.php",
                                method: "POST",
                                data: {
                                    "room_name": "<?php echo $_GET['rname']; ?>",
                                    "action": "update_room_sid"
                                }
                            });
                            
                            

                        });


                        //console.log(`Participant "${participant.identity}" connected`);
                        room.participants.forEach(participant => {
                            participant.tracks.forEach(publication => {
                                if (publication.track) {
                                    var trackElement = track.attach();
                                    // console.log('guest publication track' + trackElement);
                                    // trackElement.addEventListener('click', () => {
                                    //     //alert('clicked');
                                    //     zoomTrack(trackElement)
                                    // });
                                    document.getElementById('remote-media-div').appendChild(trackElement);

                                }
                            });
                            //on track subscribed.
                            participant.on('trackSubscribed', track => {
                                var trackElement = track.attach();
                                // console.log('guest track subscribed ' + trackElement);
                                // trackElement.addEventListener('click', () => {
                                //     //alert('clicked');
                                //     zoomTrack(trackElement)
                                // });
                                document.getElementById('remote-media-div').appendChild(trackElement).className = "screenshare";
                                if (jQuery("#remote-media-div video:nth-child(3)").is(':visible')) {
                                    jQuery("#remote-media-div video:nth-child(2)").css("display", "none");
                                } else {
                                    jQuery("#remote-media-div video:nth-child(2)").css("display", "block");
                                }
                            });
                            participant.on('trackUnsubscribed', (track) => {
                                track.detach().forEach(element => element.remove());
                                jQuery("#remote-media-div video:nth-child(2)").css("display", "block");
                            });
                        });

                        //function for zoomTrack
                        // function zoomTrack(trackElement) {
                        //     if (!trackElement.classList.contains('participantZoomed')) {
                        //         //console.log(container.childNodes);
                        //         // zoom in
                        //         contain.childNodes.forEach(participant => {

                        //             if (participant.className == 'participant') {
                        //                 participant.childNodes.forEach(track => {
                        //                     console.log(track);
                        //                     if (track === trackElement) {
                        //                         track.classList.add('participantZoomed')
                        //                     }
                        //                 });
                        //             }
                        //         });

                        //     } else {
                        //         contain.childNodes.forEach(participant => {
                        //             if (participant.className == 'participant') {
                        //                 participant.childNodes.forEach(track => {
                        //                     console.log(trackElement);
                        //                     if (track === trackElement) {
                        //                         track.classList.remove('participantZoomed');
                        //                     }

                        //                 });

                        //             }
                        //         });
                        //     }
                        // }

                        // Detach the Participant's Media from <div> element.

                        // trigger alert for remaining to time before room disconnects
                            
                            jQuery(document).ready(function(){
                                 
                                // let Timediff = <?php echo $sTimeDifferenceMs ?>;
                                // let warnTime = <?php echo $mWArning_stringms ?>;
                                // let alertTime = <?php echo $alertTime ?>;
                                // let terminationTime = <?php echo $mEnd_string ?>;
                                $('#timer').stopwatch({startTime: <?php echo $sTimeDifferenceMs ?>}).stopwatch('start');
                                setTimeout(time_remaining, 50*60*1000);
                                setTimeout(terminate_session, 60*60*1000);
                                  
                            });     

                            function time_remaining(){
                                alert('10 minutes remaining');
                            }
                            function terminate_session(){
                                alert('Session has ended!');
                                room.disconnect();
                                setTimeout(() => {
                                    window.location.href = "<?php echo home_url() ?>";
                                }, 5000);
                               
                            }


                            room.on('participantDisconnected', function(participant) {
                                console.log(participant.identity + ' left the Room');
                                terminate_session();
                                // alert('Session has ended!');
                                // room.disconnect();
                                // window.location.href = "<?php echo home_url() ?>";
                                // participant.tracks.forEach(function(track) {
                                //     track.detach().forEach(function(mediaElement) {
                                //     mediaElement.remove();
                                //     });
                                // });
                               
                               
                            });

                        room.on('disconnected', room => {
                            // Detach the local media elements
                            room.localParticipant.tracks.forEach(publication => {
                                const attachedElements = publication.track.detach();
                                attachedElements.forEach(element => element.remove());
                            });
                            // alert('Session has ended!');
                            window.location.href = "<?php echo home_url() ?>";
                        });
                        // Track unsubscribe.

                        // Trigger end call button.
                        $('#disconnect').on('click', () => {
                            
                            room.disconnect();
                            window.location.href = "<?php echo home_url() ?>";
                            //jQuery('#remote-media-div').empty();
                        });
                        //Trigger audio mute
                        $('#disconnect-sb-call-audio').on('click', () => {
                            //alert('triggered');
                            room.localParticipant.audioTracks.forEach(publication => {
                                publication.track.disable();
                            });
                            jQuery('#disconnect-sb-call-audio').hide();
                            jQuery('#connect-sb-call-audio').show();
                        })
                        //Trigger audio unmute
                        $('#connect-sb-call-audio').on('click', () => {
                            //alert('triggered');
                            room.localParticipant.audioTracks.forEach(publication => {
                                publication.track.enable();
                            });
                            jQuery('#connect-sb-call-audio').hide();
                            jQuery('#disconnect-sb-call-audio').show();
                        })

                        //Trigger video hide
                        $('#disconnect-sb-call-video').on('click', () => {
                            //alert('triggered');
                            room.localParticipant.videoTracks.forEach(publication => {
                                publication.track.disable();
                            });
                            jQuery('#disconnect-sb-call-video').hide();
                            jQuery('#connect-sb-call-video').show();
                        })
                        //Trigger video unhide
                        $('#connect-sb-call-video').on('click', () => {
                            //alert('triggered');
                            room.localParticipant.videoTracks.forEach(publication => {
                                publication.track.enable();
                            });
                            jQuery('#connect-sb-call-video').hide();
                            jQuery('#disconnect-sb-call-video').show();
                        })
                    },
                    function(error) {
                        console.error('Unable to connect to Room: ' + error.message);
                    });
            });
        });
    </script>

<?php
    return ob_get_clean();
    }
}
add_shortcode('sb-join-meeting', 'join_room');

add_action('wp_ajax_fetch_join_status', 'fetch_join_status_cb');
add_action('wp_ajax_nopriv_fetch_join_status', 'fetch_join_status_cb');

function fetch_join_status_cb()
{
    if (isset($_POST)) {
        global $wpdb;
        echo $_POST['room_name'];
        $request_id = $_SESSION['req_id'];
        $tablename = $wpdb->prefix . "sb_video_app_details";
        $results = $wpdb->get_results("SELECT tutor_join_status,student_join_status FROM $tablename WHERE request_id = $request_id ");
        if($results[0]->tutor_join_status == 0) {
            $remarks = '1';
        }elseif ($results[0]->student_join_status == 0) {
            $remarks = '2';
        }
        if(($results[0]->tutor_join_status == 1) && ($results[0]->student_join_status == 1)){
            $res_data = 'continue';
            $remarks = '3';
        }else{
            $res_data = 'exit';
        }
        $arr["res_data"]= $res_data;
        $arr["remarks"]= $remarks;
        echo json_encode($arr);
        die();
    }
    
}

add_action('wp_ajax_update_remarks', 'update_remarks_cb');
add_action('wp_ajax_nopriv_update_remarks', 'update_remarks_cb');

function update_remarks_cb(){
    if (isset($_POST[remarks])) {
        global $wpdb;
        $table_name = $wpdb->prefix . "sb_video_app_details";
        $wpdb->update($table_name, array(
            "remarks" => $_POST[remarks],
        ), array('request_id' => $_SESSION['req_id']));
        do_action('sb_video_after_update_remark', $_POST['remarks'], $_SESSION['req_id']);
        die();
    }
}


add_action('wp_ajax_update_room_sid', 'update_room_sid_cb');
add_action('wp_ajax_nopriv_update_room_sid', 'update_room_sid_cb');

function update_room_sid_cb()
{
    $sid = "AC9c033c4d217dbbd6ccbab7cde26f1f82";
    $token = "c5a06e2f29b75e31c30670545e174525";
    $twilio = new Client($sid, $token);

    if (isset($_POST['room_name'])) {
        $roomdetails = $twilio->video->v1->rooms($_POST['room_name'])->fetch();
        $room_sid = $roomdetails->sid;
        global $wpdb;
        $table_name = $wpdb->prefix . "sb_video_app_details";
        $wpdb->update($table_name, array(
            "room_sid" => $room_sid,
        ), array('request_id' => $_SESSION['req_id']));
        die();
    }
}



function participant_details_fn()
{
    if (is_user_logged_in()) {
        // echo 'User ID: ' . get_current_user_id();
        $user_Id = get_current_user_id();
        $user = wp_get_current_user();
        $role = $user->roles[0];
        $user_email = $user->user_email;
    }
    //echo $user_Id;
    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT * FROM $tablename WHERE (tutuor_id = '$user_Id' OR student_id = '$user_Id')ORDER BY id DESC");

    ob_start();
        //var_dump($results);

    $sid = "AC9c033c4d217dbbd6ccbab7cde26f1f82";
    $token = "c5a06e2f29b75e31c30670545e174525";
    $twilio = new Client($sid, $token);
    //date_default_timezone_set("Asia/Kolkata"); 
    foreach ($results as $value) {
         $req_ID =  $value->request_id;
        $post = get_post($req_ID );
        $title= $post->post_title;
        //echo'<pre>';print_r($post); echo'</pre>';
        if ($value->room_sid != "") {
            $participantDetails = $twilio->video->v1->rooms($value->room_sid)->participants->read();
            foreach ($participantDetails as $participant) {
                //if ($participant->identity == $user_email) {
                    $cpd = $twilio->video->v1->rooms($value->room_sid)->participants($participant->sid)->fetch();
                    echo '<div class="row fg_cst_va">';
                    if($title){
                        echo '<div class="col-sm-4"><div class="card">';
                        //echo '<h2> Session Details</h2>';
                        echo '<h2> Booking Title : ' . $title . '</h2>';
                        $user = get_user_by( 'email', $cpd->identity );
                        if ( !empty( $user ) ) {
                            echo '<p> Participant Name : '. $user->first_name.' '.$user->last_name . '</p>';
                        }
                        
                        
                        echo '<h5> Participants Room ID : ' . $cpd->roomSid . '</h5>';
                        echo '<p> Status : ' . $cpd->status . '</p>';
                        // echo '<p>'.$value->request_id.'</p>'
                         $stdt = $cpd->startTime->format('Y-m-d h:i:s A');

                        //echo $tz_to = get_current_user_timezone(); 

                        $uTimezone = new DateTimeZone( get_current_user_timezone() );
                        //$today_now = new DateTime( "now", $uTimezone );
                        //$newDateTime = new DateTime($stdt, $uTimezone);
                        $newDateTime = new DateTime($stdt, new DateTimeZone('UTC'));
                         $newDateTime->setTimezone($uTimezone);
                        $start_time = $newDateTime->format("Y-m-d h:i:s A");

                        $enddt = $cpd->endTime->format('Y-m-d h:i:s A');
                         $dateTime = new DateTime($enddt, new DateTimeZone('UTC'));
                        //$dateTime = new DateTime($enddt, $uTimezone);
                         $dateTime->setTimezone($uTimezone);
                        $end_time = $dateTime->format("Y-m-d h:i:s A");
                        echo '<p> Session Start Time : ' . $start_time . '</p>';
                        echo '<p> Session End Time : ' . $end_time . '</p>';
                        $datetime1 = new DateTime($start_time);
                        $datetime2 = new DateTime($end_time);
                        $interval = $datetime1->diff($datetime2);

                        // $ssT =  new DateTime('2022-11-11 09:02:19 AM');
                        // $eeT = new DateTime('2022-11-11 10:02:19 AM');
                        // $intval = $ssT->diff($eeT);
                        echo "<p> Call duration : ".  $interval->format('%h')." Hour ".  $interval->format('%i')." Minutes ".$interval->format('%s'). " Seconds </p>";
                        // echo "<p> Call duration : ".  $interval->format('%i')." Minutes ".$interval->format('%s'). " Seconds </p>";
                        echo '</div></div>';
                    }
                    echo '</div>';
                //}
            }
        }
    }
    return ob_get_clean();
}
add_shortcode('participant_details', 'participant_details_fn');

function sb_join_mail_notif_fn($request_id){
    //$request_id = 5870;

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT remarks,meeting_date,tutuor_id,student_id FROM $tablename WHERE request_id ='$request_id' ");

        $sb_tutr_id = $results[0]->tutuor_id;
        $sb_tutr_info = get_userdata($sb_tutr_id);
        $sb_tutr_email = $sb_tutr_info->user_email;
        $sb_stu_id = $results[0]->student_id;
        $sb_stu_info = get_userdata($sb_stu_id);
        $sb_stu_email = $sb_stu_info->user_email;

        $sb_curr_usr_ID = get_current_user_id();        
        if($sb_curr_usr_ID == $sb_tutr_id){
            //echo $sb_stu_email;
            $to = $sb_stu_email;
            $subject = 'Session Joining Notification';
            $msg = 'Tutor has joined the session you have 10 mintues to join. (This is an automatically generated email, do not reply. Take action in the fastgrades website application.)';
           $sb_send = wp_mail( $to, $subject, $msg );
        //    if($sb_send){
        //     echo 'true';
        //    }else{
        //     echo 'false';
        //    }
        }elseif ($sb_curr_usr_ID == $sb_stu_id) {
            //echo $sb_tutr_email;
            $to = $sb_tutr_email;
            $subject = 'Session Joining Notification';
            $msg = 'Student has joined the session you have 10 mintues to join. (This is an automatically generated email, do not reply. Take action in the fastgrades website application.)';
            $sb_send = wp_mail( $to, $subject, $msg );
            
        }
}
add_shortcode('sb_join_mail_notif','sb_join_mail_notif_fn' );