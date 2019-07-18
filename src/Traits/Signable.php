<?php

/**
 * This class handles XML invoices signing.
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT\Traits
 */

namespace joseloarca\FELGT\Traits;

use joseloarca\FELGT\Tools\XmlTools;
use joseloarca\FELGT\Tools\KeyReader;

trait Signable
{
    protected $signTime = null;

    private $publicKey = null;

    private $privateKey = null;

    private $signatureId;

    private $signatureValueId;

    private $referenceId;

    private $keyInfoId;

    private $signatureSignedPropertiesId;

    private $qPropsId;

    private $xadesObjectId;

    /**
     * Set sign time
     *
     * @param  int|string  $time  Time of the signature
     */
    public function setSignTime($time)
    {
        $this->signTime = is_string($time) ? strtotime($time) : $time;
    }

    public function sign(string $signatureId, $publicPath, $privatePath = null, $passphrase = '')
    {
        $this->signatureId = 'Signature-' . $signatureId;
        $this->signatureSignedPropertiesId = 'SignedProperties-'.$signatureId;
        $this->referenceId = 'Reference-'.$signatureId;
        $this->signatureValueId = 'SignatureValue-' . $signatureId;
        $this->keyInfoId = 'KeyInfoId-Signature-' . $signatureId;
        $this->qPropsId = 'QualifyingProperties-' .$signatureId;
        $this->xadesObjectId = 'XadesObjectId-'. $signatureId;

        // Load public and private keys
        $reader = new KeyReader($publicPath, $privatePath, $passphrase);
        $this->publicKey = $reader->getPublicKey();
        $this->privateKey = $reader->getPrivateKey();
        unset($reader);

        // Return success
        return (!empty($this->publicKey) && !empty($this->privateKey));
    }

    /**
     * Inject XML signature
     *
     * @param $xml
     *
     * @return mixed
     */
    protected function injectSignature($xml)
    {
        $xTools = new XmlTools;

        $xml = str_replace("\r", "", $xml);

        // Prepare data
        $signTime = is_null($this->signTime) ? time() : $this->signTime;
        $certData = openssl_x509_parse($this->publicKey);
        $certIssuer = [];

        foreach ($certData ['issuer'] as $key => $value) {
            $certIssuer[] = $key.'='.$value;
        }

        $certIssuer = implode(',', $certIssuer);

        // Generate signed properties
        $prop = '<xades:SignedProperties Id="'.$this->signatureSignedPropertiesId.'">'.
            '<xades:SignedSignatureProperties>'.
            '<xades:SigningTime>'.date('c', $signTime).'</xades:SigningTime>'.
            '<xades:SigningCertificate>'.
            '<xades:Cert>'.
            '<xades:CertDigest>'.
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'.
            '<ds:DigestValue>'.$xTools->getCertificateFingerprint($this->publicKey).'</ds:DigestValue>'.
            '</xades:CertDigest>'.
            '<xades:IssuerSerial>'.
            '<xd:X509IssuerName>'.$certIssuer.'</xd:X509IssuerName>'.
            '<xd:X509SerialNumber>'.$certData['serialNumber'].'</xd:X509SerialNumber>'.
            '</xades:IssuerSerial>'.
            '</xades:Cert>'.
            '</xades:SigningCertificate>'.
            '</xades:SignedSignatureProperties>'.
            '<xades:SignedDataObjectProperties>'.
            '<xades:DataObjectFormat ObjectReference="#'.$this->referenceId.'">'.
            '<xades:MimeType>text/xml</xades:MimeType>'.
            '<xades:Encoding>UTF-8</xades:Encoding>'.
            '</xades:DataObjectFormat>'.
            '</xades:SignedDataObjectProperties>'.
            '</xades:SignedProperties>';

        // Get modulus and exponent
        $privateData = openssl_pkey_get_details($this->privateKey);
        $modulus = chunk_split(base64_encode($privateData['rsa']['n']), 76);
        $modulus = str_replace("\r", "", $modulus);
        $exponent = base64_encode($privateData['rsa']['e']);

        // Generate KeyInfo
        $kInfo = '<ds:KeyInfo Id="'.$this->keyInfoId.'">'."\n".
            '<ds:X509Data>'."\n".
            '<ds:X509Certificate>'."\n".$xTools->getCertificate($this->publicKey).'</ds:X509Certificate>'."\n".
            '</ds:X509Data>'."\n".
            '<ds:KeyValue>'."\n".
            '<ds:RSAKeyValue>'."\n".
            '<ds:Modulus>'.$modulus.'</ds:Modulus>'."\n".
            '<ds:Exponent>'.$exponent.'</ds:Exponent>'."\n".
            '</ds:RSAKeyValue>'."\n".
            '</ds:KeyValue>'."\n".
            '</ds:KeyInfo>';

        // Calculate digests
        $xmlns = $xTools->getNamespaces();
        $propDigest = $xTools->getDigest($xTools->injectNamespaces($prop, $xmlns));
        $documentDigest = $xTools->getDigest($xml);
        $kInfoDigest = $xTools->getDigest($xTools->injectNamespaces($kInfo, $xmlns));

        // Generate SignedInfo
        $sInfo = '<ds:SignedInfo>'."\n".
            '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'.
            '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'.
            '<ds:Reference Id="'.$this->referenceId.'" URI="#DatosEmision">'."\n".
            '<ds:Transforms>'."\n".
            '<ds:Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'."\n".
            '</ds:Transforms>'."\n".
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'."\n".
            '<ds:DigestValue>'.$documentDigest.'</ds:DigestValue>'."\n".
            '</ds:Reference>'."\n".
            '<ds:Reference Id="ReferenceKeyInfo" URI="#'.$this->keyInfoId.'">'."\n".
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'."\n".
            '<ds:DigestValue>'.$kInfoDigest.'</ds:DigestValue>'."\n".
            '</ds:Reference>'."\n".
            '<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="'.$this->signatureSignedPropertiesId.'">'."\n".
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'."\n".
            '<ds:DigestValue>'.$propDigest.'</ds:DigestValue>'."\n".
            '</ds:Reference>'."\n".
            '</ds:SignedInfo>';

        // Calculate signature
        $signaturePayload = $xTools->injectNamespaces($sInfo, $xmlns);
        $signatureResult = $xTools->getSignature($signaturePayload, $this->privateKey);

        // Make signature
        $sig = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="'.$this->signatureId.'">'."\n".
            $sInfo."\n".
            '<ds:SignatureValue Id="'.$this->signatureValueId.'">'."\n".
            $signatureResult.
            '</ds:SignatureValue>'."\n".
            $kInfo."\n".
            '<ds:Object Id="'. $this->xadesObjectId .'">'.
            '<xades:QualifyingProperties Target="#'.$this->signatureId.'" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="'. $this->qPropsId . '">'.
            $prop.
            '</xades:QualifyingProperties>'.
            '</ds:Object>'.
            '</ds:Signature>';

        // Inject signature
        $xml = str_replace('</dte:GTDocumento>', $sig.'</dte:GTDocumento>', $xml);

        return $xml;
    }
}