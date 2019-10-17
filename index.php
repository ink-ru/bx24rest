<?
function redirect($url)
{
    Header("HTTP 302 Found");
    Header("Location: ".$url);
    die();
}

define('APP_ID', 'local.5da8013d689617.20409150'); // take it from Bitrix24 after adding a new application
define('APP_SECRET_CODE', 'VEBs3BE0APuotRGAS4oH6jfCiNZ4ZjEX89f9s8v2aEWPDXDPWu'); // take it from Bitrix24 after adding a new application
define('APP_REG_URL', 'https://bx24standalone.herokuapp.com/'); // the same URL you should set when adding a new application in Bitrix24


$domain = isset($_REQUEST['portal']) ? $_REQUEST['portal'] : ( isset($_REQUEST['domain']) ? $_REQUEST['domain'] : 'empty');

$step = 0;

if (isset($_REQUEST['portal']))
    {
        $step = 1;

        sleep(5);
        requestCode($domain);
    }
if (isset($_REQUEST['code'])) $step = 2;

$btokenRefreshed = null;

$arScope = array('user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick start. Local server-side application without UI in Bitrix24</title>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>

<?
switch ($step) {
    case 1:
        // we need to get the first authorization code from Bitrix24 where our application is _already_ installed
        ?>
        <script type="text/javascript">
            window.location.replace('<?=$domain?>');
        </script>
        <?
        break;

    case 2:
        //we've got the first authorization code and use it to get an access_token and a refresh_token (if you need it later)
        echo "step 2 (getting an authorization code):<pre>";
        print_r($_REQUEST);
        echo "</pre><br/>";

        $arAccessParams = requestAccessToken($_REQUEST['code'], $_REQUEST['server_domain']);
        echo "step 3 (getting an access token):<pre>";
        print_r($arAccessParams);
        echo "</pre><br/>";

        $arCurrentB24User = executeREST($arAccessParams['client_endpoint'], 'user.current', array(),
            $arAccessParams['access_token']);
        
        $arParams = array("order" => array("STAGE_ID" => "ASC"),
                          "filter" => array(">PROBABILITY" => '1'),
                          // "select" => array( "ID", "TITLE", "STAGE_ID", "PROBABILITY", "OPPORTUNITY", "CURRENCY_ID" ),
                          );
        
        $rDeals = executeREST($arAccessParams['client_endpoint'], 'crm.deal.list', $arParams,
            $arAccessParams['access_token']);

        break;
    default:
        break;
}
?>

</head>
<body>
<?if ($step == 0) {?>
    step 1 (redirecting to Bitrix24):<br/>
    <form action="" method="post">
        <span>https://</span><input type="text" name="portal" id="portal" placeholder="Bitrix24 domain" value="gir.bitrix24.ru">
        <!-- <input type="submit" value="Authorize"> -->
        <input type="button" name="sbmt" id="sbmt" onclick="go_auth()">
    </form>
    <script type="text/javascript">
        function go_auth() {
            let domain = document.getElementById("portal").value
            url = 'https://' + domain + '/oauth/authorize/?client_id=<?=urlencode(APP_ID)?>';
            window.location.replace(url); // window.open(url);
        }
    </script>
    <?
}
elseif ($step == 2) {
    echo "Данные из Bitrix24".$arCurrentB24User["result"]["NAME"] . " " . $arCurrentB24User["result"]["LAST_NAME"];
    echo '<pre>';
    print_r($rDeals);
    echo '</pre>';
}
?>
</body>
</html>
<?
function executeHTTPRequest ($queryUrl, array $params = array()) {
    $result = array();
    $queryData = http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $curlResult = curl_exec($curl);
    curl_close($curl);

    if ($curlResult != '') $result = json_decode($curlResult, true);

    return $result;
}

function requestCode ($domain) {
    $url = 'https://' . $domain . '/oauth/authorize/' .
        '?client_id=' . urlencode(APP_ID);
    redirect($url);
}

function requestAccessToken ($code, $server_domain) {
    $url = 'https://' . $server_domain . '/oauth/token/?' .
        'grant_type=authorization_code'.
        '&client_id='.urlencode(APP_ID).
        '&client_secret='.urlencode(APP_SECRET_CODE).
        '&code='.urlencode($code);
    return executeHTTPRequest($url);
}

function executeREST ($rest_url, $method, $params, $access_token) {
    $url = $rest_url.$method.'.json';
    return executeHTTPRequest($url, array_merge($params, array("auth" => $access_token)));
}
