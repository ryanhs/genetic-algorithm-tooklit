<?php

namespace Ryanhs\GAToolkit;

require 'vendor/autoload.php';

use \Ryanhs\Hook\Hook;

class GeneticAlgorithm{

	const HOOK_INIT = 'GeneticAlgoritm_init';
	const HOOK_INIT_POPULATION = 'GeneticAlgoritm_init_population';
	const HOOK_FITNESS_FUNCTION = 'GeneticAlgoritm_fitness_function';
	const HOOK_SELECTION = 'GeneticAlgoritm_selection';
	const HOOK_BREEDING = 'GeneticAlgoritm_breeding';
	const HOOK_CROSSOVER = self::HOOK_BREEDING;
	const HOOK_MUTATION = 'GeneticAlgoritm_mutation';
	const HOOK_REGENERATION = 'GeneticAlgoritm_regeneration';
	const HOOK_FINISH = 'GeneticAlgoritm_finish';
	const HOOK_FINISH_GOAL = 'GeneticAlgoritm_finish_goal';

	protected $dependency;
	public $options = array(
		'goal' => false,

		'max_generation' => 1000,
		'max_population' => 20,
		'selection' => 90, // percent
		'mutation' => 1, // percent
	);

	protected $population;
	public $population_i;
	protected $population_fitness;
	protected $population_historic;
	protected $best_chromosome;

	public function __construct(Dependency $dependency){
		$this->dependency = $dependency;

		$this->population = array();
		$this->population_i = 0;
		$this->population_fitness = array();
		$this->population_historic = array();

		Hook::call(self::HOOK_INIT);
	}

	public function set_option($key_options, $value = null){
		if(is_array($key_options)){
			$this->options = array_merge($this->options, $key_options);
			return true;
		}

		$this->options[$key_options] = $value;
		return true;
	}

	public function get_option($key){
		return isset($this->options[$key]) ? $this->options[$key] : null;
	}

	public function init_population($options = array()){
		$this->population = array();
		$this->population_fitness = array();
		$this->population_historic = array();

		for($i = 0; $i < $this->options['max_population']; $i++){
			$this->population[] = call_user_func($this->dependency->chromosome . '::generate', $options);
		}
		Hook::call(self::HOOK_INIT_POPULATION);
		
		return $this;
	}

	public function fitness_function(){
		$tmp_fitness = array(
			'index' => 0,
			'fitness' => 0,
		);
		
		$this->population_fitness = array();
		foreach($this->population as $index => $chromosome){
			$fitness = $chromosome->fitness_function($this->options['goal']);

			$this->population[$index]->tmp['fitness'] = $fitness;
			$this->population_fitness[$index] = $fitness;
			
			
			if($fitness > $tmp_fitness['fitness']){
				$tmp_fitness = array(
					'index' => $index,
					'fitness' => $fitness,
				);
			}
		}
		
		$this->best_chromosome = $this->population[$tmp_fitness['index']];

		arsort($this->population_fitness);
		Hook::call(self::HOOK_FITNESS_FUNCTION);

		return $this;
	}

	public function selection(){
		$pc = count($this->population);
		$pc_to = floor($this->options['selection'] / 100 * $pc);

		// get slice based on fitness_function
		$this->population_fitness = array_slice($this->population_fitness, 0, $pc_to - 1);
		$new_population = array();
		foreach($this->population_fitness as $i => $fitness){
			$new_population[] = $this->population[$i];
			
			//echo $this->population[$i]->get_data() . PHP_EOL;
		}

		$this->population = $new_population;
		Hook::call(self::HOOK_SELECTION);

		return $this;
	}

	public function crossover(){
		$this->population_historic[] = array(
			'population' => $this->population,
			'population_fitness' => $this->population_fitness,
		);

		$new_population = array();

		for($i = 0; $i < $this->options['max_population']; $i++){
			$a = array_rand($this->population);
			$b = array_rand($this->population);

			$a = $this->population[$a];
			$b = $this->population[$b];

			$c = $a->crossover($b, $this->options);
			$new_population[] = $c;
		}

		$this->population = $new_population;
		$this->population_fitness = array();

		$this->fitness_function();
		
		Hook::call(self::HOOK_CROSSOVER);

		return $this;
	}

	public function mutation(){

		Hook::call(self::HOOK_MUTATION);

		return $this;
	}

	public function get_best(){
		$this->fitness_function();
		return $this->best_chromosome;
	}

	public function get_population(){
		return $this->population;
	}

	public function get_population_fitness(){
		return $this->population_fitness;
	}

	public function get_population_historic(){
		return $this->population_historic;
	}

	public function run($chromosome_options){
		$this->set_option($chromosome_options);
		
		$this->init_population($this->options);
		$this->fitness_function();

		$match_goal = false;
		while($this->population_i < $this->options['max_generation']){
			$this->population_i++;
			
			$this->selection();			
			$this->crossover();
			$this->mutation();
			
			// compare the best chromosome to goal
			if($this->get_best()->get_data() == $this->options['goal']){
				$match_goal = true;
				break;
			}
			
			Hook::call(self::HOOK_REGENERATION);
		}

		Hook::call(self::HOOK_FINISH);

		if($match_goal){
			Hook::call(self::HOOK_FINISH_GOAL);
		}
		
		return $match_goal;
	}
}
