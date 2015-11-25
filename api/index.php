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
			"avatar" => "img/lgog.png",
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
				$u = $post->getAuthor();			
				$user_id = $u->getID();
				$avatar = $u->getProfilePictureURL();
				$name = $u->getFullname();			 
			}else{
				$user_id = "anonymous"; 
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

		sleep(1);
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

	$xml = "<?xml version=\'1.0\' encoding=\'utf-8\'?>
	<Categories>

	<Category strNum=\'1\' endNum=\'1\'>PROCESSIONAL HYMN</Category>
	<Category strNum=\'2\' endNum=\'2\'>LIGHTNING THE CANDLES</Category>
	<Category strNum=\'3\' endNum=\'4\'>KNEELING DOWN</Category>
	<Category strNum=\'5\' endNum=\'50\'>FORGIVENESS AND REPENTANCE</Category>
	<Category strNum=\'51\' endNum=\'125\'>SERVICES</Category>
	<Category strNum=\'126\' endNum=\'150\'>SONGS FOR PALM SUNDAY</Category>
	<Category strNum=\'151\' endNum=\'175\'>MERCY AND DURING PASSION WEEK</Category>
	<Category strNum=\'176\' endNum=\'200\'>EASTER DAY</Category>
	<Category strNum=\'201\' endNum=\'225\'>GOD\'S GLORY AND ASCENSION DAY</Category>
	<Category strNum=\'226\' endNum=\'250\'>HOLY SPIRIT</Category>
	<Category strNum=\'251\' endNum=\'275\'>SPIRITUAL POWER</Category>
	<Category strNum=\'276\' endNum=\'300\'>GOOD NEWS</Category>
	<Category strNum=\'301\' endNum=\'325\'>PRAISE</Category>
	<Category strNum=\'326\' endNum=\'350\'>GLORY</Category>
	<Category strNum=\'351\' endNum=\'375\'>JOY</Category>
	<Category strNum=\'376\' endNum=\'400\'>THANKSGIVING</Category>
	<Category strNum=\'401\' endNum=\'425\'>BLESSING</Category>
	<Category strNum=\'426\' endNum=\'450\'>HARVEST</Category>
	<Category strNum=\'451\' endNum=\'485\'>VICTORY</Category>
	<Category strNum=\'486\' endNum=\'500\'>HEALING</Category>
	<Category strNum=\'501\' endNum=\'520\'>BAPTISM</Category>
	<Category strNum=\'521\' endNum=\'550\'>FAITH</Category>
	<Category strNum=\'551\' endNum=\'570\'>JUDGEMENT</Category>
	<Category strNum=\'571\' endNum=\'600\'>THE COMING OF CHRIST</Category>
	<Category strNum=\'601\' endNum=\'630\'>GOD\'S WORK</Category>
	<Category strNum=\'631\' endNum=\'645\'>WARNING</Category>
	<Category strNum=\'646\' endNum=\'665\'>BURIAL AND REMEMBRANCE</Category>
	<Category strNum=\'666\' endNum=\'675\'>CALL TO HEAVEN</Category>
	<Category strNum=\'676\' endNum=\'690\'>DIVINE CALL</Category>
	<Category strNum=\'691\' endNum=\'700\'>HEAVENLY CALL</Category>
	<Category strNum=\'701\' endNum=\'725\'>REVELATION</Category>
	<Category strNum=\'726\' endNum=\'730\'>SANCTIFICATION</Category>
	<Category strNum=\'731\' endNum=\'735\'>HOUSE OPENING</Category>
	<Category strNum=\'736\' endNum=\'760\'>DIVINE LOVE</Category>
	<Category strNum=\'761\' endNum=\'770\'>HOLY RE-UNION</Category>
	<Category strNum=\'771\' endNum=\'780\'>HOLY COMMUNICATION</Category>
	<Category strNum=\'781\' endNum=\'790\'>WEDDING</Category>
	<Category strNum=\'791\' endNum=\'800\'>PRAYERS</Category>
	<Category strNum=\'801\' endNum=\'825\'>PROTECTION AND JOURNEY</Category>
	<Category strNum=\'826\' endNum=\'850\'>CHILDREN</Category>
	<Category strNum=\'851\' endNum=\'875\'>BIRTH OF CHRIST</Category>
	<Category strNum=\'876\' endNum=\'900\'>SEEKING FAVOUR FROM GOD</Category>
	<Category strNum=\'901\' endNum=\'906\'>PROMISE</Category>
	<Category strNum=\'907\' endNum=\'978\'>PRAISE AND WORSHIP</Category>


	</Categories>";


	function Parse ($url) {
	        $fileContents= $url;
	        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
	        $fileContents = trim(str_replace('"', "'", $fileContents));
	        $simpleXml = simplexml_load_string($fileContents);
	        $json = json_encode($simpleXml);

	        return $json;
	    }



	$o = json_decode(Parse($xml));

	print_r($o);

	foreach ($o as $hymns) {
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
			/*if(is_array($hymn->Verse)){
				foreach ($hymn->Verse as $v) {
					$verses[] = $v;
				}
			}else{
				$verses[] = $hymn->Verse;
			}*/

			$myStructure = ["category" => $cat,
							"start" => $start,
							"end" => $end];

							//print_r($myStructure);

							$json[] = $myStructure;
			

			//echo "\n\n\n\n\n\n\n\n\n";
			
		}

		//echo json_encode($json);
	}

});






$app->run();