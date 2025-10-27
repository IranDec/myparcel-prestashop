<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

// Include the API client class
require_once dirname(__FILE__).'/classes/SadrCodApiClient.php';

class SadrCodConnector extends Module
{
    const LOG_TABLE = 'sadrcod_log';

    public function __construct()
    {
        $this->name = 'sadrcodconnector';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Mohammad Babaei';
        $this->author_uri = 'https://adschi.com';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => '1.7.99.99');
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('SadrCod Connector');
        $this->description = $this->l('ارسال سفارشات به API پست صدرکد و دریافت کد رهگیری.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? This will delete all settings and logs.');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->installTab()
        ) {
            return false;
        }

        // Default values will be set per shop
        return $this->installDb();
    }

    public function uninstall()
    {
        Configuration::deleteByName('SADRCOD_USERNAME');
        Configuration::deleteByName('SADRCOD_PASSWORD');
        Configuration::deleteByName('SADRCOD_TARGET_STATES');
        Configuration::deleteByName('SADRCOD_PACKAGING_WEIGHT');
        Configuration::deleteByName('SADRCOD_DEFAULT_CITY_CODE');
        return $this->uninstallDb() && $this->uninstallTab() && parent::uninstall();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminSadrCodLog';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'SadrCod Logs';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminSadrCodLog');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    protected function installDb()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::LOG_TABLE."` (
            `id_log` INT AUTO_INCREMENT PRIMARY KEY,
            `id_shop` INT NOT NULL,
            `id_order` INT NOT NULL,
            `order_reference` VARCHAR(64),
            `status` ENUM('pending','sent','error','done') DEFAULT 'pending',
            `payload` TEXT,
            `response` TEXT,
            `tracking_number` VARCHAR(128),
            `attempts` INT DEFAULT 0,
            `last_attempt` DATETIME NULL,
            INDEX (id_order),
            INDEX (id_shop)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDb()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_.self::LOG_TABLE."`");
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            $username = Tools::getValue('SADRCOD_USERNAME');
            $password = Tools::getValue('SADRCOD_PASSWORD');
            $states = Tools::getValue('SADRCOD_TARGET_STATES');
            $weight = Tools::getValue('SADRCOD_PACKAGING_WEIGHT');
            $city_code = Tools::getValue('SADRCOD_DEFAULT_CITY_CODE');

            if (!$username) {
                $output .= $this->displayError($this->l('Username is required.'));
            } else {
                $id_shop_group = Shop::getContextShopGroupID();
                $id_shop = Shop::getContextShopID();

                Configuration::updateValue('SADRCOD_USERNAME', $username, false, $id_shop_group, $id_shop);
                if ($password) {
                    Configuration::updateValue('SADRCOD_PASSWORD', $password, false, $id_shop_group, $id_shop);
                }
                Configuration::updateValue('SADRCOD_TARGET_STATES', json_encode(is_array($states) ? array_map('intval', $states) : []), false, $id_shop_group, $id_shop);
                Configuration::updateValue('SADRCOD_PACKAGING_WEIGHT', (int)$weight, false, $id_shop_group, $id_shop);
                Configuration::updateValue('SADRCOD_DEFAULT_CITY_CODE', $city_code, false, $id_shop_group, $id_shop);
                $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
            }
        }
        return $output . $this->renderForm() . $this->renderFooter();
    }

    public function renderForm()
    {
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('SadrCod API Settings'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Username'),
                    'name' => 'SADRCOD_USERNAME',
                    'required' => true,
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l('Password'),
                    'name' => 'SADRCOD_PASSWORD',
                    'required' => false,
                    'desc' => $this->l('Leave blank to keep the current password.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Target Order States'),
                    'name' => 'SADRCOD_TARGET_STATES[]',
                    'multiple' => true,
                    'required' => true,
                    'class' => 'chosen',
                    'desc' => $this->l('Select the order state(s) to trigger sending the order to SadrCod.'),
                    'options' => array(
                        'query' => OrderState::getOrderStates($this->context->language->id),
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Default Packaging Weight (grams)'),
                    'name' => 'SADRCOD_PACKAGING_WEIGHT',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'suffix' => 'grams'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Default Destination City Code'),
                    'name' => 'SADRCOD_DEFAULT_CITY_CODE',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Enter the default city code for SadrCod API (e.g., 1 for Tehran).')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;

        $helper->fields_value = $this->getConfigFieldsValues();

        return $helper->generateForm($fields_form);
    }

    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        $states = Configuration::get('SADRCOD_TARGET_STATES', null, $id_shop_group, $id_shop);

        return array(
            'SADRCOD_USERNAME' => Tools::getValue('SADRCOD_USERNAME', Configuration::get('SADRCOD_USERNAME', null, $id_shop_group, $id_shop)),
            'SADRCOD_PASSWORD' => '', // Always empty for security
            'SADRCOD_TARGET_STATES[]' => Tools::getValue('SADRCOD_TARGET_STATES', json_decode($states, true)),
            'SADRCOD_PACKAGING_WEIGHT' => Tools::getValue('SADRCOD_PACKAGING_WEIGHT', Configuration::get('SADRCOD_PACKAGING_WEIGHT', null, $id_shop_group, $id_shop)),
            'SADRCOD_DEFAULT_CITY_CODE' => Tools::getValue('SADRCOD_DEFAULT_CITY_CODE', Configuration::get('SADRCOD_DEFAULT_CITY_CODE', null, $id_shop_group, $id_shop)),
        );
    }

    public function hookActionOrderStatusUpdate($params)
    {
        if (!isset($params['newOrderStatus']) || !isset($params['id_order'])) {
            return;
        }

        $order = new Order((int)$params['id_order']);
        $id_shop = (int)$order->id_shop;

        $newOrderState = $params['newOrderStatus'];

        $targetStates = json_decode(Configuration::get('SADRCOD_TARGET_STATES', null, null, $id_shop) ?: '[]', true);

        if (!is_array($targetStates) || !in_array($newOrderState->id, $targetStates)) {
            return;
        }

        $log_entry = Db::getInstance()->getRow('SELECT `status` FROM `'._DB_PREFIX_.self::LOG_TABLE.'` WHERE `id_order` = '.(int)$order->id." AND `status` = 'done'");
        if ($log_entry) {
            PrestaShopLogger::addLog(sprintf('SadrCodConnector: Order %d has already been successfully sent. Skipping.', $order->id), 1, null, 'Order', $order->id);
            return;
        }

        $username = Configuration::get('SADRCOD_USERNAME', null, null, $id_shop);
        $password = Configuration::get('SADRCOD_PASSWORD', null, null, $id_shop);

        if (empty($username) || empty($password)) {
            PrestaShopLogger::addLog('SadrCodConnector: API credentials are not configured for shop ' . $id_shop, 3, null, 'Module', $this->id);
            return;
        }

        try {
            $apiClient = new SadrCodApiClient($username, $password, $id_shop);

            $this->addOrUpdateLog($order, 'pending', '', '');
            $response = $apiClient->registerPackage($order);
            $this->processApiResponse($response, $order);

        } catch (Exception $e) {
            PrestaShopLogger::addLog('SadrCodConnector: Exception caught: ' . $e->getMessage(), 3, null, 'Order', $order->id);
            $this->addOrUpdateLog($order, 'error', '', $e->getMessage());
        }
    }

    private function processApiResponse($result, Order $order)
    {
        if (isset($result['status']) && $result['status'] === 'success' && !empty($result['data']['barcode'])) {
            $trackingNumber = $result['data']['barcode'];

            $id_order_carrier = $order->getIdOrderCarrier();
            if ($id_order_carrier > 0) {
                $order_carrier = new OrderCarrier($id_order_carrier);
                $order_carrier->tracking_number = $trackingNumber;
                $order_carrier->update();
            }

            $this->addOrUpdateLog($order, 'done', $trackingNumber, json_encode($result));
            PrestaShopLogger::addLog(sprintf('SadrCodConnector: Successfully registered order %s. Tracking: %s', $order->reference, $trackingNumber), 1, null, 'Order', $order->id);

            // Trigger SMS sending hook
            $this->sendTrackingSms($order, $trackingNumber);

        } else {
            $errorMessage = isset($result['message']) ? $result['message'] : 'Unknown error from SadrCod API.';
            $this->addOrUpdateLog($order, 'error', '', json_encode($result));
            PrestaShopLogger::addLog(sprintf('SadrCodConnector: Failed to register order %s. Error: %s', $order->reference, $errorMessage), 3, null, 'Order', $order->id);
        }
    }

    private function addOrUpdateLog(Order $order, $status, $trackingNumber, $response)
    {
        $log_id = Db::getInstance()->getValue('SELECT `id_log` FROM `'._DB_PREFIX_.self::LOG_TABLE.'` WHERE `id_order` = '.(int)$order->id);

        $data = array(
            'id_shop' => (int)$order->id_shop,
            'id_order' => (int)$order->id,
            'order_reference' => pSQL($order->reference),
            'status' => pSQL($status),
            'tracking_number' => pSQL($trackingNumber),
            'response' => pSQL($response),
            'attempts' => 1, // Simplified for now
            'last_attempt' => date('Y-m-d H:i:s'),
        );

        if ($log_id) {
            Db::getInstance()->update(self::LOG_TABLE, $data, 'id_log = '.(int)$log_id);
        } else {
            Db::getInstance()->insert(self::LOG_TABLE, $data);
        }
    }

    private function sendTrackingSms(Order $order, $trackingNumber)
    {
        $customer = new Customer($order->id_customer);
        $address = new Address($order->id_address_delivery);
        $mobile_phone = $address->phone_mobile ?: $customer->phone_mobile;

        if (empty($mobile_phone)) {
            return;
        }

        Hook::exec('actionSadrCodSendSms', array(
            'mobile_phone' => $mobile_phone,
            'tracking_number' => $trackingNumber,
            'order_reference' => $order->reference,
            'customer_name' => $customer->firstname . ' ' . $customer->lastname
        ));
    }

    private function renderFooter()
    {
        $this->context->smarty->assign(array(
            'module_name' => $this->displayName,
            'module_version' => $this->version,
            'author_name' => $this->author,
            'author_uri' => $this->author_uri,
        ));
        return $this->display(__FILE__, 'views/templates/admin/footer.tpl');
    }
}
