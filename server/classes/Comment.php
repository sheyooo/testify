<?php

class Comment
{	
	private $id;
	private $post_id;
	private $author;
	private $time;
	private $text;
	
	public function __construct($id){
		$conn = Connection::getInstance("read");
		$command = "SELECT * FROM comments 
					WHERE comment_id = {$id}";
		$result = $conn->execObject($command);
		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);

			$this->id = $row['comment_id'];
			$this->post_id = $row['post_id'];
			$this->author = $row['user_id'];
			$this->time = $row['time'];
			$this->text = $row['text'];

			
			return true;
		}else{
			return false;
		}

	}

	public function getID(){

		return $this->id;
	}

	public function getPostID(){

		return $this->post_id;
	}

	public function getAuthor(){
		//echo $this->author;
		return new User($this->author);
	}

	public function getTime(){

		return $this->time;
	}

	public function getText(){

		return $this->text;
	}
}