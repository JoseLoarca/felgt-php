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
    
    private $signedInfoId;
    
    private $signedPropertiesId;
    
    private $signatureValueId;
    
    private $certificateId;
    
    private $referenceId;
    
    private $signatureSignedPropertiesId;
    
    private $signatureObjectId;

    /**
     * Set sign time
     *
     * @param  int|string  $time  Time of the signature
     */
    public function setSignTime($time)
    {
        $this->signTime = is_string($time) ? strtotime($time) : $time;
    }

    public function sign($publicPath, $privatePath = null, $passphrase = '')
    {
        // Generate random IDS for xml
        $tools = new XmlTools();
        $this->signatureId = $tools->randomId();
        $this->signedInfoId = $tools->randomId();
        $this->signedPropertiesId = $tools->randomId();
        $this->signatureValueId = $tools->randomId();
        $this->certificateId = $tools->randomId();
        $this->referenceId = $tools->randomId();
        $this->signatureSignedPropertiesId = $tools->randomId();
        $this->signatureObjectId = $tools->randomId();

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
        $prop = '<xades:SignedProperties Id="Signature'.$this->signatureId.
            '-SignedProperties'.$this->signatureSignedPropertiesId.'">'.
            '<xades:SignedSignatureProperties>'.
            '<xades:SigningTime>'.date('c', $signTime).'</xades:SigningTime>'.
            '<xades:SigningCertificate>'.
            '<xades:Cert>'.
            '<xades:CertDigest>'.
            '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'.
            '<ds:DigestValue>'.$xTools->getCertificateFingerprint($this->publicKey).'</ds:DigestValue>'.
            '</xades:CertDigest>'.
            '<xades:IssuerSerial>'.
            '<xd:X509IssuerName>'.$certIssuer.'</xd:X509IssuerName>'.
            '<xd:X509SerialNumber>'.$certData['serialNumber'].'</xd:X509SerialNumber>'.
            '</xades:IssuerSerial>'.
            '</xades:Cert>'.
            '</xades:SigningCertificate>'.
            '</xades:SignedSignatureProperties'.
            '</xades:SignedProperties>';

        // Generate KeyInfo
        $kInfo = '<ds:KeyInfo>'."\n".
            '<ds:X509Data>'."\n".
            '<ds:X509Certificate>'."\n".$xTools->getCertificate($this->publicKey).'</ds:X509Certificate>'."\n".
            '<ds:X509SubjectName>'.$certIssuer.'</ds:X509SubjectName>'. "\n".
            '<ds:X509IssuerSerial>'.
            '<ds:X509IssuerName>'.$certIssuer.'</ds:X509IssuerName>'. "\n".
            '<ds:X509SerialNumber>'.$certData['serialNumber'].'</ds:X509SerialNumber>'. "\n".
            '</ds:X509IssuerSerial>'.
            '</ds:X509Data>'."\n".
            '</ds:KeyInfo>';

        // Calculate digests
        $xmlns = $xTools->getNamespaces();
        $propDigest = $xTools->getDigest($xTools->injectNamespaces($prop, $xmlns));
        $documentDigest = $xTools->getDigest($xml);

        // Generate SignedInfo
        $sInfo = '<ds:SignedInfo>'."\n".
            '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'.
            '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'.
            '<ds:Reference Id="xmldsig-'.$this->referenceId.'-ref0" URI="#DatosEmision">'."\n".
            '<ds:Transforms>'."\n".
            '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature">'.
            '</ds:Transform>'."\n".
            '</ds:Transforms>'."\n".
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'."\n".
            '<ds:DigestValue>'.$documentDigest.'</ds:DigestValue>'."\n".
            '</ds:Reference>'."\n".
            '<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#xmldsig'.$this->signatureSignedPropertiesId.'-signedprops">'."\n".
            '<ds:Transforms>'. "\n".
            '<ds:Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'. "\n" .
            '</ds:Transforms>'."\n".
            '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'. "\n".
            '<ds:DigestValue>'.$propDigest.'</ds:DigestValue>'."\n".
            '</ds:Reference>'."\n".

            '</ds:SignedInfo>';

        // Calculate signature
        $signaturePayload = $xTools->injectNamespaces($sInfo, $xmlns);
        $signatureResult = $xTools->getSignature($signaturePayload, $this->privateKey);

        // Make signature
        $sig = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="Signature'.$this->signatureId.'">'."\n".
            $sInfo."\n".
            '<ds:SignatureValue Id="SignatureValue'.$this->signatureValueId.'-sigvalue">'."\n".
            $signatureResult.
            '</ds:SignatureValue>'."\n".
            $kInfo."\n".
            '<ds:Object>'.
            '<xades:QualifyingProperties Target="#xmldsig'.$this->signatureId.'" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#">'.
            $prop.
            '</xades:QualifyingProperties>'.
            '</ds:Object>'.
            '</ds:Signature>';

        // Inject signature
        $xml = str_replace('</dte:GTDocumento>', $sig.'</dte:GTDocumento>', $xml);

        return $xml;
    }
}