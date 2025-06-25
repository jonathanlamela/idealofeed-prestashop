<?php


class IdealoFeed extends Module
{

    public function __construct()
    {

        $this->name = "idealofeed";
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = "Jonathan La Mela";
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = 'ba57ca6254668b27247f56de16df9d95';



        parent::__construct();

        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->displayName = "IdealoFeed";
        $this->description = "Genera feed csv per idealo";
    }


    public function install()
    {


        if (!parent::install()) {
            return false;
        }


        Configuration::updateValue('IDEALO_FEED', 'Idealo feed');

        return true;
    }


    public function unistall()
    {

        if (!parent::unistall()) {
            return false;
        }

        Configuration::deleteByName("IDEALO_FEED");

        $db = Db::getInstance();

        return true;
    }

    public function getContent()
    {
        $output = null;


        if (Tools::isSubmit('submit' . $this->name)) {

            if (Tools::getValue("IDEALO_FEED_SUPPLIERS") != null) {
                Configuration::updateValue(
                    "IDEALO_FEED_SUPPLIERS",
                    implode(",", Tools::getValue("IDEALO_FEED_SUPPLIERS"))
                );
            }

            if (Tools::getValue("IDEALO_FEED_CATEGORIES") != null) {
                Configuration::updateValue(
                    "IDEALO_FEED_CATEGORIES",
                    implode(",", Tools::getValue("IDEALO_FEED_CATEGORIES"))
                );
            } else {
                Configuration::updateValue("IDEALO_FEED_CATEGORIES", "");
            }


            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');


        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Utility'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l(
                        'For generate the file automatically throught cronojobs , '
                            . 'please add this url on your cronojobs panel :'
                    ),
                    'name' => 'URL_CRONOJOB'
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        $supp = Supplier::getSuppliers();
        $supplies = array();
        foreach ($supp as $s) {
            $supplies[] = array("id_option" => $s["id_supplier"], "name" => $s["name"]);
        }

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Filters'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('INCLUDE suppliers'),
                    'name' => 'IDEALO_FEED_SUPPLIERS[]',
                    'multiple' => true,
                    "options" => array(
                        "id" => "id_option",
                        "query" => $supplies,
                        "name" => "name"
                    )
                ),
                array(
                    'type' => 'categories',
                    'label' => $this->l('EXCLUDE categories'),
                    'name' => 'IDEALO_FEED_CATEGORIES',
                    'tree' => [
                        'selected_categories' => explode(
                            ",",
                            Configuration::get('IDEALO_FEED_CATEGORIES')
                        ),
                        'disabled_categories' => null,
                        'use_search' => false,
                        'use_checkbox' => true,
                        'id' => 'id_category_tree',
                    ],
                    'required' => false
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );





        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['MYMODULE_NAME'] = Configuration::get('MYMODULE_NAME');
        $helper->fields_value['URL_CRONOJOB'] = Tools::getHttpHost(true)
            . __PS_BASE_URI__
            . "index.php?fc=module&module=idealofeed&controller=qlcrono";


        $helper->fields_value['IDEALO_FEED_SUPPLIERS[]'] = explode(
            ",",
            Configuration::get('IDEALO_FEED_SUPPLIERS')
        );



        return $helper->generateForm($fields_form);
    }
}
