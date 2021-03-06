<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends Admin_Controller {

    /**
     * @var string
     */
    private $_redirect_url;


    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        // load the language files
        $this->lang->load('contact');

        // load the users model
        $this->load->model('contact_model');

        // set constants
        define('REFERRER', "referrer");
        define('THIS_URL', base_url('admin/contact'));
        define('DEFAULT_LIMIT', $this->settings->per_page_limit);
        define('DEFAULT_OFFSET', 0);
        define('DEFAULT_SORT', "created");
        define('DEFAULT_DIR', "DESC");

        // use the url in session (if available) to return to the previous filter/sorted/paginated list
        if ($this->session->userdata(REFERRER))
            $this->_redirect_url = $this->session->userdata(REFERRER);
        else
            $this->_redirect_url = THIS_URL;
    }


    /**************************************************************************************
     * PUBLIC FUNCTIONS
     **************************************************************************************/


    /**
     * Message list page
     */
    public function index()
    {
        // get parameters
        $limit  = $this->input->get('limit')  ? $this->input->get('limit', TRUE)  : DEFAULT_LIMIT;
        $offset = $this->input->get('offset') ? $this->input->get('offset', TRUE) : DEFAULT_OFFSET;
        $sort   = $this->input->get('sort')   ? $this->input->get('sort', TRUE)   : DEFAULT_SORT;
        $dir    = $this->input->get('dir')    ? $this->input->get('dir', TRUE)    : DEFAULT_DIR;

        // get filters
        $filters = array();

        if ($this->input->get('name'))
            $filters['name'] = $this->input->get('name', TRUE);

        if ($this->input->get('email'))
            $filters['email'] = $this->input->get('email', TRUE);

        if ($this->input->get('title'))
            $filters['title'] = $this->input->get('title', TRUE);

        if ($this->input->get('created'))
            $filters['created'] = $this->input->get('created', TRUE);

        // build filter string
        $filter = "";
        foreach ($filters as $key=>$value)
            $filter .= "&{$key}={$value}";

        // save the current url to session for returning
        $this->session->set_userdata(REFERRER, THIS_URL . "?sort={$sort}&dir={$dir}&limit={$limit}&offset={$offset}{$filter}");

        // are filters being submitted?
        if ($this->input->post())
        {
            if ($this->input->post('clear'))
            {
                // reset button clicked
                redirect(THIS_URL);
            }
            else
            {
                // apply the filter(s)
                $filter = "";

                if ($this->input->post('name'))
                    $filter .= "&name=" . $this->input->post('name', TRUE);

                if ($this->input->post('email'))
                    $filter .= "&email=" . $this->input->post('email', TRUE);

                if ($this->input->post('title'))
                    $filter .= "&title=" . $this->input->post('title', TRUE);

                if ($this->input->post('created'))
                    $filter .= "&created=" . $this->input->post('created', TRUE);

                // redirect using new filter(s)
                redirect(THIS_URL . "?sort={$sort}&dir={$dir}&limit={$limit}&offset={$offset}{$filter}");
            }
        }

        // get list
        $messages = $this->contact_model->get_all($limit, $offset, $filters, $sort, $dir);

        // build pagination
        $this->pagination->initialize(array(
            'base_url'   => THIS_URL . "?sort={$sort}&dir={$dir}&limit={$limit}{$filter}",
            'total_rows' => $messages['total'],
            'per_page'   => $limit
        ));

        // setup page header data
        $this->header_data = array_merge_recursive($this->header_data, array(
            'page_title'    => lang('contact title messages_list'),
            'css_files'     => array(
                base_url("themes/admin/css/bootstrap-datepicker.css")
            ),
            'js_files'      => array(
                base_url("themes/admin/js/bootstrap-datepicker.js")
            ),
            'js_files_i18n' => array(
                $this->jsi18n->translate("/application/modules/contact/assets/js/contact_admin_i18n.js")
            )
        ));
        $data = $this->header_data;

        // set content data
        $content_data = array(
            'this_url'   => THIS_URL,
            'messages'   => $messages['results'],
            'total'      => $messages['total'],
            'filters'    => $filters,
            'filter'     => $filter,
            'pagination' => $this->pagination->create_links(),
            'limit'      => $limit,
            'offset'     => $offset,
            'sort'       => $sort,
            'dir'        => $dir
        );

        // load views
        $data['content'] = $this->load->view('admin/emails_list', $content_data, TRUE);
        $this->load->view('admin_template', $data);
    }


    /**
     * Export list to CSV
     */
    function export()
    {
        // get parameters
        $sort = $this->input->get('sort') ? $this->input->get('sort', TRUE) : DEFAULT_SORT;
        $dir  = $this->input->get('dir')  ? $this->input->get('dir', TRUE)  : DEFAULT_DIR;

        // get filters
        $filters = array();

        if ($this->input->get('name'))
            $filters['name'] = $this->input->get('name', TRUE);

        if ($this->input->get('email'))
            $filters['email'] = $this->input->get('email', TRUE);

        if ($this->input->get('title'))
            $filters['title'] = $this->input->get('title', TRUE);

        if ($this->input->get('created'))
            $filters['created'] = $this->input->get('created', TRUE);

        // get all users
        $messages = $this->contact_model->get_all(0, 0, $filters, $sort, $dir);

        if ($messages['total'] > 0)
        {
            // export the file
            array_to_csv($messages['results'], "messages");
        }
        else
        {
            // nothing to export
            $this->session->set_flashdata('error', lang('core error no_results'));
            redirect($this->_redirect_url);
        }

        exit;
    }


    /**************************************************************************************
     * AJAX FUNCTIONS
     **************************************************************************************/


    /**
     * Marks email message as read
     *
     * @param $id
     * @return bool
     */
    public function read($id)
    {
        if ($id)
        {
            $read = $this->contact_model->read($id, $this->user['id']);

            if ($read)
                $results['success'] = "Message updated!";
            else
                $results['error'] = "Could not update status.";
        }
        else
            $results['error'] = "Could not update status.";

        display_json($results);
        exit;
    }

}
