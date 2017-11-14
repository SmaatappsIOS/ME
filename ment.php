<?php
error_reporting(0);
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**

*/
class Expertise extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        $this->output->set_header('Cache-Control: no-cache, must-revalidate');
        $this->output->set_header('Cache-Control: post-check=0, pre-check=0', false);
        $this->output->set_header('Pragma: no-cache');
        $this->load->model('Common_model');
        $this->load->helper('date');
    }
    public function index()
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        // If myuser_id not set in session value we can't able to access.
        // else it redirect to index page.
        if ((isset($user_id)) && ($user_id != "")) {
            $data['data']      = $this->Common_model->fetchUserDetails();
            $data['expertise'] = $this->Common_model->get_expertise();
            $this->load->view('expertise_list', $data);
        } else {
            header('Location:' . base_url() . 'SignIn');
        }
    }
    function edit($id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        // If myuser_id not set in session value we can't able to access.
        // else it redirect to index page.
        if ((isset($user_id)) && ($user_id != "")) {
            $data['expertise']  = $this->Common_model->get_an_expertise($id);
            $data['data']       = $this->Common_model->fetchUserDetails();
            $data['topic_list'] = $this->Common_model->get_all_topics();
            $data['topics']     = $this->mongo_db->get('mc_topics');
            if (!empty($_POST)) {
                $this->form_validation->set_rules('title', 'title', 'trim|required');
                $this->form_validation->set_rules('description', 'description', 'trim|required');
                if ($this->form_validation->run() == true) {
                    $this->Common_model->update_expertise($id);
                    redirect('expertise');
                } else {
                    $this->load->view('edit_expertise', $data);
                }
            }
            $this->load->view('edit_expertise', $data);
        } else {
            header('Location:' . base_url() . 'SignIn');
        }
    }
    function update($id)
    {
        $this->Common_model->update_expertise($id);
        redirect('expertise');
    }
    function delete_exp($id)
    {
        $this->Common_model->delete_exp($id);
        redirect('expertise');
    }
    function view($id)
    {
        $data['expertise'] = $this->Common_model->get_an_expertise($id);
        $data['data']      = $this->Common_model->fetchUserDetails();
        $this->load->view('expertise_details', $data);
    }
}