<?php
namespace gizmore\pp;

/**
 * Use the static API.
 * Object is for the binary script streamer.
 * 
 * @author gizmore
 * @version 1.0.0
 */
final class Preprocessor
{
	/**
	 * @var resource
	 */
	private $fin;

	/**
	 * @var resource
	 */
	private $out;
	
	private bool $php = false;
	
	/**
	 * @param resource $fin
	 * @param resource $out
	 */
	public function __construct($fin, $out)
	{
		$this->fin = $fin;
		$this->out = $out;
	}
	
	public function __destruct()
	{
		if ($this->fin)
		{
			fclose($this->fin);
		}
		if ($this->out)
		{
			fclose($this->out);
		}
	}
	
	/**
	 * Run the preprocessor.
	 */
	public function process(): self
	{
		self::processFiles($this->fin, $this->out, $this->php);
		return $this;
	}

	###########
	### API ###
	###########
	/**
	 * Preprocess a string / file contents.
	 */
	public static function processString(string $string, bool $php = false): string
	{
		$fin = self::openString($string);
		$out = tmpfile();
		self::processFiles($fin, $out, $php);
		$tmpname = stream_get_meta_data($out)['uri'];
		fclose($fin);
		fclose($out);
		return file_get_contents($tmpname);
	}
	
	/**
	 * Process via two file handles.
	 * 
	 * @param resource $fin
	 * @param resource $out
	 */
	public static function processFiles($fin, $out, bool $php=false): void
	{
		while ($line = fgets($fin))
		{
			fwrite($out, self::processLine($line, $php));
		}
		return $out;
	}
	
	###############
	### Private ###
	###############
	private static function processLine(string $line, bool &$php): string
	{
		if (strpos($line, '?>') !== false)
		{
			$php = false;
		}
		elseif (stripos($line, '<?php') !== false)
		{
			$php = true;
		}
		elseif ($php && strpos('/#PP#delete/', $line))
		{
			return '';
		}
		return $line;
	}
	
	private static function openString(string $string)
	{
		return fopen("data://text/plain, {$string}", 'r');
	}
	
}
