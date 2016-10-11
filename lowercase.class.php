<?php

class Lowercase {
	/** Default slide text (PP5 hides this text if that's all that's on the slide) */
	const HOTWORD_IGNORE = 'Double-click to edit';

	/** @var array Strings to uppercase */
	protected $uc_strings = [];
	/** @var string ProPresenter document data */
	protected $file_data;
	/** @var bool Sentence case */
	protected $sentence = false;

	/**
	 * Set words to transform
	 * @param string|array $uc_strings Strings to uppercase
	 * @return self
	 */
	public function setUcStrings($uc_strings) {
		if(is_string($uc_strings)) {
			$uc_strings = explode("\n", str_replace("\r", '', $uc_strings));
		}

		$this->uc_strings = (array)$uc_strings;
		return $this;
	}

	/**
	 * Load ProPresenter 5 Document
	 * @param string $filename Filename to transform
	 * @return self
	 */
	public function loadFile($filename) {
		if(!file_exists($filename)) throw new LowercaseException('File not found.');
		
		$this->file_data = file_get_contents($filename);

		return $this;
	}

	/**
	 * Transform document
	 * @return string Return transformed document
	 */
	public function transform() {
		if(!$this->file_data) throw new LowercaseException('File not loaded.');

		$data = preg_replace_callback('/RTFData="(.*?)"/is', function($matches) {
			return 'RTFData="' . $this->transformSlide($matches[1]) . '"';
		}, $this->file_data);

		return $data;
	}

	/**
	 * Transform slide
	 * Does all lowercase/uppercase transformations for a slide
	 * @param string $encoded_text Text from document (base64 encoded)
	 * @return string base64 encoded slide text
	 */
	protected function transformSlide($encoded_text) {
		$text = base64_decode($encoded_text);

		if(strpos($text, self::HOTWORD_IGNORE) !== false) return $encoded_text;

		$text = strtolower($text);
		foreach($this->uc_strings as $word_replace) {
			$search = '/\b(' . preg_quote($word_replace, '/') . ')\b/is';

			$text = preg_replace_callback($search, function($words) use($word_replace) {
				return $word_replace;
			}, $text);
		}

		if($this->sentence) $text = $this->transformSentenceCase($text);
		return base64_encode($text);
	}

	public function transformSentenceCase($text) {
		//var_dump($text);//die;
		
		//\\([a-z0-9]*) ([a-z0-9\\\r\n\n \']*)\} - new style
		//\\\cf1([a-z0-9\' \\\r\n]*)\} - old style
		return preg_replace_callback('/\\\([a-z0-9]*) ([a-z0-9\\\r\n\n \'\-]*)\}/is', function($matches) {
			$matches[2] = ucfirst(ltrim($matches[2]));
			return '\\' . $matches[1] . ' ' . $matches[2] . '}';
		}, $text);
	}

	/**
	 * Sends document to browser with headers
	 * @param string $filename Document filename
	 * @param string $prefix Filename prefix
	 * @param string $postfix Filename postfix
	 */
	public function download($filename, $prefix = 'LC - ', $postfix = '') {
		$data = $this->transform();

		header('Content-Type: text/xml');
		header('Content-Length:' . strlen($data));
		header('Content-Disposition: attachment; filename=' . 
			$prefix . str_replace('.pro5', $postfix . '.pro5', $filename)
		);
		echo $data;

		return $this;
	}

	public function setSentenceCase($sc) {
		$this->sentence = (bool)$sc;
		return $this;
	}

	public function isSong() {
		return strpos($this->file_data, 'category="Song"') === false ? false : true;
	}

	public function save($folder, $filename, $prefix = 'LC - ', $postfix = '') {
		file_put_contents($folder . '/' . $prefix . str_replace('.pro5', $postfix . '.pro5', $filename), $this->transform());
	}
	
	public function removeTransitions() {
		$this->file_data = preg_replace(
			'/<_-RVProTransitionObject-_transitionObject(.*)<\/_-RVProTransitionObject-_transitionObject>/U',
			'', $this->file_data);
		return $this;
	}
	
	/**
	 * Do all transformations
	 * Does all transforms, sends to browser and returns object
	 * @param string|array $words
	 * @param array $post_file $_FILE post array
	 * @param string $prefix
	 * @param string $postfix
	 * @param bool $sentence Every work should be capitalized
	 * @return self
	 */
	public static function quickTransformFolder($words, $folder, $prefix = '', $postfix = '', $sentence = false, $rem_trans = false) {
		$folder_list = scandir($folder);

		foreach($folder_list as $file) {
			if($file === '.' || $file === '..') continue;

			$uc_obj = new self();
			$uc_obj->setUcStrings($words)
				->setSentenceCase($sentence)
				->loadFile($folder . '/' . $file);
			if($rem_trans) $uc_obj->removeTransitions();
			if(!$uc_obj->isSong()) continue;
			
			$uc_obj->save(
				$folder . '/lc',
				$file,
				$prefix,
				$postfix
			);
		}
		return $uc_obj;
	}

	/**
	 * Do all transformations
	 * Does all transforms, sends to browser and returns object
	 * @param string|array $words
	 * @param array $post_file $_FILE post array
	 * @param string $prefix
	 * @param string $postfix
	 * @param bool $sentence Every work should be capitalized
	 * @return self
	 */
	public static function quickTransform($words, $post_file, $prefix = '', $postfix = '', $sentence = false, $rem_trans = false) {
		$uc_obj = new self();
		$uc_obj->setUcStrings($words)
			->setSentenceCase($sentence)
			->loadFile($post_file['tmp_name']);
		if($rem_trans) $uc_obj->removeTransitions();
		$uc_obj->download(
				$post_file['name'],
				$prefix,
				$postfix
			);
		return $uc_obj;
	}
}

class LowercaseException extends \Exception {}
