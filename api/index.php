<?php
require(__DIR__ . '/../server/lib/vendor/autoload.php');
require(__DIR__ . "/../server/classes/Middle/AuthMiddleware.php");
require(__DIR__ . "/../server/config.php");

function my_autoloader($class) {
    include __DIR__ . '/../server/classes/' . $class . '.php';
}
spl_autoload_register('my_autoloader');


$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$app->add(new \AuthMiddleware());

$app->post('/authenticate', function() use ($app){
	$body = json_decode($app->request->getBody());

	if($id = App::Login($body->user, $body->password)){
		$u = new User($id);
		$token = App::generateToken($id);
		echo json_encode(array("token" => "{$token}"));
	}else{
		$app->response()->status(404);
	}
});

$app->post('/fb-token', function() use ($app){
	$b = json_decode($app->request->getBody());
	$t = false;

	$fbuser = App::getFbUserFromToken($b->fb_access_token);
	if($user_id = App::findBySocial('facebook', $fbuser['id'])){
		$t = App::generateToken($user_id);
	}else{
		$user_id = App::createUserFromFbToken($b->fb_access_token);
		if($user_id){
			$t = App::generateToken($user_id);
			//echo json_encode(array("token" => $t));
		}
	}

	//echo $t;

	if($t){
		echo json_encode(array("token" => "{$t}"));
	}else{
		echo json_encode(array("status" => "duplicate"));
	}
});

$app->get('/fb-share/:post_id', function() use ($app){
	$app->response->headers->set('Content-Type', 'text/plain');

	echo '<meta property="og:url"                content="http://www.nytimes.com/2015/02/19/arts/international/when-great-minds-dont-think-alike.html" />
		<meta property="og:type"               content="article" />
		<meta property="og:title"              content="When Great Minds Don’t Think Alike" />
		<meta property="og:description"        content="How much does culture influence creative thinking?" />
		<meta property="og:image"              content="http://static01.nyt.com/images/2015/02/19/arts/international/19iht-btnumbers19A/19iht-btnumbers19A-facebookJumbo-v2.jpg" />';
});

$app->get('/categories', function(){
	$cats = App::getCategories();
	$r = [];
	foreach ($cats as $c) {
		$r[] = ["id" => $c->getID(),
				"name" => $c->getName(),
				"count" => $c->countPosts()];
	}
	echo(json_encode($r));
});

$app->get('/search', function() use ($app){
	$q = $app->request()->get("q");

	if($q){

	$result = App::search($q);
	echo(json_encode($result));
	}else{
	echo(json_encode(array()));
	}
});

$app->post('/users/', function () use ($app) {
	$body = $app->request->getBody();
	$nu = json_decode($body);

	if($uid = User::create($nu->firstName, $nu->lastName, $nu->email)){
		$app->response()->status("201");
		$token = App::generateToken($uid);

		echo json_encode(array('token' => "{$token}"));

	}else{
		$app->response()->status("401");
	}    
});

$app->get('/users/:hash_id/', function($hash_id) use ($app){
	try{
		$u = new User(Tools::decodeHashID("user", $hash_id));
		echo json_encode([
			"user_id" => $u->getID(),
			"hash_id" => $u->getHashID(),
			"first_name" => $u->getFirstName(),
			"last_name" => $u->getLastName(),
			"email" => $u->getEmail(),
			"avatar" => $u->getProfilePictureURL()]);
	}catch(Exception $e){
		$app->response->status(404);
		echo json_encode(["status" => "User not found"]);
	};
});

$app->get('/users/:id/posts', function($id) use ($app){
	$o = $app->request->getBody();
	$o = json_decode($o);

	

	

	/*if($id = $u->createPost($p, $a)){
		$app->response()->status(201);
		echo json_encode(array("post_id" => $id));
	}else{
		$app->response()->status(409);
	}*/
});

$app->post('/users/:id/posts', function($id) use ($app){
	$o = $app->request->getBody();
	$o = json_decode($o);
	$u = new User($app->environment()['testify.user_id']);

	if($u->getID() != $id){
		$app->response->status(403);
		echo json_encode(["status" => false,
				"description" => "Wrong account"]);
		return ;
	}

	if(!isset($o->post)){
		$o->post = " ";
	}

	$id = $u->createPost([
			"a" => $o->anonymous,
			"p" => $o->post,
			"i" => $o->images
		]);

	if(isset($o->category)){
		$p = new Post($id);
		$p->setCategory($o->category);
	}

	if($id){
		$p = new Post($id);
		$app->response->status(201);
		
		if($u = $p->getAuthor()){
			$user = [
				"user_id" => $u->getID(),
				"avatar" => $u->getProfilePictureURL(),
				"name" => $u->getFullname()];
		}else{
			$user = [
			"user_id" => null,
			"avatar" => "img/favicon.png",
			"name" => "Anonymous"];
		}
		$ijson = [];
		if($imgs = $p->getImages()){
			foreach ($imgs as $i) {
				$ijson[] =["url" => $i->getUrl(),
							"alt" => $i->getFileName()];
			}
		}


		$j = [
			"post_id" => $p->getID(),
			"liked" => false,
			"tapped_into" => false,			
			"likes_count" => 0,
			"comments_count" => 0,
			"taps_count" => 0,
			"text" => $p->getText(),
			"time" => $p->getTime(),
			"category" => $p->getCategory()->getName(),
			"user" => $user,
			"images" => $ijson,
			"comments" => []
			];
			echo json_encode($j);
	}else{
		echo json_encode(["status" => false]);
	}
});

$app->get('/posts', function() use ($app){

	$posts = App::getPosts(15);

	if(is_array($posts)){

		foreach ($posts as $post) {

			$post_id = $post->getID();
			$liked = false;
			$tapped_into = false;
			$likes = $post->countLikes();
			$comments = $post->countComments();
			$taps = $post->countTaps();
			$text = $post->getText();
			$time = $post->getTime();
			$cat = $post->getCategory()->getName();
			$ijson = [];

			if (!$post->isAnonymous()) {
				try{
					$u = $post->getAuthor();			
					$user_id = $u->getID();
					$hash_id = $u->getHashID();
					$avatar = $u->getProfilePictureURL();
					$name = $u->getFullname();	
				}catch(Exception $e){
					$user_id = "anonymous";
					$hash_id = "anonymous";
					$avatar = "img/favicon.png";
					$name = "Anonymous Testimony";	

				}
						 
			}else{
				$user_id = "anonymous";
				$hash_id = "anonymous";
				$avatar = "img/favicon.png";
				$name = "Anonymous Testimony";			 
			}

			if($id = $app->environment()['testify.user_id']){
				$user = new User($id);

				if($post->isLiked($user)){
					$liked = true;
				}
				if($post->isTappedInto($user)){
					$tapped_into = true;
				}
			};

			$i = $post->getImages();
			if(count($i)){
				foreach ($i as $img) {
					$ijson[] = [
							"url" => $img->getUrl(),
							"alt" => $img->getFileName(),
							"user_id" => $img->getUserID(),
							"time" => $img->getTime()
							];
						}
			};

			$j = [
				"post_id" => $post_id,
				"liked" => $liked,
				"tapped_into" => $tapped_into,			
				"likes_count" => $likes,
				"comments_count" => $comments,
				"taps_count" => $taps,
				"text" => $text,
				"time" => $time,
				"category" => $cat,
				"user" => [
					"user_id" => $user_id,
					"hash_id" => $hash_id,
					"avatar" => $avatar,
					"name" => $name
					],
				"images" => $ijson,
				"comments" => []
				];

			$json[] = $j;
		}

		echo json_encode($json);
	}else{
		echo json_encode([]);
	}

	
});

$app->delete('/posts/:id', function($id) use ($app){
	$p = new Post($id);
	if ($p->getID() && $app->environment()['testify.user_id'] ==$p->getAuthor()->getID()) {

		$p->delete();
		if($p){
			echo json_encode(['status' => true]);
		}
	}
});

$app->post('/posts/:id/likes', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->likePost($post, true);
				echo json_encode(array(
				"likes" => $post->countLikes(),
				"status" => true)
			);
		}
	}
});

$app->delete('/posts/:id/likes', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->likePost($post, false);
				echo json_encode(array(
				"likes" => $post->countLikes(),
				"status" => false)
			);
		}
	}
});

$app->post('/posts/:id/taps', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->tapPost($post, true);
				echo json_encode(array(
				"taps" => $post->countTaps(),
				"status" => true)
			);
		}
	}
});

$app->delete('/posts/:id/taps', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->tapPost($post, false);
				echo json_encode(array(
				"taps" => $post->countTaps(),
				"status" => false)
			);
		}
	}
});

$app->get('/posts/:id/comments', function($id) use ($app){
	$post = new Post($id);
	$limit = 5;
	$offset = 4;//get all these from query variables implement later
	if($post){
		$json = array();
		$comments = $post->getComments($limit);
		foreach ($comments as $c) {
			$user = $c->getAuthor();
			$u = array("user_id" => $user->getID(),
						'name' => $user->getFullname(),
						'avatar' => $user->getProfilePictureURL());

			$json[] = array(
				'comment_id' => $c->getID(),
				'post_id' => $c->getPostID(),
				'text' => $c->getText(),
				'time' => $c->getTime(),
				'user' =>  $u);
		}

		echo json_encode($json);

		//sleep(1);
	}
});

$app->post('/posts/:id/comments', function($id) use ($app){
	$b = json_decode($app->request->getBody());
	$post = new Post($id);

	if($post){
		if($id = $post->addComment(["user_id" => $app->environment()['testify.user_id'],
			"text" => $b->text])){
			$app->response->status(201);
			echo json_encode(["status" => true,
								"comment_id" => $id,
								"comment" => $b->text]);
		}else{
			$app->response->status(401);
			echo json_encode(["status"=> false,
								"error" => "Duplicate found"]);
		}
	}else{
			$app->response->status(404);
			echo json_encode(["status" => false,
							"error" => "Post not found"]);
	}
});

$app->post('/images', function() use ($app){
	$client = Aws\S3\S3Client::factory([
		'region' => 'us-west-2',
		'version' => '2006-03-01',
		'http' => ['verify' => false]
			]
		);
	$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
	
	$user_id = $app->environment()['testify.user_id'];

	if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'] < 3000000) {
		
	    // FIXME: add more validation, e.g. using ext/fileinfo
	    try {
	        $key = $user_id . time() . $_FILES['file']['name'];
	        $result = $client->putObject(array(
			    'Bucket'     => $bucket,
			    'Key'        => 'posts/' . $key,
			    'SourceFile' => $_FILES['file']['tmp_name']
			));

			$r = ["file_name" => $key,
	  			"url" => "https://testify.imgix.net/" . $key,
	  			"user_id" => $user_id
	  			];

		  	$id = Image::addTemp($r);
		  	if($r){
		  		$app->response->status(201);
		  		echo json_encode(["status" => true,
	  						"image_id" => $id]);
		  	}else{
		  		echo json_encode(["status" => false]);
		  	}	        

		 } catch(Exception $e) {
		 		//echo $e;
		 		echo json_encode(["status" => false]);
		 } 
	}
});

$app->get('/cele', function() use ($app){

	$xml = "";


	/*function Parse ($url) {
	        $fileContents= $url;
	        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
	        $fileContents = trim(str_replace('"', "'", $fileContents));
	        $simpleXml = simplexml_load_string($fileContents);
	        $json = json_encode($simpleXml);

	        return $json;
	    }*/



	/*$o = json_decode(Parse($xml));

	print_r($o);*/

	/*foreach ($o as $hymns) {
		$json;
		foreach ($hymns as $hymn) {
			//print_r($hymns);

			$attributes = "@attributes";
			//$number = $hymn->strNum;
			$start = $hymn->$attributes->strNum;
			$end = $hymn->$attributes->endNum;

			//echo $number;
			//$title = $hymn->Title;
			$cat = $hymn->Category;
			//$verses = [];
			if(is_array($hymn->Verse)){
				foreach ($hymn->Verse as $v) {
					$verses[] = $v;
				}
			}else{
				$verses[] = $hymn->Verse;
			}

			$myStructure = ["category" => $cat,
							"start" => $start,
							"end" => $end];

							//print_r($myStructure);

							$json[] = $myStructure;
			

			//echo "\n\n\n\n\n\n\n\n\n";
			
		}

		//echo json_encode($json);
	}*/

	echo Tools::generateHashID("user",13);
});






$app->run();