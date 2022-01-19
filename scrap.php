<?php

ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

include_once('simple_html_dom.php');
require_once (__DIR__ . '/vendor/autoload.php');
use Rct567\DomQuery\DomQuery;
use HeadlessChromium\BrowserFactory;

    function get_web_page( $url )
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        // $url = urlencode('https://www.amazon.com/dp/B00JITDVD2');

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
            CURLOPT_POST           => false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_PROXY          => 'zproxy.lum-superproxy.io',
            CURLOPT_PROXYPORT      => '22225',
            CURLOPT_PROXYUSERPWD   => 'lum-customer-hl_fa848026-zone-daniel_sahlin_zone-country-se:0xwx5ytxlfcc',
            CURLOPT_HTTPPROXYTUNNEL=> 1,
        );
        
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }

    function getOperatorInfo( $number , $csrf , $id )
    {
        $x_csrf = 'x-csrf-token:' . $csrf;

        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "POST",        //set request type post or get
            CURLOPT_POST           => true,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_PROXY          => 'zproxy.lum-superproxy.io',
            CURLOPT_PROXYPORT      => '22225',
            CURLOPT_PROXYUSERPWD   => 'lum-customer-hl_fa848026-zone-daniel_sahlin_zone-country-se:0xwx5ytxlfcc',
            CURLOPT_HTTPPROXYTUNNEL=> 1,
            CURLOPT_HTTPHEADER     => array(
                                        'origin: https://www.merinfo.se',
                                        $x_csrf,
                                        'Content-Type: application/json',
                                    ),

        );
        
        $ch = curl_init( 'https://www.merinfo.se/ajax/operator' );
        curl_setopt_array( $ch, $options );

        $x_csrf = 'x-csrf-token:' . $csrf;

        $number_array['phonenumber'] = $number;
        $number_array['id'] = 340741;
        $json_data = json_encode($number_array);


        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

        // print_r($ch);die();

        $result = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $data = json_decode($result, true);

        if(is_array($data)){
            if(array_key_exists('operator', $data))
                return $data['operator'];
            else
                return 'no operator information';
        }
        else{
            return 'no operator information';
        }
        
    }

    function headLessRequest($url){

        $browserCommand = 'google-chrome';

        $browserFactory = new BrowserFactory($browserCommand);
        $browser = $browserFactory->createBrowser([
                    'customFlags' => ['--no-sandbox'],
                ]);

        try {
            // creates a new page and navigate to an url
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            return $page->getHtml();
        }
        finally {
            $browser->close();
        }
    }

    function putTestHtml($html = '')
    {
        file_put_contents("uploads/html.txt", "");

        $myfile = fopen('./uploads/'.'html'.'.txt', "a") or die("Unable to open file!");
        $txt = $html;
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    function getData($number,$key,$file_name)
    {
        $original_number = $number;

        $url = 'https://www.merinfo.se/search?who='.trim($number).'&where=';
        
        $result = get_web_page($url);
        $html   = $result['content'];
        $dom    = str_get_html($html);

        $page_links   = [];
        $page_link    = '';
        $number_writeable = '';

        if(gettype($dom) !== 'boolean'){

            $found = false;

            foreach($dom->find('.link-primary') as $element){

                $page_link = $element->href;
                
                if($page_link)
                    $found = true;

                break;

            }


            if($found){

                createLog($key,trim($original_number),$page_link,true);

                $result = get_web_page($page_link);
                $html   = $result['content'];
                $dom    = str_get_html($html);

                if(gettype($dom) == 'boolean' || $dom == ''){
                    createLog($key,trim($original_number),'Second loop error');
                    return;
                }

                $result = '';
                
                $e = $dom->find('.header-name a', 0);
                if(isset($e->innertext))
                    $company_name = $e->text();
                else
                    $company_name = '';

                
                $e = $dom->find('.header-name p', 0);
                if(isset($e->innertext))
                    $company_number = $e->text();
                else
                    $company_number = '';

                
                $e = $dom->find('address', 0);
                if(isset($e->innertext))
                    $address = $e->text();
                else
                    $address = '';

                
                $address = trim(preg_replace('/\s\s+/', ' - ', $address));


                $e = $dom->find('.overflow-hidden .hidden-print a', 1);
                if(isset($e->href))
                    $phone_number_link = $dom->find('.overflow-hidden .hidden-print a', 1)->href;
                else{
                    createLog($key,trim($original_number),'Phone link not found', true);
                    return;
                }

                $result = get_web_page($phone_number_link);
                $html   = $result['content'];
                $dom    = str_get_html($html);

                if(gettype($dom) == 'boolean'){
                    createLog($key,trim($original_number),'Phone link error');
                    return;
                }

                $element = $dom->find('meta', 3);

                $csrf = explode('"', $element);

                $csrf = $csrf[3] ?? '';

                $numbers = [];
                $number_writeable = '';

                // echo $id = $dom->find('table tr')->id;
                foreach($dom->find('table tr') as $key => $element){

                    if($key == 0)
                        continue;

                    $number = $element->find('td', 0)->text();
                    $anvandare = $element->find('td', 1)->text();
                    $id = $element->id;
                    $operator = '';
                    
                    if($number)
                        $operator = getOperatorInfo($number, $csrf, $id);

                    $number_writeable .= trim($number) . "\t" .
                                         trim($operator) . "\t".
                                         trim($anvandare) . "\t";

                }


                // if (strpos($result, 'bostadsrätt') !== false) {
                //     $living_type = 'bostadsrätt';
                // }
                // else{
                //     createLog($key,trim($original_number),'third loop error');
                //     return;
                // }
                
            }
            else if(!$found){
                handleFailedAddresses($dom, $html, $key, $number);
                return;
            }

        }
        else{
            
            createLog($key,trim($original_number),'Proxy or Scraper not working');
            // sleep(10);
            return;
        
        }

        // Store data
        
        if(!$company_name){
            createLog($key,trim($original_number), 'Company name not found');
        }
        else{

            $txt = trim($original_number) . "\t" .
                   trim($company_name) . "\t" .
                   trim($company_number) . "\t" .
                   trim($address) . "\t" .
                   trim($number_writeable) . "\t";

            $myfile = fopen('./uploads/'.$file_name.'.txt', "a") or die("Unable to open file!");
            
            fwrite($myfile, $txt);
            fwrite($myfile, "\n");
            fclose($myfile);
        }

    }

    function createLog($key,$address,$page_link, $address_found = false){
        
        $myfile = fopen('./logs/log.txt', "a") or die("Unable to open file!");

        $txt = $key . ' - ' . trim($address) . ' - ' .  trim($page_link);

        fwrite($myfile, $txt);
        fwrite($myfile, "\n");
        fclose($myfile);

        // End Log

        if(!$address_found){

            $myfile  = fopen('./logs/failed.txt', "a") or die("Unable to open file!");

            fwrite($myfile, urldecode($address));
            fwrite($myfile, "\n");
            fclose($myfile);

            $myfile  = fopen('./logs/failed-log.txt', "a") or die("Unable to open file!");

            $address = $address . ',';
            fwrite($myfile, $address);
            fwrite($myfile, "\n");
            fclose($myfile);            

        }
    }

    function handleFailedAddresses($dom, $html, $key, $address){

        foreach($dom->find('.h2') as $element){
            
            if($element == '<h2 class="h2"> Ingen träff </h2>'){
                createLog($key,$address,'Address not found',true);
                return;
            }

        }

        $dom = new DomQuery($html);
        if($dom->find('h1') == '<h1 data-translate="turn_on_js" style="color:#bd2426;">Please turn JavaScript on and reload the page.</h1><h1><span data-translate="checking_browser">Checking your browser before accessing</span> merinfo.se.</h1>'){
            
            createLog($key,$address,'Javascript error');
            return;

        }
        else if($dom->find('a') == '<a rel="noopener noreferrer" href="https://www.cloudflare.com/5xx-error-landing/" target="_blank">Cloudflare</a>'){

            createLog($key,$address,'Cloudflare error');
            return;
            
        }
        else{

            createLog($key,$address,'Unknown Error');

        }

    }


    function runFailedNumbers($file_name){

        $failed_addresses = fopen("logs/failed.txt", "r") or die("Unable to open file!");

        $addresses = [];

        while (($line = fgets($failed_addresses)) !== false) {

            $addresses[] = $line;
            
        }

        // print_r($addresses);

        file_put_contents("logs/failed.txt", "");

        foreach(array_unique($addresses) as $key => $address){

            getData($address,$key,$file_name);

        }
    }


    if (1) {
        
        $file_name = 'output';
        $file = fopen('uploads/'.$file_name.'.txt', "w");
        fclose($file);

        $numbers = fopen("source/100k-4.txt", "r") or die("Unable to open file!");

        $numbers_array = [];

        while (($line = fgets($numbers)) !== false) {
            $numbers_array[] = $line;
        }


        foreach(array_unique($numbers_array) as $key => $address){

            // if($key > 344)
                getData($address,$key,$file_name);
            // else
                // die();
        }

        // print_r($numbers_array);

        // createLog(0001, 'loop 1', 'New 1 loop started', true);
        // runFailedNumbers($file_name);

        // createLog(0002, 'loop 2', 'New 2 loop started', true);
        // runFailedNumbers($file_name);

        // createLog(0003, 'loop 3', 'New 3 loop started', true);
        // runFailedNumbers($file_name);

        // createLog(0004, 'loop 4', 'New 4 loop started', true);
        // runFailedNumbers($file_name);

        // createLog(0005, 'loop 5', 'New 5 loop started', true);
        // runFailedNumbers($file_name);

        // createLog(0006, 'loop 6', 'New 6 loop started', true);
        // runFailedNumbers($file_name);        

        // echo 'Finsihed';
    }