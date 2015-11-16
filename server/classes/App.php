<?php 
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

	class App{

		public static function getPosts($h){
			$arr = false;
			$conn = Connection::getInstance("read");
			$command = "SELECT * FROM posts 
						ORDER BY time DESC 
						LIMIT {$h} ";

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

		public static function registerUserFromFacebook($first_name, $last_name, $email){

			if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
				$conn = Connection::getInstance("write");
				$command = "INSERT INTO users (first_name, last_name, email)
							VALUES('{$first_name}','{$last_name}','{$email}')";

				$result = $conn->execInsert($command);
				if($result){
					return true;
				}
			}else{
				
			}

		}

		public static function registerUserWithPassword($name, $email, $password){

		}

		public static function getTrendingTags(){
			$conn = Connection::getInstance("read");

			$command = "SELECT * FROM tags";

			$result = $conn->execObject($command);

			if(mysqli_num_rows($result)){
				$tags = null;
				while ($row = mysqli_fetch_assoc($result)) {
					$tags[] = $row['hashtag_form'];
				}

				return $tags;
			}else{
				return false;
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

			$signer = new Sha256();

			$token = (new Builder())->setIssuer('http://testify.com') // Configures the issuer (iss claim)
			                        ->setAudience('http://testify.com') // Configures the audience (aud claim)
			                        ->setId('testify_token_user_' . $uid, true) // Configures the id (jti claim), replicating as a header item
			                        ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
			                        //->setNotBefore(time() + 60) // Configures the time that the token can be used (nbf claim)
			                        ->setExpiration(time() + (60 * 60 * 24 * 7)) // Configures the expiration time of the token (exp claim)
			                        ->set('user_id', $uid) // Configures a new claim, called "uid"
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





?>