<div class="brand clearfix">
	<a href="dashboard.php" style="font-size: 25px;">Drive<span class="brand-accent">Now</span> <small>Admin</small></a>
		<span class="menu-btn"><i class="fa fa-bars"></i></span>
		<ul class="ts-profile-nav">
			<li><a href="../index.php" target="_blank"><i class="fa fa-external-link"></i> View site</a></li>
			<li class="ts-account">
				<a href="#"><img src="img/ts-avatar.jpg" class="ts-avatar hidden-side" alt=""> <?php echo e($_SESSION['alogin'] ?? 'Account'); ?> <i class="fa fa-angle-down hidden-side"></i></a>
				<ul>
					<li><a href="change-password.php">Change Password</a></li>
					<li><a href="logout.php">Logout</a></li>
				</ul>
			</li>
		</ul>
	</div>
<?php render_flashes('class="admin-flash"'); ?>
