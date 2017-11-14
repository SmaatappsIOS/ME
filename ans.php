<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**

*/
class Answer_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Common_model');
        $this->load->model('Inbox_model');
    }
    function get_answers()
    {
        if ($_POST['keyword'] == "") {
            $value = $this->mongo_db->where(array(
                'is_answered' => '1'
            ))->order_by(array(
                'created_date_time' => -1
            ))->get('mc_question');
        } else {
            $search  = $_POST['keyword'];
            $results = array();
            $data1   = $this->mongo_db->like('question', $search)->where(array(
                'is_answered' => '1'
            ))->get('mc_question');
            $data2   = $this->mongo_db->like('details', $search)->where(array(
                'is_answered' => '1'
            ))->get('mc_question');
            $data3   = $this->mongo_db->like('tags', $search)->where(array(
                'is_answered' => '1'
            ))->get('mc_question');
            $result  = array(
                $data1,
                $data2,
                $data3
            );
            foreach ($result as $key => $val) {
                $results = array_merge($results, $val);
            }
            $value = $this->unique_multidim_array($results, '_id');
        }
        foreach ($value as $key => $val) {
            $answer                        = $this->get_question_answers($val['_id']->{'$id'});
            $value[$key]['answer']         = $answer;
            $value[$key]['question_owner'] = $this->get_user($val['user_id']);
        }
        return $value;
    }
    function get_open_qus()
    {
        if ($_POST['keyword'] == "") {
            $value = $this->mongo_db->where(array(
                'is_answered' => '0'
            ))->order_by(array(
                'created_date_time' => -1
            ))->get('mc_question');
        } else {
            $search  = $_POST['keyword'];
            $results = array();
            $data1   = $this->mongo_db->like('question', $search)->where(array(
                'is_answered' => '0'
            ))->get('mc_question');
            $data2   = $this->mongo_db->like('details', $search)->where(array(
                'is_answered' => '0'
            ))->get('mc_question');
            $data3   = $this->mongo_db->like('tags', $search)->where(array(
                'is_answered' => '0'
            ))->get('mc_question');
            $result  = array(
                $data1,
                $data2,
                $data3
            );
            foreach ($result as $key => $val) {
                $results = array_merge($results, $val);
            }
            $value = $this->unique_multidim_array($results, '_id');
        }
        foreach ($value as $key => $val) {
            $value[$key]['question_owner'] = $this->get_user($val['user_id']);
        }
        return $value;
    }
    function unique_multidim_array($array, $key)
    {
        $temp_array = array();
        $i          = 0;
        $key_array  = array();
        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i]  = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }
    function get_user($user_id)
    {
        $user = $this->mongo_db->where('_id', new MongoId($user_id))->get('mc_user_details');
        return $user;
    }
    function get_a_question($question_id)
    {
        $value = $this->mongo_db->where(array(
            '_id' => new MongoId($question_id)
        ))->order_by(array(
            'created_date_time' => -1
        ))->get('mc_question');
        foreach ($value as $key => $val) {
            $answer                        = $this->get_question_answers($val['_id']->{'$id'});
            $value[$key]['answer']         = $answer;
            $value[$key]['question_owner'] = $this->get_user($val['user_id']);
        }
        return $value;
    }
    function get_question_answers($question_id)
    {
        $value = $this->mongo_db->where(array(
            'question_id' => $question_id
        ))->order_by(array(
            'created_date_time' => -1
        ))->get('mc_answer');
        foreach ($value as $key => $val) {
            $value[$key]['answer_owner'] = $this->get_user($val['user_id']);
        }
        return $value;
    }
    function create_question()
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        $get_tags     = $_POST['tags2'];
        if ($get_tags[0] != "") {
            foreach ($get_tags as $key => $val) {
                $ids   = $val['_id']->{'$id'};
                $value = $this->mongo_db->get_where('mc_topics', array(
                    '_id' => new MongoId($val)
                ));
                $topics .= $value[0]['TOPICS'] . ",";
            }
            $tag_list = substr(trim($topics), 0, -1);
        } else {
            $tag_list = "";
        }
        $question = array(
            'user_id' => $user_id,
            'question' => $_POST['question'],
            'details' => $_POST['details'],
            'tags' => $tag_list,
            'is_anonymously' => $_POST['is_anonymously'],
            'is_answered' => '0',
            'answer_count' => '0',
            'created_date_time' => new DateTime(),
            'date_time' => new DateTime()
        );
        $this->mongo_db->insert('mc_question', $question);
        $question_id = $question['_id']->{'$id'};
        if ($tag_list != "") {
            $this->send_to_inbox($tag_list, $question_id);
        }
    }
    function update_question($question_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        $get_tags     = $_POST['tags2'];
        if ($get_tags[0] != "") {
            foreach ($get_tags as $key => $val) {
                $ids   = $val['_id']->{'$id'};
                $value = $this->mongo_db->get_where('mc_topics', array(
                    '_id' => new MongoId($val)
                ));
                $topics .= $value[0]['TOPICS'] . ",";
            }
            $tag_list = substr(trim($topics), 0, -1);
        } else {
            $tag_list = "";
        }
        $question = array(
            'user_id' => $user_id,
            'question' => $_POST['question'],
            'details' => $_POST['details'],
            'tags' => $tag_list,
            'is_anonymously' => $_POST['is_anonymously'],
            'is_answered' => '0',
            'answer_count' => '0',
            'created_date_time' => new DateTime(),
            'date_time' => new DateTime()
        );
        $this->mongo_db->where('_id', new MongoId($question_id))->set($question)->update('mc_question');
        if ($tag_list != "") {
            $this->send_to_inbox($tag_list, $question_id);
        }
    }
    function send_to_inbox($tag_list, $question_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        $tag          = explode(',', $tag_list);
        foreach ($tag as $key => $val) {
            $user = $this->mongo_db->like('TOPICS', $val)->where_ne('USER_ID', $user_id)->where(array(
                'USER_TYPE' => '2',
                'is_active' => '1'
            ))->get('mc_user_details');
            if (!empty($user)) {
                $friend_id = $user[0]['_id']->{'$id'};
                $message   = $_POST['question'];
                $this->Inbox_model->send_a_message_on_qus($user_id, $friend_id, $message, $question_id);
            }
        }
    }
    function send_to_inbox_mentee($question_id, $friend_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        $message      = $_POST['answer'];
        $this->Inbox_model->send_a_message_on_ans($user_id, $friend_id, $message, $question_id);
    }
    function answer()
    {
        $qus          = $this->mongo_db->get_where('mc_question', array(
            '_id' => new MongoId($_POST['question_id'])
        ));
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        $answer       = array(
            'user_id' => $user_id,
            'question_id' => $_POST['question_id'],
            'answer' => $_POST['answer'],
            'created_date_time' => new DateTime()
        );
        $this->mongo_db->insert('mc_answer', $answer);
        $ans_count = $qus[0]['answer_count'] + 1;
        $tag_list  = $qus[0]['tags'];
        $this->send_to_inbox_mentee($_POST['question_id'], $qus[0]['user_id']);
        $question = array(
            'is_answered' => '1',
            'answer_count' => $ans_count,
            'created_date_time' => new DateTime()
        );
        $this->mongo_db->where('_id', new MongoId($_POST['question_id']))->set($question)->update('mc_question');
    }
}