<?php
namespace gizmore\pp;

use \gizmore\Filewalker;

/**
 * @author gizmore
 * @version 1.0.1
 */
final class Preprocessor
{
	/**
	 * @var resource
	 */
	private $in = STDIN;

	/**
	 * @var resource
	 */
	private $out = STDOUT;
	
	private bool $php = false; # inside php tags?
	private bool $ppc = false; # inside pp codeblock?
	private int $line = 0;
	
	public bool $recursive = false; # if infile is a folder. recurse it.
	public bool $replace = false;   # replace original file(s)
	public bool $simulate = false;  # simulate always to stdout
	public bool $verbose = false;   # print scanning results
	
	public ?string $infile = null;
	public ?string $outfile = null;
	
	private function close()
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
	}
	
	##############
	### Config ###
	##############
	public function phpMode(bool $php=true): self
	{
		$this->php = $php;
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
	
	##########
	### Go ###
	##########
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
	/**
	 * Preprocess a string / file contents.
	 * @param bool $php - a local variable if we are in php mode atm.
	 */
	public function processString(string $string, bool $php = false): string
	{
		$this->php = $php;
		$this->ppc = false;
		$in = $this->openString($string);
		$out = tmpfile();
		$this->processStream($in, $out);
		$tmpname = stream_get_meta_data($out)['uri'];
		fclose($in);
		fclose($out);
		return file_get_contents($tmpname);
	}

	public function processFolder(string $path): bool
	{
		$rec = $this->recursive ? 256: 0;
		$func = [$this, 'processFile'];
		Filewalker::traverse($path, '/.php$/iD', $func, null, $rec);
		return true;
	}
	
	public function processFile(string $entry, string $fullpath, $args=null): void
	{
		$this->executeFor($fullpath, null);
	}
	
	/**
	 * Process via two file handles.
	 * 
	 * @param resource $fin
	 * @param resource $out
	 */
	public function processStream($in, $out): bool
	{
		$this->line = 0;
		
		$infile = stream_get_meta_data($in)['uri'];
		$outpath = stream_get_meta_data($out)['uri'];
		
		$this->verb("Processing {$infile}");
		
		while (false !== ($line = fgets($in)))
		{
			$this->line++;
			
			if ('' === ($processed = self::processLine($line)))
			{
				$this->verb("Deleted: {$line}");
			}
			elseif ($this->simulate)
			{
				fwrite(STDOUT, $processed);
			}
			else
			{
				fwrite($out, $processed);
			}
		}
		
		if ($this->replace)
		{
			return rename($outpath, $infile);
		}
		
		$this->close();
		
		return true;
	}
	
	###############
	### Private ###
	###############
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
			if (preg_match('/#PP#([a-z]+)#/iD', $line, $matches))
			{
				switch (strtolower($matches[1]))
				{
					case 'delete':
						$this->verb("Line {$this->line}: delete");
						return '';
					case 'start':
						$this->verb("Line {$this->line}: start");
						$this->ppc = true;
						break;
					case 'end':
						$this->verb("Line {$this->line}: end");
						$this->ppc = false;
						break;
				}
				return '';
			}
		}
		return $this->ppc ? '' : $line;
	}
	
	private function openString(string $string)
	{
		return fopen("data://text/plain, {$string}", 'r');
	}
	
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
	
}
