<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPPI - Setup (Settings)</title>
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
				<div class="page-title">Settings</div>
				<?php
                if ($this->vars['setup_mode'] == "legacy") {
				?>
                <p>Settings have been retrieved from a legacy version of PHPPI, make sure these are correct before continuing</p>
                <?php
				} else if ($this->vars['setup_mode'] == "upgrade") {
				?>
                <p>Settings have been retrieved from an earlier version of PHPPI, make sure these are correct before continuing</p>
                <?php
				} else {
				?>
                <p>Change the settings to how you'd like PHPPI to run, these can be changed later if you make a mistake.</p>
                <?php
				}
				?>
                <form method="post" action="">
                <div class="settings-section">
                	<?php $this->outputSettingsFields(); ?>
                </div>
			</div>
            <input name="step_3" type="submit" value="Save Settings and Continue">
            </form>
		</div>
	</div>
</body>
</html>