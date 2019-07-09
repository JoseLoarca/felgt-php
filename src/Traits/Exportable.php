<?php

/**
 * This class handles invoice to XML generation
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT\Traits
 */

namespace joseloarca\FELGT\Traits;

trait Exportable
{
    /**
     * Export invoice data to XML
     *
     * @return string
     */
    public function export()
    {
        // Build root xml data
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'."\n";
        $xml .= '<dte:GTDocumento Version="'.self::DEFAULT_VERSION.'" xmlns:dte="'.self::DEFAULT_VERSION_VALIDATION.'" xmlns:xd="http://www.w3.org/2000/09/xmldsig#">';
        $xml .= '<dte:SAT ClaseDocumento="'.self::DOCUMENT_CLASS.'"><dte:DTE ID="DatosCertificados"><dte:DatosEmision ID="DatosEmision">';

        // Append invoice general data
        $generalData = $this->toXml();
        $xml .= $generalData;

        // Append issuer data
        $issuerData = $this->getIssuer()->toXml();
        $xml .= $issuerData;

        // Append recipient data
        $recipientData = $this->getRecipient()->toXml();
        $xml .= $recipientData;

        // Append phrases data
        $phrasesData = $this->phrasesToXml();
        $xml .= $phrasesData;

        // Append items data
        $itemsData = $this->itemsToXml();
        $xml .= $itemsData;

        // Append total data
        $totalData = $this->totalToXml();
        $xml .= $totalData;

        $xml .= '</dte:DatosEmision></dte:DTE></dte:SAT>';

        return $xml;
    }
}