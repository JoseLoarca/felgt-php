<?php

/**
 * This class handles public and private keys from certificates.
 *
 * @author Jose Loarca <joseloarca97@icloud.com>
 *
 * @package joseloarca\FELGT\Tools
 */

namespace joseloarca\FELGT\Tools;

class KeyReader
{
    /**
     * The public key content
     *
     * @var mixed
     */
    private $publicKey;

    /**
     * The private key content
     */
    private $privateKey;

    /**
     * Public key getter
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Private key getter
     *
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * KeyReader constructor.
     *
     * @param string $publicPath Path to public key in PEM
     * @param  null  $privatePath Path to private key
     * @param  string  $passphrase Private key passphrase
     */
    public function __construct($publicPath, $privatePath = null, $passphrase = '')
    {
        if (is_null($privatePath)) {
            $this->readPKCS12($publicPath, $passphrase);
        }

        $this->readX509($publicPath, $privatePath, $passphrase);
    }

    /**
     * Read a X.509 certificate content
     *
     * @param string $publicPath
     * @param string $privatePath
     * @param string $passphrase
     */
    private function readX509($publicPath, $privatePath, $passphrase)
    {
        if (!is_file($publicPath) || !is_file($privatePath)) {
            return;
        }

        $this->publicKey = openssl_x509_read(file_get_contents($publicPath));

        $this->privateKey = openssl_pkey_get_private(file_get_contents($privatePath), $passphrase);

    }

    /**
     * Read a PKCS#12 certificate content
     *
     * @param $certPath
     * @param $passphrase
     *
     * @return bool
     */
    private function readPKCS12($certPath, $passphrase)
    {
        if (!is_file($certPath)) {
            return false;
        }

        if (openssl_pkcs12_read(file_get_contents($certPath), $certs, $passphrase)) {
            $this->publicKey = openssl_x509_read($certs['cert']);
            $this->privateKey = openssl_pkey_get_private($certs['pkey']);
        }
    }
}