<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModulePrestaDrol extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ModulePrestaDrol';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Drol';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ModulePrestaDrol');
        $this->description = $this->l('View size product by price');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('MODULEPRESTADROL_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'drol_settings` (
                                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                `befores` INT( 11 ) UNSIGNED NOT NULL,
                                `afters` INT( 11 ) UNSIGNED NOT NULL,
                                PRIMARY KEY (`id`),
                                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        Db::getInstance()->autoExecute( _DB_PREFIX_.'drol_settings', array(
                            'befores' =>    (int)1,
                            'afters' => (int)2), 'INSERT');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MODULEPRESTADROL_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitModulePrestaDrolModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModulePrestaDrolModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return ;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MODULEPRESTADROL_LIVE_MODE' => Configuration::get('MODULEPRESTADROL_LIVE_MODE', true),
            'MODULEPRESTADROL_ACCOUNT_EMAIL' => Configuration::get('MODULEPRESTADROL_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'MODULEPRESTADROL_ACCOUNT_PASSWORD' => Configuration::get('MODULEPRESTADROL_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }     
        $aft = $_POST['after'];
        $bef = $_POST['before'];
            $sqls = "UPDATE "._DB_PREFIX_."`drol_settings` SET `befores` = '$bef', `afters` = '$aft'";
                Db::getInstance()->execute($sqls);
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookFooter(){
                $query1 = "SELECT * FROM "._DB_PREFIX_."`drol_settings`";
            $res1 = Db::getInstance()->getRow($query1);

            (int)$before = $res1['afters'];

            (int)$after = $res1['befores'];


                $query = "SELECT COUNT(*) FROM "._DB_PREFIX_."`product` WHERE `id_product` >= '$after' AND `id_product` <= '$before'";
                $res = Db::getInstance()->executeS($query);

                    return $res[0]['COUNT(*)'];
    }

}
