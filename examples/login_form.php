<h1><?=_("Login")?></h1>
<?
	echo HTML::uList($this->errors, null, 'errors');
?>
<form method="post" action="" onsubmit="return $(this).validate()">
	<dl>
		<dt><?=_("Username")?>:</dt>
		<dd><input type="text" name="username" id="username" value="" />
		<!-- type=string required=true errormsg="<?=_("Invalid username")?>" --></dd>
		<dt><?=_("Password")?>:</dt>
		<dd><input type="password" name="password" id="password" value="" /></dd>
	</dl>
	<div><input type="submit" value="<?=_("Login")?>" /></div>
</form>