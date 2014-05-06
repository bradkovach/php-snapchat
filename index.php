<?php

require('./src/snapchat.php');

$ext = array(
	'image/jpg' => 'jpg',
	'image/jpeg' => 'jpg',
	'image/gif' => 'gif',
	'image/x-png' => 'png',
	'image/png' => 'png'
);

$snapchat = new Snapchat('bradkovach', 'poke1234');

if( isset($_POST['snapchat']) )
{
	if(
		isset($_POST['snapchat']['locus'])
		&& isset($_POST['snapchat']['username'])
		&& isset($_POST['snapchat']['password'])
	)
	{
		$locus = $_POST['snapchat']['locus'];
		$username = $_POST['snapchat']['username'];
		$password = $_POST['snapchat']['password'];

		$snapchat = new Snapchat($username, $password);

		switch($locus)
		{
			case 'mass':
				if( isset($_FILES['snapchat']) )
				{
					$file = sprintf('uploads/%s.%s', date('Y-m-d-G-i-s'), $ext[$_FILES['snapchat']['type']['file'] ]);
					move_uploaded_file(
						$_FILES['snapchat']['tmp_name']['file'],
						$file
					);

					$id = $snapchat->upload(
						Snapchat::MEDIA_IMAGE,
						file_get_contents($file)
					);
					$friends = $snapchat->getFriends(true);

					foreach($friends as $friend)
					{
						$recipients[] = $friend->name;
					}
					
					$recipients[] = $username;
					$snapchat->send($id, $recipients, 10);
					// $snapchat->setStory($id, Snapchat::MEDIA_IMAGE);

				}
				break;
		}
	}
}



?><!DOCTYPE html>
<html lang="en">
<head>
	<title>Snapchat Workbench</title>
</head>
<body>
	<h3>Send A Mass Picture Snap</h3>
	<form method="post" enctype="multipart/form-data">
		<input type="hidden" name="snapchat[locus]" value="mass">

		<p>
			<label>
				Snapchat Username
				<input type="text" name="snapchat[username]">
			</label>
		</p>

		<p>
			<label>
				Snapchat Password
				<input type="password" name="snapchat[password]">
			</label>
		</p>

		<p>
			<label>
				Upload a Picture
				<input type="file" name="snapchat[file]">
			</label>
		</p>
		<button type="submit">Send Snap</button>
	</form>

</body>
</html>