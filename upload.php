<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include_once 'Model/Currency.php';
include_once 'Controller/CurrencyController.php';

function xmlEscape($string) {
    return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
}

function setCurrencyData(string $code, string $issueDateString, bool $getNewRate, float $currencyRate = 1.00) : Currency
{
    /** @var CurrencyController $currencyController */
    $currencyController = new CurrencyController();

    if ($getNewRate) {
        /** @var float $rate */
        $rate = $currencyController->getCurrencyRate($code, $issueDateString);
        $currencyRate = $rate->rates[0]->mid;
    }


    switch ($code) {
        case 'PLN':
            $currency = new Currency($code, 0.00, 'Złoty');
            break;
        case 'EUR':
            $currency = new Currency($code, $currencyRate, 'Euro');
            break;
        case 'CZK':
            $currency = new Currency($code, $currencyRate, 'Korona czeska');
            break;
        default:
            $currency = new Currency($code, 0.00, 'Złoty');
            break;
    }

    return $currency;
}

$data=file($_FILES['csv_file']['tmp_name'],FILE_IGNORE_NEW_LINES);
$currencyCode = $_POST['currency'];
$countryCode = $_POST['countryCode'];


$liczbaDokumentow = count($data) - 1;
$nowDateTime = new DateTimeImmutable('now');
$lastInvoiceDate = $nowDateTime;

$xml  = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
$xml .= '<MAGIK_EKSPORT>'."\n";
$xml .= '    <INFO_EKSPORTU>'."\n";
$xml .= '        <WERSJA_MAGIKA>4.2.0</WERSJA_MAGIKA>'."\n";
$xml .= '        <NAZWA_PROGRAMU>FatApp WAPRO XML Converter</NAZWA_PROGRAMU>'."\n";
$xml .= '        <WERSJA_PROGRAMU>1.1</WERSJA_PROGRAMU>'."\n";
$xml .= '        <DATA_EKSPORTU>' . $nowDateTime->format('d-m-Y') . '</DATA_EKSPORTU>'."\n";
$xml .= '        <GODZINA_EKSPORTU>' . $nowDateTime->format('H:i:s') . '</GODZINA_EKSPORTU>'."\n";
$xml .= '        <LICZBA_DOKUMENTOW>'.$liczbaDokumentow.'</LICZBA_DOKUMENTOW>'."\n";
$xml .= '    </INFO_EKSPORTU>'."\n";
$xml .= '    <DOKUMENTY>'."\n";
$ignoreHeaderRow = true;
$id = 1;
$lastCurrencyRate = 0.00;
foreach ($data as $row) {

    if ( $ignoreHeaderRow ) {
        $ignoreHeaderRow = false;
        continue;
    }
    $row = str_replace('"', '', $row);
    $invoice_data = explode(';', $row);

    if ( count($invoice_data) != 12 && count($invoice_data) != 13 && count($invoice_data) != 14 ) {

        $_SESSION['fileIsReady'] = false;
        $_SESSION['fileError'] = '<span class="error">Niepoprawny plik! Porównaj swój plik ze wzorem.</span>';
        header('Location: index.php');
    }
        if ( $invoice_data[11] == 'GOTÓWKA' ) {
            $paymentType = 'gotówka';
            $paymentTypeId = 1;
        } else {
            $paymentType = 'Przelew';
            $paymentTypeId = 3;
        }

        $issueDate = strtotime($invoice_data[1]);
        $issueDateYmd = new DateTimeImmutable($invoice_data[1]);

        if ( $lastInvoiceDate->format('Y-m-d') != $issueDateYmd->format('Y-m-d') ) {
            /** @var Currency $currency */
            $currency = setCurrencyData($currencyCode, $invoice_data[1], true);
        } else {
            $currency = setCurrencyData($currencyCode, $invoice_data[1], false, $lastCurrencyRate);
        }
        $clarionStartDate = strtotime('1800-12-28');
        $lastCurrencyRate = $currency->getRate();

        $datediff = $issueDate - $clarionStartDate;
        $datediff = round($datediff / (60 * 60 * 24));

        $xml .= '        <DOKUMENT>' . "\n";
        $xml .= '            <NAGLOWEK_DOKUMENTU>' . "\n";
        $xml .= '                <RODZAJ_DOKUMENTU>H</RODZAJ_DOKUMENTU>' . "\n";
        $xml .= '                <NUMER>' . $invoice_data[0] . '</NUMER>' . "\n";
        $xml .= '                <NR_DOK_ORYG/>' . "\n";
        $xml .= '                <ID_DOKUMENTU_ORYG>' . $id . '</ID_DOKUMENTU_ORYG>' . "\n";
        $xml .= '                <DOK_ZBIOR></DOK_ZBIOR>' . "\n";
        if ( $paymentTypeId === 1 ) {
            $xml .= '                <ID_KONTRAHENTA></ID_KONTRAHENTA>' . "\n";
        } else {
            $xml .= '                <ID_KONTRAHENTA>' . $id . '</ID_KONTRAHENTA>' . "\n";
        }
        $xml .= '                <ID_PLATNIKA>' . $id . '</ID_PLATNIKA>' . "\n";
        $xml .= '                <ID_OPERATORA>' . $id . '</ID_OPERATORA>' . "\n";
        $xml .= '                <ID_KONTRAHENTA_JST>0</ID_KONTRAHENTA_JST>' . "\n";
        $xml .= '                <ZAKUP_SPRZEDAZ></ZAKUP_SPRZEDAZ>' . "\n";
        $xml .= '                <ID_MAGAZYNU></ID_MAGAZYNU>' . "\n";
        $xml .= '                <SYMBOL_MAGAZYNU></SYMBOL_MAGAZYNU>' . "\n";
        $xml .= '                <ID_ROZRACHUNKU></ID_ROZRACHUNKU>' . "\n";
        $xml .= '                <OBLICZANIE_WG_CEN></OBLICZANIE_WG_CEN>' . "\n";
        $xml .= '                <TYP_DOKUMENTU>FV</TYP_DOKUMENTU>' . "\n";
        $xml .= '                <OPIS>Sprzedaż usług</OPIS>' . "\n";
        $xml .= '                <SYM_WAL>' . $currency->getCode() . '</SYM_WAL>' . "\n";
        $xml .= '                <NR_PODSTAWY/>' . "\n";
        if ( $paymentTypeId === 1 ) {
            $xml .= '                <ID_KASY>' . $id . '</ID_KASY>' . "\n";
        } else {
            $xml .= '                <ID_KASY></ID_KASY>' . "\n";
        }
        $xml .= '                <SYMBOL_KASY/>' . "\n";
        $xml .= '                <ID_RACHUNKU></ID_RACHUNKU>' . "\n";
        $xml .= '                <NUMER_RACHUNKU/>' . "\n";
        $xml .= '                <TYP_PLATNIKA/>' . "\n";
        $xml .= '                <CZY_DOKUMENT_KOREKTY></CZY_DOKUMENT_KOREKTY>' . "\n";
        $xml .= '                <WYROZNIK/>' . "\n";
        $xml .= '                <CZY_FAKTURA_ZALICZKOWA></CZY_FAKTURA_ZALICZKOWA>' . "\n";
        $xml .= '                <CZY_FAKTURA_KONCOWA></CZY_FAKTURA_KONCOWA>' . "\n";
        $xml .= '                <CZY_KOREKTA_FZAL></CZY_KOREKTA_FZAL>' . "\n";
        $xml .= '                <CZY_KOREKTA_FZAL_KONCOWEJ></CZY_KOREKTA_FZAL_KONCOWEJ>' . "\n";
        $xml .= '                <CZY_FZAL_100_PROCENT_BEZ_WZ></CZY_FZAL_100_PROCENT_BEZ_WZ>' . "\n";
        $xml .= '                <CZY_FZAL_100_PROCENT_Z_WZ></CZY_FZAL_100_PROCENT_Z_WZ>' . "\n";
        $xml .= '                <POTWIERDZONY_UE></POTWIERDZONY_UE>' . "\n";
        $xml .= '                <CZY_FISKALNY></CZY_FISKALNY>' . "\n";
        $xml .= '                <NR_DOKM>' . $invoice_data[0] . '</NR_DOKM>' . "\n";
        $xml .= '                <FORMA_PLATNOSCI>' . $paymentType . '</FORMA_PLATNOSCI>' . "\n";
        $xml .= '                <ID_FORMY_PLAT>' . $paymentTypeId . '</ID_FORMY_PLAT>' . "\n";
        $xml .= '                <CZY_POZ_KOSZTOWE_BAZOWE></CZY_POZ_KOSZTOWE_BAZOWE>' . "\n";
        $xml .= '                <TROJSTRONNY_UE></TROJSTRONNY_UE>' . "\n";
        $xml .= '                <WEWNETRZNY></WEWNETRZNY>' . "\n";
        $xml .= '                <MP></MP>' . "\n";
        $xml .= '                <METODA_KASOWA></METODA_KASOWA>' . "\n";
        $xml .= '                <ODWROTNY></ODWROTNY>' . "\n";
        $xml .= '                <ZALICZKA_ODROCZONA></ZALICZKA_ODROCZONA>' . "\n";
        $xml .= '                <FAKTURA_DO_PARAGONU></FAKTURA_DO_PARAGONU>' . "\n";
        $xml .= '                <DATY>' . "\n";
        $xml .= '                    <DATA_WYSTAWIENIA>' . $datediff . '</DATA_WYSTAWIENIA>' . "\n";
        $xml .= '                    <DATA_SPRZEDAZY>' . $datediff . '</DATA_SPRZEDAZY>' . "\n";
        $xml .= '                    <DATA_WPLYWU>' . $datediff . '</DATA_WPLYWU>' . "\n";
        $xml .= '                    <TERMIN_PLATNOSCI>' . $datediff . '</TERMIN_PLATNOSCI>' . "\n";
        $xml .= '                </DATY>' . "\n";
        $xml .= '                <WARTOSCI_NAGLOWKA>' . "\n";
        $xml .= '                    <NETTO_SPRZEDAZY>' . number_format((float)$invoice_data[9], 2, '.', '') . '</NETTO_SPRZEDAZY>' . "\n";
        $xml .= '                    <BRUTTO_SPRZEDAZY>' . number_format((float)$invoice_data[11], 2, '.', '') . '</BRUTTO_SPRZEDAZY>' . "\n";
        $xml .= '                    <NETTO_SPRZEDAZY_WALUTA></NETTO_SPRZEDAZY_WALUTA>' . "\n";
        $xml .= '                    <BRUTTO_SPRZEDAZY_WALUTA></BRUTTO_SPRZEDAZY_WALUTA>' . "\n";
        $xml .= '                    <NETTO_ZAKUPU>0.00</NETTO_ZAKUPU>' . "\n";
        $xml .= '                    <BRUTTO_ZAKUPU>0.00</BRUTTO_ZAKUPU>' . "\n";
        $xml .= '                    <SUMA_NETTO_POZYCJI_FAKTURY_ZALICZKOWEJ></SUMA_NETTO_POZYCJI_FAKTURY_ZALICZKOWEJ>' . "\n";
        $xml .= '                    <SUMA_NETTO_POZ_FZAL_WAL></SUMA_NETTO_POZ_FZAL_WAL>' . "\n";
        $xml .= '                    <KW_ROZRACH></KW_ROZRACH>' . "\n";
        $xml .= '                    <KW_ROZRACH_W></KW_ROZRACH_W>' . "\n";
        $xml .= '                    <KURS_WALUTY>' . $currency->getRate() . '</KURS_WALUTY>' . "\n";
        $xml .= '                    <KURS_WALUTY_PZ></KURS_WALUTY_PZ>' . "\n";
        $xml .= '                </WARTOSCI_NAGLOWKA>' . "\n";
        $xml .= '                <POLA_DODATKOWE>' . "\n";
        $xml .= '                    <POLE1>' . "\n";
        $xml .= '                        <NAZWA_POLA/>' . "\n";
        $xml .= '                        <WARTOSC_POLA/>' . "\n";
        $xml .= '                    </POLE1>' . "\n";
        $xml .= '                    <POLE2>' . "\n";
        $xml .= '                        <NAZWA_POLA/>' . "\n";
        $xml .= '                        <WARTOSC_POLA/>' . "\n";
        $xml .= '                    </POLE2>' . "\n";
        $xml .= '                    <POLE3>' . "\n";
        $xml .= '                        <NAZWA_POLA/>' . "\n";
        $xml .= '                        <WARTOSC_POLA/>' . "\n";
        $xml .= '                    </POLE3>' . "\n";
        $xml .= '                    <POLE4>' . "\n";
        $xml .= '                        <NAZWA_POLA/>' . "\n";
        $xml .= '                        <WARTOSC_POLA/>' . "\n";
        $xml .= '                    </POLE4>' . "\n";
        $xml .= '                    <POLE5>' . "\n";
        $xml .= '                        <NAZWA_POLA/>' . "\n";
        $xml .= '                        <WARTOSC_POLA/>' . "\n";
        $xml .= '                    </POLE5>' . "\n";
        $xml .= '                    <POLE6>' . "\n";
        $xml .= '                       <NAZWA_POLA/>' . "\n";
        $xml .= '                       <WARTOSC_POLA/>' . "\n";
        $xml .= '                   </POLE6>' . "\n";
        $xml .= '                   <POLE7>' . "\n";
        $xml .= '                       <NAZWA_POLA/>' . "\n";
        $xml .= '                       <WARTOSC_POLA/>' . "\n";
        $xml .= '                   </POLE7>' . "\n";
        $xml .= '                   <POLE8>' . "\n";
        $xml .= '                       <NAZWA_POLA/>' . "\n";
        $xml .= '                       <WARTOSC_POLA/>' . "\n";
        $xml .= '                   </POLE8>' . "\n";
        $xml .= '                   <POLE9>' . "\n";
        $xml .= '                       <NAZWA_POLA/>' . "\n";
        $xml .= '                       <WARTOSC_POLA/>' . "\n";
        $xml .= '                   </POLE9>' . "\n";
        $xml .= '                   <POLE10>' . "\n";
        $xml .= '                       <NAZWA_POLA/>' . "\n";
        $xml .= '                       <WARTOSC_POLA/>' . "\n";
        $xml .= '                   </POLE10>' . "\n";
        $xml .= '               </POLA_DODATKOWE>' . "\n";
        $xml .= '           </NAGLOWEK_DOKUMENTU>' . "\n";
        $xml .= '           <POZYCJE_DOKUMENTU>' . "\n";
        $xml .= '               <POZYCJA_DOKUMENTU>' . "\n";
        $xml .= '                   <ID_ARTYKULU></ID_ARTYKULU>' . "\n";
        $xml .= '                   <RODZAJ_POZYCJI></RODZAJ_POZYCJI>' . "\n";
        $xml .= '                   <KOD_VAT></KOD_VAT>' . "\n";
        $xml .= '                   <ID_ROZRACHUNKU>' . $id . '</ID_ROZRACHUNKU>' . "\n";
        $xml .= '                   <STRONA/>' . "\n";
        $xml .= '                   <STRONA_ROZRACHUNKU_POWIAZANEGO/>' . "\n";
        $xml .= '                   <POZYCJA_ZALICZKA></POZYCJA_ZALICZKA>' . "\n";
        $xml .= '                   <RODZAJ_KOREKTY></RODZAJ_KOREKTY>' . "\n";
        $xml .= '                   <OPIS_POZYCJI/>' . "\n";
        $xml .= '                   <KOD_OPERACJI/>' . "\n";
        $xml .= '                   <NAZWA_OPERACJI_FIN/>' . "\n";
        $xml .= '                   <TYP_PLATNIKA/>' . "\n";
        $xml .= '                   <ID_PLATNIKA></ID_PLATNIKA>' . "\n";
        $xml .= '                   <TYP_DOK_FIN/>' . "\n";
        $xml .= '                   <SYM_WAL/>' . "\n";
        $xml .= '                   <POZ_WAL_BAZOWE></POZ_WAL_BAZOWE>' . "\n";
        $xml .= '                   <ID_DOK_FIN></ID_DOK_FIN>' . "\n";
        $xml .= '                   <DATA_DOK_FIN></DATA_DOK_FIN>' . "\n";
        $xml .= '                   <TYP_POZ_FIN></TYP_POZ_FIN>' . "\n";
        $xml .= '                   <ID_KASY></ID_KASY>' . "\n";
        $xml .= '                   <SYMBOL_KASY/>' . "\n";
        $xml .= '                   <ID_RACHUNKU></ID_RACHUNKU>' . "\n";
        $xml .= '                   <NUMER_RACHUNKU/>' . "\n";
        $xml .= '                   <NR_DOKF/>' . "\n";
        $xml .= '                   <ID_URZEDU></ID_URZEDU>' . "\n";
        $xml .= '                   <WARTOSCI_POZYCJI>' . "\n";
        $xml .= '                       <WARTOSC_ZAKUPU_NETTO></WARTOSC_ZAKUPU_NETTO>' . "\n";
        $xml .= '                       <WARTOSC_ZAKUPU_BRUTTO></WARTOSC_ZAKUPU_BRUTTO>' . "\n";
        $xml .= '                       <WARTOSC_NETTO></WARTOSC_NETTO>' . "\n";
        $xml .= '                       <WARTOSC_BRUTTO></WARTOSC_BRUTTO>' . "\n";
        $xml .= '                       <WARTOSC_NETTO_WALUTA></WARTOSC_NETTO_WALUTA>' . "\n";
        $xml .= '                       <WARTOSC_BRUTTO_WALUTA></WARTOSC_BRUTTO_WALUTA>' . "\n";
        $xml .= '                       <WARTOSC_ZAKUPU_NETTO_WALUTA></WARTOSC_ZAKUPU_NETTO_WALUTA>' . "\n";
        $xml .= '                       <WARTOSC_ZAKUPU_BRUTTO_WALUTA></WARTOSC_ZAKUPU_BRUTTO_WALUTA>' . "\n";
        $xml .= '                       <WARTOSC_NETTO_BEZ_KOSZTOW></WARTOSC_NETTO_BEZ_KOSZTOW>' . "\n";
        $xml .= '                       <WARTOSC_BRUTTO_BEZ_KOSZTOW></WARTOSC_BRUTTO_BEZ_KOSZTOW>' . "\n";
        $xml .= '                       <WARTOSC_ZAKUPU_MARZA></WARTOSC_ZAKUPU_MARZA>' . "\n";
        $xml .= '                       <KURS_VAT></KURS_VAT>' . "\n";
        $xml .= '                   </WARTOSCI_POZYCJI>' . "\n";
        $xml .= '                   <AKCYZA></AKCYZA>' . "\n";
        $xml .= '                   <KWOTA_AKCYZY></KWOTA_AKCYZY>' . "\n";
        $xml .= '                   <KOD_CN/>' . "\n";
        $xml .= '                   <NR_DOK_AKCYZY/>' . "\n";
        $xml .= '                   <DATA_VAT>' . $datediff . '</DATA_VAT>' . "\n";
        $xml .= '                   <DATA_KURSU_VAT></DATA_KURSU_VAT>' . "\n";
        $xml .= '                   <ODWROTNY></ODWROTNY>' . "\n";
        $xml .= '                   <PODZIELONA_PLATNOSC>' . "\n";
        $xml .= '                       <PP></PP>' . "\n";
        $xml .= '                       <PP_NR_FAKTURY/>' . "\n";
        $xml .= '                       <PP_NIP/>' . "\n";
        $xml .= '                       <PP_KW_VAT_K></PP_KW_VAT_K>' . "\n";
        $xml .= '                       <PP_KW_VAT_R></PP_KW_VAT_R>' . "\n";
        $xml .= '                   </PODZIELONA_PLATNOSC>' . "\n";
        $xml .= '                   <POLA_DODATKOWE>' . "\n";
        $xml .= '                       <POLE1/>' . "\n";
        $xml .= '                       <POLE2/>' . "\n";
        $xml .= '                       <POLE3/>' . "\n";
        $xml .= '                       <POLE4/>' . "\n";
        $xml .= '                       <POLE5/>' . "\n";
        $xml .= '                       <POLE6/>' . "\n";
        $xml .= '                       <POLE7/>' . "\n";
        $xml .= '                       <POLE8/>' . "\n";
        $xml .= '                       <POLE9/>' . "\n";
        $xml .= '                       <POLE10/>' . "\n";
        $xml .= '                   </POLA_DODATKOWE>' . "\n";
        $xml .= '               </POZYCJA_DOKUMENTU>' . "\n";
        $xml .= '           </POZYCJE_DOKUMENTU>' . "\n";
        $xml .= '           <POZYCJE_KOSZTOWE/>' . "\n";
        $xml .= '           <ROZLICZENIA>' . "\n";
        $xml .= '               <ROZLICZENIE>' . "\n";
        $xml .= '                   <ID_DOKUMENTU_HANDLOWEGO>' . $id . '</ID_DOKUMENTU_HANDLOWEGO>' . "\n";
        $xml .= '                   <ID_DOKUMENTU_FINANSOWEGO>' . $id . '</ID_DOKUMENTU_FINANSOWEGO>' . "\n";
        $xml .= '                   <DATA_ROZLICZENIA>' . $datediff . '</DATA_ROZLICZENIA>' . "\n";
        $xml .= '                   <KWOTA>' . number_format((float)$invoice_data[11], 2, '.', '') . '</KWOTA>' . "\n";
        $xml .= '                   <KWOTA_W>' . number_format((float)$invoice_data[11], 2, '.', '') . '</KWOTA_W>' . "\n";
        $xml .= '                    <ID_ROZRACHUNKU_HANDLOWEGO></ID_ROZRACHUNKU_HANDLOWEGO>' . "\n";
        $xml .= '                    <ID_ROZRACHUNKU_FINANSOWEGO></ID_ROZRACHUNKU_FINANSOWEGO>' . "\n";
        $xml .= '                   <ID_ROZLICZENIA></ID_ROZLICZENIA>' . "\n";
        $xml .= '                   <ID_ROZLICZENIA_ZWIAZEK></ID_ROZLICZENIA_ZWIAZEK>' . "\n";
        $xml .= '                   <NUMER_DOKUMENTU_HANDLOWEGO>' . $invoice_data[0] . '</NUMER_DOKUMENTU_HANDLOWEGO>' . "\n";
        $xml .= '                   <NUMER_DOKUMENTU_FINANSOWEGO></NUMER_DOKUMENTU_FINANSOWEGO>' . "\n";
        $xml .= '               </ROZLICZENIE>' . "\n";
        $xml .= '           </ROZLICZENIA>' . "\n";
        $xml .= '           <VAT>' . "\n";
        $xml .= '               <STAWKA>' . "\n";
        $xml .= '                   <KOD_VAT>23</KOD_VAT>' . "\n";
        $xml .= '                   <NETTO>' . $invoice_data[9] . '</NETTO>' . "\n";
        $xml .= '                   <VAT>' . $invoice_data[10] . '</VAT>' . "\n";
        $xml .= '                   <NETTO_WALUTA>' . $invoice_data[9] . '</NETTO_WALUTA>' . "\n";
        $xml .= '                   <VAT_WALUTA>' . $invoice_data[10] . '</VAT_WALUTA>' . "\n";
        $xml .= '                   <KW_NABYCIA></KW_NABYCIA>' . "\n";
        $xml .= '                   <MARZA></MARZA>' . "\n";
        $xml .= '                   <DATA_VAT>' . $datediff . '</DATA_VAT>' . "\n";
        $xml .= '                   <DATA_KURSU>' . $datediff . '</DATA_KURSU>' . "\n";
        $xml .= '                   <KURS_VAT>0.23</KURS_VAT>' . "\n";
        $xml .= '                   <ODWROTNY></ODWROTNY>' . "\n";
        $xml .= '                   <ODWROTNY_TOWAR_USLUGA/>' . "\n";
        $xml .= '               </STAWKA>' . "\n";
        $xml .= '           </VAT>' . "\n";
        $xml .= '       </DOKUMENT>' . "\n";
        $id++;
    }
    $xml .= '   </DOKUMENTY>' . "\n";
    $xml .= '   <KARTOTEKA_KONTRAHENTOW>' . "\n";
    $ignoreHeaderRow = true;
    $id = 1;
    foreach ($data as $row) {
        if ( $ignoreHeaderRow ) {
            $ignoreHeaderRow = false;
            continue;
        }
        $row = str_replace('"', '', $row);
        $invoice_data = explode(';', $row);

        $xml .= '       <KONTRAHENT>' . "\n";
        $xml .= '           <ID_KONTRAHENTA>' . $id . '</ID_KONTRAHENTA>' . "\n";
        $xml .= '           <KOD_KONTRAHENTA>' . $id . '</KOD_KONTRAHENTA>' . "\n";
        $xml .= '           <NAZWA>' . xmlEscape(mb_substr(str_replace('"', '', $invoice_data[2]), 0, 30)) . '</NAZWA>' . "\n";
        $xml .= '           <NIP>' . $invoice_data[3] . '</NIP>' . "\n";
        $xml .= '           <PESEL/>' . "\n";
        $xml .= '           <RODZAJ_EWIDENCJI>1</RODZAJ_EWIDENCJI>' . "\n";
        $xml .= '           <VAT_CZYNNY></VAT_CZYNNY>' . "\n";
        $xml .= '           <KOD_POCZTOWY>' . $invoice_data[6] . '</KOD_POCZTOWY>' . "\n";
        $xml .= '           <MIEJSCOWOSC>' . $invoice_data[5] . '</MIEJSCOWOSC>' . "\n";
        $xml .= '           <ULICA_LOKAL>' . $invoice_data[4] . '</ULICA_LOKAL>' . "\n";
        $xml .= '           <NAZWA_PELNA>' . xmlEscape($invoice_data[2]) . '</NAZWA_PELNA>' . "\n";
        $xml .= '           <ADRES>' . $invoice_data[4] . '</ADRES>' . "\n";
        $xml .= '           <SYMBOL_KRAJU_KONTRAHENTA>'.$countryCode.'</SYMBOL_KRAJU_KONTRAHENTA>' . "\n";
        $xml .= '           <NAZWA_KLASYFIKACJI></NAZWA_KLASYFIKACJI>' . "\n";
        $xml .= '           <NAZWA_GRUPY></NAZWA_GRUPY>' . "\n";
        $xml .= '           <ODBIORCA>1</ODBIORCA>' . "\n";
        $xml .= '           <DOSTAWCA></DOSTAWCA>' . "\n";
        $xml .= '           <ID_KLASYFIKACJI></ID_KLASYFIKACJI>' . "\n";
        $xml .= '           <ID_GRUPY></ID_GRUPY>' . "\n";
        $xml .= '           <CZY_KONTRAHENT_UE></CZY_KONTRAHENT_UE>' . "\n";
        $xml .= '           <POLA_DODATKOWE>' . "\n";
        $xml .= '               <POLE1>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE1>' . "\n";
        $xml .= '               <POLE2>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE2>' . "\n";
        $xml .= '               <POLE3>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE3>' . "\n";
        $xml .= '               <POLE4>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE4>' . "\n";
        $xml .= '               <POLE5>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE5>' . "\n";
        $xml .= '               <POLE6>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE6>' . "\n";
        $xml .= '               <POLE7>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE7>' . "\n";
        $xml .= '               <POLE8>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE8>' . "\n";
        $xml .= '               <POLE9>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE9>' . "\n";
        $xml .= '               <POLE10>' . "\n";
        $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
        $xml .= '                   <WARTOSC_POLA/>' . "\n";
        $xml .= '               </POLE10>' . "\n";
        $xml .= '           </POLA_DODATKOWE>' . "\n";
        $xml .= '           <RAKS_KOD_KONTRAHENTA/>' . "\n";
        $xml .= '           <ID_KONTRAHENTA_JST>0</ID_KONTRAHENTA_JST>' . "\n";
        $xml .= '       </KONTRAHENT>' . "\n";
        $id++;
    }
    $xml .= '   </KARTOTEKA_KONTRAHENTOW>' . "\n";
    $xml .= '   <KARTOTEKA_PRACOWNIKOW>' . "\n";
    $xml .= '   </KARTOTEKA_PRACOWNIKOW>' . "\n";
    $xml .= '   <KARTOTEKA_ARTYKULOW>' . "\n";
    $xml .= '       <ARTYKUL>' . "\n";
    $xml .= '           <ID_ARTYKULU></ID_ARTYKULU>' . "\n";
    $xml .= '           <NAZWA_ARTYKULU></NAZWA_ARTYKULU>' . "\n";
    $xml .= '           <KOD_KRESKOWY/>' . "\n";
    $xml .= '           <ID_MAGAZYNU></ID_MAGAZYNU>' . "\n";
    $xml .= '           <SYMBOL_MAGAZYNU></SYMBOL_MAGAZYNU>' . "\n";
    $xml .= '           <RODZAJ_ARTYKULU></RODZAJ_ARTYKULU>' . "\n";
    $xml .= '           <INDEKS_KATALOGOWY></INDEKS_KATALOGOWY>' . "\n";
    $xml .= '           <INDEKS_HANDLOWY></INDEKS_HANDLOWY>' . "\n";
    $xml .= '           <ID_KATEGORII></ID_KATEGORII>' . "\n";
    $xml .= '           <NAZWA_KATEGORII></NAZWA_KATEGORII>' . "\n";
    $xml .= '           <POLA_DODATKOWE>' . "\n";
    $xml .= '               <POLE1>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE1>' . "\n";
    $xml .= '               <POLE2>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE2>' . "\n";
    $xml .= '               <POLE3>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE3>' . "\n";
    $xml .= '               <POLE4>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE4>' . "\n";
    $xml .= '               <POLE5>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE5>' . "\n";
    $xml .= '               <POLE6>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE6>' . "\n";
    $xml .= '               <POLE7>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE7>' . "\n";
    $xml .= '               <POLE8>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE8>' . "\n";
    $xml .= '               <POLE9>' . "\n";
    $xml .= '                   <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                   <WARTOSC_POLA/>' . "\n";
    $xml .= '               </POLE9>' . "\n";
    $xml .= '               <POLE10>' . "\n";
    $xml .= '                    <NAZWA_POLA></NAZWA_POLA>' . "\n";
    $xml .= '                    <WARTOSC_POLA/>' . "\n";
    $xml .= '                </POLE10>' . "\n";
    $xml .= '            </POLA_DODATKOWE>' . "\n";
    $xml .= '        </ARTYKUL>' . "\n";
    $xml .= '    </KARTOTEKA_ARTYKULOW>' . "\n";
    $xml .= '</MAGIK_EKSPORT>' . "\n";

    if ( file_exists('wapro.xml') ) {
        unlink('wapro.xml');
    }

    $xmlFile = fopen('wapro.xml', 'w');
    fwrite($xmlFile, $xml);
    fclose($xmlFile);
    if ( !isset($_SESSION['fileIsReady']) ) {
        $_SESSION['fileIsReady'] = true;
    }

header('Location: index.php');
