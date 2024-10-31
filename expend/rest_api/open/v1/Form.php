<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;

class Form extends Base
{


    public function submitFeedback(\WP_REST_Request $request) {
        // Retrieve submitted data
        $name = $request->get_param('name');
        $email = $request->get_param('email');
        $content = $request->get_param('content');
        if (empty($name) || empty($email) || empty($content)) {
            return $this->resultError('One or more fields are empty');
        }
        if (!is_email($email)) {
            return $this->resultError('Invalid email address');
        }
        // Check content length
        if (mb_strlen($name) > 120) {
            return $this->resultError('Content exceeds maximum length of 600 characters');
        }
        // Check content length
        if (mb_strlen($content) > 600) {
            return $this->resultError('Content exceeds maximum length of 600 characters');
        }
        $user_id = get_current_user_id();
        $transient_name = 'last_feedback_time_' . $user_id;
        $last_submission_time = get_transient($transient_name);
        if ($last_submission_time) {
            $current_time = current_time('timestamp');
            if ($current_time - $last_submission_time < 60) {
                return $this->resultError('You can only submit feedback once every minute.');
            }
        }
        // Sanitize and validate data
        $name = sanitize_text_field($name);
        $email = sanitize_email($email);
        $content = sanitize_textarea_field($content);
        // Insert feedback into database
        $transient_name = 'last_feedback_time_' . $user_id;
        set_transient($transient_name, current_time('timestamp'), 60);

        $rs = DbHelper::name('tsim_feedback_list')->insertData(array(
            'name' => $name,
            'email' => $email,
            'content' => $content,
            'user_id' => $user_id,
        ));
        // Return response
        if($rs){
            return $this->result([], "Feedback submitted successfully");
        }
        return $this->result([], "Feedback submitted fail");
    }
}