<?
	$indir  = "/var/www/html/imgproc/in";
	$outdir = "/var/www/html/imgproc/out";

	$inurl  = "/imgproc/in";
	$outurl = "/imgproc/out";

	$tool_map = array(
		'im'		=> 'Image Magick',
		'im_latest'	=> 'Image Magick 6.6.8-5',
		'nq'		=> 'PNG NQ',
		'adv'		=> 'AdvPNG',
		'out'		=> 'PNGOUT',
		'opt'		=> 'OptiPNG',
		'c1'		=> 'Chain 1',
	);

	function OrNone($s){

		return strlen($s) ? $s : '<i>none</i>';
	}


	#
	# get list of input files
	#

	$files = array();

	$dh = opendir($indir);
	while ($file = readdir($dh)){
		if (preg_match('!\.png$!', $file, $m)){

			$files[$file] = filesize("$indir/$file");
		}
	}
	closedir($dh);

	ksort($files);


	#
	# process?
	#

	if ($_POST['done']){

		$hash = md5($_POST['source'].$_POST['tool'].$_POST['args']);
		if (file_exists("$outdir/$hash") && is_dir("$outdir/$hash")){

			header("location: ./?h=$hash");
			exit;
		}


		#
		# new args - run it
		#

		@mkdir("$outdir/$hash", 0777);

		$args = $_POST['args'];
		$src = "$indir/$_POST[source]";

		$command_pre = "";
		$command = "";
		$command_post = "";

		$out = "$outdir/$hash/out.png";

		if ($_POST['tool'] == 'im'){
			$command = "convert {$src} $args $out";
		}

		if ($_POST['tool'] == 'im_latest'){
			$command = "/usr/local/bin/convert_6.6.8-5 {$src} $args $out";
		}

		if ($_POST['tool'] == 'nq'){

			$source_filename = basename($src);
			$nq_out = substr($source_filename, 0, -4).'-nq8.png';

			$command = "/usr/local/bin/pngnq $args -d $outdir/$hash/ {$src}";
			$command_post = "mv $outdir/$hash/$nq_out $out";
		}

		if ($_POST['tool'] == 'adv'){

			$command_pre = "cp {$src} $out";
			$command = "/usr/local/bin/advpng $args $out";
		}

		if ($_POST['tool'] == 'out'){

			$command = "/usr/local/bin/pngout-20110109-x86_64-static $src $out $args";
		}

		if ($_POST['tool'] == 'opt'){

			$command = "/usr/local/bin/optipng $args -out $out $src";
		}

		if ($_POST['tool'] == 'c1'){

			$source_filename = basename($src);
			$nq_out = substr($source_filename, 0, -4).'-nq8.png';

			$command = "/usr/local/bin/pngnq -s10 -d $outdir/$hash/ {$src} && mv $outdir/$hash/$nq_out $out && /usr/local/bin/advpng -z -4 $out";
		}


		#
		# run
		#

		$result = array();

		$result['cmd'] = $command;
		$result['cmd_pre'] = $command_pre;
		$result['cmd_post'] = $command_post;
		$result['source'] = $_POST[source];
		$result['tool'] = $_POST[tool];
		$result['args'] = $_POST[args];

		if ($command_pre){
			$result['pre_out'] = shell_exec($command_pre);
		}

		$out = array();
		$exit = 0;
		$now = microtime(true);
		exec("$command 2>&1", $out, $exit);
		$time = microtime(true) - $now;

		$result['elapsed'] = $time;
		$result['output'] = implode("\n", $out);
		$result['exit'] = $exit;

		if ($command_post){
			$result['post_out'] = shell_exec($command_post);
		}


		#
		# save
		#

		$fp = fopen("$outdir/$hash/ret.txt", 'w');
		fwrite($fp, "<"."?php \$result = ");
		fwrite($fp, var_export($result, true));
		fwrite($fp, ";\n?".">");


		header("location: ./?h=$hash");
		exit;
	}


	#
	# display results?
	#

	if ($_GET['h']){

		include("$outdir/$_GET[h]/ret.txt");

		$row = $result;

		$in_url  = "$inurl/$row[source]";
		$out_url = "$outurl/$_GET[h]/out.png";

		$in_path  = "$indir/$row[source]";
		$out_path = "$outdir/$_GET[h]/out.png";

		$in_size = filesize($in_path);
		$out_size = filesize($out_path);

		$ratio = round(100 * $out_size / $in_size);

		$replace_map = array(
			$in_path  => "\$source",
			$out_path => "\$output",
		);

		$cmd_pre  = str_replace(array_keys($replace_map), array_values($replace_map), $row['cmd_pre' ]);
		$cmd      = str_replace(array_keys($replace_map), array_values($replace_map), $row['cmd'     ]);
		$cmd_post = str_replace(array_keys($replace_map), array_values($replace_map), $row['cmd_post']);

	#echo "<pre>";
	#echo HtmlSpecialChars(var_export($result, true));
	#echo "</pre>";
?>

<h1>Results</h1>

<table border="1">
	<tr>
		<th>Input</th>
		<td><a href="<?=$in_url?>"><?=round($in_size / 1024)?>&nbsp;KB</a></td>
	</tr>
	<tr>
		<th>Output</th>
		<td><a href="<?=$out_url?>"><?=round($out_size / 1024)?>&nbsp;KB</a></td>
	</tr>
	<tr>
		<th>Size Diff</th>
		<td><?=$ratio?>%</td>
	</tr>
	<tr>
		<th>Return</th>
		<td style="background-color: <?=$row['exit']?'red':'green'?>; color: white;"><?=$row['exit']?></td>
	</tr>
	<tr>
		<th>Time</th>
		<td><?=number_format(round(1000*$row['elapsed']))?> ms</td>
	</tr>
<? if ($row['pre_out']){ ?>
	<tr>
		<th>Pre Console</th>
		<td><?=OrNone(nl2br(HtmlSpecialChars($row['pre_out'])))?></td>
	</tr>
<? } ?>
	<tr>
		<th>Console</th>
		<td><?=OrNone(nl2br(HtmlSpecialChars($row['output'])))?></td>
	</tr>
<? if ($row['post_out']){ ?>
	<tr>
		<th>Post Console</th>
		<td><?=OrNone(nl2br(HtmlSpecialChars($row['post_out'])))?></td>
	</tr>
<? } ?>
	<tr>
		<th>Tool</th>
		<td><?=$tool_map[$row['tool']]?></td>
	</tr>
	<tr>
		<th>Arguments</th>
		<td><?=OrNone(HtmlSpecialChars($row['args']))?></td>
	</tr>
</table>

<p>&nbsp;</p>

<table border="1">
	<tr>
		<th>Source File</th>
		<td><?=$in_path?></td>
	</tr>
	<tr>
		<th>Output File</th>
		<td><?=$out_path?></td>
	</tr>
<? if ($row['cmd_pre']){ ?>
	<tr>
		<th>Pre</th>
		<td><?=HtmlSpecialChars($cmd_pre)?></td>
	</tr>
<? } ?>
	<tr>
		<th>Command</th>
		<td><?=HtmlSpecialChars($cmd)?></td>
	</tr>
<? if ($row['cmd_post']){ ?>
	<tr>
		<th>Post</th>
		<td><?=HtmlSpecialChars($cmd_post)?></td>
	</tr>
<? } ?>
</table>

<? if (file_exists($out_path) && filesize($out_path)){ ?>

<p>&nbsp;</p>

<table border="1">
	<tr>
		<th>Params</th>
		<td><pre><?=shell_exec("perl peek.pl $out_path 2>&1")?></pre></td>
	</tr>
	<tr>
		<th>Chunks</th>
		<td><pre><?=shell_exec("perl chunks.pl $out_path 2>&1")?></pre></td>
	</tr>
</table>

<? } ?>


<hr />
<?
	}
?>


<h1>Image Processing Test</h1>

<form action="./" method="post">
<input type="hidden" name="done" value="1">

Source: <select name="source"><?

	foreach ($files as $file => $size){
		$kb = round($size / 1024);
		$sel = $file == $result['source'] ? ' selected' : '';
		echo "<option value=\"$file\"$sel>{$file} ({$kb} KB)</option>\n";
	}

?></select><br />
Tool: <select name="tool"><?

	foreach ($tool_map as $k => $v){
		$sel = $k == $result['tool'] ? ' selected' : '';
		echo "<option value=\"$k\"$sel>$v</option>\n";
	}

?></select><br />
Arguments: <input type="text" name="args" value="<?=HtmlSpecialChars($result['args'])?>" style="width: 600px" /><br />
<input type="submit" value="Process" />

</form>

<style>
th { text-align: left; }
</style>
