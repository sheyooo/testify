<?php

class Post{
	private $id;
	private $author;
	private $time;
	private $anonymous;
	private $text;


	public function __construct($id){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM posts WHERE post_id = {$id}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			$this->id = $row['post_id'];
			$this->author = $row['user_id'];
			$this->time = $row['time'];
			$this->anonymous = $row['anonymous'];
			$this->text = $row['text'];
		}else{
			return false;
		}

	}

	public function getID(){

		return $this->id;
	}

	public function getAuthor(){
		return $this->author;
	}

	public function isAnonymous(){
		return $this->anonymous;
	}

	public function getTime(){
		return $this->time;
	}

	public function getText(){
		return $this->text;
	}

	public function countLikes(){
		$conn = Connection::getInstance("read");
		$command = "SELECT COUNT(*) AS likes FROM likes 
					WHERE post_id = {$this->id}";
		$r = $conn->execObject($command);

		return mysqli_fetch_assoc($r)['likes'];
	}

	public function countComments(){
		$conn = Connection::getInstance("read");
		$command = "SELECT COUNT(*) AS comments FROM comments 
					WHERE post_id = {$this->id}";
		$r = $conn->execObject($command);

		return mysqli_fetch_assoc($r)['comments'];
	}

	public function countTaps(){
		$conn = Connection::getInstance("read");
		$command = "SELECT COUNT(*) AS taps FROM taps 
					WHERE post_id = {$this->id}";
		$r = $conn->execObject($command);

		return mysqli_fetch_assoc($r)['taps'];
	}

	public function isLiked($user){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM likes WHERE post_id = {$this->id} AND user_id = {$user->getId()}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			return true;
		}else{
			return false;
		}
	}

	public function isTappedInto($user){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM taps WHERE post_id = {$this->id} AND user_id = {$user->getId()}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			return true;
		}else{
			return false;
		}
	}

	public function getComments($limit){
		$arr = array();
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM comments 
					WHERE post_id = {$this->id}
					ORDER BY time DESC
					LIMIT {$limit}";

		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			while($row = mysqli_fetch_assoc($result)){
				$comment = new Comment($row['comment_id']);
				$arr[] = $comment;
			}
		}

		return $arr;
	}


	public function registerImages($r){
		if(is_array($r) && count($r) > 1){
			$conn = Connection::getInstance("write");
			//$images = implode("OR", $r);
		
			$r = array_map(function($v){
				return " image_id=" . $v;
			}, $r);
			$string = implode(" OR ", $r);
			//echo $string;
			$command = "UPDATE images 
						SET post_id = {$this->id}
						WHERE " . $string;
			$r = $conn->execUpdate($command);
		}
	}





}

?>