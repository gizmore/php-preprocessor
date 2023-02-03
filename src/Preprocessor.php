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
	 * @param bool $php - a local variable if we are in php mode atm.
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

	###################
	### InPlace API ###
	###################
	public static function processPath(string $path): bool
	{
		if (is_readable($path))
		{
			if (is_file($path))
			{
				return self::processFolder($path);
			}
			elseif (is_dir($path))
			{
				return self::processFilename($path);
			}
		}
		return false;
	}
	
	public static function processFolder(string $path): bool
	{
	}
	
	public static function processFilename(string $path): bool
	{
		
	}
	
	/**
	 * Process via two file handles.
	 * 
	 * @param resource $fin
	 * @param resource $out
	 * @param bool $php - a local variable if we are in php mode atm.
	 * @param bool $ppc - a local variable if we are in a pp comment block atm.
	 */
	public static function processFiles($fin, $out, bool $php=false, bool $ppc=false): void
	{
		while ($line = fgets($fin))
		{
			fwrite($out, self::processLine($line, $php, $ppc));
		}
		return $out;
	}
	
	###############
	### Private ###
	###############
	private static function processLine(string $line, bool &$php, bool &$ppc): string
	{
		if (strpos($line, '?>') !== false)
		{
			$php = false;
		}
		elseif (stripos($line, '<?php') !== false)
		{
			$php = true;
		}
		elseif ($php)
		{
			$matches = [];
			if (preg_match('/#PP#([a-z]+)#/iD', $line, $matches))
			{
				switch (strtolower($matches[1]))
				{
					case 'delete':
						return '';
					case 'start':
						$ppc = true;
						break;
					case 'end':
						$ppc = false;
						break;
				}
				return '';
			}
		}
		return $ppc ? '' : $line;
	}
	
	private static function openString(string $string)
	{
		return fopen("data://text/plain, {$string}", 'r');
	}
	
}
