<?php
    session_start();
?>
<!Doctype html>
<html lang="pl">
<head>
    <style>
        #header {
            width: 100%;
            height: 100px;
            margin-right: auto;
            margin-left: auto;
            text-align: center;
        }

        #content {
            width: 500px;
            height: 250px;
            margin-right: auto;
            margin-left: auto;
            text-align: center;
            border: 1px solid #C1C1C1;
            font-size: 30px;
        }

        #submit-button {
            margin-top: 50px;
            width: 250px;
            height: 60px;
            font-size: 30px;
            background-color: #00FF00;
            border-radius: 20px;
            cursor: pointer;
        }

        #download-button {
            margin-left: 42.5%;
            margin-top: 50px;
            width: 250px;
            height: 60px;
            font-size: 30px;
            background-color: #28c3ff;
            border-radius: 20px;
            cursor: pointer;
            text-align: center;
        }

        .error {
            color: #FF0000;
            text-align: center;
            font-size: 20px!important;
        }
    </style>
</head>
<body>
    <header id="header">
        <h1>FatApp WAPRO XML Converter v.0.3</h1>
    </header>
    <main id="content">
        <div style="font-size: 15px;">
            <a href="examples/withoutpayment.csv">Wzór 1. Bez typu płatności(FGZ, ORŁY)</a><br>
            <a href="examples/withpayment.csv">Wzór 2. Z typem płatności(Greenwash)</a><br>
        </div>
        <form action="upload.php" method="post" ENCTYPE="multipart/form-data">
            <div class="form-field">
                <label for="csv_file">Plik CSV z danymi faktur</label>
                <input style="font-size: 20px;" type="file" accept="text/csv" name="csv_file" required>
            </div>
            <button id="submit-button" type="submit">Konwertuj</button>
            <br><br>
<?php
            if(isset($_SESSION['fileError'])) {
                echo $_SESSION['fileError'];
                unset($_SESSION['fileError']);
                unset($_SESSION['fileIsReady']);
            }
?>

        </form>
    </main>
<?php
    if(isset($_SESSION['fileIsReady']) && $_SESSION['fileIsReady'] === true) {
        unset($_SESSION['fileIsReady']);
        echo  '<section id="file-download">'
             .'    <a href="download.php">'
             .'        <button id ="download-button" type="button">Pobierz</button >'
             .'    </a>'
             .'</section>';
    }


?>
</body>
</html>