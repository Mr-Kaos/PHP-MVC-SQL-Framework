<div class="login-container">
	<img src="res/img/EasyMVCLogo.svg">
	<form id="Login" class="login-form" method="post" action="Login?mode=select">
		<h3>Login</h3>
		<fieldset>
			<label for="Username">Username *</label>
			<input id="Username" name="Username" type="text" maxlength="128" required="">
			<label for="Password">Password *</label>
			<input id="Password" name="Password" type="password" maxlength="128" required="">
		</fieldset>
		<button id="LoginSubmit" type="submit">Login</button>
		<?= $controller->getPreparedData("err"); ?>
	</form>
</div>