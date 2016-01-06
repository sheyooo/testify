<?php
class User{
	private $id;
	private $hash_id;
	protected $first_name;
	protected $last_name;
	private $avatar;
	private $sex;
	private $location;
	private $username;
	private $phone;
	private $email;
	private $online_status;
	private $fields_from_db;
	private static $salt = "user";

	public function __construct($id){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM users
					LEFT JOIN profile ON (users.user_id = profile.user_id)
					WHERE users.user_id = {$id}";
		$result = $conn->execObject($command);
	
		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			unset($row['password']);		
			$this->id = $id;
			$this->hash_id = $row['hash_id'];
			$this->first_name = $row['first_name'];
			$this->last_name = $row['last_name'];
			$this->avatar = $row['avatar'];
			$this->email = $row['email'];
			$this->sex = $row['sex'];
			$this->location = trim($row['state'] . ", " . $row['country']);
			$this->fields_from_db = $row;
		} else{
			throw new Exception("Invalid id not found in the database");
		};
	}
	public function getFields($r){
		
		if(is_array($r)){
			//Implement here getting specific fields from the db through an array parameter and throw Exceptions if such fields dont exist from $fields_from_db
		}
	}
	public function getID(){

		return $this->id;
	}

	public function getHashID(){

		return $this->hash_id;
	}
	public function getFullname(){
		
		return $this->first_name . " " . $this->last_name;
	}
	public function getFirstName(){

		return $this->first_name;
	}
	public function getLastName(){

		return $this->last_name;
	}
	public function getSex(){
		$sex = null;
		if (strtoupper($this->sex) == "M") {
			$sex = "MALE";
		} else {
			$sex = "FEMALE";
		}
		return $sex;
	}
	public function createPost($r){
		$conn = Connection::getInstance("write");

		if ($r) {
			if($r['a'] == true){$r['a'] = 1;}else{$r['a'] = 0;};

			$command = "INSERT INTO posts (anonymous, text, user_id) VALUES({$r['a']}, '{$r['p']}', {$this->id})";
			$p_id = $conn->execInsert($command);
			if($p_id){
				//return $result;
				$p = new Post($p_id);
				$p->registerImages($r['i']);
				return $p_id;
			}else{
				return false;
			}
		}
	}

	public function getActivities(){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM activities WHERE user_id = $this->";

	}

	public function getLocation(){

		if(!$this->location OR $this->location == ",")
			return "";

		return $this->location;
	}

	public function favoritePost($p, $type){
		$conn = Connection::getInstance("write");
		if($type){
			$command = "REPLACE INTO favorites (post_id, user_id)
						VALUES({$p->getID()}, {$this->id})";
			$result = $conn->execInsert($command);
			PostActivity::logActivity(["action" => "favorite",
										"action_id" => $result,
										"post_id" => $p->getID(),
										"user_id" => $this->id]);


			return true;
		}else{
			$command = "DELETE FROM favorites 
						WHERE user_id = {$this->id}
						AND post_id = {$p->getID()}";
			$result = $conn->execDelete($command);

			PostActivity::deleteActivity(["action" => "tap",
										"post_id" => $p->getID(),
										"user_id" => $this->id]);

			return false;
		}
	}

	public function tapPost($p, $type){
		$conn = Connection::getInstance("write");
		if($type){
			$command = "REPLACE INTO taps (post_id, user_id)
						VALUES({$p->getID()}, {$this->id})";
			$result = $conn->execInsert($command);

			PostActivity::logActivity(["action" => "tap",
										"action_id" => $result,
										"post_id" => $p->getID(),
										"user_id" => $this->id]);

			return true;
		}else{
			$command = "DELETE FROM taps 
						WHERE user_id = {$this->id}
						AND post_id = {$p->getID()}";
			$result = $conn->execDelete($command);
			PostActivity::deleteActivity(["action" => "tap",
										"post_id" => $p->getID(),
										"user_id" => $this->id]);

			return false;
		}
	}

	public function sayAmen($p, $type){
		$conn = Connection::getInstance("write");
		if($type){
			$command = "REPLACE INTO amens (post_id, user_id)
						VALUES({$p->getID()}, {$this->id})";
			$result = $conn->execInsert($command);

			PostActivity::logActivity(["action" => "amen",
										"action_id" => $result,
										"post_id" => $p->getID(),
										"user_id" => $this->id]);
			return true;
		}else{
			$command = "DELETE FROM amens 
						WHERE user_id = {$this->id}
						AND post_id = {$p->getID()}";
			$result = $conn->execDelete($command);
			PostActivity::deleteActivity(["action" => "amen",
										"post_id" => $p->getID(),
										"user_id" => $this->id]);

			return false;
		}
	}

	public function postComment($p, $text){
		if($p AND $text = trim($text)){

			$conn = Connection::getInstance("write");
			$command = "INSERT INTO comments (post_id, user_id, text) VALUES({$p->getID()}, {$this->id}, '{$text}')";
			$id = $conn->execInsert($command);

			PostActivity::logActivity(["action" => "comment",
										"action_id" => $id,
										"post_id" => $p->getID(),
										"user_id" => $this->id]);
			if($id){
				return $id;
			}else{
				return false;
			}
		}
	}

	public function setProfilePicture($url){
		$conn = Connection::getInstance("write");
		$command = "UPDATE users SET avatar = '{$url}'
		WHERE user_id = {$this->id}";
		$conn->execUpdate($command);
	}
	public function getProfilePictureURL(){
		if(!$this->avatar)
			return  "img/faceless.jpg";

		return $this->avatar;
	}
	public function changePassword($current_password, $new_password){
		$conn = Connection::getInstance("read");
		$command = "SELECT password FROM users
		WHERE user_id = {$this->id}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			if(password_verify($current_password, $row['password'])){
				$conn = Connection::getInstance("write");
				$new_password = password_hash($new_password, PASSWORD_DEFAULT);
				$command = "UPDATE users
				SET password = '{$new_password}'
				WHERE user_id = {$this->id}";
				$result = $conn->execUpdate($command);
				return true;
			} else{
				return false;
			}
		} else{
			return false;
		}
	}
	public function setPassword($new_password){

		$conn = Connection::getInstance("write");
		$new_password = password_hash($new_password, PASSWORD_DEFAULT);
		$command = "UPDATE users
		SET password = '{$new_password}'
		WHERE user_id = {$this->id}";
		$result = $conn->execUpdate($command);
		return true;

	}
	public function isOnline(){
		$conn = Connection::getInstance("read");
		$command = "SELECT last_ping FROM users WHERE user_id = {$this->id}";
		$result = $conn->execObject($command);
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_array($result);
			/*CHECK IF THEY ARE ONLINE*/
			$lastping = strtotime($row['last_ping']);
			$now = time();
			$time_diff_in_min =  round(($now - $lastping) / 60 ,0);

			if($time_diff_in_min <= 2){
				return true;
			}else{
				return false;
			}
		}else{
		//return false;
		}
	}
	public function getEmail(){
		
		return $this->email;
	}
	public function getChatsID(){
		$chats = null;
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM chat_subscribers WHERE user_id = {$this->id}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			while($row  = mysqli_fetch_array($result)){
				$chats[$row['chat_id']] = $row['chat_id'];
			};
			return $chats;
		}else{
			return false;
		}
	}
	public function clearChatNotification($chat_id){
		$conn = Connection::getInstance("write");
		$command = "UPDATE chat_subscribers SET last_message_id = (SELECT MAX(message_id) FROM messages WHERE chat_id = {$chat_id})
		WHERE chat_id = {$chat_id}
		AND user_id = {$this->id}";
		$conn->execInsert($command);
	//return $id;

	//return false;

	}
	public function getLastMessage(){
		$conn = Connection::getInstance("read");
		if($chats = $this->getChatsID()){
			$max = 0;
			foreach ($chats as $chat_id) {
				$chat = new Chat($chat_id);
				if($m = $chat->getLastMessage()){
				//print_r($m);
					$tstamp = strtotime($m['time']);
					if($tstamp > $max){
					//echo $tstamp . " ";
						$max = $tstamp;
						$latest_message = $m;
					}
				}

			}
			return $latest_message;
		}else{
			return false;
		}
	}
	public function getLastChatid(){
		$conn = Connection::getInstance("read");
		if($chats = $this->getChats()){

		}
	}
	public function checkMessages(){
		$conn = Connection::getInstance("read");
		$command = "SELECT DISTINCT(chat.chat_id) FROM chat
		LEFT JOIN chat_subscribers
		ON (chat.last_message_id > chat_subscribers.last_message_id AND chat.chat_id = chat_subscribers.chat_id)
		WHERE chat_subscribers.user_id = {$this->id}
		";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			$ids = null;
			while ($row = mysqli_fetch_assoc($result)) {
				$ids[$row['chat_id']] = $row['chat_id'];
			}
			return $ids;
		}else{
			return false;
		}
	}
	public function getSuggestedChatUsers(){
		$user_ids;
		$conn = Connection::getInstance("read");
		$command = "SELECT user_id FROM users
		WHERE school_id = {$this->school_id}
		AND user_id != {$this->id}
		ORDER BY RAND() LIMIT 10";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			while($row = mysqli_fetch_array($result)){
				$user_ids[] = $row['user_id'];
			}
			return $user_ids;
		}else{
			return false;
		}
	}
	public function initiatePasswordReset(){
		$conn = Connection::getInstance("write");
		$v_code = App::generateConfirmationCode();
		$v_code = strtoupper($v_code);
		$command = "INSERT INTO password_reset (user_id, verification_code)
		VALUES({$this->id}, '{$v_code}')
		ON DUPLICATE KEY UPDATE verification_code = '{$v_code}'
		";
		if($conn->execInsert($command)){
			return $v_code;
		}
	}
	public function delete(){
		if($this->type = "teacher"){
			$conn = Connection::getInstance("write");
			$command = "DELETE FROM class_to_teacher
			WHERE teacher_id = {$this->id}";
			$conn->execDelete($command);
			$command = "DELETE FROM teachers
			WHERE teacher_id = {$this->id}";
			$conn->execDelete($command);
			$command = "DELETE FROM users
			WHERE user_id = {$this->id}";
			$conn->execDelete($command);
		}
	}
	public static function create($r){
		$conn = Connection::getInstance("write");
		$columns;
		$values;

		foreach ($r as $column => $value) {
			$columns[] = $column;
			$values[] = "'" . $value . "'";			
		}

		$columns = implode(",", $columns);	
		$values = implode(",", $values);	


		$command = "REPLACE INTO users ({$columns}) VALUES({$values})";
		$id = $conn->execInsert($command);
		if($id){
			$hash_id = self::generateHashID($id);
			$command = "UPDATE users 
						SET hash_id = '{$hash_id}'
						WHERE user_id = {$id}";
			$conn->execUpdate($command);
			return $id;
		}else{
			return false;
		}
	}

	public static function decodeHashID($hash_id){
		return Tools::decodeHashID(self::$salt, $hash_id);
	}

	public static function generateHashID($id){
		return Tools::generateHashID(self::$salt, $id);
	}

}
?>