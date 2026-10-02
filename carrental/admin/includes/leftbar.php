<?php
$adminPage = basename($_SERVER['PHP_SELF']);
$menu = [
    ['dashboard.php', 'fa-dashboard', 'Dashboard'],
    ['Brands', 'fa-tags', [
        ['create-brand.php', 'Create Brand'],
        ['manage-brands.php', 'Manage Brands'],
    ]],
    ['Vehicles', 'fa-car', [
        ['post-avehical.php', 'Add a Vehicle'],
        ['manage-vehicles.php', 'Manage Vehicles'],
    ]],
    ['Bookings', 'fa-calendar-check-o', [
        ['manage-bookings.php', 'All Bookings'],
        ['new-bookings.php', 'New'],
        ['confirmed-bookings.php', 'Confirmed'],
        ['canceled-bookings.php', 'Cancelled'],
    ]],
    ['testimonials.php', 'fa-comments', 'Testimonials'],
    ['manage-conactusquery.php', 'fa-envelope', 'Contact Queries'],
    ['reg-users.php', 'fa-users', 'Registered Users'],
    ['manage-subscribers.php', 'fa-newspaper-o', 'Subscribers'],
    ['manage-pages.php', 'fa-file-text-o', 'Manage Pages'],
    ['update-contactinfo.php', 'fa-phone', 'Contact Info'],
];
?>
	<nav class="ts-sidebar">
		<ul class="ts-sidebar-menu">
			<li class="ts-label">Main</li>
<?php foreach ($menu as [$target, $icon, $children]) {
    if (is_array($children)) {
        $open = in_array($adminPage, array_column($children, 0), true); ?>
			<li class="<?php echo $open ? 'open' : ''; ?>"><a href="#"><i class="fa <?php echo $icon; ?>"></i> <?php echo e($target); ?></a>
				<ul>
				<?php foreach ($children as [$href, $label]) { ?>
					<li class="<?php echo $adminPage === $href ? 'active' : ''; ?>"><a href="<?php echo $href; ?>"><?php echo e($label); ?></a></li>
				<?php } ?>
				</ul>
			</li>
<?php } else { ?>
			<li class="<?php echo $adminPage === $target ? 'active' : ''; ?>"><a href="<?php echo $target; ?>"><i class="fa <?php echo $icon; ?>"></i> <?php echo e($children); ?></a></li>
<?php } } ?>
		</ul>
	</nav>
