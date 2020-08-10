<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPPI - Setup (Finish)</title>
<link rel="stylesheet" type="text/css" href="css/style.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
</head>
<body>
	<div id="page-container">
		<div id="page-content">
			<div style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #eeeeee;">
				<div style="float: left; margin-right: 50px;"><img src="images/setup_logo.png" alt="PHPPI - Setup"></div>
				<div style="float: right; font-size: 1.5em;">Setup</div>
				<div style="clear: both;"></div>
			</div>
			<div>
				<div class="page-title">Finish</div>
				<?php
                if ($this->vars['settings_output'] !== '') {
                    ?>
                <p>
                	Setup could not write your settings to the phppi_settings.php file, make sure you've passed all the tests on the first step and try again. Otherwise you can create a
                    file called phppi_settings.php with the following content and save it to your PHPPI folder (example: installfolder/phppi/phppi_settings.php). Also make sure that your version.txt file
                    contains the correct version number for the version of PHPPI you're installing (located at: installfolder/phppi/version.txt).
                </p>
                <p>
                	 Click Finish once you have done this otherwise you will return to the first step of the setup.
                </p>
                <p>
                	 Once finished you can access the admin section of PHPPI by visiting the following URL - <a href="<?php echo $this->getURL(); ?>" target="_blank"><?php echo $this->getURL(); ?></a>
                </p>
                <div>
                	<textarea id="settings-output-box"><?php echo $this->vars['settings_output']; ?></textarea>
                </div>
                <?php
                } else {
                    ?>
                <p>Setup has saved your settings successfully. Click Finish to start using PHPPI.</p>
                <p>To access the admin section of PHPPI visit the following URL - <a href="<?php echo $this->getURL(); ?>" target="_blank"><?php echo $this->getURL(); ?></a></p>
                <?php
                }
                ?>
			</div>
            <form method="post" action=""><input name="finish" type="submit" value="Finish"></form>
		</div>
	</div>
</body>
</html>