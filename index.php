<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick start. Local server-side application with UI</title>
</head>
<body>
<div id="auth-data">OAuth 2.0 data from REQUEST:
    <PRE>
    <?
        print_r($_REQUEST);
    ?>
    </PRE>
</div>
<div id="name">

    <?
        // use $_REQUEST data as OAuth 2.0 params to execute REST API calls

        $queryUrl = 'https://'.$_REQUEST['DOMAIN'].'/rest/user.current.json';

        // as user.current does not have any specific parameters we just set an access_token ("auth")
        $queryData = http_build_query(array(
            "auth" => $_REQUEST['AUTH_ID']
        ));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);

        // print_r($result);
        echo "Приложение с стороннего сервера: ".$result['result']['NAME'].' '.$result['result']['LAST_NAME'];
    
        // ================================================================
        // use $_REQUEST data as OAuth 2.0 params to execute REST API calls

        filter[CLOSED]=Y&filter[STAGE_SEMANTIC_ID]=S&Params[NAV_PARAMS][nPageSize]=50&Params[NAV_PARAMS][iNumPage]=2
        $queryUrl = 'https://'.$_REQUEST['DOMAIN'].'/rest/crm.deal.list.json';

        // as user.current does not have any specific parameters we just set an access_token ("auth")
        $queryData = http_build_query(array(
            "auth" => $_REQUEST['AUTH_ID']
        ));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
        echo '<h4>Deals</h4><pre>'
        print_r($result);
        echo '</pre>'
        
    
    ?>
</div>
</body>
</html>
