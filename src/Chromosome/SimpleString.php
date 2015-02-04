<?php

namespace Ryanhs\GAToolkit\Chromosome;

require 'vendor/autoload.php';

class SimpleString extends AbstractChromosome{
	
	protected static $options_default = array(
		'length' => 0,
		//'seed' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890 !@#$%^&*()_+'
		'seed' => 'abcdefghijklmnopqrstuvwxyz'
	);
	
	public function __construct($data){
		$this->data = $data;
	}
	
	public static function generate($options){
		$data = '';
		
		$tmp_options = array_merge(self::$options_default, $options);
		for($i = 0; $i < intval($tmp_options['length']); $i++){
			$c = $tmp_options['seed'][rand(0, strlen($tmp_options['seed']) - 1)];
			$data .= $c;
		}
		return new SimpleString($data);
	}
	
	public function fitness_function($goal = false){
		$goal = str_split(strval($goal));
		$data = str_split($this->data);
		
		$fitness = 0;
		
		foreach($data as $i => $c){
			if(!empty($goal[$i])){
				if($goal[$i] == $c)
					$fitness++;
			}
		}
		
		return $fitness;
	}
	
	public function breeding($partner, $options = array()){
		$goal = str_split($options['goal']);
		$a = str_split($partner->get_data());
		$b = str_split($this->data);
		
		$c_data = '';
		foreach($goal as $i => $chr){
			
			$tmp_a = isset($a[$i]) ? $a[$i] : rand(0, strlen($tmp_options['seed']) - 1);
			$tmp_b = isset($b[$i]) ? $b[$i] : rand(0, strlen($tmp_options['seed']) - 1);
			
			if($tmp_a == $chr)
				$c_data .= $tmp_a;
			else if($tmp_b == $chr)
				$c_data .= $tmp_b;
			else
				$c_data .= rand(0, 1) == 1 ? $tmp_a : $tmp_b;
		}
		$class_name = get_called_class();
		return new $class_name($c_data);
	}
	
	public function mutation($goal = false){
		if($goal === false)
			return $this;
		
		$new_data = str_split($this->data);
		$goal = str_split(strval($goal));
		
		$chromosome_i = rand(0, count($goal) - 1);
		if(isset($new_data[$chromosome_i])){
			$new_data[$chromosome_i] = $goal[$chromosome_i];
		}
		
		$this->data = implode($new_data);
		
		return $this;
	}
	
}
