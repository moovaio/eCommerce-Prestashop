<?php

class MoovaLoginModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        return  $this->setTemplate('module:MOOVA/views/templates/dpage.tpl');
    }

    public function postProcess()
    {
        if (!$this->canCreateStatus()) {
            return $this->ajaxDie(
                json_encode([
                    'Error' => 'Unathorized'
                ])
            );
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $status = $data['status'];
        $id = $data['id'];
        $date = $data['date'];
        $query = "INSERT INTO `prestashop`.`ps_moova_status` (`shipping_id`, `date`, `status`) VALUES ('$id', '$date', '$status')";
        Db::getInstance()->execute($query);
        $this->ajaxDie("ok");
    }


    private function canCreateStatus()
    {
        $headers = getallheaders();
        return $headers['Authorization'] != Configuration::get('MOOVA_KEY_AUTHENTICATION');
    }
}
