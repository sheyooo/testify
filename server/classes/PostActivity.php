<?php

class PostActivity{
	private $id;
	private $post_id;
	private $activities = [];
	private static $map_tables = [
						"favorite" => "favorites",
						"tap" => "taps",
						"amen" => "amens",
						"comment" => "comments"
					];

	public function __construct($post_id){
		$conn = Connection::getInstance("read");
		$command = "SELECT *, GROUP_CONCAT(action_id) AS ids, 			GROUP_CONCAT(user_id) AS users,
						GROUP_CONCAT(time) AS times 
						FROM post_activities 
					WHERE post_id = {$post_id}
                    GROUP BY post_id, action";
		$r = $conn->execObject($command);
		if(mysqli_num_rows($r)){
			while($row = mysqli_fetch_assoc($r)){
				$this->id = $row['id'];
				$this->post_id = $row['post_id'];

				$this->activities[$row['action']] = [
					"action" => $row['action'],
					"users" => explode(",", $row['users']),
					"action_ids" => explode(",", $row['ids']),
					"times" => explode(",", $row['times'])
					];

			}
		}else{
			throw new Exception("Activity not found by Post ID");
		}
	}

	public function getPost(){

		return new Post($this->post_id);
	}

	public function getActivities(){

		return $this->activities;
	}

	public function getUsers($action){
		$a = [];
		foreach ($this->activities[$action]['users'] as $id) {
			$a[] = new User($id);
		 };
		 return $a;
	}

	public static function logActivity($r){
		if(isset($r['action']) && 
			isset($r['post_id']) &&
			isset($r['action_id']) && 
			isset($r['user_id'])){


			if(isset(self::$map_tables[$r['action']])){
				$conn = Connection::getInstance("write");
				$command = "REPLACE INTO post_activities (post_id, action, action_id, user_id) 
							VALUES({$r['post_id']}, 
									'{$r['action']}', 
									{$r['action_id']},
									{$r['user_id']})";
				$insert = $conn->execInsert($command);
				return $insert;
			}else{
				throw new Exception("The action specified doesnt exist");
			}

		}else{
			throw new Exception("Requirements for Activity::logActivity not met");
		}

	}

	public static function deleteActivity($r){
		if(isset($r['action']) && 
			isset($r['post_id']) &&
			isset($r['user_id'])){


			if(isset(self::$map_tables[$r['action']])){
				$conn = Connection::getInstance("write");
				$command = "DELETE FROM post_activities 
							WHERE post_id = {$r['post_id']}
							AND action = '{$r['action']}'
							AND user_id = {$r['user_id']}";
				$d = $conn->execDelete($command);
				return $d;
			}else{
				throw new Exception("The action specified doesnt exist");
			}

		}else{
			throw new Exception("Requirements for Activity::logActivity not met");
		}

	}

}


