<?php

class MoovaLoginModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        return  $this->setTemplate('module:MOOVA/views/templates/dpage.tpl');
    }

    public function postProcess()
    {
        $this->ajaxDie(
            json_encode([
                'asd' =>  'asd'
            ])
        );
        /*
        $headers = getallheaders();
        
        if ($headers['Authorization'] = Configuration::get('MOOVA_KEY_AUTHENTICATION') {
            $this->ajaxDie(
                json_encode([
                    'Error' => 'Unathorized'
                ])
            );
        }
        */

        $data = json_decode(file_get_contents('php://input'), true);

        $this->ajaxDie(
            json_encode([
                'asd' =>  'asd'
            ])
        );
    }
}
