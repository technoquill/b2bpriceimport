<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\Config\B2BPriceImportConfig;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;


/**
 * Class for managing the B2B Price Import configuration in the admin panel.
 */
class AdminB2BPriceImportConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $configRepository = $this->getConfigRepository();

        $this->context->smarty->assign([
            'configGroups' => $configRepository->getGroupedDefinitions(
                B2BPriceImportConfig::SECTION_IMPORT
            ),
            'ajaxUrl' => $this->context->link->getAdminLink('AdminB2BPriceImportConfig', true),
        ]);

        $this->setTemplate('tabs/config_panel.tpl');


//        if (!file_exists($templatePath)) {
//            $this->errors[] = 'Template not found: ' . $templatePath;
//            return;
//        }
//
//        $this->content .= $this->context->smarty->fetch($templatePath);
    }

    /**
     * @return void
     */
    public function ajaxProcessSaveConfig(): void
    {
        header('Content-Type: application/json');

        $key = (string) Tools::getValue('key');
        $value = Tools::getValue('value', []);

        try {
            $savedValue = $this->getConfigRepository()->save($key, $value);

            die(json_encode([
                'success' => true,
                'message' => 'Configuration saved.',
                'value' => $savedValue,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * @return B2BPriceImportConfigRepository
     */
    private function getConfigRepository(): B2BPriceImportConfigRepository
    {
        return new B2BPriceImportConfigRepository();
    }

}
