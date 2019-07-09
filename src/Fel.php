<?php

/**
 * This class represents an invoice
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT
 */

namespace joseloarca\FELGT;

use DateTime;
use DateTimeZone;
use Exception;
use joseloarca\FELGT\Tools\XmlTools;
use joseloarca\FELGT\Traits\Exportable;

class Fel
{
    use Exportable;

    /**
     * This value indicates default schema version (0.4 as of 2019-07-09 09:24:00 -06:00)
     *
     * @var string
     */
    const DEFAULT_VERSION = '0.4';

    /**
     * This value indicates the default validation url for the current schema (0.4 as of 2019-07-09 09:24:00 -06:00)
     *
     * @var string
     */
    const DEFAULT_VERSION_VALIDATION = 'http://www.sat.gob.gt/dte/fel/0.1.0';

    /**
     * This value indicates default document class
     *
     * @var string
     */
    const DOCUMENT_CLASS = 'dte';

    /**
     * This value indicates default invoice type
     *
     * @var string
     */
    const INVOICE_TYPE = 'FACT';

    /**
     * This value indicates the local currency code
     *
     * @var string
     */
    const LOCAL_CURRENCY_CODE = 'GTQ';

    /**
     * This value indicates the default IVA short name
     *
     * @var string
     */
    const IVA_SHORT_NAME = 'IVA';

    /**
     * This value indicates the default value of the timezone that should be used when setting the issue date
     *
     * @var string
     */
    const DEFAULT_TIMEZONE = 'America/Guatemala';

    /**
     * This value indicates if the invoice should include the default phrase
     *
     * @var bool
     */
    const SHOULD_INCLUDE_DEFAULT_PHRASE = true;

    /**
     * Invoice access number
     *
     * @var string
     */
    private $accessNumber;

    /**
     * Invoice issuer
     *
     * @var FelIssuer
     */
    private $issuer;

    /**
     * Invoice recipient
     *
     * @var FelRecipient
     */
    private $recipient;

    /**
     * Invoice currency code
     *
     * @var string
     */
    private $currencyCode;

    /**
     * Invoice issue date
     *
     * @var string
     */
    private $issueDate;

    /**
     * Invoice items
     *
     * @var array
     */
    private $items = [];

    /**
     * Invoice total (price and taxes)
     *
     * @var array
     */
    private $totals = ['grandTotal' => null, 'taxesTotal' => null];

    /**
     * Invoice phrases
     *
     * @var array
     */
    private $phrases = [];

    /**
     * Fel constructor.
     *
     * @param  string  $accessNumber
     * @param  string|null  $issueDate
     * @param  string  $currencyCode
     *
     * @throws Exception
     */
    public function __construct($accessNumber, $issueDate = null, $currencyCode = self::LOCAL_CURRENCY_CODE)
    {
        $this->accessNumber = $accessNumber;
        $this->currencyCode = $currencyCode;

        if (is_null($issueDate)) {
            $currentDate = new DateTime('now', new DateTimeZone('America/Guatemala'));
            $this->issueDate = $currentDate->format('c');
        }

        if (self::SHOULD_INCLUDE_DEFAULT_PHRASE) {
            $this->phrases[] = new FelPhrase();
        }
    }

    /**
     * Get the invoice issuer
     *
     * @return mixed
     */
    public function getIssuer(): FelIssuer
    {
        return $this->issuer;
    }

    /**
     * Set the invoice issuer
     *
     * @param  mixed  $issuer
     *
     * @return Fel
     */
    public function setIssuer(FelIssuer $issuer): Fel
    {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * Get the invoice recipient
     *
     * @return mixed
     */
    public function getRecipient(): FelRecipient
    {
        return $this->recipient;
    }

    /**
     * Set the invoice recipient
     *
     * @param  mixed  $recipient
     *
     * @return Fel
     */
    public function setRecipient(FelRecipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Get the invoice phrases
     *
     * @return mixed
     */
    public function getPhrases(): array
    {
        return $this->phrases;
    }

    /**
     * Set a new invoice phrase
     *
     * @param  FelPhrase  $phrase
     *
     * @return $this
     */
    public function setPhrase(FelPhrase $phrase): Fel
    {
        $this->phrases[] = $phrase;
        return $this;
    }

    /**
     * Get the invoice items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Set a new invoice item
     *
     * @param  FelItem  $item
     *
     * @return Fel
     */
    public function setItem(FelItem $item): Fel
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Get invoice grand total
     *
     * @return string|null
     */
    public function getGrandTotal(): string
    {
        return !is_null($this->totals['grandTotal']) ? number_format($this->totals['grandTotal']) : null;
    }

    /**
     * Get invoice taxes total
     *
     * @return string|null
     */
    public function getTaxesTotal(): string
    {
        return !is_null($this->totals['taxesTotal']) ? number_format($this->totals['taxesTotal']) : null;
    }

    /**
     * Calculates invoice grand total and taxes
     *
     * @return void
     */
    private function calculateTotal()
    {
        if (!empty($this->items) && count($this->items)) {
            foreach ($this->items as $item) {
                $this->totals['taxesTotal'] += $item->getTax();
                $this->totals['grandTotal'] += $item->getTotal();
            }
        }
    }

    /**
     * Get the XML representation of an invoice general data
     *
     * @return string
     */
    public function toXml(): string
    {
        $xTools = new XmlTools;

        // Invoice general data
        $xml = '<dte:DatosGenerales CodigoMoneda="'.$xTools->escapeValue($this->currencyCode).'" FechaHoraEmision="'.$xTools->escapeValue($this->issueDate).'" NumeroAcceso="'.$xTools->escapeValue($this->accessNumber).'" Tipo="'.$xTools->escapeValue(self::INVOICE_TYPE).'">';

        return $xml;
    }

    /**
     * Get the XML representation of an invoice phrases
     *
     * @return string
     */
    public function phrasesToXml(): string
    {
        // Phrases root data
        $xml = '<dte:Frases>';

        // Loop through phrases
        foreach ($this->phrases as $phrase) {
            $xml .= $phrase->toXml();
        }

        $xml .= '</dte:Frases>';

        return $xml;
    }

    /**
     * Get the XML representation of an invoice items
     *
     * @return string
     */
    public function itemsToXml(): string
    {
        // Items root data
        $xml = '<dte:Items>';

        if (!empty($this->items)) {
            // Loop through items if not empty
            foreach ($this->items as $item) {
                $xml .= $item->toXml();
            }
        }

        $xml .= '</dte:Items>';

        return $xml;
    }

    /**
     * Get the XML representation of an invoice total
     *
     * @return string
     */
    public function totalToXml(): string
    {
        $this->calculateTotal();

        $xTools = new XmlTools;

        // Build total root data
        $xml = '<dte:Totales>';

        // Taxes
        $xml .= '<dte:TotalImpuestos>';
        $xml .= '<dte:TotalImpuesto NombreCorto="'.$xTools->escapeValue(self::IVA_SHORT_NAME).'" TotalMontoImpuesto="'.$xTools->escapeValue(number_format($this->totals['taxesTotal'],
                2, '.', '')).'"/>';
        $xml .= '</dte:TotalImpuestos>';

        // Grand Total
        $xml .= '<dte:GranTotal>'.$xTools->escapeValue(number_format($this->totals['grandTotal'], 2, '.',
                '')).'</dte:GranTotal>';
        $xml .= '</dte:Totales>';

        return $xml;
    }
}