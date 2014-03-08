<?php

// Default words to capitalize
$caps_words = [
	'You', 'I', 'God', 'Jesus', 'Him', 'King',
	'Lord', 'Father', 'Savior', 'Saviour', 'Christ', "You're", 'Holy Spirit',
	"I'll", 'Spirit', 'Man of Sorrows', 'Man of Suffering',
	'Your', 'He', 'His', 'Yours', 'King of Glory', 'Hosanna',
	'Great I Am', 'King of Majesty', 'Lamb of God'
];

if(!empty($_FILES['pro']['tmp_name'])) {
	require_once('lowercase.class.php');
	try {
		Lowercase::quickTransform(
			isset($_POST['words']) ? $_POST['words'] : $caps_words,
			$_FILES['pro'],
			isset($_POST['prefix']) ? $_POST['prefix'] : '',
			isset($_POST['postfix']) ? $_POST['postfix'] : ''
		);
	} catch(LowercaseException $e) {
		die('Error transforming: ' . $e->getMessage());
	}
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<title>ProPresenter Lowercase</title>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="HandheldFriendly" content="true">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="lowercase.css">
</head>
<body>
<div id="wrapper">
	<div id="header">
		<h1>ProPresenter5 to Lowercase Transformer</h1>
	</div>
	<form action="./pro.php" method="post" enctype="multipart/form-data">
	<p>Choose a ProPresenter 5 document to transform:</p>
	<div class="lcInput">
		<input type="file" name="pro" onchange="updateFilename(this.value)" />
	</div>
	<p>You can now add a prefix and/or postfix to the file to ensure you do not overwrite any current document loaded into ProPresenter. It is recommended you keep these settings the same.</p>
	<div class="lcInput">
		<input type="text" name="prefix" id="" value="LC - " size="5" />
		<span id="currentFilename">current filename</span>
		<input type="text" name="postfix" value="" size="5" />
		.pro5
	</div>
	<p>Words to always capitalize:</p>
	<div class="lcInput">
		<textarea name="words" rows="10" cols="40"><?php echo implode("\n", $caps_words) ?></textarea>
	</div>
	<div class="lcInput">
		<button type="submit">
			Convert to Lowercase
		</button>
	</div>
</form>
<script>
updateFilename = (function(name) {
	var filename = name.split(/(\\|\/)/g).pop();
	document.getElementById('currentFilename').innerHTML = filename.split(/.pro5/i).shift();
});
</script>
</body>
</html>
