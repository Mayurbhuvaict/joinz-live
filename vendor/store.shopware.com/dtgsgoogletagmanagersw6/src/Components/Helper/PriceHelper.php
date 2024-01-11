<?php
/**
 * Created by PhpStorm.
 * User: constantin
 * Date: 28.02.17
 * Time: 15:35
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class PriceHelper
{
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * V 1.3.5 - Netto/Bruttopreise übergeben - Preistyp ermitteln
     * @return string
     * @throws \Exception
     */
    public function getPriceType() {

        $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config');

        if(isset($tagManagerConfig['showPriceType'])) {

            $price_type = $tagManagerConfig['showPriceType'];

            if($price_type == 'netto') return 'netto';
            else return 'brutto';

        }

    }

    /**
     * V 1.3.5 - Netto/Bruttopreise übergeben - Preis errechnen
     * @param $price
     * @param int $tax
     * @return string
     * @throws \Exception
     */
    public function getPrice($price, $tax = 19) {

        //Einstellung im Plugin
        $plugin_price_type = $this->getPriceType();

        //ist dieser Wert = 0, werden im Frontend ohnehin Nettopreise ausgegeben
        //0 = netto
        //1 = brutto
        //@TODO!!!
        //$sw_price_type = (int) Shopware()->Modules()->System()->sUSERGROUPDATA['tax'];

        //nur wenn im Plugin Netto eingestellt und die SW Preise Brutto sind! (Änderung in V2.6.1)
        if($plugin_price_type == 'netto') {
            return number_format($price / (100 + $tax) * 100, 2, '.', ''); //1.3.6 - 1000er Separator entfernt
        }
        else {
            return number_format($price, 2, '.', '');
        }

    }

    /**
     * Float Converter
     * @param $str
     * @return float
     */
    public function parseFloat($str) {
        if (is_int($str) || is_float($str)) { return floatval($str); }

        $str = trim($str);

        $last = max(strrpos($str, ','), strrpos($str, '.'));
        if ($last!==false) {
            $str = strtr($str, ',.', 'XX');
            $str[$last] = '.';
            $str = str_replace('X', '', $str); // strtr funktioniert nicht mit $to=''
        }
        return (float)$str;
    }

    public function formatPrice($price) {
        return number_format($price, 2, '.', '');
    }

}
