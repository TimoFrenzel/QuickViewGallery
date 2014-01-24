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
			<div>
            	<div class="tab-menu">
                	<a href="?status" class="selected">Status</a>
                    <a href="?settings">Settings</a>
                </div>
				<div class="page-title">Status</div>
                <p>Installed Version: <?php echo $this->vars['installed_version']; $temp_update = $this->checkUpdate(); if ($temp_update !== false) { echo " (<a href=\"" . $temp_update[1] . "\" target=\"_blank\">" . $temp_update[0] . " is available for update</a>)"; } else { echo " (No updates available)"; } ?></p>
			</div>
		</div>
	</div>
</body>
</html>