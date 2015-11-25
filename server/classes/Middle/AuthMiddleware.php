<?php
//require(__DIR__ . "/../../../server/lib/vendor/autoload.php");

class AuthMiddleware extends \Slim\Middleware
{   



    public function call()
    {
        //The Slim application
        $app = $this->app;
        //The Environment object
        $env = $app->environment;
        //The Request object
        $req = $app->request;
        //The Response object
        $res = $app->response;

        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $res->header('Access-Control-Allow-Headers', 'X-MY-CUSTOM HEADER');
        $res->header('Access-Control-Allow-Credentials', 'true');

        if($req->isOptions()){

        }
        


        $open_access_endpoints = [
            ["method" => "GET",
                "url" => "/\/cele/"],

            array(
                'method' => 'POST',
                'url' => "/\/authenticate/"),
            array(
                'method' => 'POST',
                'url' => "/\/fb-token/"),
            array(
                'method' => 'GET',
                'url' => "/\/fb-share\/[0-9]+/"),
            array(
                'method' => 'GET',
                'url' => "/\/categories/"),
            array(
                'method' => 'GET',
                'url' => "/\/search/"),
            array(
                'method' => 'POST',
                'url' => "/\/users\z/"),
            array(
                'method' => 'GET',
                'url' => "/\/posts\z/"),            
            array(
                'method' => 'GET',
                'url' => "/\/posts\/[0-9]+\/comments/"),
            array(
                'method' => 'GET',
                'url' => "/\/users\/[0-9]+\z/"),
            ];

        $bearer_token = $app->request->headers->get('Authorization');

        if($bearer_token){
            $token = explode(" ", $bearer_token)[1];
            if($t = App::isValid($token)){
                //echo "Middleware says valid";
                //echo $token;
                //echo $t;

                if($token = App::refreshToken($token)){
                    $res->headers['Authorization'] = $token;
                }


                $env['testify.user_id'] = $t;
                $this->next->call();

                //TOKEN VALID
            }else{
                $env['testify.user_id'] = false;

                //MAKE SURE THE URI IS AVAILABLE BEFORE UNAUTHORIZING
                if($app->router->getMatchedRoutes($req->getMethod(),$req->getResourceUri())){

                    echo json_encode(array("status" => "Invalid Authorization"));
                    $app->response->status(401);
                }else{
                    echo json_encode(array("status" => "Not Found"));
                    $app->response->status(404);
                }
                //TOKEN AVAILABLE BUT INVALID
            };
        }else{
            $env['testify.user_id'] = false;
            $url = $app->request->getPathInfo();

            function is_open_endpoint($o, $url, $app){
                //$o = $open_access_endpoints;
                $found = false;
                foreach ($o as $v) {
                    if(preg_match($v['url'], $url) AND $v['method'] == $app->request->getMethod()){
                        $found = true;
                        //echo "trueff";
                    };
                }

                return $found;
            }

            if(is_open_endpoint($open_access_endpoints, $url, $app)){
                //echo "yepss";
                $this->next->call();

            }elseif($app->request->isOptions()){
                $app->response->status(200);
            }else{
                //$this->next->call();
                $r = array("status" => "Unauthorized");
                //$app->response->setBody($r);
                echo json_encode($r);

                $app->response->status(401);
                //$app->halt(401);
            }

            //NO TOKEN PROVIDED
        }


    }
}