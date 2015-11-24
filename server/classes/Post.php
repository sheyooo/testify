<?php

class Post{
	private $id;
	private $author;
	private $time;
	private $anonymous;
	private $text;
	private $images = [];
	private $category_id;


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
			$this->category_id = $row['cat_id'];

			$command = "SELECT * FROM images WHERE post_id = {$this->id}";
			$r = $conn->execObject($command);
			if(mysqli_num_rows($r)){
				while ($row = mysqli_fetch_assoc($r)) {
					$img = new Image($row['image_id']);
					$this->images[] = $img;
				}
			}
		}else{
			return false;
		}
	}

	public function getID(){

		return $this->id;
	}

	public function getAuthor(){
		if(!$this->anonymous){
			return new User($this->author);
		}else{
			return false;
		}
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

	public function addComment($r){
		if(is_array($r) && $r['text'] && $r['user_id']){

			$conn = Connection::getInstance("write");
			$command = "INSERT INTO comments (post_id, user_id, text) VALUES({$this->id}, {$r['user_id']}, '{$r['text']}')";
			$id = $conn->execInsert($command);
			if($id){
				return $id;
			}else{
				return false;
			}
		}
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

	public function getImages(){

		return $this->images;
	}

	public function registerImages($r){
		if(is_array($r) && count($r) > 0){
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

	public function getCategory(){

		return new Category($this->category_id);
	}

	public function setCategory($id){
		if($this->id){
			$conn = Connection::getInstance("write");
			$command = "UPDATE posts 
						SET cat_id = '{$id}'
						WHERE post_id = {$this->id}";
			$r = $conn->execUpdate($command);
		}

	}


	public function delete(){
		$conn = Connection::getInstance("write");
		$command = "DELETE FROM posts 
					WHERE post_id = {$this->id}";
		$r = $conn->execDelete($command);
		if($r){
			return true;
		}
	}





}

?>