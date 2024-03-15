<?php

/**
 * 2014-2016 MyQuickList
 *
 * NOTICE OF LICENSE
 *
 * E' vietata la riproduzione parziale e non del modulo ,
 * la vendita e la distribuzione non autorizzata dalla MyQuickList.
 *
 *  @author    Jonathan La Mela <jonathan.la.mela@gmail.com>
 *  @copyright 2007-2016 MyQuickList
 *  @license   http://www.creativecommons.it/ Creative Commons
 */


class IdealoFeedQlcronoModuleFrontController extends ModuleFrontController
{

    public $products = array();

    public function init()
    {
        $this->page_name = 'qlcrono'; // page_name and body id

        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::init();
    }

    public function initContent()
    {
        parent::initContent();


        if (!file_exists(_PS_ROOT_DIR_ . "/feed/")) {
            mkdir(_PS_ROOT_DIR_ . "/feed/");
        }

        $this->setTemplate("module:idealofeed/views/templates/front/qlcrono.tpl");


        $this->writeFeed();
    }

    public function writeFeed()
    {

        $file = fopen(_PS_ROOT_DIR_ . "/datafeed/idealo.csv", "w");

        $db = Db::getInstance();

        /*
        Query con spese di spedizione da webfeed
        $str_query = "
        select
`p`.`id_product` AS `vendor_code`,
`p`.`id_product`,
`p`.`ean13` AS `ean`,
`p`.`reference` AS `sku`,
`pm`.`name` AS `brand`,
`pl`.`name` AS `name`,
truncate(round((`p`.`price`)* 1.22,2),2) AS `price`,
truncate(ROUND(ifnull(`wp`.`delivery`,0)*1.22,2),2) AS `shipping_costs`,
`pl`.`delivery_in_stock` AS `delivery_days`,
`rc`.`ramo` AS `category`,
ifnull(`pl`.`description`,`pl`.`description_short`) AS `description`,
concat('https://www.ldc.it/',`p`.`id_product`,'-',`pl`.`link_rewrite`,'.html?utm_source=idealo') AS `product_url`,
concat('https://www.ldc.it/',`pi`.`id_image`,'-large_default/',`pl`.`link_rewrite`,'.jpg') AS `image_url`
from `" . _DB_PREFIX_ . "product` `p`
left join `" . _DB_PREFIX_ . "product_lang` `pl` on `p`.`id_product` = `pl`.`id_product`
left join `" . _DB_PREFIX_ . "webfeed_ramo_categoria` `rc` on `p`.`id_category_default` = `rc`.`id_ramo_categoria`
left join `" . _DB_PREFIX_ . "image` `pi` on `pi`.`id_product` = `p`.`id_product`
left join `" . _DB_PREFIX_ . "manufacturer` `pm` on `pm`.`id_manufacturer` = `p`.`id_manufacturer`
left join `" . _DB_PREFIX_ . "stock_available` `st` on `st`.`id_product` = `p`.`id_product`
left join `" . _DB_PREFIX_ . "webfeed_product` `wp` on `wp`.`prestashop_id`=`p`.`id_product`
where `rc`.`ramo` is not null
and `p`.`id_category_default` >= 2
and `pi`.`id_image` is not null
and `pi`.`cover` = 1
and `pl`.`id_lang` = 1
and `st`.`quantity` > 0
        ";*/

        $str_query = "
        select
`p`.`id_product` AS `vendor_code`,
`p`.`id_product`,
`p`.`ean13` AS `ean`,
`p`.`reference` AS `sku`,
`pm`.`name` AS `brand`,
`pl`.`name` AS `name`,
truncate(round((`p`.`price`)* 1.22,2),2) AS `price`,
0 AS `shipping_costs`,
`pl`.`delivery_in_stock` AS `delivery_days`,
`rc`.`ramo` AS `category`,
ifnull(`pl`.`description`,`pl`.`description_short`) AS `description`,
concat('https://www.ldc.it/',`p`.`id_product`,'-',`pl`.`link_rewrite`,'.html?utm_source=idealo') AS `product_url`,
concat('https://www.ldc.it/',`pi`.`id_image`,'-large_default/',`pl`.`link_rewrite`,'.jpg') AS `image_url`
from `" . _DB_PREFIX_ . "product` `p`
left join `" . _DB_PREFIX_ . "product_lang` `pl` on `p`.`id_product` = `pl`.`id_product`
left join `" . _DB_PREFIX_ . "webfeed_ramo_categoria` `rc` on `p`.`id_category_default` = `rc`.`id_ramo_categoria`
left join `" . _DB_PREFIX_ . "image` `pi` on `pi`.`id_product` = `p`.`id_product` AND `pi`.`cover` = 1
left join `" . _DB_PREFIX_ . "manufacturer` `pm` on `pm`.`id_manufacturer` = `p`.`id_manufacturer`
left join `" . _DB_PREFIX_ . "stock_available` `st` on `st`.`id_product` = `p`.`id_product`
left join `" . _DB_PREFIX_ . "webfeed_product` `wp` on `wp`.`prestashop_id`=`p`.`id_product`
where `rc`.`ramo` is not null
and `p`.`id_category_default` >= 2
and `pl`.`id_lang` = 1
and `st`.`quantity` > 0
        ";


        $products = $db->executeS($str_query);

        fputcsv($file, array_keys($products[0]), ";");

        foreach ($products as $p) {
            fputcsv($file, $p, ";");
        }

        fclose($file);


        echo "<a href='/datafeed/idealo.csv' download>Scarica feed</a>";
    }
}
