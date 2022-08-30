<?php

ini_set('display_errors', E_ALL);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
error_reporting(0);

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

    function getDescriptionInfo( $url , $csrf )
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
        
        $ch = curl_init( $url );
        curl_setopt_array( $ch, $options );

        $x_csrf = 'x-csrf-token:' . $csrf;

        $result = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $data = json_decode($result, true);

        $phone = '0';

        if(is_array($data))
            if(array_key_exists('data', $data))
                if(array_key_exists('phone', $data['data']))
                    if(array_key_exists('numbers', $data['data']['phone']))
                        if(isset($data['data']['phone']['numbers'][0]))
                            $phone = $data['data']['phone']['numbers'][0]['text'];
                        

        return $phone;

        
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

                $name = $dom->find('.link-primary', 0)->plaintext;

                createLog($key,trim($original_number),$page_link,true);

                $result = get_web_page($page_link);
                $html   = $result['content'];
                $dom    = str_get_html($html);

                if(gettype($dom) == 'boolean' || $dom == ''){
                    createLog($key,trim($original_number),'Second loop error');
                    return;
                }

                // Get Token

                $token = '';
                foreach($dom->find('.col.mb-4') as $element){
                    $dom3 = str_get_html($element);

                    if(trim($dom3->find('h3', 0)->plaintext) == 'Lön och anmärkning'){
                        $url = $dom3->find('a', 0)->href;
                        $token = explode('/', $url);
                        $token = end($token);
                    }
                }


                foreach($dom->find('.inner') as $element){

                    $dom1 = str_get_html($element);

                    if(trim($dom1->find('h3', 0)->plaintext) == 'Bolagsinformation'){

                        foreach($dom1->find('table tr') as $key => $element){

                            if($key > 4)
                                break;

                            $dom2 = str_get_html($element);

                            if(trim($dom2->find('td', 0)->plaintext) == 'Org.nummer:')
                                $org_number = $dom2->find('td', 1)->plaintext;

                            if(trim($dom2->find('td', 0)->plaintext) == 'Ordförande:')
                                $president = $dom2->find('td', 1)->plaintext;

                            
                            if(trim($dom2->find('td', 0)->plaintext) == 'E-post:')
                                $email = $dom2->find('td', 1)->plaintext;

                            
                            if(trim($dom2->find('td', 0)->plaintext) == 'Hemsida:')
                                $website = $dom2->find('td', 1)->plaintext;
                            
                        }
                    }
                }

                $element = $dom->find('meta[name="csrf-token"]', 0);

                $csrf = $element->content ?? '';

                $phone = '';

                if($token && $csrf)
                    $phone = getDescriptionInfo('https://www.merinfo.se/api/v1/people/' . $token . '/description' , $csrf);

                

                
            }
            else if(!$found){
                handleFailedAddresses($dom, $html, $key, $number);
                return;
            }

        }
        else{
            
            createLog($key,trim($original_number),'Proxy or Scraper not working');
            return;
        
        }

        // Store data
        
        if(!$name){
            createLog($key,trim($original_number), 'Company name not found');
        }
        else{

            $txt =  trim($original_number)  . "\t" 
                    .trim($name)            . "\t" 
                    .trim($org_number)      . "\t" 
                    .trim($president)       . "\t" 
                    .trim($phone)           . "\t" 
                    // .trim($email)           . "\t" 
                    // .trim($website)         . "\t"
                   ;

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

    function getLastAddress()
    {
        
        $file_addresses = fopen('uploads/output.txt', "r") or die("Unable to open file!");

        $last_line = '';

        while (($line = fgets($file_addresses)) !== false)
            $last_line = $line;

        $last_address = [];
        $last_address = preg_split("/\t+/", $last_line);

        return $last_address[0] ?? '';

    }


    if (1) {
        
        $input_file_name = php_uname('n');
        
        $file_name = "output";
        // $file = fopen('uploads/'.$file_name.'.txt', "w");
        // fclose($file);


        if($input_file_name == 'DESKTOP-AJFT9FC')
            $file_addresses = fopen("source/ubuntu-s-1vcpu-1gb-amd-fra1-01.txt", "r") or die("Unable to open file!");
        else{
            $input_file_name = str_replace("scraper", "input", $input_file_name);
            $input_file_name = 'source/' . $input_file_name . '.txt';
            $file_addresses = fopen($input_file_name, "r") or die("Unable to open file!");
        
        }


        $addresses   = [];
        $unique_addresses = [];
        $found = false;


        while (($line = fgets($file_addresses)) !== false)
            $numbers_array[] = $line;


        $last_address = getLastAddress();

        foreach(array_unique($numbers_array) as $key => $address){
            
            if($last_address && !$found){
                
                if(trim($last_address) !== trim($address))
                    continue;
                
                else
                    $found = true;
            
            }
            
            getData(trim($address), $key, $file_name);
                
        }

    }