<?php

namespace EventEspresso\core\domain\values\currency;

use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\services\currency\Calculator;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class Money
 * Immutable object representing an amount of money for a particular currency
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class Money
{

    /**
     * number of decimal places to be added to currencies for internal computations,
     * but removed before any output or formatting is applied.
     * This allows us to avoid rounding errors during calculations.
     */
    const EXTRA_PRECISION = 3;

    /**
     * @var int $amount
     */
    private $amount;

    /**
     * @var Currency $currency
     */
    private $currency;

    /**
     * @var Calculator $calculator
     */
    protected $calculator;



    /**
     * Money constructor.
     *
     * @param float|int|string $amount money amount IN THE STANDARD UNIT FOR THE CURRENCY ie: dollars, Euros, etc
     *                                 example: $12.5 USD would equate to a value amount of 12.50
     * @param Currency         $currency
     * @param Calculator       $calculator
     * @throws InvalidDataTypeException
     */
    public function __construct($amount, Currency $currency, Calculator $calculator)
    {
        $this->currency   = $currency;
        $this->amount     = $this->parseAmount($amount);
        $this->calculator = $calculator;
    }



    /**
     * @return Calculator
     */
    protected function calculator()
    {
        return $this->calculator;
    }



    /**
     * Convert's a standard unit amount into the subunits of the currency
     *
     * @param float|int|string $amount money amount IN THE STANDARD UNIT FOR THE CURRENCY ie: dollars, Euros, etc
     *                                 example: $12.5 USD would equate to a value amount of 12.50
     * @return integer                 in the currency's subunit
     * @throws InvalidDataTypeException
     */
    private function parseAmount($amount)
    {
        if (! in_array(gettype($amount), array('integer', 'double', 'string'), true)) {
            throw new InvalidDataTypeException(
                '$amount',
                $amount,
                'integer (or float or string)'
            );
        }
        if ($this->currency->decimalMark() !== '.') {
            // remove thousands separator and replace decimal place with standard decimal.
            $amount = str_replace(
                array(
                    $this->currency->thousands(),
                    $this->currency->decimalMark(),
                ),
                array(
                    '',
                    '.',
                ),
                $amount
            );
        }
        // remove any non numeric values but leave the decimal
        $amount = (float) preg_replace('/([^0-9\\.-])/', '', $amount);
        // maybe convert the incoming  decimal amount to the currencies subunits
        // ex: 12.5 for a currency with 100 subunits would become 1250
        if ($this->currency()->subunits()) {
            $amount *= $this->currency()->subunits();
        }
        // then shift the decimal position by the number of decimal places used internally
        // so if our extra internal precision was 3, it would become 1250000
        $amount *= pow(10, $this->precision());
        // then round up the remaining value if there is still a fractional amount left
        $amount = round($amount);
        return (int)$amount;
    }



    /**
     * adds or subtracts additional decimal places based on the value of the Money::EXTRA_PRECISION constant
     *
     * @param bool $adding_precision if true, will move the decimal to the right (increase amount)
     *                               if false, will move the decimal to the left (decrease amount)
     * @return integer
     */
    private function precision($adding_precision = true)
    {
        $sign = $adding_precision ? 1 : -1;
        return Money::EXTRA_PRECISION * $sign;
    }

    /**
     * Returns the money amount as an unformatted float
     * @return float
     */
    public function floatAmount()
    {
        // shift the decimal position BACK by the number of decimal places used internally
        // ex: if our extra internal precision was 3, then 1250000 would become 1250
        $amount = $this->amount * pow(10, $this->precision(false));
        // then maybe adjust for the currencies subunits
        // ex: 1250 for a currency with 100 subunits would become 12.50
        if ($this->currency()->subunits()) {
            $amount /= $this->currency()->subunits();
        }
        // then shave off our extra internal precision using the number of decimal places for the currency
        return $amount;
    }



    /**
     * Returns the money amount as an unformatted string
     * IF YOU REQUIRE A FORMATTED STRING, THEN USE Money::format()
     * In the currency's standard units
     *
     * @return string
     */
    public function amount()
    {
        return (string) round($this->floatAmount(), $this->currency()->decimalPlaces());
    }



    /**
     * Returns the money SUBUNITS amount as an INTEGER
     *
     * @return integer
     */
    public function amountInSubunits()
    {
        // shift the decimal position BACK by the number of decimal places used internally
        // for extra internal precision, but NOT for the number of decimals used by the currency
        // ex: if our extra internal precision was 3, then 1250000 would become 1250
        // and even if the currency used 100 subunits, we would return 1250 and NOT 12.50
        $amount = (string) $this->amount * pow(10, $this->precision(false));
        // then shave off anything after the decimal
        $amount = round($amount);
        return (int) $amount;
    }



    /**
     * Returns the Currency object for this money
     *
     * @return Currency
     */
    public function currency()
    {
        return $this->currency;
    }



    /**
     * adds the supplied Money amount to this Money amount
     * and returns a new Money object
     *
     * @param Money $other
     * @return Money
     * @throws InvalidArgumentException
     */
    public function add(Money $other)
    {
        $this->verifySameCurrency($other->currency());
        return new Money(
            $this->calculator()->add(
                $this->amount(),
                $other->amount()
            ),
            $this->currency(),
            $this->calculator()
        );
    }



    /**
     * subtracts the supplied Money amount from this Money amount
     * and returns a new Money object
     *
     * @param Money $other
     * @return Money
     * @throws InvalidArgumentException
     */
    public function subtract(Money $other)
    {
        $this->verifySameCurrency($other->currency());
        return new Money(
            $this->calculator()->subtract(
                $this->amount(),
                $other->amount()
            ),
            $this->currency(),
            $this->calculator()
        );
    }


    /**
     * multiplies this Money amount by the supplied $multiplier
     * and returns a new Money object
     *
     * @param float|int|string $multiplier
     * @param int              $rounding_mode
     * @return Money
     * @throws InvalidDataTypeException
     */
    public function multiply($multiplier, $rounding_mode = Calculator::ROUND_HALF_UP)
    {
        return new Money(
            $this->calculator()->multiply(
                $this->amount(),
                $multiplier,
                $this->precision(),
                $rounding_mode
            ),
            $this->currency(),
            $this->calculator()
        );
    }


    /**
     * divides this Money amount by the supplied $divisor
     * and returns a new Money object
     *
     * @param float|int|string $divisor
     * @param int              $rounding_mode
     * @return Money
     * @throws InvalidDataTypeException
     */
    public function divide($divisor, $rounding_mode = Calculator::ROUND_HALF_UP)
    {
        return new Money(
            $this->calculator()->divide(
                $this->amount(),
                $divisor,
                $this->precision(),
                $rounding_mode
            ),
            $this->currency(),
            $this->calculator()
        );
    }



    /**
     * @param Currency $other_currency
     * @throws InvalidArgumentException
     */
    public function verifySameCurrency(Currency $other_currency)
    {
        if ($this->currency()->equals($other_currency) !== true) {
            throw new InvalidArgumentException(
                esc_html__(
                    'Currencies must be the same in order to add or subtract their values.',
                    'event_espresso'
                )
            );
        }
    }



    /**
     * @return string
     */
    public function __toString()
    {
        return $this->amount();
    }
}
