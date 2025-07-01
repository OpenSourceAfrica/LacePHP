<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server Error</title>
    <style>
        body { font-family:sans-serif; text-align:center; padding:5em; }
        h1 { font-size:4em; margin-bottom:0.5em; color:#c00; }
        pre { text-align:left; background:#f9f9f9; padding:1em; overflow:auto; }
    </style>
</head>
<body>
<h1>500</h1>
<p>Something went wrong on our end.</p>
<!-- In debug mode, we can show the exception message & stack: -->
<?php if (!empty(\config('boot.debug'))): ?>
<pre><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></pre>
<?php endif; ?>
</body>
</html>
