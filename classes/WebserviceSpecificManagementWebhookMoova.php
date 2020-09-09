<?php

include_once(_PS_MODULE_DIR_ . '/moova/Helper/Log.php');
include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');

class WebserviceSpecificManagementWebhookMoova implements WebserviceSpecificManagementInterface
{
    protected $objOutput;
    protected $output;
    protected $wsObject;

    public function __construct()
    {
        $this->moova = new MoovaSdk();
    }

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;
        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->objOutput->getObjectRender()->overrideContent($this->output);
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        return $this;
    }

    /**
     * @param WebserviceOutputBuilderCore $obj
     * @return WebserviceSpecificManagementInterface
     */

    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;
        return $this;
    }

    public function manage()
    {
        $body = json_decode(file_get_contents('php://input'), true);

        $trackingNumber = pSQL($body['internalCode']);

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "orders WHERE reference = '$trackingNumber'LIMIT 1";
        $listOfOrders = Db::getInstance()->executeS($sql);
        $orderId = sizeof($listOfOrders) > 0 ? $listOfOrders[0]['id_order'] : null;
        $order = new Order($orderId);
        if (!$this->moova->isCarrierMoova($orderId)) {
            return;
        }

        $status = $body['status'];
        $prestashopStatusId = Configuration::get("RECEIVE_MOOVA_STATUS_$status", 'disabled');
        if (isset($order->id_currency) && $prestashopStatusId && $prestashopStatusId != 'disabled') {
            Log::info("Changing status $status in order:$prestashopStatusId ");
            $order->setCurrentState((int)$prestashopStatusId);
        }
        $this->output = $body['status'];
    }
}
