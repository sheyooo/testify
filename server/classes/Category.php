<?php 

class Category {

	private $id;
	private $name;


	public function __construct($id){
		if($id){
			$conn = Connection::getInstance("read");
			$command = "SELECT * FROM categories 
						WHERE cat_id = {$id}";
			$r = $conn->execObject($command);
			if(mysqli_num_rows($r)){
				$row = mysqli_fetch_assoc($r);

				$this->id = $row['cat_id'];
				$this->name = $row['name'];
			}else{
				$this->id = null;
				$this->name = "Not categorized";
			}
		}

	}

	public function getID(){

		return $this->id;
	}

	public function getName(){

		return $this->name;
	}

	public function countPosts(){
		$conn = Connection::getInstance("read");
		$command = "SELECT COUNT(*) AS count 
					FROM posts 
					WHERE cat_id = {$this->id}";
		$r = $conn->execObject($command);
		$row = mysqli_fetch_assoc($r);
		return $row['count'];
	}
















}