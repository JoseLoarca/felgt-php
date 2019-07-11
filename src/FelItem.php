<?php

/**
 * This class represents an invoice item.
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT
 */

namespace joseloarca\FELGT;

use joseloarca\FELGT\Tools\XmlTools;

class FelItem
{
    /**
     * This value indicates the default IVA rate (12%)
     *
     * @var int
     */
    const IVA_RATE = 12;

    /**
     * This value indicates if the item is a good or service
     *
     * @var string
     */
    private $goodOrService = 'S';

    /**
     * This value indicates the item line number
     *
     * @var int
     */
    private $lineNumber;

    /**
     * This value indicates the item unit measure
     *
     * @var int
     */
    private $unitMeasure = 1;

    /**
     * This value indicates the item description
     *
     * @var string
     */
    private $description;

    /**
     * This value indicates total item quantity
     *
     * @var string
     */
    private $quantity;

    /**
     * This value indicates item unit price
     *
     * @var string
     */
    private $unitPrice;

    /**
     * This value indicates item price
     *
     * @var string
     */
    private $price;

    /**
     * This value indicates item total price (price + taxes)
     *
     * @var string
     */
    private $total;

    /**
     * This value indicates item discount
     *
     * @var string
     */
    private $discount;

    /**
     * This value indicates item price without taxes
     *
     * @var string
     */
    private $priceWithoutTax;

    /**
     * This value indicates item taxes
     *
     * @var string
     */
    private $tax;

    /**
     * FelItem constructor.
     *
     * @param  string  $description
     * @param  mixed  $price
     * @param  int  $lineNumber
     * @param  int  $quantity
     * @param  int  $discount
     * @param  null  $unitPrice
     */
    public function __construct(
        string $description,
        $price,
        $lineNumber,
        $quantity = 1,
        $discount = 0,
        $unitPrice = null
    ) {
        $this->description = $description;
        $this->lineNumber = $lineNumber;
        $this->discount = number_format($discount, 2, '.', '');
        $this->quantity = number_format($quantity, 2, '.', '');

        $rate = self::IVA_RATE / 100;

        $this->tax = number_format($price * $rate, 2, '.', '');
        $this->priceWithoutTax = number_format($price - $this->tax, 2, '.', '');
        $this->total = number_format($price, 2, '.', '');

        $this->price = number_format($this->priceWithoutTax, 2, '.', '');

        if (is_null($unitPrice)) {
            $this->unitPrice = number_format($price / $quantity, 2, '.', '');
        }
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return $this->total;
    }

    /**
     * @return string
     */
    public function getPriceWithoutTax(): string
    {
        return $this->priceWithoutTax;
    }

    /**
     * @return string
     */
    public function getTax(): string
    {
        return $this->tax;
    }


    /**
     * Get the XML representation of an invoice item.
     *
     * @return string
     */
    public function toXml(): string
    {
        $xTools = new XmlTools;

        // Build the item root data
        $xml = '<dte:Item BienOServicio="'.$xTools->escapeValue($this->goodOrService).'" NumeroLinea="'.$xTools->escapeValue($this->lineNumber).'">';

        // Append basic data
        $xml .= '<dte:Cantidad>'.$xTools->escapeValue($this->quantity).'</dte:Cantidad>';
        $xml .= '<dte:UnidadMedida>'.$xTools->escapeValue($this->unitMeasure).'</dte:UnidadMedida>';
        $xml .= '<dte:Descripcion>'.$xTools->escapeValue($this->description).'</dte:Descripcion>';
        $xml .= '<dte:PrecioUnitario>'.$xTools->escapeValue($this->unitPrice).'</dte:PrecioUnitario>';
        $xml .= '<dte:Precio>'.$xTools->escapeValue($this->price).'</dte:Precio>';
        $xml .= '<dte:Descuento>'.$xTools->escapeValue($this->discount).'</dte:Descuento>';

        // Append tax data
        $xml .= '<dte:Impuestos>';
        $xml .= '<dte:Impuesto>';
        $xml .= '<dte:NombreCorto>'.$xTools->escapeValue(Fel::IVA_SHORT_NAME).'</dte:NombreCorto';
        $xml .= '<dte:CodigoUnidadGravable>'.$xTools->escapeValue($this->lineNumber).'</dte:CodigoUnidadGravable>';
        $xml .= '<dte:MontoGravable>'.$xTools->escapeValue($this->priceWithoutTax).'</dte:MontoGravable>';
        $xml .= '<dte:CantidadUnidadesGravables>'.$xTools->escapeValue($this->quantity).'</dte:CantidadUnidadesGravables>';
        $xml .= '<dte:MontoImpuesto>'.$xTools->escapeValue($this->tax).'</dte:>';
        $xml .= '</dte:Impuesto>';
        $xml .= '</dte:Impuestos>';

        // Total
        $xml .= '<dte:Total>'.$xTools->escapeValue($this->total).'</dte:Total>';

        // Close xml
        $xml .= '</dte:Item>';

        return $xml;
    }

}