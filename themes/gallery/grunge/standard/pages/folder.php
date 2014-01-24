<div id="page-title">
	<div style="float: left;">
		<?php 
			if ($this->logoExists()) {
				$this->showLogo();
			} else {
				echo "<div style=\"padding: 10px;\">" . $this->showSiteName(1) . "</div>";
			}
		?>
	</div>
    <div id="thumb-size-change"><?php $this->insertThumbSize(); ?></div>
    <div style="clear: both;"></div>
</div>
<?php if ($this->noticeExists()) { echo '<div id="page-notice">' . $this->showNotice(1) . '</div>'; } ?>
<div class="nav-bar">
	<?php $this->showNav(0, "<img src=\"" . $this->showThemeURL(1) . "images/home.png\">", "<img src=\"" . $this->showThemeURL(1) . "images/prev.png\">", "<img src=\"" . $this->showThemeURL(1) . "images/sep.png\">"); ?>
	<div style="clear: both;"></div>
</div>
<div id="page-container">
<?php $this->showGallery(); ?>
</div>