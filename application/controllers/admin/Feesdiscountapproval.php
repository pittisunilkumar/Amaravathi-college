<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
ob_start();
class Feesdiscountapproval extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('generate_certificate', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Certificate');
        $this->session->set_userdata('sub_menu', 'admin/generatecertificate');

        $certificateList         = $this->feediscount_model->get();
        $data['certificateList'] = $certificateList;
        $progresslist            = $this->customlib->getProgress();
        $data['progresslist']    = $progresslist;
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/feediscount/feesdiscountapproval', $data);
        $this->load->view('layout/footer', $data);
        
    }

    public function search()
    {
        $this->session->set_userdata('top_menu', 'Certificate');
        $this->session->set_userdata('sub_menu', 'admin/generatecertificate');

        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $certificateList         = $this->feediscount_model->get();
        $progresslist            = $this->customlib->getProgress();
        $data['progresslist']    = $progresslist;
        $data['certificateList'] = $certificateList;
        $button                  = $this->input->post('search');
        if ($this->input->server('REQUEST_METHOD') == "GET") {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/feediscount/feesdiscountapproval', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $class       = $this->input->post('class_id');
            $section     = $this->input->post('section_id');
            $disstatus   = $this->input->post('progress_id');
            $search      = $this->input->post('search');
            $certificate = $this->input->post('certificate_id');
            if (isset($search)) {
                $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('certificate_id', $this->lang->line('certificate'), 'trim|required|xss_clean');
                if ($this->form_validation->run() == false) {

                } else {

                    $data['searchby']          = "filter";
                    $data['class_id']          = $this->input->post('class_id');
                    $data['section_id']        = $this->input->post('section_id');
                    $certificate               = $this->input->post('certificate_id');
                    $certificateResult         = $this->feediscount_model->get($certificate);
                    $data['certificateResult'] = $certificateResult;
                    $resultlist                = $this->student_model->searchByClassSectionAnddiscountStatus($class, $section,$disstatus);
                    $data['resultlist']        = $resultlist;
                    $data['discountstat'] = $disstatus;
                    $title                     = $this->classsection_model->getDetailbyClassSection($data['class_id'], $data['section_id']);
                    $data['title']             = $this->lang->line('std_dtl_for') . ' ' . $title['class'] . "(" . $title['section'] . ")";
                }
            }
            $data['sch_setting'] = $this->sch_setting_detail;
            $this->load->view('layout/header', $data);
            $this->load->view('admin/feediscount/feesdiscountapproval', $data);
            $this->load->view('layout/footer', $data);
        }
    }

    public function generate($student, $class, $certificate)
    {
        $certificateResult         = $this->Generatecertificate_model->getcertificatebyid($certificate);
        $data['certificateResult'] = $certificateResult;
        $resultlist                = $this->student_model->searchByClassStudent($class, $student);
        $data['resultlist']        = $resultlist;

        $this->load->view('admin/certificate/transfercertificate', $data);
    }

    public function generatemultiple()
    {

        $studentid           = $this->input->post('data');
        $student_array       = json_decode($studentid);
        $certificate_id      = $this->input->post('certificate_id');
        $class               = $this->input->post('class_id');
        foreach ($student_array as $key => $value) {
            $item['student_session_id']=$value->student_id;
            $item['fees_discount_id']=$certificate_id;
            $temp=$this->feediscount_model->allotdiscount($item);
            $this->feediscount_model->updateapprovalstatus($certificate_id,$value->student_id,1);
        }
        
        redirect('admin/feesdiscountapproval/index');
        

    }


    public function dismissapprovalgeneratemultiple()
    {

        $studentid           = $this->input->post('data');
        $student_array       = json_decode($studentid);
        $certificate_id      = $this->input->post('certificate_id');
        $class               = $this->input->post('class_id');
        foreach ($student_array as $key => $value) {
            $this->feediscount_model->updateapprovalstatus($certificate_id,$value->student_id,2);
        }
        
        redirect('admin/feesdiscountapproval/index');
        
    }


    public function dismissapprovalsingle()
    {

        $studentid           = $this->input->post('data');
        $certificate_id      = $this->input->post('certificate_id');
        $class               = $this->input->post('class_id');
        $this->feediscount_model->updateapprovalstatus($certificate_id,$studentid,2);
        redirect('admin/feesdiscountapproval/index');
        
    }

    public function approvalsingle()
    {

        $studentid           = $this->input->post('data');
        $certificate_id      = $this->input->post('certificate_id');
        $class               = $this->input->post('class_id');
        $item['student_session_id']=$studentid;
        $item['fees_discount_id']=$certificate_id;
        // $temp=$this->feediscount_model->allotdiscount($item);
        $this->feediscount_model->updateapprovalstatus($certificate_id,$studentid,1);
        
        redirect('admin/feesdiscountapproval/index');
        
    }



    public function addstudentfee()
    {

        $studentid=$this->input->post('student_session_id');
        $fee_groups_feetype_id  = $this->input->post('fee_groups_feetype_id');

        $temp =$this->feediscount_model->getfeetypeid($studentid,$fee_groups_feetype_id);


        $staff_record = $this->staff_model->get($this->customlib->getStaffID());
        $collected_by             = $this->customlib->getAdminSessionUserName() . "(" . $staff_record['employee_id'] . ")";
        $json_array               = array(
            'amount'          => convertCurrencyFormatToBaseAmount($this->input->post('amount')),
            'amount_discount' => convertCurrencyFormatToBaseAmount($this->input->post('amount_discount')),
            'amount_fine'     => convertCurrencyFormatToBaseAmount($this->input->post('amount_fine')),
            'date'            => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date'))),
            'description'     => $this->input->post('description'),
            'collected_by'    => $collected_by,
            'payment_mode'    => $this->input->post('payment_mode'),
            'received_by'     => $staff_record['id'],
        );


        
        
        
        $student_fees_master_id = $temp['id'];
        $transport_fees_id      = $this->input->post('transport_fees_id');


        $data = array(
            
            'student_fees_master_id' => $student_fees_master_id,
            'fee_groups_feetype_id'  => $fee_groups_feetype_id,
            'amount_detail'          => $json_array,
        );

    
        
        $send_to            = $this->input->post('guardian_phone');
        // $email              = $this->input->post('guardian_email');
        // $parent_app_key     = $this->input->post('parent_app_key');
        // $student_session_id = $this->input->post('student_session_id');
        $inserted_id        = $this->studentfeemaster_model->discount_fee_deposit($data, $send_to, $student_fees_discount_id);

        
        echo json_encode(['status' => 'success', 'message' => $inserted_id]);
        exit();





















        // $this->form_validation->set_rules('student_fees_master_id', $this->lang->line('fee_master'), 'required|trim|xss_clean');
        // $this->form_validation->set_rules('date', $this->lang->line('date'), 'required|trim|xss_clean');
        // $this->form_validation->set_rules('fee_groups_feetype_id', $this->lang->line('student'), 'required|trim|xss_clean');
        // $this->form_validation->set_rules('amount', $this->lang->line('amount'), 'required|trim|xss_clean|numeric|callback_check_deposit');
        // $this->form_validation->set_rules('amount_discount', $this->lang->line('discount'), 'required|trim|numeric|xss_clean');
        // $this->form_validation->set_rules('amount_fine', $this->lang->line('fine'), 'required|trim|numeric|xss_clean');
        // $this->form_validation->set_rules('payment_mode', $this->lang->line('payment_mode'), 'required|trim|xss_clean');

        // if ($this->form_validation->run() == false) {
        //     $data = array(
        //         'amount'                 => form_error('amount'),
        //         'student_fees_master_id' => form_error('student_fees_master_id'),
        //         'fee_groups_feetype_id'  => form_error('fee_groups_feetype_id'),
        //         'amount_discount'        => form_error('amount_discount'),
        //         'amount_fine'            => form_error('amount_fine'),
        //         'payment_mode'           => form_error('payment_mode'),
        //         'date'           => form_error('date'),
        //     );
        //     $array = array('status' => 'fail', 'error' => $data);
        //     echo json_encode($array);
        // } else {

        //     $staff_record = $this->staff_model->get($this->customlib->getStaffID());

        //     $collected_by             = $this->customlib->getAdminSessionUserName() . "(" . $staff_record['employee_id'] . ")";
        //     $student_fees_discount_id = $this->input->post('student_fees_discount_id');
        //     $json_array               = array(
        //         'amount'          => convertCurrencyFormatToBaseAmount($this->input->post('amount')),
        //         'amount_discount' => convertCurrencyFormatToBaseAmount($this->input->post('amount_discount')),
        //         'amount_fine'     => convertCurrencyFormatToBaseAmount($this->input->post('amount_fine')),
        //         'date'            => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date'))),
        //         'description'     => $this->input->post('description'),
        //         'collected_by'    => $collected_by,
        //         'payment_mode'    => $this->input->post('payment_mode'),
        //         'received_by'     => $staff_record['id'],
        //     );

        //     $student_fees_master_id = $this->input->post('student_fees_master_id');
        //     $fee_groups_feetype_id  = $this->input->post('fee_groups_feetype_id');
        //     $transport_fees_id      = $this->input->post('transport_fees_id');
        //     $fee_category           = $this->input->post('fee_category');

        //     $data = array(
        //         'fee_category'           => $fee_category,
        //         'student_fees_master_id' => $this->input->post('student_fees_master_id'),
        //         'fee_groups_feetype_id'  => $this->input->post('fee_groups_feetype_id'),
        //         'amount_detail'          => $json_array,
        //     );

        //     if ($transport_fees_id != 0 && $fee_category == "transport") {
        //         $mailsms_array                    = new stdClass();
        //         $data['student_fees_master_id']   = null;
        //         $data['fee_groups_feetype_id']    = null;
        //         $data['student_transport_fee_id'] = $transport_fees_id;

        //         $mailsms_array                 = $this->studenttransportfee_model->getTransportFeeMasterByStudentTransportID($transport_fees_id);
        //         $mailsms_array->fee_group_name = $this->lang->line("transport_fees");
        //         $mailsms_array->type           = $mailsms_array->month;
        //         $mailsms_array->code           = "";
        //     } else {

        //         $mailsms_array = $this->feegrouptype_model->getFeeGroupByIDAndStudentSessionID($this->input->post('fee_groups_feetype_id'), $this->input->post('student_session_id'));

        //         if($mailsms_array->is_system){
        //              $mailsms_array->amount=$mailsms_array->balance_fee_master_amount;  
        //         }

        //     }

        //     $action             = $this->input->post('action');
        //     $send_to            = $this->input->post('guardian_phone');
        //     $email              = $this->input->post('guardian_email');
        //     $parent_app_key     = $this->input->post('parent_app_key');
        //     $student_session_id = $this->input->post('student_session_id');
        //     $inserted_id        = $this->studentfeemaster_model->fee_deposit($data, $send_to, $student_fees_discount_id);

        //     $print_record = array();
        //     if ($action == "print") {
        //         $receipt_data           = json_decode($inserted_id);
        //         $data['sch_setting']    = $this->sch_setting_detail;
                
        //         $student                = $this->studentsession_model->searchStudentsBySession($student_session_id);
        //         $data['student']        = $student;
        //         $data['sub_invoice_id'] = $receipt_data->sub_invoice_id;
              
        //         $setting_result         = $this->setting_model->get();
        //         $data['settinglist']    = $setting_result;

        // if ($transport_fees_id != 0 && $fee_category == "transport") {

        //     $fee_record = $this->studentfeemaster_model->getTransportFeeByInvoice($receipt_data->invoice_id, $receipt_data->sub_invoice_id);
        //      $data['feeList']        = $fee_record;
        //         $print_record = $this->load->view('print/printTransportFeesByName', $data, true);

        // } else {

        //      $fee_record             = $this->studentfeemaster_model->getFeeByInvoice($receipt_data->invoice_id, $receipt_data->sub_invoice_id);
        //        $data['feeList']        = $fee_record;
        //         $print_record = $this->load->view('print/printFeesByName', $data, true);
        // }
        //     }

        //     $mailsms_array->invoice            = $inserted_id;
        //     $mailsms_array->student_session_id = $student_session_id;
        //     $mailsms_array->contact_no         = $send_to;
        //     $mailsms_array->email              = $email;
        //     $mailsms_array->parent_app_key     = $parent_app_key;
        //     $mailsms_array->fee_category       = $fee_category;

        //     $this->mailsmsconf->mailsms('fee_submission', $mailsms_array);

        //     $array = array('status' => 'success', 'error' => '', 'print' => $print_record);
        //     echo json_encode($array);
        // }
    }


}


?>






