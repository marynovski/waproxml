<?php


class CurrencyController
{
    private function isWeekend($date) {
        /** @var DateTimeImmutable $rateDate */
        $rateDate = new DateTimeImmutable($date);

        if (date('N', strtotime($date)) == 6) {
            $rateDate = $rateDate->modify('-1 day');
        } elseif (date('N', strtotime($date)) == 7) {
            $rateDate = $rateDate->modify('-2 day');
        }

        return $rateDate;
    }

    public function getCurrencyRate(string $code, string $date)
    {
        /** @var DateTimeImmutable $issueDate */
        $issueDate = new DateTimeImmutable($date);
        /** @var DateTimeImmutable $rateDate */
        $rateDate = $issueDate->modify('-1 day');

        $rateDate = $this->isWeekend($rateDate->format('Y-m-d'));
        if ($code !== 'PLN') {
            $rate_json = file_get_contents('http://api.nbp.pl/api/exchangerates/rates/a/' . strtolower($code) . '/' . $rateDate->format('Y-m-d') . '/');
        }
        sleep(1);

        return json_decode($rate_json);
    }
}