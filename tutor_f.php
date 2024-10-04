<?php 

// Hook into the quiz submission process of Tutor LMS
add_action('tutor_quiz/attempt_submitted', 'iabse_handle_quiz_failures', 10, 2);

function iabse_handle_quiz_failures($quiz_attempt_id, $quiz_id) {
    // Get the current user's ID
    $user_id = get_current_user_id();
    
    // Get the total number of attempts the user has made on this quiz
    $attempts = tutor_utils()->get_quiz_attempts_by_quiz_id($quiz_id, $user_id);
    
    // Get the current quiz status (pass or fail)
    $quiz_status = tutor_utils()->get_quiz_attempt_status($quiz_attempt_id);
    
    // Check if the user has failed this quiz
    if ($quiz_status === 'failed') {
        
        // Count the number of failures
        $failed_attempts = count($attempts);
        
        // Check if it's the first or second failure
        if ($failed_attempts === 1 || $failed_attempts === 2) {
            // Mark the previous lesson as incomplete
            iabse_mark_previous_lesson_incomplete($quiz_id, $user_id);
        }
        
        // If it's the third failure, reset the whole topic
        if ($failed_attempts === 3) {
            iabse_reset_topic($quiz_id, $user_id);
        }
    }
}

// Function to mark the previous lesson as incomplete
function iabse_mark_previous_lesson_incomplete($quiz_id, $user_id) {
    // Get the previous lesson associated with this quiz
    $lesson_id = tutor_utils()->get_previous_lesson_from_quiz($quiz_id);
    
    // Mark the previous lesson as incomplete
    if ($lesson_id) {
        tutor_utils()->mark_lesson_incomplete($lesson_id, $user_id);
    }
}

// Function to reset the entire topic on the third quiz failure
function iabse_reset_topic($quiz_id, $user_id) {
    // Get the topic associated with this quiz
    $topic_id = tutor_utils()->get_topic_from_quiz($quiz_id);
    
    // Get all lessons and quizzes in the topic
    $lessons = tutor_utils()->get_lessons_in_topic($topic_id);
    $quizzes = tutor_utils()->get_quizzes_in_topic($topic_id);
    
    // Loop through each lesson and mark it as incomplete
    foreach ($lessons as $lesson_id) {
        tutor_utils()->mark_lesson_incomplete($lesson_id, $user_id);
    }
    
    // Loop through each quiz and reset the attempts
    foreach ($quizzes as $quiz_id) {
        tutor_utils()->reset_quiz_attempts($quiz_id, $user_id);
    }
}
