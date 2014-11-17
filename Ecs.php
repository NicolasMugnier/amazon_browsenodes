<?php

/**
 * Class ECS_Browsenodes
 *
 * @author Nicolas Mugnier <nicolas@boostmyshop.com>
 */
class ECS_Browsenodes {

    const AWS_ACCESS_KEY_ID = '';
    const SECRET_KEY = '';

    /**
     * BrowseNode lookup request on advertising API
     *
     * @param $browseNodeId
     * @param string $country
     * @throws Exception
     * @internal param string $itemId
     * @internal param string $idType
     * @return object $response
     */
    public function request($browseNodeId, $country = 'US') {

        $endpoints = array(
            'CA' => '.ca', // OK | OK
            'CN' => '.com.cn', // KO | KO
            'DE' => '.de', // OK | OK
            'ES' => '.es', // KO | OK
            'FR' => '.fr', // OK | OK
            'IT' => '.it', // KO | OK
            'JP' => '.jp', // OK | KO
            'UK' => '.co.uk', // OK | OK
            'US' => '.com' // OK | OK
        );
        
        if(array_key_exists($country, $endpoints)){
            $ext = $endpoints[$country];
        }else{
            throw new Exception('Unknow endpoint for '.$country);
        }
        
        // init
        //$host = "ecs.amazonaws".$ext; // OLD URL, does not work for all countries       
        $host = 'webservices.amazon'.$ext; // work for all :)       
        $path = "/onca/xml";

        $timestamp = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time() + 3600 * 2);

        // set params
        $params = array(
            'Service' => 'AWSECommerceService',
            'AWSAccessKeyId' => self::AWS_ACCESS_KEY_ID,
            'Operation' => 'BrowseNodeLookup',
            'BrowseNodeId' => $browseNodeId,
            'Timestamp' => $timestamp,
            'AssociateTag' => 'none',
            //'ResponseGroup' => 'NewReleases',
            'Version' => '2011-08-01'
        );     

        // sort parameters
        uksort($params, 'strcmp');

        // encode params
        foreach ($params as $param => $value) {
            $param = str_replace('%7E', '~', rawurlencode($param));
            if ($param != 'Timestamp')  //do not encode timestamp !!!
                $value = str_replace('%7E', '~', rawurlencode($value));
        }

        // calculate signature
        $signature = $this->calculSignature($params, self::SECRET_KEY, $host, $path);

        // add signature to query parameters
        $params['Signature'] = $signature;        
        
        $url = 'http://'.$host.$path.'?'.http_build_query($params);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if($status == 200){
            
            // save response 
            $path = dirname(__FILE__) . '/ECS/BrowseNodeLookup/'.implode('/',str_split($browseNodeId)).'/'.$country;
            if(!file_exists($path)){
                mkdir($path, 0755, true);
            }
            
            file_put_contents($path.'/response.xml', $data);
            
        }/*else{
            
            throw new Exception('Status : '.$status.', message : '.$data);
            
        }*/
        
        return array('data' => $data, 'status' => $status);
    }        

    /**
     * Calcul signature
     *
     * @param array $params
     * @param string $secretAccessKey
     * @param string $host
     * @param string $path
     * @return string
     */
    public function calculSignature($params, $secretAccessKey, $host, $path) {

        $tmp = array();
        $signature = "";

        // url encode parameters names and values
        foreach ($params as $param => $value) {

            if ($param == 'Timestamp')  //now, encode timestamp
                $value = str_replace('%7E', '~', rawurlencode($value));

            $tmp[] = $param . "=" . $value;
        }

        // construct query string
        $query = implode('&', $tmp);

        // construct signToString
        $signToString = "GET" . "\n";
        $signToString .= $host . "\n";
        $signToString .= $path . "\n";
        $signToString .= $query;

        // HMAC $signToString with $secretAccessKey and convert result to base64
        $signature = base64_encode(hash_hmac("sha256", $signToString, $secretAccessKey, true));

        return $signature;
    }   
        
}
