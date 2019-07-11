<?php

/**
 * This class represents an invoice issuer
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT
 */

namespace joseloarca\FELGT;

use joseloarca\FELGT\Tools\XmlTools;

class FelIssuer
{
    /**
     * Issuer IVA affiliation (Afiliación IVA)
     *
     * @var string
     */
    private $ivaAffiliation = 'GEN';

    /**
     * Issuer business code (Código Establecimiento, ver constancia RTU.)
     *
     * @var string
     */
    private $businessCode;

    /**
     * Issuer email address
     *
     * @var string|null
     */
    private $emailAddress = null;

    /**
     * Issuer identifier (NIT)
     *
     * @var string
     */
    private $identifier;

    /**
     * Issuer business name (Nombre Comercial)
     *
     * @var string
     */
    private $businessName;

    /**
     * Issuer name
     *
     * @var string
     */
    private $name;

    /**
     * Issuer address
     *
     * @var string
     */
    private $address;

    /**
     * Issuer postal code
     *
     * @var string
     */
    private $postalCode;

    /**
     * Issuer town
     *
     * @var string
     */
    private $town;

    /**
     * Issuer department
     *
     * @var string
     */
    private $department;

    /**
     * Issuer country code
     *
     * @var string
     */
    private $countryCode = 'GT';

    /**
     * FelIssuer constructor.
     *
     * @param  array  $issuerData
     *
     * @return void
     */
    public function __construct($issuerData = [])
    {
        foreach ($issuerData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get the XML representation of the invoice issuer.
     *
     * @return string
     */
    public function toXml(): string
    {
        $xTools = new XmlTools;

        // Build the basic issuer data
        $xml = '<dte:Emisor AfiliacionIVA="'.$xTools->escapeValue($this->ivaAffiliation).'" CodigoEstablecimiento="'.$xTools->escapeValue($this->businessCode).'"';

        // Should we append email address to the issuer data?
        if ( ! is_null($this->emailAddress)) {
            $xml .=  ' CorreoEmisor="'.$xTools->escapeValue($this->emailAddress).'" ';
        }

        $xml .= 'NITEmisor="'.$xTools->escapeValue($this->identifier).'" NombreComercial="'.$xTools->escapeValue($this->businessName).'" NombreEmisor="'.$xTools->escapeValue($this->name).'">';

        // Build the address issuer data
        $issuerAddressXml = '<dte:DireccionEmisor>';
        $issuerAddressXml .= '<dte:Direccion>'.$xTools->escapeValue($this->address).'</dte:Direccion>';
        $issuerAddressXml .= '<dte:CodigoPostal>'.$xTools->escapeValue($this->postalCode).'</dte:CodigoPostal>';
        $issuerAddressXml .= '<dte:Municipio>'.$xTools->escapeValue($this->town).'</dte:Municipio>';
        $issuerAddressXml .= '<dte:Departamento>' . $xTools->escapeValue($this->department) .'</dte:Departamento>';
        $issuerAddressXml .= '<dte:Pais>' . $xTools->escapeValue($this->countryCode) . '</dte:Pais>';
        $issuerAddressXml .= '</dte:DireccionEmisor>';

        // Append address data
        $xml .= $issuerAddressXml;

        $xml .= '</dte:Emisor>';

        // Return xml
        return $xml;
    }
}