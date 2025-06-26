<?php

class IdealoFeedQlcronoModuleFrontController extends ModuleFrontController
{

    public $auth = false;

    public $ajax;

    public function display()
    {
        $this->ajax = 1;

        $this->writeFeed();
    }

    public function writeFull()
    {
        ini_set('memory_limit', '4096M');

        $db = Db::getInstance();

        $str_query = "
        SELECT
            pl.link_rewrite,
            ps.id_supplier,
            p.id_product,
            p.ean13,
            p.reference,
            p.id_category_default,
            pm.name AS brand,
            pl.name,
            p.price,
            pl.delivery_in_stock,
            pl.description_short,
            wi.image_url AS image_url
        FROM " . _DB_PREFIX_ . "product p
        INNER JOIN " . _DB_PREFIX_ . "product_lang pl
            ON p.id_product = pl.id_product AND pl.id_lang = 1
        INNER JOIN " . _DB_PREFIX_ . "webfeed_product wp
            ON wp.id_product = p.id_product
        INNER JOIN " . _DB_PREFIX_ . "webfeed_images wi
            ON wp.internal_code = wi.internal_code AND wi.image_url IS NOT NULL
        INNER JOIN " . _DB_PREFIX_ . "stock_available st
            ON st.id_product = p.id_product AND st.quantity > 0
        LEFT JOIN " . _DB_PREFIX_ . "manufacturer pm
            ON pm.id_manufacturer = p.id_manufacturer
        LEFT JOIN " . _DB_PREFIX_ . "product_supplier ps
            ON ps.id_product = p.id_product
        WHERE p.id_category_default >= 2;
        ";



        $filePath = _PS_ROOT_DIR_ . "/datafeed/idealo.csv";
        $file = fopen($filePath, "w");

        $header = [
            "Numero articolo nello shop",
            "EAN / GTIN / codice a barre / UPC",
            "Numero articolo produttore originale (HAN/MPN)",
            "Produttore / Marca",
            "Nome prodotto",
            "Prezzo (lordo)",
            "Tempi di consegna",
            "Categoria di prodotti nello shop",
            "Descrizione prodotto",
            "URL prodotto",
            "URL immagine",
            "Spese di spedizione"
        ];
        fputcsv($file, $header, "|");

        $results = $db->executeS($str_query);

        $skipped_by_supplier = 0;
        $skipped_by_category = 0;

        $suppliers_config = Configuration::get('IDEALO_FEED_SUPPLIERS');
        $suppliers = [];

        if ($suppliers_config) {
            $suppliers = explode(",", $suppliers_config);
        }

        $categories_config = Configuration::get('IDEALO_FEED_CATEGORIES');
        $categories = [];

        if ($categories_config) {
            $categories = explode(",", $categories_config);
        }

        $rami_query = $db->executeS("SELECT id_ramo_categoria, ramo FROM " . _DB_PREFIX_ . "webfeed_ramo_categoria");
        $rami = [];

        foreach ($rami_query as $ramo) {
            $rami[$ramo["id_ramo_categoria"]] = $ramo["ramo"];
        }

        //Ottieni i prezzi specifici attivi
        $specific_prices_query = $db->executeS("SELECT * FROM " . _DB_PREFIX_ . "specific_price WHERE `to` > NOW()");
        $specific_prices = [];

        foreach ($specific_prices_query as $specific_price) {
            $specific_prices[$specific_price["id_product"]] = [
                "reduction" => $specific_price["reduction"],
                "reduction_type" => $specific_price["reduction_type"],
                "price" => $specific_price["price"],
            ];
        }

        if ($results) {
            foreach ($results as $row) {

                if (!empty($suppliers) && !in_array($row["id_supplier"], $suppliers)) {
                    $skipped_by_supplier++;
                    continue; // Skip products not in suppliers
                }

                if (!empty($categories) && in_array($row["id_category_default"], $categories)) {
                    $skipped_by_category++;
                    continue; // Skip products from excluded suppliers
                }

                if (isset($rami[$row["id_category_default"]])) {
                    $row["category_tree"] = $rami[$row["id_category_default"]];
                } else {
                    continue; // Skip products with no category
                }

                if (isset($specific_prices[$row["id_product"]])) {
                    $specific_price = $specific_prices[$row["id_product"]];
                    if ($specific_price["reduction_type"] == "amount") {
                        $row["price"] = $specific_price["price"];
                    }
                }

                $link = "https://" . Tools::getShopDomain() . "/" . $row["id_product"] . "-" . $row["link_rewrite"] . ".html?utm_source=idealo";

                fputcsv($file, [
                    $row["id_product"],
                    $row["ean13"],
                    $row["reference"],
                    $row["brand"],
                    $row["name"],
                    round($row["price"] * 1.22, 2),
                    $row["delivery_in_stock"],
                    $row["category_tree"],
                    $row["description_short"],
                    $link,
                    $row["image_url"],
                    0
                ], "|");
            }
        }

        fclose($file);

        // Controlla se il file è vuoto (solo header o completamente vuoto)
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (count($lines) <= 1) {
            http_response_code(500);
            $this->ajaxRender(json_encode([
                "error" => "Esportazione fallita"
            ]));
            return;
        }

        unset($results);

        ini_set('memory_limit', '256M');

        $this->ajaxRender(json_encode([
            "url" => Tools::getHttpHost(true) . __PS_BASE_URI__ . "/datafeed/idealo.csv?v=" . time(),
            "filesize" => filesize($filePath),
            "skipped_by_supplier" => $skipped_by_supplier,
            "skipped_by_category" => $skipped_by_category,
        ]));
    }

    public function writeFeed()
    {
        $this->writeFull();
    }
}
