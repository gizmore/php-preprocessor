<?php
namespace gizmore\pp;

use \gizmore\Filewalker;

/**
 * @author gizmore
 * @version 1.0.2
 */
final class Preprocessor
{
	 
	# I/O
	/**
	 * @var resource
	 */
	private $in = STDIN;

	/**
	 * @var resource
	 */
	private $out = STDOUT;

	private ?string $infile = null;  # full path

	private string $recursionFilePattern = '/\\.php[34578]?$/iD';

	# state
	private int $line = 0;
	private int $linesProcessed = 0;
	private bool $php = false; # inside php tags?
	private bool $ppc = false; # inside #pp#start# #pp#end# block?
	
	
	# options
	public ?string $outfile = null; # full path
	public bool $recursive = false; # if infile is a folder. recurse it.
	public bool $replace = false;   # replace original file(s)
	public bool $simulate = false;  # simulate always to stdout
	public bool $verbose = false;   # print scanning results
	public bool $phpmode = false;   # initial $php state
	public bool $uglify = false;   # apply php-uglify
	
	##############
	### Config ###
	##############
	/**
	 * Set the initial php state, i.e. if the processor shall be in initial <?php state.
	 */
	public function phpMode(bool $state=true): self
	{
		$this->phpmode = $state;
		return $this;
	}
	
	public function verbose(bool $verbose=true): self
	{
		$this->verbose = $verbose;
		return $this;
	}
	
	public function input(string $path): self
	{
		$this->infile = $path;
		return $this;
	}
	
	public function output(string $outfile): self
	{
		$this->outfile = $outfile;
		return $this;
	}
	
	public function simulate(bool $simulate=true): self
	{
		$this->simulate = $simulate;
		return $this;
	}
	
	public function recurse(bool $recurse=true): self
	{
		$this->recursive = $recurse;
		return $this;
	}
	
	public function replace(bool $replace=true): self
	{
		$this->replace = $replace;
		return $this;
	}
	
	public function uglify(bool $uglify=true): self
	{
		$this->uglify = $uglify;
		return $this;
	}
	
	############
	### Exec ###
	############
	public function execute(): bool
	{
		return $this->executeFor($this->infile, $this->outfile);
	}
	
	public function executeFor(string $infile, string $outfile=null): bool
	{
		if ($outfile)
		{
			$this->out = fopen($outfile, 'w');
			$this->verb('Opened output file.');
		}
		elseif ($this->replace)
		{
			$this->out = tmpfile();
			$this->verb('Replace mode engaged.');
		}
		
		if ($infile)
		{
			if (!is_readable($infile))
			{
				return $this->error("File {$infile} is not readable.");
			}
			if (is_dir($infile))
			{
				return $this->processFolder($infile);
			}
			if (is_file($infile))
			{
				$this->in = fopen($infile, 'r');
			}
		}
		return $this->processStream($this->in, $this->out);
	}
	
	###########
	### API ###
	###########
	public function processFile(string $path): void
	{
		$this->executeFor($path, null);
	}
	
	public function processString(string $string): string
	{
		$in = $this->openString($string);
		$out = tmpfile();
		$this->processStreamB($in, $out, true);
		return stream_get_contents($out);
	}

	public function processFolder(string $path): bool
	{
		$rec = $this->recursive ? 256: 0;
		$func = [$this, 'processFileCB'];
		Filewalker::traverse($path, $this->recursionFilePattern, $func, null, $rec);
		return true;
	}
	
	public function processFileCB(string $entry, string $fullpath): void
	{
		$this->processFile($fullpath);
	}
	
	
	/**
	 * Process via two file handles.
	 * 
	 * @param resource $fin
	 * @param resource $out
	 */
	public function processStream($in, $out): bool
	{
		return $this->processStreamB($in, $out, false);
	}
	
	private function processStreamB($in, $out, bool $stringmode): bool
	{
		$this->php = $this->phpmode;
		$this->ppc = false;
		$this->line = 0;
		
		$infile = stream_get_meta_data($in)['uri'];
		$outpath = stream_get_meta_data($out)['uri'];
		
		$this->verb("Processing {$infile}");
		
		while (false !== ($line = fgets($in)))
		{
			$this->line++;
			
			# empty lines are actually \n
			if ('' === ($processed = self::processLine($line)))
			{
				$this->verb("Deleted: {$line}");
			}
			elseif ($this->simulate)
			{
				fwrite(STDOUT, $processed);
				if ($stringmode)
				{
					fwrite($out, $processed);
				}
			}
			else
			{
				fwrite($out, $processed);
			}
		}
		
		if ($this->replace)
		{
			if ($this->simulate)
			{
				$this->verb("Skipping the replace in simulation mode.");
			}
			else
			{
				return rename($outpath, $infile);
			}
		}
		
		if (!$this->close())
		{
			return false;
		}
		
		if ($this->uglify)
		{
			$this->verb('Would run php-uglify now');
		}
		
		return true;
	}
	
	###############
	### Private ###
	###############
	/**
	 * Turn a string into a stream:
	 * @return resource
	 */
	private function openString(string $string)
	{
		return fopen("data://text/plain, {$string}", 'r');
	}
	
	private function processLine(string $line): string
	{
		if (strpos($line, '?>') !== false)
		{
			$this->php = false;
		}
		elseif (stripos($line, '<?php') !== false)
		{
			$this->php = true;
		}
		elseif ($this->php)
		{
			$matches = [];
			if (preg_match('/#PP#([a-z]+)#/D', $line, $matches))
			{
				switch (strtolower($matches[1]))
				{
					case 'delete':
						$this->verb("Line {$this->line}: delete");
						return '';
					
					case 'start':
						$this->verb("Line {$this->line}: start");
						$this->ppc = true;
						return '';
					
					case 'end':
						$this->verb("Line {$this->line}: end");
						$this->ppc = false;
						return '';

					case 'linux':
						if (!self::OS('lin'))
						{
							return '';
						}
						break;
						
					case 'windows':
						if (!self::OS('win'))
						{
							return '';
						}
						break;
						
					default:
						throw new \Exception(sprintf('Unknown #PP# command: #PP#%s#', $matches[1]));
				}
			}
		}
		
		# return the line, unless in #PP#start# mode
		return $this->ppc ? '' : $line;
	}
	
	private function close(): bool
	{
		if ($this->in !== STDIN)
		{
			fclose($this->in);
			$this->in = STDIN;
		}
		if ($this->out !== STDOUT)
		{
			fclose($this->out);
			$this->out = STDOUT;
		}
		return true;
	}
	
	##############
	### Output ###
	##############
	public function error(string $error): bool
	{
		fwrite(STDERR, "{$error}\n");
		return false;
	}
	
	public function message(string $msg): bool
	{
		fwrite(STDOUT, "{$msg}\n");
		return true;
	}
	
	public function verb(string $msg): bool
	{
		if ($this->verbose)
		{
			fwrite(STDOUT, "{$msg}\n");
		}
		return true;
	}
	
	###############
	### Utility ###
	###############
	/**
	 * Check the OS signature for a substring.
	 */
	public static function OS(string $string_sequence):bool
	{
		return !!stristr(PHP_OS, $string_sequence);
	}
	
}
