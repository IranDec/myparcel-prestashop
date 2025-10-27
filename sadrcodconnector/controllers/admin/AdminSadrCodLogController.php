<?php

class AdminSadrCodLogController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'sadrcod_log';
        $this->className = 'SadrCodLog'; // Dummy class name, not a real object model
        $this->bootstrap = true;
        $this->lang = false;
        $this->list_no_link = true;

        parent::__construct();

        $this->fields_list = array(
            'id_log' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback' => 'printOrderLink'
            ),
            'order_reference' => array(
                'title' => $this->l('Order Reference'),
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'badge_success' => true,
                'badge_danger' => true,
            ),
            'tracking_number' => array(
                'title' => $this->l('Tracking Number'),
            ),
            'last_attempt' => array(
                'title' => $this->l('Last Attempt'),
                'type' => 'datetime',
            ),
            'actions' => array(
                'title' => $this->l('Actions'),
                'callback' => 'printActions',
                'search' => false,
                'sort' => false,
                'remove_onclick' => true
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->_select = 'a.id_order, a.order_reference, a.tracking_number, a.response, a.last_attempt';
        $this->_select .= ', IF(a.status = "done", 1, 0) as badge_success';
        $this->_select .= ', IF(a.status = "error", 1, 0) as badge_danger';
    }

    public function printOrderLink($id_order, $tr)
    {
        $link = $this->context->link->getAdminLink('AdminOrders', true, [], ['vieworder' => '', 'id_order' => $id_order]);
        return '<a href="' . $link . '" target="_blank">' . $id_order . '</a>';
    }

    public function printActions($value, $row)
    {
        $actions = '';
        if ($row['status'] === 'done') {
            $response = json_decode($row['response'], true);
            // Assuming the label URL is in the response data
            if (isset($response['data']['labelUrl']) && filter_var($response['data']['labelUrl'], FILTER_VALIDATE_URL)) {
                $actions .= '<a class="btn btn-default" href="' . $response['data']['labelUrl'] . '" target="_blank"><i class="icon-print"></i> '.$this->l('Print Label').'</a>';
            }
        }
        return $actions;
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function renderList()
    {
        // Remove add new button
        $this->toolbar_btn = array();
        $list = parent::renderList();

        $footer = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/footer.tpl');

        return $list . $footer;
    }
}
