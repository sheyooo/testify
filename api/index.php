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

$app->get('/tags', function(){
	$tags = App::getTrendingTags();
	echo(json_encode($tags));
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

$app->get('/users/:id/', function($id) use ($app){
	$u = new User($id);
	
	if($u->getID()){
		echo json_encode([
			"user_id" => $u->getID(),
			"first_name" => $u->getFirstName(),
			"last_name" => $u->getLastName(),
			"email" => $u->getEmail(),
			"avatar" => $u->getProfilePictureURL()]);
	}else{
		$app->response->status(404);
		echo json_encode(["status" => "User not found"]);
	}
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

	if($id){
		$app->response->status(201);
		echo json_encode(["status" => true,
						"post_id" => $id]);
	}else{
		echo json_encode(["status" => false]);
	}

	

	/*if($id = $u->createPost($p, $a)){
		$app->response()->status(201);
		echo json_encode(array("post_id" => $id));
	}else{
		$app->response()->status(409);
	}*/
});

$app->get('/posts', function() use ($app){

	$posts = App::getPosts(15);

	foreach ($posts as $post) {

		$post_id = $post->getID();
		$liked = false;
		$tapped_into = false;
		$likes = $post->countLikes();
		$comments = $post->countComments();
		$taps = $post->countTaps();
		$text = $post->getText();
		$time = $post->getTime();
		$ijson = [];

		if (!$post->isAnonymous()) {
			$u = new User($post->getAuthor());			
			$user_id = $u->getID();
			$avatar = $u->getProfilePictureURL();
			$name = $u->getFullname();			 
		}else{
			$user_id = null; 
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
			"user" => [
				"user_id" => $user_id,
				"avatar" => $avatar,
				"name" => $name
				],
			"images" => $ijson,
			"comments" => []
			];

		$json[] = $j;
	}

	echo json_encode($json);
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

		sleep(1);
	}
});

$app->post('/images', function() use ($app, $___CONFIG){
	error_reporting(E_ALL);
	$filename = $_FILES['file']['name'];
	  //$tags = $_POST['tags'];  // $tags = array('dark', 'moon');
	  $destination = __DIR__ . '/../img/imgix_source/' . $filename;
	  if(move_uploaded_file( $_FILES['file']['tmp_name'] , $destination ) && $_FILES['file']['size'] <= 2000000){
	  	$r = ["file_name" => $filename,
	  			"url" => $___CONFIG['BASE_URL'] . '/img/imgix_source/' . $filename,
	  			"user_id" => $app->environment['testify.user_id']
	  			];

	  	$id = Image::addTemp($r);
	  	echo json_encode(["status" => true,
	  						"image_id" => $id]);
	  }else{
	  	//echo $_FILES['file']['error'];
	  	//echo $_FILES['file']['tmp_name'];

	  	echo json_encode(["status" => false]);
	  } 
});






$app->run();