<nav>
	<ul>
		<li class="nav-logo">
			<a href="<?= $router->route('Home') ?>"><i class="logo"></i></a>
		</li>
		<li class="nav-dropdown">
			<a href="<?= $router->route('#') ?>" class="btn-dropdown"><i class="fa fa-wrench" aria-hidden="true"></i>Pages</a>
			<ul class="nav-dropdown-list">
				<li><a href="<?= $router->route('#') ?>">Page A</a></li>
				<li><a href="<?= $router->route('#') ?>">Page B</a></li>
			</ul>
		</li>
		<li class="nav-dropdown">
			<a href="<?= $router->route('#') ?>" class="btn-dropdown"><i class="fa fa-wrench" aria-hidden="true"></i>Pages</a>
			<ul class="nav-dropdown-list">
				<li><a href="<?= $router->route('#') ?>">Page A</a></li>
				<li><a href="<?= $router->route('#') ?>">Page B</a></li>
			</ul>
		</li>
	</ul>
</nav>