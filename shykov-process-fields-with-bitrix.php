<?php

if ( !isset ( $_POST['process-fields-with-bitrix'] ) ) {
	die('Only POST-request with fields is allowed!');
}

require '../../../wp-load.php';
define('BITRIX_URL', 'https://webhook.site/2509c65f-6999-47d6-81bb-9625b7999f73');

try {

	$processor = new Process_Fields_With_Bitrix($_POST);
	$result = $processor->process();
	echo $result;

} catch(Exception $e){

	echo $e->getMessage();

}

class Process_Fields_With_Bitrix {


	private $_bitrix_url = BITRIX_URL;
	private $_response_from_bitrix = null;
	private $_fields = array(
			'email' => array(
					'field_value' => null, 
					'field_validation_method_name' => 'validate_email',
					),
			'username' => array(
					'field_value' => null, 
					'field_validation_method_name' => 'validate_username',
					),
			);
	private $_dirty_fields = array();

	public function __construct($fields){

		$this->_dirty_fields = $fields;
	}

	public function process(){

		foreach(array_keys($this->_fields) as $field_name){
			if( !array_key_exists($field_name, $this->_dirty_fields) ){
				throw new Exception("Field '$field_name' is missing!");
			}
			// If field is present let's validate it by name and value:
			if ( !$this->_validate_field($field_name, $this->_dirty_fields[$field_name]) ){
				throw new Exception("Field '$field_name' is not valid!");
			}
			$this->_fields[$field_name]['field_value'] = $this->_dirty_fields[$field_name];
		}
		return $this->_send_fields_to_bitrix();
	}

	public function validate_email($email){

		if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
    		return true;
		}
		return false;
	}

	public function validate_username($username){

		return true;
	}

	private function _validate_field($field_name, $field_value){

		return call_user_func_array( 
			array( $this, $this->_fields[ $field_name ]['field_validation_method_name'] ), 
			array("$field_name" => $field_value) 
			);
	}

	private function _send_fields_to_bitrix(){

		$fields_to_send = array();
		foreach ($this->_fields as $field_name => $field_array) {
			$fields_to_send[$field_name] = $field_array['field_value'];
		}
		$this->_response_from_bitrix = wp_remote_post($this->_bitrix_url, $fields_to_send);
		if( is_wp_error($this->_response_from_bitrix) ){
			throw new Exception('Error on querying Bitrix!');
		}
		return "Fields sent succesfully!";
	}

}

?>