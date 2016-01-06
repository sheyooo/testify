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

$app->get('/users/:hash_id/posts', function($hash_id) use ($app){
	$req = $app->request;
	$prms = [];
	if($req->get("limit"))
		$prms['limit'] = $req->get("limit");

	if($req->get("offset") && $req->get("direction")){
		$prms['offset'] = $req->get("offset");
		$prms['direction'] = $req->get("direction");
	}
	
	try{
		$id = User::decodeHashID($hash_id);
	}
	catch(Exception $e){
		$id = $hash_id;
	};
	$prms['user_id'] = $id;

	$posts = App::getActivities($prms);
	echo json_encode(Utility::formatActivitiesToPosts($posts, $app));	
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
	$posts = App::getPosts(["limit" => 25, "user_id" => 13]);
	echo json_encode(Utility::formatActivitiesToPosts($posts, $app));	
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

$app->post('/posts/:id/favorites', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->favoritePost($post, true);
				echo json_encode(array(
				"favorites" => $post->countFavorites(),
				"status" => true)
			);
		}
	}
});

$app->delete('/posts/:id/favorites', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->favoritePost($post, false);
				echo json_encode(array(
				"favorites" => $post->countFavorites(),
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

$app->post('/posts/:id/amens', function($id) use ($app){
	if($uid = $app->environment['testify.user_id']){	
		$post = new Post($id);
			if($post){
				$u = new User($uid);
				$u->sayAmen($post, true);
				echo json_encode(array(
				"amen_count" => $post->countAmens(),
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
	$u = new User($app->environment()['testify.user_id']);

	if($post){
		if($id = $u->postComment($post,$b->text)){
			
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
	
	
	$user_id = $app->environment()['testify.user_id'];

	if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'] < 3000000) {
		
	    // FIXME: add more validation, e.g. using ext/fileinfo
	    try {
	        $key = $user_id . time() . $_FILES['file']['name'];
	        Tools::uploadToAmazon($_FILES['file']['tmp_name'], $key);

			$r = ["file_name" => $key,
	  			"url" => "https://testify.imgix.net/" . $key,
	  			"user_id" => $user_id];

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

	//echo Tools::generateHashID("user",13);
	$fb = "https://scontent.xx.fbcdn.net/hprofile-xpf1/v/t1.0-1/c0.210.540.540/11924754_919608601443630_3427903200323619364_n.jpg?oh=75d9a51a2e8e26c51678cfff510dc82a&oe=56FA5CD2";
	echo __DIR__;
	copy($fb, "../img/fb.jpg");
});






$app->run();