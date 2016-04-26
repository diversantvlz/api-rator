<?php

namespace ApiRator\Includes;


abstract class Request extends Opts
{

    private $error_code;
    private $error_msg;

    public function getErrorCode()
    {
        return $this->error_code;
    }

    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    public function execApi()
    {
        $this->checkRequiredParams();
        
        $url = $this->getResultApiUrl();

        if($this->logger){
            $this->logger->debug("execApi: " . $url);
        }

        $parameters = $this->getParameters();

        if($this->logger){
            $this->logger->debug("with parameters: " . serialize($parameters));
        }

        $apiCurl = curl_init($url);
        curl_setopt($apiCurl, CURLOPT_POST, 1);
        curl_setopt($apiCurl, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
        curl_setopt($apiCurl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($apiCurl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($apiCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYHOST, 0);
        $apiContent = curl_exec($apiCurl);

        if ($apiContent === false) {
            if($this->logger){
                $this->logger->error(curl_error($apiCurl));
            }
            curl_close($apiCurl);
            return false;
        }

        curl_close($apiCurl);

        if (!$apiContent) {
            if($this->logger){
                $this->logger->debug("apiContent is empty");
            }
            return false;
        }

        if($this->logger){
            $this->logger->debug("execApi result: " . $apiContent);
        }

        $json = json_decode($apiContent);

        if (isset($json->error) && $json->error) {
            if (isset($json->error->error_code) && $json->error->error_code) {
                if ($json->error->error_code == 14) {
                    /*
                    if( $need_captcha_response ){
                        if( is_object( $json ) && isset( $json->error->captcha_sid ) && isset( $json->error->captcha_img ) ){
                            $recognize = new AntigateRecognizeCaptchaRequest( $json->error->captcha_img );
                            $recognize->downloadCaptcha();
                            $result = $recognize->doRecognize();
                            if( !$result ) {
                                if($this->logger){
                                    $this->logger->debug("Don't recognize captcha!");
                                }
                                return false;
                            }
                            $this->setParameter( "captcha_sid", $json->error->captcha_sid );
                            $this->setParameter( "captcha_key", $recognize->getRecognizeCaptcha() );
                            if($this->logger) {
                                $this->logger->debug("Send captcha_sid " . $json->error->captcha_sid);
                                $this->logger->debug("Send captcha_key " . $recognize->getRecognizeCaptcha());
                            }
                        }
                        else{
                            if($this->logger){
                                $this->logger->debug("Json is corrupt: " . serialize( $json ));
                            }
                            return false;
                        }
                    }
                    /**/
                }
                $this->error_code = $json->error->error_code;
            }
            if (isset($json->error->error_msg) && $json->error->error_msg) {
                $this->error_msg = $json->error->error_msg;
                if($this->logger){
                    $this->logger->error($this->error_msg);
                }
            }

            return -1;
        }

        return $json;
    }

    abstract public function doRequest();

    abstract public function getResultApiUrl();

}