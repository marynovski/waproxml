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
            height: 200px;
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
    </style>
</head>
<body>
    <header id="header">
        <h1>FatApp WAPRO XML Converter v.0.1</h1>
    </header>
    <main id="content">
        <form action="upload.php" method="post" ENCTYPE="multipart/form-data">
            <div class="form-field">
                <label for="csv_file">Plik CSV z danymi faktur</label>
                <input style="font-size: 20px;" type="file" accept="text/csv" name="csv_file" required>
            </div>
            <button id="submit-button" type="submit">Konwertuj</button>
        </form>
    </main>
<?php
    if(isset($_SESSION['fileIsReady']) && $_SESSION['fileIsReady'] === true) {
        echo  '<section id="file-download">'
             .'    <a href="download.php">'
             .'        <button id ="download-button" type="button">Pobierz</button >'
             .'    </a>'
             .'</section>';
        unset($_SESSION['fileIsReady']);
    }
?>
</body>
</html>