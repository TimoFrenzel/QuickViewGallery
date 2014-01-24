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
    <div style="clear: both;"></div>
</div>
<?php if ($this->noticeExists()) { echo '<div id="page-notice">' . $this->showNotice(1) . '</div>'; } ?>
<div class="nav-bar">
	<?php $this->showNav(0, "<img src=\"" . $this->showThemeURL(1) . "images/home.png\">", "<img src=\"" . $this->showThemeURL(1) . "images/prev.png\">", "<img src=\"" . $this->showThemeURL(1) . "images/sep.png\">"); ?>
	<div style="clear: both;"></div>
</div>
<div class="page-bar">
	<?php if ($this->prevImageExists()) { echo '<a class="previous-link" href="' . $this->showPrevImageURL(1) . '">Previous</a>'; } else { echo '<a class="previous-link-disabled" href="#">Previous</a>'; } ?>
    <?php if ($this->nextImageExists()) { echo '<a class="next-link"  href="' . $this->showNextImageURL(1) . '">Next</a>'; } else { echo '<a class="next-link-disabled" href="#">Next</a>'; } ?>
    <div style="clear: both;"></div>
</div>
<div id="page-image-container">
	<?php $this->showImage(); ?>
</div>