<div id="page-title">
	<div style="float: left;">
		<?php
            if ($this->logoExists()) {
                $this->showLogo();
            } else {
                echo '<div style="padding: 10px;">'.$this->showSiteName(1).'</div>';
            }
        ?>
	</div>
    <div style="clear: both;"></div>
</div>
<?php if ($this->noticeExists()) {
            echo '<div id="page-notice">'.$this->showNotice(1).'</div>';
        } ?>
<div class="nav-bar">
	<ul>
		<li class="nav-prev"><a href="javascript: history.back();"><img src="<?php $this->showThemeURL(0); ?>images/prev.png"></a></li>
		<li class="nav-sep"><img src="<?php $this->showThemeURL(0); ?>images/sep.png"></li>
		<li class="nav-curr"><div class="title">Error</div></li>
	</ul>
    <div style="clear: both;"></div>
</div>
<div id="page-error"><?php $this->showError(); ?></div>