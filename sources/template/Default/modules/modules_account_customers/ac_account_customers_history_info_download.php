<?php
  /**
   * ac_account_customers_history_info_download.php
   * @copyright Copyright 2008 - http://www.innov-concept.com
   * @Brand : ClicShopping(Tm) at Inpi all right Reserved
   * @license GPL 2 License & MIT Licence

   */

  use ClicShopping\OM\CLICSHOPPING;
  use ClicShopping\OM\Registry;
  use ClicShopping\OM\HTML;
//  use ClicShopping\Sites\Shop\Download;

  class ac_account_customers_history_info_download {

    public $code;
    public $group;
    public $title;
    public $description;
    public $sort_order;
    public $enabled = false;

    public function __construct() {
      $this->code = get_class($this);
      $this->group = basename(__DIR__);

      $this->title = CLICSHOPPING::getDef('module_account_customers_history_info_download_title');
      $this->description = CLICSHOPPING::getDef('module_account_customers_history_info_download_description');

      if (defined('MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_STATUS')) {
        $this->sort_order = (int)MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_SORT_ORDER;
        $this->enabled = (MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_STATUS == 'True');
      }
    }

    public function execute() {
      $CLICSHOPPING_Template = Registry::get('Template');
      $CLICSHOPPING_Db = Registry::get('Db');
      $CLICSHOPPING_Language = Registry::get('Language');
      $CLICSHOPPING_Customer = Registry::get('Customer');

      if (isset($_GET['Account']) &&  isset($_GET['HistoryInfo']) ) {
/*
        Registry::set('Download', new Download() );
        $CLICSHOPPING_Download = Registry::get('Download');

        echo $CLICSHOPPING_Download->Download();
*/
// Get last order id for checkout_success
        $Qorders = $CLICSHOPPING_Db->get('orders', 'orders_id', ['customers_id' => $CLICSHOPPING_Customer->getID()], 'orders_id desc', 1);

        $last_order = $Qorders->valueInt('orders_id');
      } else {
        $last_order = HTML::sanitize($_GET['order_id']);
      }

// Now get all downloadable products in that order
      $Qdownloads = $CLICSHOPPING_Db->prepare('select date_format(o.date_purchased, "%Y-%m-%d") as date_purchased_day,
                                                      opd.download_maxdays,
                                                      op.products_name,
                                                      opd.orders_products_download_id,
                                                      opd.orders_products_filename,
                                                      opd.download_count,
                                                      opd.download_maxdays
                                                  from :table_orders o,
                                                        :table_orders_products op,
                                                        :table_orders_products_download opd,
                                                        :table_orders_status os
                                                  where o.orders_id = :orders_id
                                                  and o.customers_id = :customers_id
                                                  and o.orders_id = op.orders_id
                                                  and op.orders_products_id = opd.orders_products_id
                                                  and opd.orders_products_filename <> ""
                                                  and o.orders_status = os.orders_status_id
                                                  and os.downloads_flag = 1
                                                  and os.language_id = :language_id
                                               ');

      $Qdownloads->bindInt(':orders_id', $last_order);
      $Qdownloads->bindInt(':customers_id', $CLICSHOPPING_Customer->getID());
      $Qdownloads->bindInt(':language_id', $CLICSHOPPING_Language->getId());
      $Qdownloads->execute();

      if ($Qdownloads->fetch() !== false) {

        $account = '<!-- Start account_customers_download --> ' . "\n";

        $content_width = (int)MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_CONTENT_WIDTH;

        ob_start();
        require($CLICSHOPPING_Template->getTemplateModules($this->group . '/content/account_customers_history_info_download'));
        $account .= ob_get_clean();

        $account .= '<!-- end account_customers_download-->' . "\n";

        $CLICSHOPPING_Template->addBlock($account, $this->group);
      }
    } // function execute

    public function isEnabled() {
      return $this->enabled;
    }

    public function check() {
      return defined('MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_STATUS');
    }

    public function install() {
      $CLICSHOPPING_Db = Registry::get('Db');

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Souhaitez-vous activer ce module ?',
          'configuration_key' => 'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_STATUS',
          'configuration_value' => 'True',
          'configuration_description' => 'Souhaitez vous activer ce module à votre boutique ?',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Veuillez selectionner la largeur de l\'affichage?',
          'configuration_key' => 'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_CONTENT_WIDTH',
          'configuration_value' => '12',
          'configuration_description' => 'Veuillez indiquer un nombre compris entre 1 et 12',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_content_module_width_pull_down',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Ordre de tri d\'affichage',
          'configuration_key' => 'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_SORT_ORDER',
          'configuration_value' => '120',
          'configuration_description' => 'Ordre de tri pour l\'affichage (Le plus petit nombre est montré en premier)',
          'configuration_group_id' => '6',
          'sort_order' => '115',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );

      return $CLICSHOPPING_Db->save('configuration', ['configuration_value' => '1'],
        ['configuration_key' => 'WEBSITE_MODULE_INSTALLED']
      );
    }

    public function remove() {
      return Registry::get('Db')->exec('history_info_download from :table_configuration where configuration_key in ("' . implode('", "', $this->keys()) . '")');
    }

    public function keys() {
      return array (
        'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_STATUS',
        'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_CONTENT_WIDTH',
        'MODULE_ACCOUNT_CUSTOMERS_HISTORY_INFO_DOWNLOAD_TITLE_SORT_ORDER'
      );
    }
  }
