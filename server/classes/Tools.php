<?php


class Tools {


	public static function cleanString($string){
	
		return mysqli_real_escape_string( Connection::getInstance("read")->connObject(), $string);
	}

	public static function valueGetAllowTags($param){
		if (isset($_GET[$param])){
			return trim(Tools::cleanString($_GET[$param]));
		}else{
			return false;
		}
	}

	public static function valuePostAllowTags($param){
		if (isset($_POST[$param])){
			return trim(Tools::cleanString($_POST[$param]));
		}else{
			return false;
		}
	}

	public static function valueGet($param){
		if (isset($_GET[$param])){
			$param = strip_tags($_GET[$param]);
			return trim(Tools::cleanString($param));
		}else{
			return false;
		}
	}

	public static function valuePost($param){
		if (isset($_POST[$param])){
			$param = strip_tags($_POST[$param]);
			return trim(Tools::cleanString($param));
		}else{
			return false;
		}
	}

	public static function generateHashID($salt, $id){
		$hashids = new Hashids\Hashids($salt, 10);
		$id = $hashids->encode($id);
		//$numbers = $hashids->decode($id);
		//echo $id;
		return $id;
	}

	public static function decodeHashID($salt, $hash_id){
		$hashids = new Hashids\Hashids($salt, 10);
		$number = $hashids->decode($hash_id);
		//print_r($number);
		if(isset($number[0])){
			return $number[0];
		}else{
			throw new Exception("Unable to decode hash_id");
		}
	}



};

?>