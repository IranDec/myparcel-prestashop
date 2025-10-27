<?php

class SadrCodApiClient
{
    private $username;
    private $password;
    private $token;
    private $id_shop;
    private $apiUrl = 'http://sadrcod.com/api';
    private $wsUrl = 'http://sadrcod.com/ws/v005';

    public function __construct($username, $password, $id_shop)
    {
        $this->username = $username;
        $this->password = $password;
        $this->id_shop = $id_shop;
    }

    private function getAuthenticationToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $loginData = array(
            'username' => $this->username,
            'password' => $this->password,
            'type' => 2
        );
        $loginHeaders = array('Content-Type: text/json');

        $response = $this->callAPI('POST', $this->apiUrl . '/security/token/generate', $loginData, $loginHeaders);
        $result = json_decode($response, true);

        if (isset($result['data']['token'])) {
            $this->token = $result['data']['token'];
            return $this->token;
        }

        PrestaShopLogger::addLog('SadrCod API Error: Failed to get token. Response: ' . $response, 3);
        return false;
    }

    public function registerPackage(Order $order)
    {
        $token = $this->getAuthenticationToken();
        if (!$token) {
            return array('success' => false, 'message' => 'Authentication failed.');
        }

        $payload = $this->buildPayloadFromOrder($order);

        $headers = array(
            "X-ATJ-Auth-Token: " . $token,
            'X-ATJ-Username: ' . $this->username,
            'Content-Type: application/json'
        );

        $response = $this->callAPI('POST', $this->wsUrl . '/registerPackage', $payload, $headers);
        $result = json_decode($response, true);

        if ($result === null) {
             return array('success' => false, 'message' => 'Invalid JSON response from API.', 'response' => $response);
        }

        return $result;
    }

    private function buildPayloadFromOrder(Order $order)
    {
        $address = new Address($order->id_address_delivery);
        $customer = new Customer($order->id_customer);
        $products = [];
        foreach ($order->getProducts() as $product) {
            $products[] = [
                'name' => $product['product_name'],
                'price' => (int)$product['unit_price_tax_incl'],
                'weight' => (int)($product['product_weight'] > 0 ? $product['product_weight'] * 1000 : 100), // convert kg to g
                'count' => (int)$product['product_quantity']
            ];
        }

        $packagingWeight = (int)Configuration::get('SADRCOD_PACKAGING_WEIGHT', null, null, $this->id_shop);
        $destinationCityCode = $this->getCityCodeFromAddress($address);

        return array(
            'products' => $products,
            'deliveryType' => 0,
            'packagingWeight' => $packagingWeight,
            'prepayment' => (int)$order->total_paid_tax_incl,
            'destinationCityCode' => $destinationCityCode,
            'customerName' => $address->firstname . ' ' . $address->lastname,
            'customerAddress' => $address->address1 . ' ' . $address->address2,
            'customerPostalCode' => $address->postcode,
            'customerLandPhone' => $address->phone,
            'customerCellPhone' => $address->phone_mobile,
            'customerMessage' => '', // Customer messages are not standard in PrestaShop order
            'discountDelivery' => false,
            'discountService' => false,
            'discountTax' => false,
            'inCash' => ($order->module == 'ps_cashondelivery')
        );
    }

    private function getCityCodeFromAddress(Address $address)
    {
        $stateName = State::getNameById($address->id_state);

        $stateMap = [
            'آذربایجان شرقی' => '41', 'آذربايجان شرقي' => '41',
            'آذربایجان غربی' => '44', 'آذربايجان غربي' => '44',
            'اردبیل' => '45',
            'اصفهان' => '31',
            'البرز' => '26',
            'ایلام' => '84',
            'بوشهر' => '77',
            'تهران' => '21',
            'چهارمحال و بختیاری' => '38',
            'خراسان جنوبی' => '56',
            'خراسان رضوی' => '51',
            'خراسان شمالی' => '58',
            'خوزستان' => '61',
            'زنجان' => '24',
            'سمنان' => '23',
            'سیستان و بلوچستان' => '54',
            'فارس' => '71',
            'قزوین' => '28',
            'قم' => '25',
            'کردستان' => '87',
            'کرمان' => '34',
            'کرمانشاه' => '83',
            'کهگیلویه و بویراحمد' => '74',
            'گلستان' => '17',
            'گیلان' => '13',
            'لرستان' => '66',
            'مازندران' => '11',
            'مرکزی' => '86',
            'هرمزگان' => '76',
            'همدان' => '81',
            'یزد' => '35',
        ];

        if ($stateName && isset($stateMap[$stateName])) {
            return $stateMap[$stateName];
        }

        return Configuration::get('SADRCOD_DEFAULT_CITY_CODE', null, null, $this->id_shop);
    }


    private function callAPI($method, $url, $data = false, $headers = false)
    {
        $curl = curl_init();

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case "GET":
                if ($data) {
                    $url = $url . "?" . http_build_query($data);
                }
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($curl);

        if(curl_errno($curl)){
            PrestaShopLogger::addLog('SadrCod cURL Error: ' . curl_error($curl), 3);
        }

        curl_close($curl);

        return $result;
    }
}
