<?php 

class Image {

	private $id;
	private $url;
	private $file_name;
	private $user_id;
	private $post_id;
	private $time;



	public function __construct($id){
		$conn = Connection::getInstance("read");

		$command ="SELECT * FROM images 
					WHERE image_id = {$id}";

		$result = $conn->execObject($command);

		if(mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);

			$this->id = $row['image_id'];
			$this->url = $row['url'];
			$this->file_name = $row['file_name'];
			$this->user_id = $row['user_id'];
			$this->post_id = $row['post_id'];
			$this->time = $row['time'];

			return true;
		}else{
			return false;
		}
	}

	public function getID(){

		return $this->id;
	}

	public function getUrl(){

		return $this->url;	
	}

	public function getFileName(){
    	
		return $this->file_name;		
	}

	public function getUserID(){
		
		return $this->user_id;
	}

	public function getPostID(){
		
		return $this->post_id;
	}

	public function getTime(){

		return $this->time;
	}

	public static function addTemp($r){
		if(is_array($r)){
			$columns;
			$values;
			foreach ($r as $column => $value) {
				$columns[]  = $column;
				$values[] ="'" . $value . "'"; 
			}

			$columns = implode(",", $columns);
			$values =  implode(",", $values);

			$conn = Connection::getInstance("write");
			$command = "INSERT INTO images ({$columns}) VALUES ({$values})";
			$id = $conn->execInsert($command);

			return $id;
		}else{
			return false;
		}

	}

}

?>