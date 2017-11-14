<?php
error_reporting(0);
defined('BASEPATH') OR exit('No direct script access allowed');
class Callrequest extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        $this->output->set_header('Cache-Control: no-cache, must-revalidate');
        $this->output->set_header('Cache-Control: post-check=0, pre-check=0', false);
        $this->output->set_header('Pragma: no-cache');
        $this->load->model('Search_model');
        $this->load->model('Common_model');
        $this->load->model('Call_model');
        $this->load->model('Appointment_model');
    }
    function create($mendor_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        if ((isset($user_id)) && ($user_id != "")) {
            if ($user_id != $mendor_id) {
                if ($mendor_id != "") {
                    if (!empty($_POST['reason'])) {
                        $data['paypal_id']  = 'mentorengage1@gmail.com';
                        $data['paypal_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
                        $request_id         = $this->Call_model->create_call_request();
                        $data['mendor_id']  = $mendor_id;
                        $data['mentor']     = $this->Common_model->get_mentor_details($mendor_id);
                        $data['calls']      = $this->Call_model->get_a_request($request_id);
                        $amount             = $this->Call_model->get_pay_amount($request_id);
                        if ($data['calls'][0]['apply_percentage'] == "") {
                            $to_pay = $amount;
                        } else {
                            $reduce_amount = ($amount * $data['calls'][0]['apply_percentage']) / 100;
                            $to_pay        = $amount - $reduce_amount;
                        }
                        $data['pay_amount'] = $to_pay;
                        $data['request_id'] = $request_id;
                        $this->load->view('call_request_payment', $data);
                    } else {
                        $data['title']     = $_POST['title'];
                        $data['exp_id']    = $_POST['exp_id'];
                        $data['mendor_id'] = $mendor_id;
                        $data['mentor']    = $this->Common_model->get_mentor_details($mendor_id);
                        $data['calender']  = $this->Appointment_model->get_active_calender($mendor_id);
                        //die();
                        $this->load->view('call_request', $data);
                    }
                } else {
                    header('Location:' . base_url() . 'SignIn');
                }
            } else {
                header('Location:' . base_url() . 'SignIn');
            }
        } else {
            header('Location:' . base_url() . 'SignIn');
        }
    }
    function suggest($call_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        if ((isset($user_id)) && ($user_id != "")) {
            $data['calls']     = $this->Call_model->get_a_request($call_id);
            $mendor_id         = $data['calls'][0]['mendor_id'];
            $data['call_id']   = $call_id;
            $data['mendor_id'] = $mendor_id;
            $data['mentor']    = $this->Common_model->get_mentor_details($mendor_id);
            $data['calender']  = $this->Appointment_model->get_active_calender($mendor_id);
            if (!empty($_POST)) {
                // print_r($_POST);
                $request_id = $this->Call_model->update_call_request($call_id);
                redirect('calls/view/' . $call_id);
            } else {
                $this->load->view('call_request_edit', $data);
            }
        } else {
            header('Location:' . base_url() . 'SignIn');
        }
    }
    function payment($request_id)
    {
        $data['calls'] = $this->Call_model->get_a_request($request_id);
        $call          = $this->Call_model->payment_request($request_id, $data['calls'][0]['request_user_id']);
        redirect('callrequest/success/' . $request_id);
    }
    function payfail($request_id)
    {
        $data['paypal_id']  = 'mentorengage1@gmail.com';
        $data['paypal_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        $data['calls']      = $this->Call_model->get_a_request($request_id);
        $data['mendor_id']  = $data['calls'][0]['mendor_id'];
        $data['mentor']     = $this->Common_model->get_mentor_details($data['calls'][0]['mendor_id']);
        $data['request_id'] = $request_id;
        if ($data['calls'][0]['extra'] == "1") {
            $data['pay_amount'] = $this->Call_model->get_excess_amount($request_id, $data['calls'][0]['request_user_id']);
        } else {
            $data['pay_amount'] = $this->Call_model->get_pay_amount($request_id);
        }
        $this->load->view('call_request_fail_payment', $data);
    }
    function success($request_id)
    {
        $user_profile = $this->session->all_userdata();
        $user_id      = $user_profile['USER_ID'];
        if ((isset($user_id)) && ($user_id != "")) {
            $data['calls']     = $this->Call_model->get_a_request($request_id);
            $data['mendor_id'] = $data['calls'][0]['mendor_id'];
            $data['mentor']    = $this->Common_model->get_mentor_details($data['calls'][0]['mendor_id']);
            $this->load->view('call_request_success', $data);
        } else {
            header('Location:' . base_url() . 'SignIn');
        }
    }
}