<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPPI - Login</title>
<link rel="stylesheet" type="text/css" href="css/style.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
</head>
<body>
	<div id="page-container">
		<div id="page-content">
        	<div style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #eeeeee;">
				<div style="float: left; margin-right: 50px;"><img src="images/setup_logo.png" alt="PHPPI - Setup"></div>
				<div style="float: right; font-size: 1.5em;">Login</div>
				<div style="clear: both;"></div>
			</div>
			<div>
				<div class="page-title">Password</div>
                <?php if ($error !== '' && isset($_POST['login'])) {
    echo '<p>'.$error.'</p>';
} ?>
				<form method="post" action=""><div class="password-box"><input type="password" name="password_field"><input name="login" type="submit" value="Login"></div></form>
			</div>
    	</div>
	</div>
</body>
</html>