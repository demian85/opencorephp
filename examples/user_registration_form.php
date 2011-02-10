<h1><?=_("User registration")?></h1>
<?
	echo HTML::uList($this->errors, null, 'errors');
?>
<form method="post" action="" enctype="multipart/form-data" onsubmit="return $(this).validate()">
	<dl>
		<dt><?=_("Username")?>:</dt>
		<dd><input type="text" name="username" id="username" value="" />
		<!-- type=string required=true errormsg="<?=_("Invalid username")?>" --></dd>

		<dt><?=_("Email")?>:</dt>
		<dd><input type="text" name="email" id="email" value="" />
		<!-- type=email required=true errormsg="<?=_("Invalid email")?>" --></dd>

		<dt><?=_("First Name")?>:</dt>
		<dd><input type="text" name="fname" id="fname" value="" />
		<!-- type=string required=true errormsg="<?=_("Invalid first name")?>" --></dd>

		<dt><?=_("Last Name")?>:</dt>
		<dd><input type="text" name="lname" id="lname" value="" />
		<!-- type=string required=true errormsg="<?=_("Invalid last name")?>" --></dd>

		<dt><?=_("Password")?>:</dt>
		<dd><input type="password" name="password" id="password" value="" />
		<!-- type=string(6,) required=true errormsg="<?=_("Password must have at least 6 characters")?>" --></dd>

		<dt><?=_("Repeat Password")?>:</dt>
		<dd><input type="password" name="password2" id="password2" value="" /></dd>

		<dt><?=_("Gender")?>:</dt>
		<dd><input type="radio" name="gender" id="gender_f" value="F" checked="checked" /> <label for="gender_f"><?=_("Female")?></label><br />
		<input type="radio" name="gender" id="gender_m" value="M" /> <label for="gender_m"><?=_("Male")?></label></dd>

		<dt><?=_("Birth date")?>:</dt>
		<dd><input type="text" name="birthdate" id="birthdate" value="" />
		<!-- type=date(yyyy-mm-dd) required=true errormsg="<?=_("Invalid birth date")?>" --></dd>
	</dl>

	<div><input type="submit" value="<?=_("Submit")?>" /></div>
</form>
