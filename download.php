<?php
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="wapro.xml"');
readfile('wapro.xml');