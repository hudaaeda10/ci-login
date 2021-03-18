<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index()
    {
        // agar tak bisa ke auth ketika login
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $data['title'] = 'Login Page';

        // validation input login
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
        $this->form_validation->set_rules('password', 'Password', 'required|trim');
        if ($this->form_validation->run() ==  FALSE) {
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            $this->_login();
        }
    }

    private function _login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        // jika user ada
        if ($user) {
            if ($user['is_activated'] == 1) {                                                                   // jika user sudah activated
                if (password_verify($password, $user['password'])) {                            // jika user sesuai
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    $this->session->set_userdata($data);
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Your email or your password wrong</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Your email has not been activated</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Email is not registered </div>');
            redirect('auth');
        }
    }

    public function registration()
    {
        // agar tak bisa ke auth ketika login
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $data['title'] = 'CI Login Website';
        // rule form validation
        $this->form_validation->set_rules('name', 'Name', 'required|trim');                         //trim agar spasi di awal dan di akhir tidak masuk database
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|is_unique[user.email]');
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', ['matches' => 'Password dont matches!', 'min_length' => 'Password too sort!']);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');
        // form validation
        if ($this->form_validation->run() ==  FALSE) {
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => htmlspecialchars($email),
                'image' => 'default.png',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_activated' => 0,
                'date_created' => time()
            ];
            // siapkan token
            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('user', $data);
            $this->db->insert('user_token', $user_token);
            $this->_sendEmail($token, 'verify');
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert"> Please Verify your account </div>');
            redirect('auth');
        }
    }

    private function _sendEmail($token, $type)
    {
        $config = [
            'protocol'      => 'smtp',
            'smtp_host'   => 'ssl://smtp.googlemail.com',
            'smtp_user'   => 'xanastren19@gmail.com',
            'smtp_pass'   => 'codelyoko',
            'smtp_port'   => 465,
            'mailtype'   => 'html',
            'charset'   => 'utf-8',
            'newline'   => "\r\n"
        ];

        $this->load->library('email', $config);
        $this->email->initialize($config);
        $this->email->from('xanastren19@gmail.com', 'Web Programming Login');
        $this->email->to($this->input->post('email'));
        if ($type == 'verify') {
            $this->email->subject('Account Verify');
            $this->email->message('Click this link to verify your account: <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '"> active </a>');
        }
        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_activated', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');
                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">' . $email . 'Your account has been verify! Please Login  </div>');  // berhasil verify
                    redirect('auth');
                    $this->db->delete('user_token', ['email' => $email]);
                } else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Activation failed! your token is expired  </div>');  // memastikan token sesuai
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Activation failed! your token wrong  </div>');  // memastikan token sesuai
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Activation failed! your account wrong  </div>');    // memastikan email benar atau tidak
            redirect('auth');
        }
    }


    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert"> You have been logged out!  </div>');
        redirect('auth');
    }

    public function blocked()
    {
        $data['title'] = 'Blocked Page';
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('auth/blocked');
        $this->load->view('templates/footer');
    }
}
