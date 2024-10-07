<?php

// Hook into the Tutor LMS quiz submission process
function insdfhasdhf()
{
    error_log('Init');
}
add_action('init', 'insdfhasdhf');

function iabse_handle_quiz_failures($attempt_id, $course_id, $user_id) {
    global $wpdb;

    // Log to check if hook is triggered
    error_log('Quiz Failed/Completed Called. Attempt ID: ' . $attempt_id . ' | Course ID: ' . $course_id . ' | User ID: ' . $user_id);

    // Fetch the quiz_id from tutor_quiz_attempts using the attempt_id
    $quiz_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT quiz_id FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id = %d",
            $attempt_id
        )
    );

    if (!$quiz_id) {
        error_log('Failed to retrieve quiz ID for Attempt ID: ' . $attempt_id);
        return;
    }

    // Get quiz attempt data
    $attempt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id = %d AND user_id = %d",
            $attempt_id, $user_id
        )
    );

    if (!$attempt) {
        error_log('Failed to retrieve quiz attempt for Attempt ID: ' . $attempt_id . ' | Quiz ID: ' . $quiz_id);
        return;
    }

    // Calculate quiz status based on earned marks
    $passing_percentage = 80; // Default percentage needed to pass
    $total_marks = $attempt->total_marks;
    $earned_marks = $attempt->earned_marks;
    $earned_percentage = ($earned_marks / $total_marks) * 100;

    // Determine pass or fail
    $quiz_status = ($earned_percentage >= $passing_percentage) ? 'passed' : 'failed';

    error_log('Quiz Attempt Result: ' . $quiz_status . ' | Earned: ' . $earned_percentage . '%');

    // Get the number of failed attempts for this quiz
    $total_attempts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id = %d AND user_id = %d",
            $quiz_id, $user_id
        )
    );

    $failed_attempts = 0;
    foreach ($total_attempts as $attempt) {
        $earned_percentage = ($attempt->earned_marks / $attempt->total_marks) * 100;
        if ($earned_percentage < $passing_percentage) {
            $failed_attempts++;
        }
    }

    error_log('Total Failed Attempts: ' . $failed_attempts);

    // Handle quiz failure conditions
    if ($quiz_status === 'failed') {
        if ($failed_attempts === 1 || $failed_attempts === 2) {
            iabse_mark_previous_lesson_incomplete($quiz_id, $user_id);
        } elseif ($failed_attempts === 3) {
            iabse_reset_topic($quiz_id, $user_id);
        }
    }
}

function iabse_mark_previous_lesson_incomplete($quiz_id, $user_id) {
    global $wpdb;

    error_log('Marking Previous Lesson Incomplete. Quiz ID: ' . $quiz_id . ' | User ID: ' . $user_id);

    // Get the course ID associated with the quiz
    $course_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'tutor_quiz'",
            $quiz_id
        )
    );

    if (!$course_id) {
        error_log('No course found for the given Quiz ID.');
        return;
    }

    error_log('Course ID found: ' . $course_id);

    // Get all lessons and quizzes in this course, sorted by menu_order
    $course_contents = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_type, post_title FROM {$wpdb->posts} WHERE post_parent = %d AND post_type IN ('lesson', 'tutor_quiz') ORDER BY menu_order ASC",
            $course_id
        )
    );

    // Log the full course contents for debugging
    error_log(print_r($course_contents, true));

    // Find the lesson that comes before the current quiz
    $previous_lesson_id = null;
    foreach ($course_contents as $index => $content) {
        if ($content->ID == $quiz_id) {
            if ($index > 0 && $course_contents[$index - 1]->post_type == 'lesson') {
                $previous_lesson_id = $course_contents[$index - 1]->ID;
            }
            break;
        }
    }

    if ($previous_lesson_id) {
        error_log('Previous Lesson ID: ' . $previous_lesson_id);

        // Check if the lesson is marked as completed
        $is_completed = get_user_meta($user_id, '_tutor_completed_lesson_id_' . $previous_lesson_id, true);
        if ($is_completed) {
            error_log('Lesson is marked as completed. Now resetting...');

            // Delete the user meta that marks the lesson as completed
            delete_user_meta($user_id, '_tutor_completed_lesson_id_' . $previous_lesson_id);

            error_log('Marked Lesson ID: ' . $previous_lesson_id . ' as incomplete for User ID: ' . $user_id);
        } else {
            error_log('Lesson was not marked as completed for User ID: ' . $user_id);
        }
    } else {
        error_log('No previous lesson found before the quiz in the course.');
    }
}


function iabse_reset_topic($quiz_id, $user_id) {
    global $wpdb;

    error_log('Resetting Topic for Quiz ID: ' . $quiz_id . ' | User ID: ' . $user_id);

    // Get the course ID related to the quiz
    $course_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'tutor_quiz'",
            $quiz_id
        )
    );

    if (!$course_id) {
        error_log('Course not found for Quiz ID: ' . $quiz_id);
        return;
    }

    // Get all lessons in the course
    $lessons = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lesson' AND post_parent = %d",
            $course_id
        )
    );

    // Loop through each lesson and mark it as incomplete
    foreach ($lessons as $lesson_id) {
        
        iabse_mark_lesson_incomplete($lesson_id, $user_id);
    }

    // Get all quizzes in the course
    $quizzes = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'tutor_quiz' AND post_parent = %d",
            $course_id
        )
    );

    // Loop through each quiz and reset attempts
    foreach ($quizzes as $quiz_id) {
        iabse_reset_quiz_attempts($quiz_id, $user_id);
    }

    error_log('Topic reset completed for Course ID: ' . $course_id . ' and User ID: ' . $user_id);
}

function iabse_mark_lesson_incomplete($lesson_id, $user_id) {
    global $wpdb;

    // Log the lesson ID and user ID
    error_log('Marking Lesson Incomplete - Lesson ID: ' . $lesson_id . ' | User ID: ' . $user_id);

    // Check if the lesson is marked as completed by checking the user meta
    $is_completed = get_user_meta($user_id, '_tutor_completed_lesson_id_' . $lesson_id, true);

    if ($is_completed) {
        error_log('Lesson is marked as completed. Now resetting...');

        // Delete the user meta that marks the lesson as completed
        delete_user_meta($user_id, '_tutor_completed_lesson_id_' . $lesson_id);

        error_log('Marked Lesson as Incomplete. Lesson ID: ' . $lesson_id . ' | User ID: ' . $user_id);
    } else {
        error_log('Lesson was not marked as completed for User ID: ' . $user_id);
    }
}

function iabse_reset_quiz_attempts($quiz_id, $user_id) {
    global $wpdb;

    // Reset quiz attempts for the user
    $wpdb->delete(
        $wpdb->prefix . 'tutor_quiz_attempts',
        array(
            'quiz_id' => $quiz_id,
            'user_id' => $user_id
        ),
        array('%d', '%d')
    );

    error_log('Reset quiz attempts for Quiz ID: ' . $quiz_id . ' | User ID: ' . $user_id);
}

add_action('tutor_quiz/attempt_ended', 'iabse_handle_quiz_failures', 10, 3);
