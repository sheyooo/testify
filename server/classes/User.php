<?php
class User{
	private $id;
	private $school_id;
	private $title;
	protected $first_name;
	protected $last_name;
	private $avatar;
	private $sex;
	private $type;
	private $username;
	private $phone;
	private $email;
	private $online_status;
	private $fields_from_db;

	public function __construct($id){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM users
					WHERE user_id = {$id}";
		$result = $conn->execObject($command);
	
		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			unset($row['password']);		
			$this->initialized = true;
			$this->id = $row['user_id'];
			$this->first_name = $row['first_name'];
			$this->last_name = $row['last_name'];
			$this->avatar = $row['avatar'];
			$this->email = $row['email'];
			$this->sex = $row['sex'];
			$this->fields_from_db = $row;
		} else{
			return false;
		};
	}
	public function getFields(){
		if($this->fields_from_db){
			return $this->fields_from_db;
		} else{
			return false;
		};
	}
	public function getID(){

		return $this->id;
	}
	public function getFullname(){
		$t = $this->type;
		if($t == "parent" OR $t == "admin" OR $t == "teacher"){
			return $this->title . " " . $this->first_name . " " . $this->last_name;
		}else{
			return $this->first_name . " " . $this->last_name;
		}
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
	public function createPost($p, $a){
		$conn = Connection::getInstance("write");

	//if($a == true){$a = 1;}else{$a = 0;};

		if ($p) {		
			$command = "INSERT INTO posts (anonymous, testimony, user_id) VALUES({$a}, '{$p}', {$this->id})";
			$result = $conn->execInsert($command);
			if($result){
				return $result;
			}else{
				return false;
			}
		}
	}

	public function likePost($p, $type){
		$conn = Connection::getInstance("write");
		if($type){
			$command = "REPLACE INTO likes (post_id, user_id)
						VALUES({$p->getID()}, {$this->id})";
			$result = $conn->execInsert($command);
			return true;
		}else{
			$command = "DELETE FROM likes 
						WHERE user_id = {$this->id}
						AND post_id = {$p->getID()}";
			$result = $conn->execDelete($command);

			return false;
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
		if($this->email){
			return $this->email;
		}else{
			return false;
		}
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


		$command = "INSERT INTO users ({$columns}) VALUES({$values})";
		$result = $conn->execInsert($command);
		if($result){
			return $result;
		}else{
			return false;
		}
	}

}
?>