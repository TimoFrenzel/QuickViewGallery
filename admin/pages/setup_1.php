<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPPI - Setup (Pre-setup Check)</title>
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
				<div class="page-title">Pre-setup Check (<a href="">Re-test</a>)</div>
				<?php
                if ($this->vars['setup_mode'] == 'legacy') {
                    ?>
                <p>
                	It appears you are upgrading your install of PHPPI from a legacy version. Confirm that 
                    the following tests pass and then continue to make sure that your settings have carried across. If you have any issues feel free to report them at 
                    <a href="http://code.google.com/p/phppi/issues/list" target="_blank">http://code.google.com/p/phppi/issues/list</a>
                </p>
                <?php
                } elseif ($this->vars['setup_mode'] == 'upgrade') {
                    ?>
                <p>
                	It appears you are upgrading your install of PHPPI from version <?php echo $this->vars['installed_version'].' to '.$this->vars['version']; ?>. Confirm that 
                    the following tests pass and then continue to make sure that your settings have carried across. If you have any issues feel free to report them at 
                    <a href="http://code.google.com/p/phppi/issues/list" target="_blank">http://code.google.com/p/phppi/issues/list</a>
                </p>
                <?php
                } else {
                    ?>
                <p>
                	For PHPPI to work correctly all of the following tests must pass. You can continue if you fail a test but some features may not work and the install may fail.
                    If you have any issues feel free to report them at <a href="http://code.google.com/p/phppi/issues/list" target="_blank">http://code.google.com/p/phppi/issues/list</a>
                </p>
                <?php
                }

                $this->outputChecks();
                ?>
			</div>
            <form method="post"><input name="step_2" type="submit" value="Continue"></form>
		</div>
	</div>
</body>
</html>