<nav>
	<ul>
		<li>
			<a href="<?= $router->route('Home') ?>"><i class="logo"></i></a>
		</li>
		<li class="nav-dropdown">
			<a href="<?= $router->route('Examples') ?>" class="btn-dropdown"><i class="fa fa-book" aria-hidden="true"></i>Examples</a>
			<ul class="nav-dropdown-list">
				<li><a href="<?= $router->route('Examples/formBuilder') ?>">Form Builder</a></li>
				<li><a href="<?= $router->route('Examples/table') ?>">Table iFrames</a></li>
			</ul>
		</li>

		<?php if (isset($_SESSION['USER_INFO']['PrivilegeLevel']) ? ($_SESSION['USER_INFO']['PrivilegeLevel'] <> 1) : false) : ?>
			<li class="nav-dropdown">
				<a href="#" class="btn-dropdown"><i class="fa fa-university" aria-hidden="true"></i>User Group Page</a>
			</li>
		<?php endif; ?>

		<li class="nav-dropdown">
			<a href="<?= $router->route('Sample') ?>" class="btn-dropdown"><i class="fa fa-cubes" aria-hidden="true"></i>Sample</a>
		</li>

		<li class="nav-dropdown nav-settings">
			<?php if (isset($_SESSION['USER_INFO']['PrivilegeLevel'])) : ?>
				<a href="<?= $router->route('Login') ?>" class="btn-dropdown"><i class="fa fa-user" aria-hidden="true"></i>Logout</a>
			<?php else : ?>
				<a href="<?= $router->route('Login') ?>" class="btn-dropdown"><i class="fa fa-user" aria-hidden="true"></i>Login</a>
			<?php endif; ?>
	</ul>
	</li>
	</ul>
</nav>