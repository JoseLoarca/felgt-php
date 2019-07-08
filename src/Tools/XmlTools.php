<?php

/**
 * This class provides multiple useful tools for working with xml files
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT\Tools
 */

namespace joseloarca\FELGT\Tools;


class XmlTools
{
    /**
     * Escape XML value
     *
     * @param  string  $value
     *
     * @return string
     */
    public function escapeValue(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1, 'UTF-8');
    }

    /**
     * Generate a random id
     *
     * @return int
     * @throws Exception if it was not possible to gather sufficient entropy.
     */
    public function randomId(): int
    {
        if (function_exists('random_int')) {
            return random_int(0x10000000, 0x7FFFFFFF);
        }

        return rand(100000, 999999);
    }

    /**
     * Inject new namespaces
     *
     * @param  string  $xml
     * @param  string  $namespace
     *
     * @return string
     */
    public function injectNamespaces($xml, $namespace): string
    {
        if (!is_array($namespace)) {
            $namespace = [$namespace];
        }

        $xml = explode('>', $xml, 2);
        $oldNs = explode(' ', $xml[0]);
        $elementName = array_shift($oldNs);

        // Combine and sort namespaces
        $xmlns = [];
        $attributes = [];

        foreach (array_merge($oldNs, $namespace) as $name) {
            if (strpos($name, 'xmlns:') === 0) {
                $xmlns[] = $name;
            } else {
                $attributes[] = $name;
            }
        }

        sort($xmlns);
        sort($attributes);

        $ns = array_merge($xmlns, $attributes);

        // Generate new XML element
        return $elementName.' '.implode($ns, ' ').'>'.$xml[1];
    }

    /**
     * Base64 encoding wrapper
     *
     * @param $bytes
     * @param  bool  $prettify
     *
     * @return string
     */
    public function toBase64($bytes, $prettify = false): string
    {
        $encoded = base64_encode($bytes);

        return $prettify ? $this->prettify($encoded) : $encoded;
    }

    /**
     * Transform a big string into a multiline response
     *
     * @param $input
     *
     * @return string
     */
    private function prettify($input): string
    {
        return chunk_split($input, 76, "\n");
    }

    /**
     * Get digest
     *
     * @param $input
     * @param  bool  $prettify
     *
     * @return string
     */
    public function getDigest($input, $prettify = false): string
    {
        return $this->toBase64(sha1($input, true), $prettify);
    }

    /**
     * Get certificate content
     *
     * @param  string  $publicKey
     * @param  bool  $prettify
     *
     * @return string
     */
    public function getCertificate($publicKey, $prettify = true): string
    {
        openssl_x509_export($publicKey, $exportedPEM);

        $exportedPEM = str_replace('-----BEGIN CERTIFICATE-----', '', $exportedPEM);
        $exportedPEM = str_replace('-----END CERTIFICATE-----', '', $exportedPEM);
        $exportedPEM = str_replace("\n", '', str_replace("\r", '', $exportedPEM));

        if ($prettify) {
            $exportedPEM = $this->prettify($exportedPEM);
        }

        return $exportedPEM;
    }

    /**
     * Get the certificate fingerprint (digest)
     *
     * @param $publicKey
     * @param  bool  $prettify
     *
     * @return string
     */
    public function getCertificateFingerprint($publicKey, $prettify = false): string
    {
        $fingerprint = openssl_x509_fingerprint($publicKey, 'sha1', true);

        return $this->toBase64($fingerprint, $prettify);
    }

    /**
     * Get the certificate signature
     *
     * @param string $payload
     * @param string $privateKey
     * @param  bool  $prettify
     *
     * @return string
     */
    public function getSignature($payload, $privateKey, $prettify = true): string
    {
        openssl_sign($payload, $signature, $privateKey);

        return $this->toBase64($signature, $prettify);
    }
}