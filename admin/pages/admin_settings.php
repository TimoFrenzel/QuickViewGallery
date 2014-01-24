<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPPI - Admin</title>
<link rel="stylesheet" type="text/css" href="css/style.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="../scripts/jquery/jquery.js"></script>
</head>
<body>
	<div id="page-container">
		<div id="page-content">
			<div style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #eeeeee;">
				<div style="float: left; margin-right: 50px;"><img src="images/setup_logo.png" alt="PHPPI - Setup"></div>
				<div style="float: right; font-size: 1.5em;">Admin (<a href="<?php echo substr($this->getURL(false), 0, -12); ?>">View Gallery</a> / <a href="?logout">Logout</a>)</div>
				<div style="clear: both;"></div>
			</div>
            <form method="post" action="">
			<div>
            	<div class="tab-menu">
                	<a href="?status">Status</a>
                    <a href="?settings" class="selected">Settings</a>
                </div>
				<div class="page-title">Settings</div>
                <?php 
					if (isset($status)) { 
						echo "<p>" . $status . "</p>";
						if ($status == "Settings failed to save, check your file permissions") {
							echo "<p><b>Manual Output</b></p>";
							echo "<textarea id=\"settings-output-box\">" . $this->vars['settings_output'] . "</textarea>";
                        }
					} 
				?>
                <div class="settings-section">
                	<?php $this->outputSettingsFields(); ?>
                </div>
			</div>
            <input name="save_settings" type="submit" value="Save Settings">
            </form>
		</div>
	</div>
</body>
</html>