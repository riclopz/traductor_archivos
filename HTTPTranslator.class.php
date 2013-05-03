<?php
/*
 * Class:HTTPTranslator
 *
 * Processing the translator request.
 */
Class HTTPTranslator {
    /*
     * Create and execute the HTTP CURL request.
     *
     * @param string $url        HTTP Url.
     * @param string $authHeader Authorization Header string.
     * @param string $postData   Data to post.
     *
     * @return string.
     *
     */
    function curlRequest($url, $authHeader, $postData=''){
        //Initialize the Curl Session.
        $ch = curl_init();
        //Set the Curl url.
        curl_setopt ($ch, CURLOPT_URL, $url);
        //Set the HTTP HEADER Fields.
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array($authHeader,"Content-Type: text/xml"));
        //CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, False);
        if($postData) {
            //Set HTTP POST Request.
            curl_setopt($ch, CURLOPT_POST, TRUE);
            //Set data to POST in HTTP "POST" Operation.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        //Execute the  cURL session.
        $curlResponse = curl_exec($ch);
        //Get the Error Code returned by Curl.
        $curlErrno = curl_errno($ch);
        if ($curlErrno) {
            $curlError = curl_error($ch);
            throw new Exception($curlError);
        }
        //Close a cURL session.
        curl_close($ch);
        return $curlResponse;
    }


    /*
     * Create Request XML Format.
     *
     * @param string $fromLanguage   Source language Code.
     * @param string $toLanguage     Target language Code.
     * @param string $category       Category.
     * @param string $contentType    Content Type.
     * @param string $user           User Type.
     * @param string $inputStrArr    Input String Array.
     * @param string $maxTranslation MaxTranslation Count.
     *
     * @return string.
     */
    function createReqXML($fromLanguage,$toLanguage,$category,$contentType,$user,$inputStrArr,$maxTranslation) {
        //Create the XML string for passing the values.
        $requestXml = '<GetTranslationsArrayRequest>';
        $requestXml .= '<AppId></AppId>';
        $requestXml .= '<From>'.$fromLanguage.'</From>';
        $requestXml .= '<Options>'.
                  '<Category xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2">'.$category.'</Category>'.  
                 '<ContentType xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2">'.$contentType.'</ContentType>'.
                 '<ReservedFlags xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2"/>'.
                 '<State xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2"/>'.  
                 '<Uri xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2"></Uri>'. 
                 '<User xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2">'.$user.'</User>'. 
                 '</Options>';
        $requestXml .= '<Texts>';
        foreach($inputStrArr as $str) {
            $requestXml .= '<string xmlns="http://schemas.microsoft.com/2003/10/Serialization/Arrays">'.$str.'</string>';
        }
        $requestXml .= '</Texts>';
        $requestXml .= '<To>'.$toLanguage.'</To>';
        $requestXml .= '<MaxTranslations>'.$maxTranslation.'</MaxTranslations>';
        $requestXml .= '</GetTranslationsArrayRequest>';
        return $requestXml;
    }
}
