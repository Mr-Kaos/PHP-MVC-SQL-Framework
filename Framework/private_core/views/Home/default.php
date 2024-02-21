<main>
	<h1>Easy MVC Home Page</h1>
	<p>Welcome to the home page!</p>
	<p>If you can see this page, that means you have successfully set up this framework!<br>
		The containers below indicate the status of various components available in this framework. If any of them show any errors and you are trying to use them, there may be a misconfiguration in the config file.</p>
	<div class="container-menu">
		<div class="container-menu-box">
			<h2>Core Framework</h2>
			<p>Display details of core framework components here</p>
		</div>
		<div class="container-menu-box">
			<h2>Database Connection</h2>
			<?php if ($controller->checkPreparedData('DB')) : ?>
				<?= $controller->getPreparedData('DB') ?>
			<?php else : ?>
				<p>No database has been configured.</p>
			<?php endif ?>
		</div>
	</div>
</main>