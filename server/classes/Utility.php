<?php 
class Utility 
{

	public static function parsePost($post, $app){
		
		if(is_a($post, "Post")){
			$j = [];

			$post_id = $post->getID();
			$prayer = false;
			$amen = false;
			$favorited = false;
			$tapped_into = false;
			$location = "";
			$favorites = $post->countFavorites();
			$comments = $post->countComments();
			$taps = $post->countTaps();
			$text = $post->getText();
			$time = $post->getTime();
			$timestamp = strtotime($time);
			$cats = [];
			$ijson = [];

			if (!$post->isAnonymous()) {
				$u = self::parseUser($post->getAuthor());			
				
			}else{
				$u = [
					"user_id" => "anonymous",
					"hash_id" => "anonymous",
					"avatar" => "img/favicon.png",
					"name" => "Anonymous Testimony"
					];
			}

			if($post->isPrayer()){
				$prayer = true;
				$amen_count = $post->countAmens();

				$j["amen_count"] = $amen_count;
			}

			foreach ($post->getCategories() as $cat) {
				$cats[] = ["name" => $cat->getName(),
							"id" => $cat->getID()];
			}

			if($id = $app->environment()['testify.user_id']){
				$user = new User($id);

				if($post->isFavorite($user)){
					$favorited = true;
				}
				if($post->isTappedInto($user)){
					$tapped_into = true;
				}
				if($post->isPrayer()){
					if($post->saidAmen($user)){
						$amen = true;
					}
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

			$j = array_merge($j, [
				"post_id" => $post_id,
				"favorited" => $favorited,
				"tapped_into" => $tapped_into,
				"amen" => $amen,
				"prayer" => $prayer,		
				"favorites_count" => $favorites,
				"comments_count" => $comments,
				"taps_count" => $taps,
				"text" => $text,
				"time" => $time,
				"timestamp" => $timestamp,
				"categories" => $cats,
				"user" => $u,
				"images" => $ijson,
				"comments" => []
				]);
			//print_r($j);

			return $j;
		}else{
			throw new Exception("Expects an instance of Post class");
		}
	}

	public static function parseUsers($r){
		$users = [];
		if(is_array($r)){
			foreach ($r as $u) {
				$users[] = self::parseUser($u);			
			}
			return $users;
		}else{
			throw new Exception("Expects array of Users class");
		}
	}

	public static function formatActivitiesToPosts($activities, $app){
		if(is_array($activities)){
			$json = [];
			foreach ($activities as $a) {
				if(is_a($a, "PostActivity")){
					$j = [];
					$j = self::parsePost($a->getPost(), $app);
					$acts = $a->getActivities();
					$json_acts = [];

					foreach ($acts as $k => $v) {
						$activity[] = [
								"action" => $k,
								"users" => self::parseUsers($a->getUsers($k)),
								
								];
					}			
					
					$j = array_merge($j, ["activities" => $activity]);
					//print_r($j);

					$json[] = $j;
				}elseif(is_a($a, "Post")){
					$j = self::parsePost($a, $app);
					$json[] = $j;
				}else{
					throw new Exception("Expects an instance of PostActivity class");
				}
			}
			return $json;
		}else{
			throw new Exception("Expects an array of PostActivity objects");		
		}
	}

	public static function parseUser($user){

		if(is_a($user, "User")){
			$u = [
			"user_id" => $user->getID(),
			"hash_id" => $user->getHashID(),
			"avatar" => $user->getProfilePictureURL(),
			"name" => $user->getFullName(),
			"location" => $user->getLocation()];


			return $u;
		}else{
			throw new Exception("Expects parameter to be an instance of User class");
		}
	}

}