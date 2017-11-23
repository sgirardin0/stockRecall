<?php

if (!defined('_PS_VERSION_'))      
  exit;


class StockRecall extends Module {

	private $_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'stockrecall';
		$this->tab = 'emailing';
		$this->version = '1.0';
		$this->author = 'Simon Girardin';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.7');

		$this->bootstrap = true;
	
		parent::__construct();

		$this->displayName = $this->l('StockRecall');
		$this->description = $this->l('Prenez le contrôle de votre stock avec le module StockRecall, une fois cette fonctionnalité activée, configurez l\'email destinataire et recevez une notification sur votre boîte de messagerie à chaque fois que le stock d\'un produit est modifié.');

		$this->confirmUninstall = $this->l('Êtes vous certain de vouloir désinstaller ce module ?');
	}

	public function install()
	{
		return (parent::install() && $this->registerHook('actionupdatequantity'));
	}
 
	public function uninstall()
	{
	    return parent::uninstall() && Configuration::deleteByName('EMAIL_STOCK_CHANGE');
	}

	public function hookActionUpdateQuantity($params) {
		$destinataire = Configuration::get('EMAIL_STOCK_CHANGE');
		if(!empty($destinataire)) {
			$lang_id = Context::getContext()->context->language->id;
			$product = new Product($params['id_product'], $params['id_product_attribute'], $lang_id);
			$tpl_vars = array('{prod_name}'=>$product->name, '{prod_quantity}'=>$params['quantity']);
	        Mail::Send($lang_id,'email_tpl','Le stock a été modifié',$tpl_vars, $destinataire, NULL, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/');
	    }
	}

	public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
        	$this->_postValidation();
        	if(!count($this->_postErrors)) {
                $this->_postProcess();
        	}
        	else {
        		foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
        	}
        }
        $this->context->smarty->assign('module_dir', $this->_path);

        $this->_html .= $this->context->smarty->fetch($this->local_path.'views/admin/config.tpl');
        $this->_html .= $this->renderForm();

        return $this->_html;
    }
    public function getFieldsValues()
	{
		return array(
			'stock_email' => Configuration::get('EMAIL_STOCK_CHANGE'),
		);
	}	

	protected function getFormFields()
	{
		return array(
			'form' => array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-cogs',
				),
				'input' => array(
					
					array(
						'col' => 3,
						'type' => 'text',
						'prefix' => '<i class="icon icon-envelope"></i>',
						'desc' => $this->l('Entrez un email valide.'),
						'name' => 'stock_email',
						'label' => $this->l('Email'),
					)

				),
				'submit' => array(
					'title' => $this->l('Create'),
				),
			),
		);
	}	
	
	public function renderForm()
	{
		$helper = new HelperForm();
		$helper->show_toolbar = true;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);
	 
		return $helper->generateForm(array($this->getFormFields()));
	}
	
	
	public function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit')) {
			Configuration::updateValue('EMAIL_STOCK_CHANGE', Tools::getValue('stock_email'));
		}
	}

	public function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('stock_email') || !preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', Tools::getValue('stock_email'))) {
                $this->_postErrors[] = 'Veuillez insérer un email valide.';
            } 
        }
	}
}



