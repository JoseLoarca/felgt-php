<?php

/**
 * This class represents an invoice recipient
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT
 */

namespace joseloarca\FELGT;

use joseloarca\FELGT\Tools\XmlTools;

class FelRecipient
{

    /**
     * Recipient name
     *
     * @var string|null
     */
    private $name = null;

    /**
     * Recipient identifier (NIT)
     *
     * @var string
     */
    private $identifier = 'CF';

    /**
     * Recipient email address
     *
     * @var string|null
     */
    private $emailAddress = null;

    /**
     * Recipient address
     *
     * @var string|null
     */
    private $address = null;

    /**
     * Recipient postal code
     *
     * @var string|null
     */
    private $postalCode = null;

    /**
     * Recipient town
     *
     * @var string|null
     */
    private $town = null;

    /**
     * Recipient department
     *
     * @var string|null
     */
    private $department = null;

    /**
     * Recipient country code
     *
     * @var string
     */
    private $countryCode = 'GT';

    /**
     * FelRecipient constructor.
     *
     * @param  array  $recipientData
     *
     * @return void
     */
    public function __construct($recipientData = [])
    {
        foreach ($recipientData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get the XML representation of the invoice recipient.
     *
     * @return string
     */
    public function toXml(): string
    {
        $xTools = new XmlTools;

        // Build required recipient data
        $xml = '<dte:Receptor CorreoReceptor="'.$xTools->escapeValue($this->emailAddress).'" IDReceptor="'.$xTools->escapeValue($this->identifier).'" NombreReceptor="'.$xTools->escapeValue($this->name).'">';

        // If recipient address data is available, append it to the xml
        if (!is_null($this->address) && !is_null($this->postalCode) && !is_null($this->town) && !is_null($this->department) && !is_null($this->countryCode)) {
            $recipientAddressXml = '<dte:DireccionReceptor>';
            $recipientAddressXml .= '<dte:Direccion>' . $xTools->escapeValue($this->address) . '</dte:Direccion>';
            $recipientAddressXml .= '<dte:CodigoPostal>' . $xTools->escapeValue($this->postalCode) . '</dte:CodigoPostal>';
            $recipientAddressXml .= '<dte:Municipio>' . $xTools->escapeValue($this->town) . '</dte:Municipio>';
            $recipientAddressXml .= '<dte:Departamento>' . $xTools->escapeValue($this->department) . '</dte:Departamento>';
            $recipientAddressXml .= '<dte:Pais>' . $xTools->escapeValue($this->countryCode) . '</dte:Pais>';
            $recipientAddressXml .= '</dte:DireccionReceptor>';

            $xml .= $recipientAddressXml;

        }

        $xml .= '</dte:Receptor>';

        // Return xml
        return $xml;
    }
}