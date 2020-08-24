<?php


class Currency
{
    /** @var string */
    private $code;
    /** @var float */
    private $rate;
    /** @var string */
    private $name;
    /** @var string */
    private $vatCode;
    /** @var float */
    private $vatRate;

    /**
     * Set VAT rate to 0 if is EU invoice
     *
     * @param string $code
     */
    private function setVatData(string $code) : void
    {
        if ($code === 'PLN') {
            $this->vatCode = '23';
            $this->$this->vatRate = 0.23;
        } else {
            $this->vatCode = '0';
            $this->vatRate = 0.00;
        }
    }

    /**
     * Currency constructor.
     *
     * @param string $code
     * @param float|null $rate
     * @param string $name
     */
    public function __construct(string $code, $rate, string $name){
        $this->code = $code;
        $this->rate = $rate;
        $this->name = $name;

        $this->setVatData($this->code);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return float|string
     */
    public function getRate()
    {
        if ($this->rate === 0.00) {
            return '';
        } else {
            return $this->rate;
        }
    }

    /**
     * @param float $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getVatCode(): string
    {
        return $this->vatCode;
    }

    /**
     * @param string $vatCode
     */
    public function setVatCode(string $vatCode): void
    {
        $this->vatCode = $vatCode;
    }

    /**
     * @return float
     */
    public function getVatRate(): float
    {
        return $this->vatRate;
    }

    /**
     * @param float $vatRate
     */
    public function setVatRate(float $vatRate): void
    {
        $this->vatRate = $vatRate;
    }

}