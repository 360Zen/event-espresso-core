<?php

namespace EventEspresso\core\services\currency\formatters;

use EventEspresso\core\domain\values\currency\Currency;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class CurrencyCodeCurrencyAmountFormatter
 * appends the currency code to the supplied amount
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class CurrencyCodeCurrencyAmountFormatter implements CurrencyAmountFormatterInterface
{

    /**
     * @param string   $money_amount
     * @param Currency $currency
     * @return string
     */
    public function format($money_amount, Currency $currency)
    {
        return $money_amount . ' ' . $currency->code();
    }

}
