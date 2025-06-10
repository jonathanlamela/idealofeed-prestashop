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
            p.id_product,
            p.ean13,
            p.reference,
            pm.name AS brand,
            pl.name AS name,
            ROUND(p.price * 1.22, 2) AS price,
            pl.delivery_in_stock AS delivery_time,
            rc.ramo AS category,
            pl.description_short AS description,
            wi.image_url AS image_url,
            CONCAT('https://', '" . Tools::getShopDomain() . "', '" . __PS_BASE_URI__ . "', p.id_product, '-', pl.link_rewrite, '.html?utm_source=idealo') AS product_url
        FROM ps_product p
        INNER JOIN ps_product_lang pl
            ON p.id_product = pl.id_product AND pl.id_lang = 1
        INNER JOIN ps_webfeed_product wp
            ON wp.id_product = p.id_product
        INNER JOIN ps_webfeed_images wi
            ON wp.internal_code = wi.internal_code AND wi.image_url IS NOT NULL
        INNER JOIN ps_webfeed_ramo_categoria rc
            ON p.id_category_default = rc.id_ramo_categoria
        INNER JOIN ps_stock_available st
            ON st.id_product = p.id_product AND st.quantity > 0
        LEFT JOIN ps_manufacturer pm
            ON pm.id_manufacturer = p.id_manufacturer
        WHERE rc.ramo IS NOT NULL
        AND p.id_category_default >= 2;
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

        if ($results) {
            foreach ($results as $p) {
                $row = [
                    $p["id_product"],
                    $p["ean13"],
                    $p["reference"],
                    $p["brand"],
                    $p["name"],
                    $p["price"],
                    $p["delivery_time"],
                    $p["category"],
                    $p["description"],
                    $p["product_url"],
                    $p["image_url"],
                    0
                ];
                fputcsv($file, $row, "|");
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

        ini_set('memory_limit', '256M');

        $this->ajaxRender(json_encode([
            "url" => Tools::getHttpHost(true) . __PS_BASE_URI__ . "/datafeed/idealo.csv?v=" . time(),
            "filesize" => filesize($filePath)
        ]));
    }

    public function writeFeed()
    {
        $this->writeFull();
    }
}
