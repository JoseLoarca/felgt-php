<?php

namespace joseloarca\FELGT;

use joseloarca\FELGT\Tools\XmlTools;

class FelPhrase
{
    /**
     * This value indicates the default phrase code
     *
     * @var int
     */
    const DEFAULT_CODE = 1;

    /**
     * This value indicates the default phrase type
     *
     * @var int
     */
    const DEFAULT_TYPE = 1;

    /**
     * Phrase code
     *
     * @var int
     */
    private $code;

    /**
     * Phrase type
     *
     * @var int
     */
    private $type;

    /**
     * FelPhrase constructor.
     *
     * @param  int  $code
     * @param  int  $type
     */
    public function __construct(int $code = self::DEFAULT_CODE, int $type = self::DEFAULT_TYPE)
    {
        $this->code = $code;
        $this->type = $type;
    }

    /**
     * Get the XML representation of an invoice phrase
     *
     * @return string
     */
    public function toXml()
    {
        $xTools = new XmlTools;

        // Build phrase data
        $xml = '<dte:Frase CodigoEscenario="'.$xTools->escapeValue($this->code).'" TipoFrase="'.$xTools->escapeValue($this->type).'">';

        return $xml;
    }
}