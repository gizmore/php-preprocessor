<?php
namespace gizmore\pp;

switch (count($argv))
{
	case 1:
		$fin = fopen(STDIN, 'rw');
		$out = fopen(STDOUT, 'w');
		break;
	case 2:
		$fin = fopen($argv[1], 'rw');
		$out = tmpfile();
		break;
	default:
		echo "Usage: {$argv[1]} [<path>]";
		break;
}

$pp = new Preprocessor($fin, $out);
$pp->process();
