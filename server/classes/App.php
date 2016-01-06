<?php 

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

	class App{

		public static function getPosts($prms){
			$arr = false;
			$conn = Connection::getInstance("read");
			$command = "SELECT * FROM posts 
						ORDER BY time DESC 
						LIMIT 15 ";

			$result = $conn->execObject($command);
			if(mysqli_num_rows($result)){
				while ($row = mysqli_fetch_assoc($result)) {	
						//$row['timestamp'] = strtotime($row['time']);
						$post = new Post($row['post_id']);
						$arr[] = $post;
					}
				return $arr;
			}else{
				return $arr;
			}
		}

		public static function getActivitiesTime(){

		}

		public static function getActivities($prms){
			$arr = [];
			$conn = Connection::getInstance("read");
			$command = "SELECT id, posts.post_id FROM posts
				LEFT JOIN post_activities
				ON(posts.post_id = post_activities.post_id)";

				if(isset($prms['user_id'])){
					$command .= " WHERE post_activities.user_id = {$prms['user_id']}
							OR (posts.user_id = 
							{$prms['user_id']}  AND posts.anonymous = 0)";
				}

				if(isset($prms['offset']) && isset($prms['direction'])){
					if($prms['direction'] == "after"){
						$command .= " AND( UNIX_TIMESTAMP(posts.time) > {$prms['offset']} OR UNIX_TIMESTAMP(post_activities.time) > {$prms['offset']} )";
					}elseif($prms['direction'] == "before"){
						$command .= " AND (UNIX_TIMESTAMP(posts.time) < {$prms['offset']} OR UNIX_TIMESTAMP(post_activities.time) < {$prms['offset']})";
					}

				}

				$command .= " GROUP BY posts.post_id
				ORDER BY post_activities.time DESC, 
				posts.time DESC  ";

				if(isset($prms['limit'])){
					$command .= " LIMIT {$prms['limit']}";
				}else{
					$command .= " LIMIT 20";
				}
			

			$result = $conn->execObject($command);
			if(mysqli_num_rows($result)){
				while ($row = mysqli_fetch_assoc($result)) {
						
						if($row['id']){
							$arr[] = new PostActivity($row['post_id']);

						}elseif($row['post_id']){
							$arr[] = new Post($row['post_id']);

						}
					}
				return $arr;
			}else{
				return $arr;
			}
		}

		public static function Login($u, $p){
			$conn = Connection::getInstance("read");

			$command = "SELECT * FROM users WHERE email = '{$u}'";
			$result = $conn->execObject($command);

			if(mysqli_num_rows($result)){
				$row = mysqli_fetch_assoc($result);
				if(password_verify($p, $row['password'])){
					return $row['user_id'];
				}else{
					return false;
				}

			}else{
				return false;
			}
		}

		public static function getFb(){
			$fb = new Facebook\Facebook([
			  'app_id' => '180042792329807',
			  'app_secret' => 'ba22567709fbfd8cab73c1fcd8cb168a',
			  'default_graph_version' => 'v2.4',

			  ]);

			return $fb;
		}

		public static function getFbUserFromToken($token){
			$fb = App::getFb();

			//Use the javascript helper when done with testing if you prefer from the fb sdk for php to get the access token from cookie after setting cookie = true
			try {
			    // Returns a `Facebook\FacebookResponse` object
			    $response = $fb->get('/me?fields=id,name,first_name,last_name,email', $token);
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
			    echo 'Graph returned an error: ' . $e->getMessage();
			    exit;
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
			    echo 'Facebook SDK returned an error: ' . $e->getMessage();
			    exit;
			}
			$user = $response->getGraphUser();

			if($user){
				return $user;
				//print_r($user);
			}else{
				return false;
			}
		}

		public static function findBySocial($type, $social_id){
			$conn = Connection::getInstance("read");
			$command = "SELECT * FROM social_users 
						WHERE type = '{$type}' 
						AND social_id = {$social_id} ";
			$result  = $conn->execObject($command);
			if(mysqli_num_rows($result)){
				$row = mysqli_fetch_assoc($result);

				return $row['user_id'];
			}else{
				return false;
			}

		}

		public static function createUserFromFbToken($token){
			$fbuser = App::getFbUserFromToken($token);
			if($fbuser){
				$f = $fbuser;
				$fb = App::getFb();
				$r = $fb->get("/{$f['id']}/picture?type=square&width=500&height=500&redirect=0", $token);

				$fb_pic = $r->getGraphUser()['url'];
				$details = ["first_name" => "{$f['first_name']}",		"last_name" => "{$f['last_name']}"
					];
				if(isset($f['email'])){
					$details["email"] = $f['email'];
				}
				$user_id = User::create($details);
				//print_r($f);
				if($user_id){
					$conn = Connection::getInstance("write");
					$command = "INSERT INTO social_users (user_id, social_id, type, name) VALUES(
						{$user_id}, {$f['id']}, 'facebook', '{$f['name']}')";
					$conn->execInsert($command);

					$u = new User($user_id);
					$u->setProfilePicture($fb_pic);

					return $user_id;
				}else{
					return false;
				}
			}

		}

		

		public static function registerUserWithPassword($name, $email, $password){

		}

		public static function getCategories(){
			$conn = Connection::getInstance("read");

			$command = "SELECT * FROM categories
						ORDER BY sort ASC";

			$result = $conn->execObject($command);

			if(mysqli_num_rows($result)){
				$tags = null;
				while ($row = mysqli_fetch_assoc($result)) {
					$cats[] = new Category($row['cat_id']);
				}

				return $cats;
			}else{
				return [];
			}
		}

		public static function search($query){
			$conn = Connection::getInstance("read");

			$command = "SELECT * FROM repo 
						WHERE item LIKE '{$query}%' 
						OR item LIKE '#{$query}%' ";

			$result = $conn->execObject($command);
			$search = array();

			if(mysqli_num_rows($result)){				
				while ($row = mysqli_fetch_assoc($result)) {
					$search[] = $row;
				}
				return $search;
			}else{
				return array();
			}
		}		

		public static function findUserByEmail($email){
			$conn = Connection::getInstance("read");

			$command = "SELECT * FROM users WHERE email = '{$email}'";
			$conn->execObject($command);
			if($result->mysqli_num_rows){
				return true;
			}else{
				return false;
			}
		}

		public static function generateToken($uid){
			$u = new User($uid);

			$signer = new Sha256();

			$token = (new Builder())->setIssuer('http://testify.com') // Configures the issuer (iss claim)
			                        ->setAudience('http://testify.com') // Configures the audience (aud claim)
			                        ->setId('testify_token_user_' . $uid, true) // Configures the id (jti claim), replicating as a header item
			                        ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
			                        //->setNotBefore(time() + 60) // Configures the time that the token can be used (nbf claim)
			                        ->setExpiration(time() + (60 * 60 * 24 * 7)) // Configures the expiration time of the token (exp claim)
			                        ->set('user_id', $uid)
			                        ->set('hash_id', $u->getHashID())
			                        ->sign($signer, 'sheyi') // creates a signature using "testing" as key
			                        ->getToken(); // Retrieves the generated token

			return $token;
		}

		public static function validateToken($token){
			$data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
			$data->setIssuer('http://testify.com');
			$data->setAudience('http://testify.com');
			//$data->setId('4f1g23a12aa');

			if($token->validate($data)){
				return true;
			}else{
				return false;
			}			
		}

		public static function refreshToken($token){
			//ONLY RUN THIS IN THE CONTEXT OF App::isValid() for validation of the token
			$token = (new Parser())->parse((string) $token); // Parses from a string
			if($token->getClaim('exp') - time() < 60 * 60){
				return App::generateToken($token->getClaim('user_id'));
			}else{
				return false;
			}
			//echo $token->getClaim('exp');
			//return $token;
		}

		public static function isValid($token){
			try{
				$token = (new Parser())->parse((string) $token); // Parses from a string
				if(App::validateToken($token)){

				$token->getHeaders(); // Retrieves the token header
				$token->getClaims(); // Retrieves the token claims

				return $token->getClaim('user_id'); 

					//return $token->getClaim();

				}else{
					return false;
					$app->halt();
				}
			}
			catch(Exception $e){

			}			
		}

	}

